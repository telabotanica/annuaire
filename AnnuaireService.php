<?php

require_once 'Annuaire.php';
require_once 'services/Auth.php';

/**
 * API REST de l'annuaire (point d'entrée des services)
 */
class AnnuaireService extends BaseRestServiceTB {

	/** Bibliothèque Annuaire */
	protected $lib;

	/** Autodocumentation en JSON */
	//public static $AUTODOC_PATH = "autodoc.json";

	/** Configuration du service en JSON */
	const CHEMIN_CONFIG = "config/service.json";

	public function __construct() {
		// config
		$config = null;
		if (file_exists(self::CHEMIN_CONFIG)) {
			$contenuConfig = file_get_contents(self::CHEMIN_CONFIG);
			// dé-commentarisation du pseudo-JSON @TODO valider cette stratégie cheloute
			$contenuConfig = preg_replace('`^[\t ]*//.*\n`m', '', $contenuConfig);
			$config = json_decode($contenuConfig, true);
		} else {
			throw new Exception("fichier de configuration " . self::CHEMIN_CONFIG . " introuvable");
		}

		// lib Annuaire
		$this->lib = new Annuaire();

		// ne pas indexer - placé ici pour simplifier l'utilisation avec nginx
		// (pas de .htaccess)
		header("X-Robots-Tag: noindex, nofollow", true);

		parent::__construct($config);
	}

	/**
	 * Renvoie une brève explication de l'utilisation du service
	 * @TODO faire mieux (style autodoc.json)
	 */
	protected function usage() {
		$utilisation = array(
			'Utilisation' => 'https://'
				. $this->config['domain_root']
				. $this->config['base_uri']
				. ':service'
				. '[/ressource1[/ressource2[...]]]',
			'service' => '(utilisateur|testloginmdp|nbinscrits|auth)'
		);
		// 400 pour signifier l'appel d'une URL non gérée
		$this->sendError($utilisation);
	}

	protected function get() {
		// réponse positive par défaut;
		http_response_code(200);

		$nomService = strtolower(array_shift($this->resources));
		//var_dump($nomService);
		switch($nomService) {
			case 'testloginmdp':
				$this->testLoginMdp();
				break;
			case 'nbinscrits':
				$this->nbInscrits();
				break;
			case 'utilisateur':
				$this->utilisateur();
				break;
			case 'auth':
				$this->auth();
				break;
			default:
				$this->usage();
		}
	}

	protected function post() {
		// réponse positive par défaut;
		http_response_code(200);

		$nomService = strtolower(array_shift($this->resources));
		//var_dump($nomService);
		switch($nomService) {
			case 'utilisateur':
				// @WARNING RÉTROCOMPATIBILITÉ
				// l'id ou courriel utilisateur est passé avant l'action
				if (count($this->resources) > 0) {
					$idOuCourriel = array_shift($this->resources);
					// action
					if (count($this->resources) > 0) {
						$action = strtolower(array_shift($this->resources));
						switch ($action) {
							case "message":
								$this->message($idOuCourriel);
								break;
							default:
								$this->usage();
						}
					}
				}
				break;
			default:
				$this->usage();
		}
	}

	// https://.../service:annuaire:auth/...
	protected function auth() {
		// service d'authentification SSO
		$auth = new Auth($this->config, $this->lib);
		$auth->run();
	}

	// -------------- rétrocompatibilité (11/2016) -------------------
	// l'organisation des services et les noms d'action sont hérités de
	// l'annuaire précédent @TODO homogénéiser et réorganiser, dans un ou
	// plusieurs sous-services (comme "Auth")

	/**
	 * @WARNING MÉTHODE DE RÉTROCOMPATIBILITÉ
	 * 
	 * Envoie un message (email) à l'utilisateur identifié par $idOuCourriel
	 * Nécessite un jeton SSO pour détecter l'expéditeur
	 * 
	 * POST	http://www.tela-botanica.org/service:annuaire:utilisateur/24604/message
	 * POST	http://www.tela-botanica.org/service:annuaire:utilisateur/mathias@tela-botanica.org/message
	 */
	protected function message($idOuCourriel) {
		// destinataire (adresse email)
		// WTF ? Rétrocompatibilité...
		// le destinataire devrait *toujours* être défini par $idOuCourriel
		$destinataire = $this->getParam('destinataire', $idOuCourriel);
		if (is_numeric($destinataire)) {
			$destinataire = $this->lib->courrielParId($destinataire);
		}
		if (empty($destinataire)) {
			$this->sendError("Impossible de trouver l'utilisateur [$idOuCourriel]");
		}

		// message
		$contenu = $this->getParam('contenu_message');
		// bulletproof rétrocompat cracra (1/2)
		if (empty($contenu)) {
			$contenu = $this->getParam('message');
		}
		$sujet = $this->getParam('sujet_message');
		// bulletproof rétrocompat cracra (2/2)
		if (empty($sujet)) {
			$sujet = $this->getParam('sujet');
		}
		$redirect = $this->getParam('redirect');
		if (empty($contenu)) {
			$this->sendError("Parametre 'contenu_message' ou 'message' manquant");
		}
		if (empty($sujet)) {
			$this->sendError("Parametre 'sujet_message' manquant");
		}

		// envoi
		$retour = $this->lib->envoyerMessage($destinataire, $sujet, $contenu);
		if ($retour) {
			if (! empty($redirect)) {
				// rétrocompatibilité à deux ronds
				if (strtolower(substr($redirect, 0, 4)) != 'http') {
					$redirect = 'http://' . $redirect;
				}
				header('Location: ' . $redirect);
				exit;
			} else {
				$this->sendJson(array(
					"message" => "Votre message a été envoyé"
				));
			}
		} else {
			// ne devrait jamais se produire, la lib est censée jeter une
			// exception en cas de pb
			$this->sendError("Erreur lors de l'envoi du message");
		}
	}

	/**
	 * @TODO
	 * POST
	 * http://www.tela-botanica.org/service:annuaire:utilisateur (POST: methode=connexion, courriel, mdp, persistance)
	 */
	protected function connexion() {
		
	}

	// https://.../service:annuaire:nbinscrits/...
	protected function nbInscrits() {
		$retour = $this->lib->nbInscrits();
		$this->sendJson($retour);
	}

	// https://.../service:annuaire:testloginmdp/...
	protected function testLoginMdp() {
		if (count($this->resources) < 2) {
			$this->sendError("élément d'URL manquant");
		}
		$courriel = array_shift($this->resources);
		// astuce si le mot de passe contient un slash
		$mdpHache = implode('/',$this->resources);

		$retour = $this->lib->identificationCourrielMdpHache($courriel, $mdpHache);
		$this->sendJson($retour);
	}

	// https://.../service:annuaire:utilisateur/...
	protected function utilisateur() {
		$ressource = strtolower(array_shift($this->resources));
		switch($ressource) {
			case "":
				$this->usage();
				break;
			case "identite-par-courriel":
				$this->identiteParCourriel();
				break;
			case "avatar-par-courriel":
				$this->avatarParCourriel();
				break;
			case "identite-par-nom-wiki": // usage interne
				$this->identiteParNomWiki();
				break;
			case "identite-complete-par-courriel":
				$this->identiteCompleteParCourriel();
				break;
			case "prenom-nom-par-courriel":
				$this->prenomNomParCourriel();
				break;
			case "infosparids":
				$this->infosParIds();
				break;
			default:
				// si on passe un ID numérique directement, ça marche aussi
				if (is_numeric($ressource)) {
					// réenfilage cracra pour ne pas dé-génériciser infosParIds()
					array_unshift($this->resources, $ressource);
					$this->infosParIds();
				} else {
					$this->usage();
				}
		}
	}

	/**
	 * Retourne des informations publiques pour une liste d'ids numériques
	 * d'utilisateurs, séparé par des virgules
	 */
	protected function infosParIds() {
		if (count($this->resources) < 1) {
			$this->sendError("élément d'URL manquant");
		}
		$unOuPlusieursIds = $this->resources[0];
		$unOuPlusieursIds = explode(',', $unOuPlusieursIds);
		// les ids sont toujours entiers
		$unOuPlusieursIds = array_map(function($v) {
			return intval($v);
		}, $unOuPlusieursIds);

		$retour = $this->lib->infosParids($unOuPlusieursIds);
		// @TODO formatage des résultats
		$this->sendJson($retour);
	}

	/**
	 * @WARNING MÉTHODE DE RÉTROCOMPATIBILITÉ
	 * 
	 * Retourne un jeu mégarestreint d'informations publiques pour une adresse
	 * courriel donnée :
	 * - id
	 * - prenom
	 * - nom
	 * @WARNING, ne considère pas le pseudo - obsolète !
	 */
	protected function prenomNomParCourriel() {
		// @TODO optimiser pour ne pas ramener toutes les infos
		$infos = $this->infosParCourriels();
		// formatage des résultats
		$retour = array();
		foreach($infos as $email => $i) {
			$retour[$email] = array(
				"id" => $i['id'],
				"prenom" => $i['prenom'],
				"nom" => $i['nom']
			);
		}
		$this->sendJson($retour);
	}

	/**
	 * Retourne les identités pouvant correspondre à un nom wiki donné
	 * @WARNING usage interne
	 * - id
	 * - prenom
	 * - nom
	 * - pseudo
	 * - intitule (nom à afficher)
	 */
	protected function identiteParNomWiki() {
		// @TODO optimiser pour ne pas ramener toutes les infos
		$infos = $this->infosParCourriels();
		// formatage des résultats
		$retour = array();
		foreach($infos as $email => $i) {
			$retour[$email] = $this->sousTableau($i, array(
				"id",
				"courriel",
				"prenom",
				"nom",
				"pseudo",
				"pseudoUtilise", // obsolète
				"intitule",
				"nomWiki",
				"avatar"
			));
		}
		$this->sendJson($retour);
	}


	/**
	 * @WARNING MÉTHODE DE RÉTROCOMPATIBILITÉ
	 * 
	 * Retourne un jeu restreint d'informations publiques pour une adresse
	 * courriel donnée :
	 * - id
	 * - prenom
	 * - nom
	 * - pseudo
	 * - pseudoUtilise
	 * - intitule (nom à afficher)
	 * - nomWiki
	 */
	protected function identiteParCourriel() {
		// @TODO optimiser pour ne pas ramener toutes les infos
		$infos = $this->infosParCourriels();
		// formatage des résultats
		$retour = array();
		foreach($infos as $email => $i) {
			if (! empty($i)) {
				$retour[$email] = $this->sousTableau($i, array(
					"id",
					"prenom",
					"nom",
					"pseudo",
					"pseudoUtilise", // obsolète
					"intitule",
					"nomWiki",
					"avatar",
					"groupes",
					"permissions"
				));
			}
		}
		$this->sendJson($retour);
	}


	/**
	 * @WARNING MÉTHODE DE RÉTROCOMPATIBILITÉ
	 * 
	 * Retourne un jeu plus large d'informations publiques pour une adresse
	 * courriel donnée (intégralité du "profil Tela Botanica") :
	 * - id
	 * - prenom
	 * - nom
	 * - pseudo
	 * - pseudoUtilise
	 * - intitule (nom à afficher)
	 * - nomWiki
	 * - ...
	 * - ...
	 */
	protected function identiteCompleteParCourriel() {
		$infos = $this->infosParCourriels();
		$format = "json";
		if (count($this->resources) > 0 && (strtolower($this->resources[0]) == "xml")) {
			$format = "xml";
		}
		// formatage des résultats
		if ($format == "xml") {
			if (count($infos) > 1) {
				$this->sendError("Le format XML n'est disponible que pour un utilisateur à la fois");
			}
			$info = array_shift($infos);
			// @WARNING rétrocompatibilité dégueulasse
			// @TODO faire quelque chose de moins artisanal
			// @NONOBSTANT ce format n'est utilisé que par CoeL (2016-11) et
			// devrait disparaître
			$retour = '<?xml version="1.0" encoding="UTF-8"?>';
			$retour .= '<personne>';
				$retour .= '<adresse>';
				if (! empty($info['adresse'])) {
					$retour .= $info['adresse'];
				}
				$retour .= '</adresse>';
				$retour .= '<adresse_comp>';
				if (! empty($info['adresse_comp'])) {
					$retour .= $info['adresse_comp'];
				}
				$retour .= '</adresse_comp>';
				$retour .= '<code_postal>';
				if (! empty($info['code_postal'])) {
					$retour .= $info['code_postal'];
				}
				$retour .= '</code_postal>';
				$retour .= '<date_inscription>';
				if (! empty($info['date_inscription'])) {
					$retour .= $info['date_inscription'];
				}
				$retour .= '</date_inscription>';
				$retour .= '<id>';
				if (! empty($info['id'])) {
					$retour .= $info['id'];
				}
				$retour .= '</id>';
				$retour .= '<lettre>';
				if (! empty($info['lettre'])) {
					$retour .= $info['lettre'];
				}
				$retour .= '</lettre>';
				$retour .= '<mail>';
				if (! empty($info['mail'])) {
					$retour .= $info['courriel'];
				}
				$retour .= '</mail>';
				$retour .= '<nom>';
				if (! empty($info['nom'])) {
					$retour .= $info['nom'];
				}
				$retour .= '</nom>';
				$retour .= '<pass>';
				if (! empty($info['pass'])) {
					$retour .= $info['pass'];
				}
				$retour .= '</pass>';
				$retour .= '<pays>';
				if (! empty($info['pays'])) {
					$retour .= $info['pays'];
				}
				$retour .= '</pays>';
				$retour .= '<prenom>';
				if (! empty($info['prenom'])) {
					$retour .= $info['prenom'];
				}
				$retour .= '</prenom>';
				$retour .= '<ville>';
				if (! empty($info['ville'])) {
					$retour .= $info['ville'];
				}
				$retour .= '</ville>';
				$retour .= '<fonction>';
				if (! empty($info['fonction'])) {
					$retour .= $info['fonction'];
				}
				$retour .= '</fonction>';
				$retour .= '<titre>';
				if (! empty($info['titre'])) {
					$retour .= $info['titre'];
				}
				$retour .= '</titre>';
				$retour .= '<site_web>';
				if (! empty($info['site_web'])) {
					$retour .= $info['site_web'];
				}
				$retour .= '</site_web>';
				$retour .= '<region>';
				if (! empty($info['region'])) {
					$retour .= $info['region'];
				}
				$retour .= '</region>';
				$retour .= '<adresse>';
				if (! empty($info['adresse'])) {
					$retour .= $info['adresse'];
				}
				$retour .= '</adresse>';
				$retour .= '<adresse01>';
				if (! empty($info['adresse01'])) {
					$retour .= $info['adresse01'];
				}
				$retour .= '</adresse01>';
				$retour .= '<adresse02>';
				if (! empty($info['adresse02'])) {
					$retour .= $info['adresse02'];
				}
				$retour .= '</adresse02>';
				$retour .= '<courriel>';
				if (! empty($info['courriel'])) {
					$retour .= $info['courriel'];
				}
				$retour .= '</courriel>';
				$retour .= '<mot_de_passe>';
				if (! empty($info['mot_de_passe'])) {
					$retour .= $info['mot_de_passe'];
				}
				$retour .= '</mot_de_passe>';
				$retour .= '<pseudo>';
				if (! empty($info['pseudo'])) {
					$retour .= $info['pseudo'];
				}
				$retour .= '</pseudo>';
				$retour .= '<pseudoUtilise>';
				if (! empty($info['pseudoUtilise'])) {
					$retour .= $info['pseudoUtilise'];
				}
				$retour .= '</pseudoUtilise>';
				$retour .= '<intitule>';
				if (! empty($info['intitule'])) {
					$retour .= $info['intitule'];
				}
				$retour .= '</intitule>';
			$retour .= '</personne>';
			// il n'y a pas de $this->sendXML() dans BaseRestServiceTB (2016-11)
			http_response_code(200);
			header('Content-type: text/xml'); // human-readable XML
			echo $retour;
			exit;
		} else {
			// retour normal en JSON
			$retour = array();
			foreach($infos as $email => $i) {
				if ($i != null) {
					$retour[$email] = $this->sousTableau($i, array(
						"id",
						"courriel",
						"prenom",
						"nom",
						"pseudo",
						"pseudoUtilise", // obsolète
						"intitule",
						"nomWiki",
						"avatar",
						"permissions",
						"groupes"
					));
				}
			}
			$this->sendJson($retour);
		}
	}

	/**
	 * Méthode interne pour obtenir les infos d'un ou plusieurs utilisateurs
	 * identifiés par leurs adresses courriel
	 */
	protected function infosParCourriels() {
		if (count($this->resources) < 1) {
			$this->sendError("élément d'URL manquant");
		}
		$unOuPlusieursCourriels = trim(array_shift($this->resources));
		$unOuPlusieursCourriels = explode(',', $unOuPlusieursCourriels);
		// les courriels doivent contenir un arrobase @TODO utile ?
		$unOuPlusieursCourriels = array_filter($unOuPlusieursCourriels, function($v) {
			return (strpos($v, '@') !== false);
		});

		$retour = $this->lib->infosParCourriels($unOuPlusieursCourriels);
		return $retour;
	}

	/**
	 * Retourne l'URL de l'avatar (miniature) pour l'utilisateur dont le
	 * courriel est fourni dans l'URL
	 */
	protected function avatarParCourriel() {
		$retour = null;
		// @TODO optimiser pour ne pas ramener toutes les infos
		$infos = $this->infosParCourriels();
		if (! empty($infos)) {
			$infosUtilisateur = array_shift($infos);
			if (isset($infosUtilisateur["avatar"])) {
				// formatage des résultats
				$retour = $infosUtilisateur["avatar"];
			}
		}
		$this->sendJson($retour);
	}

	/**
	 * Retourne un sous-tableau de $tableau, où seules les clefs contenues dans
	 * $listeClefs sont conservées, récursivement (array_intersect amélioré)
	 */
	protected function sousTableau(array $tableau, array $listeClefs) {
		$nouveauTableau = array();
		foreach ($tableau as $k => $v) {
			if (in_array($k, $listeClefs)) {
				if (is_array($v) && (isset($listeClefs[$k]) && is_array($listeClefs[$k]))) {
					// descente en profondeur
					$nouveauTableau[$k] = $this->sousTableau($v, $listeClefs[$k]);
				} else {
					$nouveauTableau[$k] = $v;
				}
			}
		}
		return $nouveauTableau;
	}
}
