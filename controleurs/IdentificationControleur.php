<?php
/**
* PHP Version 5
*
* @category  PHP
* @package   annuaire
* @author    aurelien <aurelien@tela-botanica.org>
* @copyright 2010 Tela-Botanica
* @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
* @version   SVN: <svn_id>
* @link      /doc/annuaire/
*/

Class IdentificationControleur extends AppControleur {

	private $nom_cookie_persistant = '';
	private $duree_identification = '0';
	private $fonction_cryptage_mdp_cookie = 'md5';
	private $objet_identification = null;
	
	/*public function IdentificationControleur() {

		Controleur::__construct();
		$this->cookie_persistant_nom = session_name().'-memo';
		$this->cookie_persistant_nom = 'pap-admin_papyrus_-memo';
		$this->duree_identification = time()+Config::get('duree_session_identification');
		$this->fonction_cryptage_mdp_cookie = Config::get('fonction_cryptage_mdp_cookie');
		
	}*/

	public function afficherFormulaireIdentification($id_annuaire, $donnees = array()) {

		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire);

		if(!isset($donnees['informations'])) {
			$donnees['informations'] = array();
		}

		$donnees['id_annuaire'] = $id_annuaire;

		return $this->getVue(Config::get('dossier_squelettes_formulaires').'identification',$donnees);
	}

	public function loggerUtilisateur($utilisateur, $pass) {
		
		$this->objet_identification = Config::get('objet_identification');
		
		// on cree le cookie
		$this->creerCookie($utilisateur, $pass);
		
		// On loggue l'utilisateur
		$this->objet_identification->username = $utilisateur;
		$this->objet_identification->password = $pass;
		$this->objet_identification->login();

		return true;
	}
	
	public function deLoggerUtilisateur() {
		
		$this->objet_identification = Config::get('objet_identification');
		$this->objet_identification->logout();
		
		return true;
	}
	
	public function setUtilisateur($nom_utilisateur) {
		$this->objet_identification = Config::get('objet_identification');
		$this->objet_identification->setAuth($nom_utilisateur);
		$pass = $this->objet_identification->password;
		$this->creerCookie($nom_utilisateur, $pass, true);
	}
	
	public function creerCookie($utilisateur, $pass, $pass_deja_crypte = false) {
		
		$this->objet_identification = Config::get('objet_identification');
		
		// Expiration si l'utilisateur ne referme pas son navigateur
		$this->objet_identification->setExpire(0);
		// Création d'un cookie pour rendre permanente l'identification de Papyrus
		if(!$pass_deja_crypte) {
			$pass_crypt = md5($pass); 
		} else {
			$pass_crypt = $pass;
		}
		$cookie_val = $pass_crypt.$utilisateur;
		setcookie(session_name().'-memo', $cookie_val, 0, '/');		
	}
	
	public function obtenirLoginUtilisateurParCookie() {

		$nom_utilisateur = Config::get('nom_utilisateur');
		
		if(isset($_COOKIE[$nom_utilisateur])) {
			$login_utilisateur =  $_COOKIE[$nom_utilisateur];
			return $login_utilisateur;
		} else {
			return false;
		}
		
	}
}
?>