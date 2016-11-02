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

/**
 * Controleur chargé de la propagation et le rassemblement d'informations extérieures
 * lors dce la consultation et modification des fiches utilisateurs
 */
Class ApplicationExterneControleur extends AppControleur {

	private $applications_resume = null;
	private $applications_gestion = null;
	private $mode_reponse = 'json';

	public function ApplicationExterneControleur() {

		$this->__construct();

		// on charge les variables de classes à partir du fichier de configuration
		if(Config::get('url_services_applications_inscription') != null) {

			$application_str = Config::get('url_services_applications_inscription');
			$this->applications_inscription = explode('##',$application_str);
		} else {
			$this->applications_inscription = array();
		}

		// on charge les variables de classes à partir du fichier de configuration
		if(Config::get('url_services_applications_resume') != null) {

			$application_str = Config::get('url_services_applications_resume');
			$this->applications_resume = explode('##',$application_str);
		} else {
			$this->applications_resume = array();
		}

		// on charge les variables de classes à partir du fichier de configuration
		if(Config::get('url_services_applications_gestion') != null) {

			$application_str = Config::get('url_services_applications_gestion');
			$this->applications_gestion = explode('##',$application_str);
		} else {
			$this->applications_gestion = array();
		}
	}

	/**
	 * parcourt la liste des applications et appelle une adresse spécifique pour l'inscription
	 * et l'inclut, le cas échéant.
	 * @param l'identifiant de l'utilisateur
	 * @param le mail de l'utilisateur
	 */
	public function ajouterInscription($id_utilisateur, $params) {

		if(count($this->applications_inscription) > 0) {
			foreach($this->applications_inscription as $application) {

				$inscription = @file_get_contents($application.'Inscription/'.$this->fabriquerRequete($id_utilisateur, $params));
				$inscription = json_decode($inscription);

				if($inscription && $inscription == "OK") {

				} else {
					//echo 'Erreur d\'inscription à l\'application '.$application;
				}
			}
		}

		return true;
	}


	/**
	 * parcourt la liste des applications et appelle une adresse spécifique pour la modification
	 * et l'inclut, le cas échéant.
	 * @param l'identifiant de l'utilisateur
	 * @param le mail de l'utilisateur
	 */
	public function modifierInscription($id_utilisateur,$params) {

		if(count($this->applications_inscription) > 0) {
			foreach($this->applications_inscription as $application) {

				$modification = @file_get_contents($application.'Modification/'.$this->fabriquerRequete($id_utilisateur, $params));
				$modification = json_decode($modification);
				if($modification && $modification == "OK") {

				} else {
					//echo 'Erreur de modification  l\'application '.$application.'<br />'.$modification;
				}
			}
		}

		return true;
	}

	/**
	 * parcourt la liste des applications et appelle une adresse spécifique pour la suppression
	 * et l'inclut, le cas échéant.
	 * @param l'identifiant de l'utilisateur
	 * @param le mail de l'utilisateur
	 */
	public function supprimerInscription($id_utilisateur, $params) {

		if(count($this->applications_inscription) > 0) {
			foreach($this->applications_inscription as $application) {

				$suppression = @file_get_contents($application.'Suppression/'.$this->fabriquerRequete($id_utilisateur, $params));
				$suppression = json_decode($suppression);

				if($suppression && $suppression == "OK") {

				} else {
					//echo 'Erreur de desinscription à l\'application '.$application;
				}
			}
		}

		return true;
	}

	/**
	 * Parcourt le repertoire racine des applications et appelle un web service contenant la méthode
	 * Resume qui renvoie les informations associées à l'utilisateur qui seront affichées dans la fiche
	 * de profil
	 * @param l'identifiant de l'utilisateur
	 * @param le mail de l'utilisateur
	 * @return array un tableau associatif dont les clés sont les noms des applis et les valeurs sont le html qui sera
	 * inclus dans la fiche profil
	 */
	public function obtenirResume($id_utilisateur, $mail) {

		$resumes = array();

		if(count($this->applications_resume) > 0) {
			foreach($this->applications_resume as $application) {

				$resume = @file_get_contents($application.'Resume'.DS.$id_utilisateur.DS.$mail);


				if($resume) {
					$resume = json_decode($resume, true);
					$resumes[] = $resume;
				} else {
					$resume = array('elements' => array(), 'titre' => '', 'message' => '');
				}
			}
		}

		return $resumes;
	}

	 /** Parcourt le repertoire racine des applications et cherche un fichier spécifique contenant la méthode
	 * obtenirResume qui renvoie les informations associées à l'utilisateur qui seront affichées dans la fiche
	 * de profil
	 * @param l'identifiant de l'utilisateur
	 * @param le mail de l'utilisateur
	 * @return array un tableau associatif dont les clés sont les noms des applis et les valeurs sont le html qui sera
	 * inclus dans la fiche profil
	 */
	public function gererInscription($id_utilisateur, $mail) {

		$gestions = array();

		if(count($this->applications_gestion) > 0) {
			foreach($this->applications_gestion as $application) {

				$gestion = file_get_contents($application.'Gestion'.DS.$id_utilisateur.DS.$mail);

				if($gestion) {
					$gestion = json_decode($gestion, true);
					$gestions[] = $gestion;
				} else {
					$gestion = array('elements' => array(), 'titre' => '', 'message' => '');
				}
			}
		}

		return $gestions;
	}

	private function fabriquerRequete($id, $params) {

		$requete = '?';

		foreach($params as $cle => $param) {
			$requete .= '&'.$cle.'='.$param;
		}

		return $requete;
	}
}
?>