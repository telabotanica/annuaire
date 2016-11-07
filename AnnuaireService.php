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
		$this->sendJson($utilisation);
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

	// https://.../service:annuaire:auth/...
	protected function auth() {
		// service d'authentification SSO
		$auth = new Auth($this->config, $this->lib);
		$auth->run();
	}

	protected function post() {
		
	}

	/**
	 * POST
	 * 	http://www.tela-botanica.org/service:annuaire:utilisateur/24604/message
	 */
	protected function message() {
		
	}

	/**
	 * POST
	 * http://www.tela-botanica.org/service:annuaire:utilisateur (POST: methode=connexion, courriel, mdp, persistance)
	 */
	protected function connexion() {
		
	}

	// -------------- rétrocompatibilité (11/2016) -------------------
	// l'organisation des services et les noms d'action sont hérités de
	// l'annuaire précédent @TODO homogénéiser et réorganiser, dans un ou
	// plusieurs sous-services (comme "Auth")

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
				// réenfilage cracra pour ne pas dé-génériciser infosParIds()
				array_unshift($this->resources, $ressource);
				$this->infosParIds();
		}
	}

	protected function infosParIds() {
		if (count($this->resources) < 1) {
			$this->sendError("élément d'URL manquant");
		}
		$unOuPlusieursIds = $this->resources[0];

		$retour = $this->lib->infosParids($unOuPlusieursIds);
		// @TODO formatage des résultats
		$this->sendJson($retour);
	}

	protected function identiteParCourriel() {
		$retour = $this->infosParCourriels();
		// @TODO formatage des résultats
		$this->sendJson($retour);
	}

	protected function identiteCompleteParCourriel() {
		$retour = $this->infosParCourriels();
		$format = "json";
		if (count($this->resources) > 0 && (strtolower($this->ressources[0]) == "xml")) {
			$format = "xml";
		}
		// @TODO formatage des résultats
		$this->sendJson($retour);
	}

	protected function prenomNomParCourriel() {
		$retour = $this->infosParCourriels();
		// @TODO formatage des résultats
		$this->sendJson($retour);
	}

	protected function infosParCourriels() {
		if (count($this->resources) < 1) {
			$this->sendError("élément d'URL manquant");
		}
		$unOuPlusieursCourriels = array_shift($this->resources);

		$retour = $this->lib->infosParCourriels($unOuPlusieursCourriels);
		return $retour;
	}
}
