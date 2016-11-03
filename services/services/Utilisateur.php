<?php
// declare(encoding='UTF-8');
/**
 * Service retournant les prénoms et nom  d'un utilisateur en fonction de son courriel.
 * UNe liste de courriel peut être passé dans la ressource.
 * Exemple :
 * /utilisateur/Prenom-nom-par-courriel/jpm@tela-botanica.org,aurelien@tela-botanica.org
 *
 * @category	php 5.2
 * @package		Annuaire::Services
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version		$Id$
 */
class Utilisateur extends JRestService {

	private $donnees = null;
	private $idAnnuaire = null;
	private $utilisateurId = null;
	private $metadonneeModele = null;
	private $annuaireModele = null;
	private $messagerieModele = null;

	const FORMAT_JSON = "json";
	const FORMAT_XML = "xml";
	const FORMAT_LDEHYDE = "méthanal"; // hihi hoho

	public function __construct($config, $demarrer_session = true) {
		parent::__construct($config, $demarrer_session);
		$this->idAnnuaire = Config::get('annuaire_defaut');
	}

	/*+----------------------------------------------------------------------------------------------------+*/
	// GET : consultation

	public function getElement($ressources){
		$this->ressources = $ressources;
		$infos = null;

		if (isset($this->ressources[0])) {
			if (preg_match('/^[0-9]+$/', $this->ressources[0])) {
				// ATTENTION : Ces web services ne doivent être accessible que depuis des applis installées sur nos serveurs
				// pour les communications inter-serveurs.
				$this->controlerIpAutorisees();
				$infos = $this->getInfosParId($this->ressources[0]);
			} else {
				$methode_demande = array_shift($this->ressources);
				$methode = $this->traiterNomMethodeGet($methode_demande);
				if (method_exists($this, $methode)) {
					$infos = $this->$methode($this->ressources[0]);
				} else {
					$this->messages[] = "Ce type de ressource '$methode_demande' n'est pas disponible pour la requete GET.";
				}
			}
		} else {
			$this->messages[] = "Le premier paramètre du chemin du service doit correspondre au type de ressource demandée.";
		}

		// possibilité d'envoyer en plusieurs formats @TODO faire ça plus proprement
		$format = self::FORMAT_JSON;
		$dernierIndex = count($this->ressources) - 1;
		if ($dernierIndex >= 0) {
			$dernierParametre = $this->ressources[$dernierIndex];
			if (in_array($dernierParametre, array(self::FORMAT_JSON, self::FORMAT_XML))) {
				$format = $dernierParametre;
			}
		}

		if (!is_null($infos)) {
			switch ($format) {
				case self::FORMAT_XML :
					$this->envoyerXml($infos);
				break;
				case self::FORMAT_JSON :
				default :
					$this->envoyerJson($infos);
			}
		} else {
			$info = 'Un problème est survenu : '.print_r($this->messages, true);
			$this->envoyerTxt($info);
		}
	}

	/**
	 * Permet d'obtenir des infos pour un ou plusieurs ids utilisateurs indiqué(s) dans la ressource.
	 * RESSOURCE : /infos-par-ids/#id[,#id]*
	 * PARAMÈTRES : forceArrayOfArrays - si true, retourne un tableau associatif même s'il n'y a qu'un
	 * 		résultat (casse la rétrocompatibilté)
	 * RÉPONSE : Tableau possédant un courriel de la ressource en clé et en valeur :
	 *  - id : identifiant numérique de l'utilisateur
	 *  - pseudoUtilise : indique si on doit utiliser le pseudo à la place de Prénom NOM
	 *  - pseudo : pseudo de l'utilisateur.
	 *  - intitule : l'intitulé à affiche (choix auto entre "pseudo" et "prénom nom")
	 *  - prenom : prénom
	 *  - nom : nom de famille.
	 *  - courriel : courriel
	 */
	public function getInfosParIds($ids_utilisateurs, $forceArrayOfArrays = false) {
		$ids_utilisateurs = explode(',', $ids_utilisateurs);
		if (count($ids_utilisateurs) == 1) {
			// s'il n'y en a qu'un on ne passe pas un array
			$ids_utilisateurs = array_shift($ids_utilisateurs);
		}
		$infos = $this->getAnnuaire()->obtenirInfosUtilisateurParId($this->idAnnuaire, $ids_utilisateurs);
 
		foreach ($infos as $i => $info) {
			$infos[$i]['pseudoUtilise'] = $this->obtenirPseudoUtilise($info['id']);
			$infos[$i]['pseudo'] = $this->obtenirPseudo($info['id']);
			$infos[$i]['intitule'] = $this->formaterIntitule($infos[$i]);
		}

		// retrocompatibilité
		if (count($infos) == 1 && (! $forceArrayOfArrays)) {
			$infos = array_shift($infos);
		}

		return $infos;
	}

	// proxy pour AnnuaireModele::obtenirIdParMail() car le présent service
	// est utilisé comme une lib => c'est MAL ! @TODO séparer lib et service !!
	public function getIdParCourriel($courriel) {
		return $this->getAnnuaire()->obtenirIdParMail($this->idAnnuaire, $courriel);
	}

	// proxy pour AnnuaireModele::inscrireUtilisateurCommeUnGrosPorc() car le présent service
	// est utilisé comme une lib => c'est MAL ! @TODO séparer lib et service !!
	public function inscrireUtilisateurCommeUnGrosPorc($donnees) {
		return $this->getAnnuaire()->inscrireUtilisateurCommeUnGrosPorc($donnees);
	}

	/**
	 * Méthode rétrocompatible : appelle getInfosParIds et s'il n'y a qu'un résultat,
	 * ne retourne pas un tableau associatif mais un tableau simple
	 * @return array
	 */
	public function getInfosParId($ids_utilisateurs) {
		return $this->getInfosParIds($ids_utilisateurs, true);
	}

	/**
	 * Permet d'obtenir les prénoms et noms des courriels des utilisateurs indiqués dans la ressource.
	 * RESSOURCE : /utilisateur/prenom-nom-par-courriel/[courriel,courriel,...]
	 * PARAMÈTRES : $courriels des adresses courriel séparées par des virgules; si != null, sera utilisé à la place de la ressource d'URL
	 * RÉPONSE : Tableau possédant un courriel de la ressource en clé et en valeur :
	 *  - id : identifiant numérique de l'utilisateur
	 *  - prenom : prénom
	 *  - nom : nom de famille.
	 */
	public function getPrenomNomParCourriel($courriels) {
		$courriels = explode(',', $courriels);
		$infos = $this->getAnnuaire()->obtenirPrenomNomParCourriel($this->idAnnuaire, $courriels);
		return $infos;
	}

	/**
	 * Permet d'obtenir les identités des utilisateurs indiqués dans la ressource.
	 * RESSOURCE : /utilisateur/identite-par-courriel/[courriel,courriel,...]
	 * PARAMÈTRES : $courriels des adresses courriel séparées par des virgules; si != null, sera utilisé à la place de la ressource d'URL
	 * RÉPONSE : Tableau possédant un courriel de la ressource en clé et en valeur :
	 *  - id : identifiant numérique de l'utilisateur
	 *  - pseudoUtilise : indique si on doit utiliser le pseudo à la place de Prénom NOM
	 *  - pseudo : pseudo de l'utilisateur.
	 *  - prenom : prénom
	 *  - nom : nom de famille.
	 */
	public function getIdentiteParCourriel($courriels) {
		$infos_utilisateurs = array();
		$utilisateurs = $this->getPrenomNomParCourriel($courriels);
		foreach ($utilisateurs as $courriel => $utilisateur) {
			$id = $utilisateur['id'];
			$utilisateur['pseudo'] = $this->obtenirPseudo($id);
			$utilisateur['pseudoUtilise'] = $this->obtenirPseudoUtilise($id);
			$utilisateur['intitule'] = $this->formaterIntitule($utilisateur);
			$utilisateur['nomWiki'] = $this->formaterNomWiki($utilisateur['intitule']);
			$courriel = strtolower($courriel);
			$infos_utilisateurs[$courriel] = $utilisateur;
		}
		return $infos_utilisateurs;
	}

	/**
	 * Même principe que getIdentiteParCourriel() mais pour un seul courriel, et renvoie plus d'infos :
	 * RESSOURCE : /utilisateur/identite-complete-par-courriel/courriel[/format]
	 * PARAMÈTRES : format : "json" (par défaut) ou "xml" (pour
	 *   rétrocompatibilité avec le service eFlore_chatin/annuaire_tela/xxx/courriel) 
	 * RÉPONSE : Tableau possédant un courriel de la ressource en clé et en valeur :
	 * - id : identifiant numérique de l'utilisateur
	 * - pseudoUtilise : indique si on doit utiliser le pseudo à la place de Prénom NOM
	 * - pseudo : pseudo de l'utilisateur.
	 * - prenom : prénom
	 * - nom : nom de famille.
	 * - mot_de_passe : le mot de passe haché (15% de matières grasses, peut contenir des traces de soja)
	 * - fonction
	 * - titre
	 * - site_web
	 * - adresse01
	 * - adresse02
	 * - code_postal
	 * - ville
	 * - departement
	 * - region
	 * - pays
	 * - date_inscription
	 */
	public function getIdentiteCompleteParCourriel() {

		$this->authentificationHttpSimple();

		$infos_utilisateurs = array();
		$courriel = $this->ressources[0];
		$utilisateur = $this->getAnnuaire()->obtenirMaximumInfosParCourriel($this->idAnnuaire, $courriel);

		$id = $utilisateur['id'];
		$utilisateur['pseudo'] = $this->obtenirPseudo($id);
		$utilisateur['pseudoUtilise'] = $this->obtenirPseudoUtilise($id);
		$utilisateur['intitule'] = $this->formaterIntitule($utilisateur);

		// ouksépabo
		$this->baliseMaitresse = "personne";

		return $utilisateur;
	}

	private function getAnnuaire() {
		if (!isset($this->annuaireModele)) {
			$this->annuaireModele = new AnnuaireModele();
		}
		return $this->annuaireModele;
	}

	private function getMeta() {
		if (!isset($this->metadonneeModele)) {
			$this->metadonneeModele = new MetadonneeModele();
		}
		return $this->metadonneeModele;
	}

	private function obtenirPseudo($id_utilisateur) {
		$pseudo = '';
		$id_champ_pseudo = $this->getMeta()->renvoyerIdChampMetadonneeParAbreviation($this->idAnnuaire, 'pseudo');
		if ($this->getMeta()->valeurExiste($id_champ_pseudo, $id_utilisateur)) {
			$pseudo = $this->getMeta()->obtenirValeurMetadonnee($id_champ_pseudo, $id_utilisateur);
		}
		return $pseudo;
	}

	private function obtenirPseudoUtilise($id_utilisateur) {
		$pseudo_utilise = false;
		$id_champ_utilise_pseudo = $this->getMeta()->renvoyerIdChampMetadonneeParAbreviation($this->idAnnuaire, 'utilise_pseudo');
		if ($this->getMeta()->valeurExiste($id_champ_utilise_pseudo, $id_utilisateur)) {
				$booleen = $this->getMeta()->obtenirValeurMetadonnee($id_champ_utilise_pseudo, $id_utilisateur);
				$pseudo_utilise = ($booleen == 0) ? false : true;
		}
		return $pseudo_utilise;
	}

	private function formaterIntitule($utilisateur) {
		$intitule = '';
		if ($utilisateur['pseudoUtilise'] && trim($utilisateur['pseudo']) != '') {
			$intitule = $utilisateur['pseudo'];
		} else {
			$intitule = $utilisateur['prenom'].' '.$utilisateur['nom'];
		}
		return $intitule;
	}

	/**
	 * Retourne la date de dernière modification du profil, piochée dans
	 * annu_triples
	 * 
	 * @param numeric $id identifiant de l'utilisateur
	 * @param boolean $timestamp si true, fournira un timestamp Unix; si
	 * 		false, une date GMT sous forme de string
	 * @return mixed une date (string ou timestamp), ou null si la date
	 * 		n'a pas été trouvée dans les "triples" de l'annuaire  
	 */
	public function getDateDerniereModifProfil($id, $timestamp=false) {
		$date = $this->getAnnuaire()->obtenirDateDerniereModificationProfil($this->idAnnuaire, $id);
		if (($timestamp === true) && ($date !== null)) {
			// normalement, strtotime accepte le format "yyyy-mm-dd hh:ii:ss"
			$date = strtotime($date);
		}
		return $date;
	}

	/*+----------------------------------------------------------------------------------------------------+*/
	// POST : mise à jour

	public function updateElement($ressources, $donnees) {
		$this->ressources = $ressources;
		$this->donnees = $donnees;
		$this->idAnnuaire = Config::get('annuaire_defaut');

		$infos = null;
		if (isset($this->ressources[0])) {
			$this->utilisateurId = array_shift($this->ressources);
			if (isset($this->ressources[0])) {
				$methode_demande = array_shift($this->ressources);
				$methode = $this->traiterNomMethodePost($methode_demande);
				if (method_exists($this, $methode)) {
					$infos = $this->$methode();
				} else {
					$this->messages[] = "Ce type de ressource '$methode_demande' n'est pas disponible pour la requete POST.";
				}
			} else {
				$this->messages[] = "La seconde ressource du service pour les requêtes POST doit correspondre au type de ressource demandée.";
			}
		} else {
			$this->messages[] = "La première ressource du service pour les requêtes POST doit être l'identifiant de l'utilisateur.";
		}

		if (!is_null($infos)) {
			$this->envoyerJson($infos);
		} else {
			$info = 'Un problème est survenu : '.print_r($this->messages, true);
			$this->envoyerTxt($info);
		}
	}

	/**
	 * Permet d'envoyer un message à un utilisateur.
	 * RESSOURCE : /utilisateur/[id]/message
	 * POST :
	 *  - sujet : contient le sujet du message à envoyer.
	 *  - message : contient le contenu du message à envoyer.
	 *  - message_txt : (optionnel) si format HTML, peut contenir le contenu du message au format texte comme alternative au HTML à envoyer.
	 *		Sinon le texte est extrait du HTML (attention à la mise en page!).
	 *  - utilisateur_courriel : contient le courriel de l'utilisateur qui envoie le message (Il doit être
	 *		inscrit dans l'annuaire par défaut de Tela Botanica).
	 *  - copies : peut contenir une liste de courriels séparés par des virguels auxquels une copie du
	 *		message sera envoyée.
	 *  - format (optionnel) : text ou html
	 * RÉPONSE :
	 *  - message : contient le message d'information concernant l'envoie.
	 */
	private function updateMessage() {
		$destinataireId = $this->utilisateurId;//$this->donnees['destinataire_id'];
		$sujet = stripslashes($this->donnees['sujet']);
		$contenu = stripslashes($this->donnees['message']);
		$contenuTxt = (isset($this->donnees['message_txt'])) ? $this->donnees['message_txt'] : null;
		$envoyeur = $this->donnees['utilisateur_courriel'];
		$adresse_reponse = (isset($this->donnees['reponse_courriel']) ? $this->donnees['reponse_courriel'] : $this->donnees['utilisateur_courriel']);
		$copies = array_key_exists('copies', $this->donnees) ? explode(',', $this->donnees['copies']) : null;
		$format = isset($this->donnees['format']) ? $this->donnees['format'] : 'text';

		$info = null;
		if ($this->estAutoriseMessagerie($envoyeur) || $this->getAnnuaire()->utilisateurExisteParMail($this->idAnnuaire, $envoyeur)) {
			// il est possible de passer directement un email ou bien un id utilisateur
			if(filter_var($destinataireId, FILTER_VALIDATE_EMAIL)) {
				$destinataire = $destinataireId;
			} else {
				$destinataire = $this->getAnnuaire()->obtenirMailParId($this->idAnnuaire, $destinataireId);
			}
			if ($destinataire) {
				if ($format == 'html') {
					if (isset($contenuTxt)) {
						$envoie = $this->getMessagerie()
							->envoyerMail($envoyeur, $destinataire, $sujet, $contenu, $contenuTxt, $adresse_reponse);
					} else {
						$envoie = $this->getMessagerie()
							->envoyerMail($envoyeur, $destinataire, $sujet, $contenu, '', $adresse_reponse);
					}
				} else {
					$envoie = $this->getMessagerie()->envoyerMailText($envoyeur, $destinataire, $sujet, $contenu, '', $adresse_reponse);
				}
				if ($envoie) {
					$info['message'] = "Votre message a bien été envoyé.";
					foreach ($copies as $copie) {
						$sujet = '[COPIE] '.$sujet;
						$contenu = "Message original envoyé par $envoyeur pour $destinataire.\n--\n".$contenu;
						$this->getMessagerie()->envoyerMailText($envoyeur, $copie, $sujet, $contenu, '', $adresse_reponse);
					}
				} else {
					$info['message'] = "Le message n'a pas pu être envoyé.";
				}
			} else {
				$info['message'] = "Aucun courriel ne correspond à l'id du destinataire.";
			}
		} else {
			$info['message'] = "Vous n'êtes pas inscrit à Tela Botanica avec le courriel : $envoyeur.\n".
				"Veuillez saisir votre courriel d'inscription ou vous inscrire à Tela Botanica.";
		}
		return $info;
	}

	private function getMessagerie() {
		if (!isset($this->messagerieModele)) {
			$this->messagerieModele = new MessageControleur();
		}
		return $this->messagerieModele;
	}

	/*+----------------------------------------------------------------------------------------------------+*/
	// PUT : ajout

	public function createElement($donnees) {
		
		$this->donnees = $donnees;
		$this->idAnnuaire = Config::get('annuaire_defaut');

		$infos = null;
		if (isset($this->donnees['methode'])) {
			$methode_demande = $this->donnees['methode'];
			$methode = $this->traiterNomMethodePut($methode_demande);
			if (method_exists($this, $methode)) {
				$infos = $this->$methode();
			} else {
				$this->messages[] = "Ce type de méthode '$methode_demande' n'est pas disponible pour la requete PUT.";
			}
		} else {
			$this->messages[] = "Ce service n'est pas implémenté.";
		}

		if (!is_null($infos)) {
			$this->envoyerJson($infos);
		} else {
			$info = 'Un problème est survenu : '.print_r($this->messages, true);
			$this->envoyerTxt($info);
		}
	}

	/**
	 * Permet d'identifier un utilisateur, sans utiliser SSO (à l'ancienne).
	 * RESSOURCE : /utilisateur
	 * POST :
	 *  - methode = 'connexion' : methode doit valoir 'connexion' pour connecter l'utilisateur.
	 *  - courriel : contient le courriel de l'utilisateur .
	 *  - mdp : le mot de passe de l'utilisateur.
	 *  - persistance : true si on veut laisser l'utilisateur connecté au delà de la session sinon false
	 * RÉPONSE :
	 *  - identifie : indiquer si l'utilisateur a été identifié (true) ou pas (false)
	 *  - message : contient un message d'information complémentaire de l'état.
	 */
	private function createConnexion() {
		$courriel = stripslashes($this->donnees['courriel']);
		$mdp = stripslashes($this->donnees['mdp']);
		$persistance = (stripslashes($this->donnees['persistance']) == 'true') ? true : false;

		$infos = null;
		$infos['persistance'] = $persistance;
		if ($this->verifierAcces($courriel, $mdp)) {
			$infos['identifie'] = true;
			$infos['message'] = "Bienvenu.";
			$dureeCookie = 0;
			if ($persistance === true) {
				$dureeCookie = time()+3600*24*30;
				$this->creerCookiePersistant($dureeCookie, $courriel, $mdp);
			}
			$this->creerCookieUtilisateur($dureeCookie, $courriel);
			$infos['message'] = $_COOKIE;
		} else {
			$infos['identifie'] = false;
			$infos['message'] = "Le courriel ou le mot de passe saisi est incorrect.";
		}
		return $infos;
	}
	
	/*+----------------------------------------------------------------------------------------------------+*/
	// DELETE : suppression
	
	/**
	 * Permet de déconnecter un utilisateur
	 * RESSOURCE : /utilisateur
	 * DELETE 
	 */
	public function deleteElement($uid) {
		if($uid[0] == 'deconnexion') {
			$this->supprimerCookieUtilisateur();
		}
	}	
}
?>
