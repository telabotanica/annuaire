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
 * 
 * Classe mère des controleurs de l'application, abstraite, elle contient
 * les fonctions utiles à tous les controleurs
 * @author aurelien
 *
 */

abstract class AppControleur extends Controleur {
		
	/**
	 * (fonction héritée de la classe Controleur)
	 * Avant chaque chargement de vue, on ajoute l'url de la page dans
	 * les variables à insérer.
	 * @param Array $donnes les données à insérer dans la vue
	 * @return Array $donnees les données modifiées
	 */
	public function preTraiterDonnees($donnees) {

		// ajout de l'url de l'appli
		$donnees['base_url'] = new Url(Config::get('base_url_application'));
		
		$donnees['base_url_styles'] = $this->getUrlBase();
		
		$donnees['url_cette_page'] = $this->getUrlCettePage() ;
		
		$donnees['base_url_application'] = $this->getUrlBaseComplete();
		
		$this->chargerModele('AnnuaireModele');

		//ajout des variables d'identification
		$donnees['est_admin'] = Registre::getInstance()->get('est_admin');
		$donnees['identification_id'] =	Registre::getInstance()->get('identification_id');
		$donnees['identification_mail']	= Registre::getInstance()->get('identification_mail');
				
		$format = Config::get('date_format_simple');
		
		if($format) {
			$donnees['format_date_simple'] = $format;
		} else {
			$donnees['format_date_simple'] = 'd/m/Y';
		}

		return $donnees;
	}
		
	public function getUrlBase() {
		
		$base_vrai_chemin = str_replace(realpath($_SERVER['DOCUMENT_ROOT']),'',realpath(Application::getChemin()));
		$base_vrai_chemin .= '/';
		
		return new Url($base_vrai_chemin);
	}
	
	public function getUrlBaseComplete() {
		return new Url('http://'.$_SERVER['SERVER_NAME'].str_replace(realpath($_SERVER['DOCUMENT_ROOT']),'',realpath(Application::getChemin())));
	}
	
	public function getUrlCettePage() {
		return $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	}
	
	public static function getUrlConfirmationInscriptionAdmin($code_confirmation_inscription) {
		
		$url_cette_page = 'http://'.$_SERVER['SERVER_NAME'].str_replace('annuaire_utilisateur','annuaire_admin',$_SERVER['REQUEST_URI']);
		$base_url = new URL($url_cette_page);
		$base_url->setVariablesRequete(array());
		$base_url->setVariableRequete('m','annuaire_inscription_confirmation_admin');
		$base_url->setVariableRequete('id',$code_confirmation_inscription);
		
		return $base_url->getURL();
	}
	
	public static function getUrlConfirmationInscription($code_confirmation_inscription) {
		
		$url_cette_page = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$base_url = new URL($url_cette_page);
		$base_url->setVariablesRequete(array());
		$base_url->setVariableRequete('m','annuaire_inscription_confirmation');
		$base_url->setVariableRequete('id',$code_confirmation_inscription);
		
		return $base_url->getURL();
	}
	
	public static function getUrlSuppressionInscriptionTemporaire($id_annuaire, $code_donnee_temporaire) {
		
		$url_cette_page = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$base_url = new URL($url_cette_page);
		$base_url->setVariablesRequete(array());
		$base_url->setVariableRequete('m','annuaire_suppression_inscription_temp');
		$base_url->setVariableRequete('id',$code_donnee_temporaire);
		$base_url->setVariableRequete('id_annuaire',$id_annuaire);
		
		return $base_url->getURL();
	}
	
	public static function getUrlConsultationProfil($id_annuaire, $id_utilisateur) {
		
		$url_consultation_profil = new Url(Config::get('base_url_application'));
		$url_consultation_profil->setVariableRequete('m','annuaire_fiche_utilisateur_consultation'); 
		$url_consultation_profil->setVariableRequete('id_annuaire',$id_annuaire); 
		$url_consultation_profil->setVariableRequete('id_utilisateur',$id_utilisateur);
		 
		return $url_consultation_profil; 
	}
	
	public static function getUrlModificationProfil($id_annuaire, $id_utilisateur) {
		
		$url_modification_profil = new Url(Config::get('base_url_application'));
		$url_modification_profil->setVariableRequete('m','annuaire_formulaire_modification_inscription');
		$url_modification_profil->setVariableRequete('id_annuaire',$id_annuaire);
		$url_modification_profil->setVariableRequete('id_utilisateur',$id_utilisateur);
		
		return $url_modification_profil;
	}
	
	public static function getUrlOubliMotDePasse($id_annuaire,$id_utilisateur) {
		
		$url_oubli_mdp = new Url(Config::get('base_url_application'));
		$url_oubli_mdp->setVariableRequete('m','annuaire_afficher_formulaire_oubli_mdp');
		$url_oubli_mdp->setVariableRequete('id_annuaire',$id_annuaire);
		$url_oubli_mdp->setVariableRequete('id_utilisateur',$id_utilisateur);
		
		return $url_oubli_mdp;
	}
	
	public static function getUrlAjoutOuModificationImage($id_annuaire, $id_utilisateur) {
		
		$url_ajout_modification_image = new Url(Config::get('base_url_application'));
		$url_ajout_modification_image->setVariableRequete('m','annuaire_afficher_formulaire_ajout_image'); 
		$url_ajout_modification_image->setVariableRequete('id_annuaire',$id_annuaire); 
		$url_ajout_modification_image->setVariableRequete('id_utilisateur',$id_utilisateur); 
		return $url_ajout_modification_image;
			
	}
	
	/**
	 * Renvoie le template de pagination, considérant des éléments donnés en paramètre
	 * @param int $numero_page le numéro de page en cours
	 * @param int $taille_page la taille de page
	 * @param int $total le nombre total de pages
	 * @param object $url_base l'url de base de la page
	 * @param array $valeurs les valeurs à concatener à l'url
	 * @return string le html contenu la template de pagination rempli avec les infos
	 */
	protected function paginer($numero_page = 1, $taille_page = 50, $total, $url_base, $valeurs) {

	    $start = ($numero_page - 1)*$taille_page;
	    $limit = $taille_page;
	    $intervalle_pages = 5;
    	    	
	    $page_en_cours = $numero_page;
	    
	    $pages_avant_apres = (ceil($intervalle_pages /2) + 1);
	    $pages_debut_intervalle = 0;
	    $nb_pages = 0;
	    
	    if ($page_en_cours < $pages_avant_apres)  {
		    $pages_debut_intervalle = 1;
	    } else {
		    $pages_debut_intervalle = $page_en_cours - $pages_avant_apres + 2;
	    }
	    
	    $pages_a_afficher = $intervalle_pages;
	    
	    $intervalle_max = (($page_en_cours) * $limit);
	    
	    foreach($valeurs as $cle => $variable) {
			    $url_base->setVariableRequete($cle,$variable);
		    }    	
	    $donnees['url_base_pagination'] = $url_base->getUrl().'&amp;taille_page='.$taille_page.'&amp;numero_page=';
	    
	    $nb_pages = ceil($total/$limit);  

	    if ($page_en_cours == $nb_pages) {
		    $intervalle_max = $total;
	    }
	    
	    $donnees['pages_taille_intervalle'] = $intervalle_pages;
	    $donnees['pages_debut_intervalle'] = $pages_debut_intervalle;
	    $donnees['page_en_cours'] = $page_en_cours;
	    $donnees['intervalle_min'] = (($page_en_cours-1) * $limit);
	    $donnees['intervalle_max'] = $intervalle_max;
	    $donnees['nb_resultats'] = $total;
	    $donnees['nb_pages'] = $nb_pages;
	    $donnees['taille_page'] = $limit;
	    
	    return $this->getVue(Config::get('dossier_squelettes_elements').'pagination',$donnees);
	}

	
	public function obtenirIdParMail($id_annuaire, $mail_utilisateur) {
		
		$this->chargerModele('AnnuaireModele');
		$id = $this->AnnuaireModele->obtenirIdParMail($id_annuaire, $mail_utilisateur);
		
		return $id;
	}
	
	public function utilisateurExiste($id_annuaire,$id, $utilise_mail = true) {

		$this->chargerModele('AnnuaireModele');

		if($utilise_mail) {
			$existe = $this->AnnuaireModele->utilisateurExisteParMail($id_annuaire,$id);
		} else {
			$existe = $this->AnnuaireModele->utilisateurExisteParId($id_annuaire,$id);
		}

		return $existe;
	}

	
/** ---------------------------------	 Fonction de formatage de données communes aux classes ---------------------------------*/
	
	public static function formaterVersDateMysql($date) {
		
		$format = Config::get('date_format_simple');
					
		if(!isset($format)) {
			$format = 'd/m/Y';
		}
		
		$recherche = array('d','m','Y');
		$remplacement = array('([0-9]{1,2})','([0-9]{1,2})','([0-9]{4})');
		
		$pattern_date_simple = str_replace($recherche, $remplacement, $format);

		if(ereg($pattern_date_simple, $date)) {
			$date_tab = split('/', $date);
			$time = mktime(0,0,0,$date_tab[1],$date_tab[0],$date_tab[2]);	
		} else {			
			$time = strtotime($date);
		}
		
		return date('Y-m-d h:i:s', $time); 	
	} 
	
	public static function formaterDateMysqlVersDateAnnuaire($date) {
		
		$format = Config::get('date_format_simple');
					
		if(!isset($format)) {
			$format = 'd/m/Y';
		}
		
		$time = strtotime($date);
		return date($format, $time); 			
	} 
	
	public static function genererDateCouranteFormatMySql() {
		return date('Y-m-d h:i:s');
	}
	
	public static function genererDateCouranteFormatAnnuaire() {
		$date_mysql_courante = self::genererDateCouranteFormatMySql();
		return self::formaterDateMysqlVersDateAnnuaire($date_mysql_courante);
	}
	
	public static function formaterMotPremiereLettreChaqueMotEnMajuscule($chaine) {
		$encodage = Config::get('appli_encodage');
		
		return str_replace(' - ', '-', 
					mb_convert_case(
						mb_strtolower(
								str_replace('-', ' - ', $chaine),
								$encodage
						), 
						MB_CASE_TITLE,
						$encodage
					)
				);
	}
	
	public static function formaterMotEnMajuscule($chaine) {
		return mb_convert_case($chaine, MB_CASE_UPPER, Config::get('appli_encodage'));
	}
	
	function aplatirTableauSansPreserverCles($tableau) {
		
	    $temp = array();
	    foreach ($tableau as $cle => $valeur) {
	        if (is_array($valeur)) {
	            $temp = array_merge($temp,$this->aplatirTableauSansPreserverCles($valeur));
	        } else {
	        	$temp[] = $valeur;
	        }
	    }
	    return $temp;
	}

/** ---------------------------------    Fonction d'extraction des champs de mappage -------------------------------------------*/	
	
	/**
	 * Renvoie les champs de mappage correspondant à un annuaire donné
	 * @param int $id_annuaire l'indentifant de l'annuaire pour lequel on veut ces informations
	 * @return Array un tableau de mappage des champs
	 *
	 */
	protected function obtenirChampsMappageAnnuaire($id_annuaire) {

		$this->chargerModele('AnnuaireModele');
		$tableau_mappage = $this->AnnuaireModele->obtenirChampsMappageAnnuaire($id_annuaire);

		return $tableau_mappage;
	}
	
	protected function obtenirNomsChampsMappageAnnuaire($id_annuaire) {

		$this->chargerModele('AnnuaireModele');
		$tableau_mappage = $this->AnnuaireModele->obtenirChampsMappageAnnuaire($id_annuaire);
		
		$this->chargerModele('MetadonneeModele');
		$metadonnees = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id_annuaire);
		
		$tableau_nom_champs = array();
		
		foreach($metadonnees as $id_champ => $valeur) {

			// Si le champ fait partie des champs mappés
			$cle_champ_mappage = array_search($id_champ, $tableau_mappage[1]);
			
			if($cle_champ_mappage) {
				$tableau_nom_champs[$cle_champ_mappage] = $valeur['amc_abreviation'];
			}
		}

		return $tableau_nom_champs;
	}
	
	
	

/** ---------------------------------    Fonction d'affichage des champs de metadonnées -------------------------------------------*/	
	
	/**
	 * Charge et affiche le champ correspondant à la modification ou l'ajout d'un champ de metadonnée
	 * @param int $id_champ l'identifiant du champ demandé
	 * @return string la vue contenant le champ de formulaire correspondant
	 */
	public function afficherFormulaireChampMetadonnees($id_champ, $donnees) {

		// si le champ est restreint à une valeur de liste
		if($donnees['amc_ce_ontologie'] != 0) {
				$this->chargerModele('OntologieModele');
				$donnees['liste_valeurs'] = $this->OntologieModele->chargerListeOntologie($donnees['amc_ce_ontologie']);
		}

		$donnees['amc_id_champ'] = $id_champ;

		if(isset($donnees['amc_ce_template_affichage'])) {
			$nom_type_champ = $donnees['amc_ce_template_affichage'];
		} else {
			$this->chargerModele('MetadonneeModele');
			$nom_type_champ = $this->MetadonneeModele->renvoyerTypeAffichageParId($donnees['amc_ce_type_affichage']);
		}

		return $this->getVue(Config::get('dossier_squelettes_champs').$nom_type_champ,$donnees);
	}
	
	
	
	
/** ---------------------------------    Fonction d'existence et de génération des formulaires -------------------------------------------*/
	
	public function annuaireAvoirFormulaireInscription($code_annuaire) {
		return $this->templateExiste($code_annuaire.'_inscription','formulaires/');
	}
	
	public function annuaireAvoirPageAccueilPostInscription($code_annuaire) {
		return $this->templateExiste($code_annuaire.'_inscription_confirmation', Config::get('dossier_squelettes_annuaires'));
	}
	
	public function annuaireAvoirFicheUtilisateur($code_annuaire) {
		return $this->templateExiste($code_annuaire.'_fiche','/fiches/');
	}
	
	public function annuaireAvoirFormulaireModificationInscription($code_annuaire) {
		return $this->templateExiste($code_annuaire.'_modification','/formulaires/');
	}
	
	public function annuaireAvoirPagePostDesinscription($code_annuaire) {
		return $this->templateExiste($code_annuaire.'_desinscription_confirmation','/annuaires/');
	}
	
	public function annuaireAvoirFormulaireRecherche($code_annuaire) {
		return $this->templateExiste($code_annuaire.'_recherche','/formulaires/');
	}
	
	public function annuaireAvoirPageResultatRecherche($code_annuaire) {
		return $this->templateExiste($code_annuaire.'_resultat_recherche', Config::get('dossier_squelettes_annuaires'));
	}
	
	/**
	 * Renvoie true si le template demandé existe, sinon faux
	 * @param string $nom_formulaire le nom du formulaire demandé (qui est normalement le code d'un annuaire)
	 * @param string $dossier le nom du dossier sous dossier demandé
	 * @return boolean true si le formulaire existe, false sinon
	 */
	protected function templateExiste($nom_template, $dossier = '/') {
		
		return file_exists(Config::get('chemin_squelettes').$dossier.$nom_template.'.tpl.html');
	}
	
	
	/**
	 * Renvoie une fiche utilisateur minimale auto-générée
	 * @param string $donnees les données à inclure dans le formulaire
	 * @return string la vue contenant le formulaire généré
	 */
	protected function genererFicheInscrit($donnees) {

		$formulaire_modele = $this->getVue(Config::get('dossier_squelettes_fiches').'fiche',$donnees);

		if($formulaire_modele) {
			return $formulaire_modele;
		} else {
			trigger_error("impossible de trouver le squelette de référence pour le formulaire");
		}

		return false;
	}

	/**
	 * Renvoie un formulaire d'inscription minimal auto-généré
	 * @param string $donnees les donnée à inclure dans le formulaire
	 * @return string la vue contenant le formulaire généré
	 */
	protected function genererFormulaireInscription($donnees) {

		$formulaire_modele = $this->getVue(Config::get('dossier_squelettes_formulaires').'inscription',$donnees);

		if($formulaire_modele) {
			return $formulaire_modele;
		} else {
			trigger_error("impossible de trouver le squelette de référence pour le formulaire");
		}

		return false;
	}
	
	/**
	 * Renvoie un formulaire d'inscription minimal auto-généré
	 * @param string $donnees les donnée à inclure dans le formulaire
	 * @return string la vue contenant le formulaire généré
	 */
	protected function genererFormulaireModificationInscription($donnees) {

		$formulaire_modele = $this->getVue(Config::get('dossier_squelettes_formulaires').'modification',$donnees);

		if($formulaire_modele) {
			return $formulaire_modele;
		} else {
			trigger_error("impossible de trouver le squelette de référence pour le formulaire");
		}

		return false;
	}

	/**
	 * Renvoie un formulaire d'inscription minimal auto-généré
	 * @param string $donnees les donnée à inclure dans le formulaire
	 * @return string la vue contenant le formulaire généré
	 */
	protected function genererFormulaireRecherche($donnees) {

		$formulaire_modele = $this->getVue(Config::get('dossier_squelettes_formulaires').'recherche',$donnees);

		if($formulaire_modele) {
			return $formulaire_modele;
		} else {
			trigger_error("impossible de trouver le squelette de référence pour le formulaire");
		}

		return false;
	}
	
/** ---------------------------------    Fonction d'extraction d'informations utilisées entre autres par les web services -------------------------------------------*/

	public function obtenirValeursUtilisateur($id_annuaire, $id_utilisateur) {
		
		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire, false);

		$this->chargerModele('MetadonneeModele');

		$metadonnees = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id_annuaire);
		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$valeurs_annuaire = $this->AnnuaireModele->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur,$tableau_mappage[0]);
		$valeurs_metadonnees= $this->MetadonneeModele->chargerListeValeursMetadonneesUtilisateur($id_annuaire, $id_utilisateur);
		
		foreach($tableau_mappage[0] as $cle => $nom_champ) {

			if($cle != 'champ_id') {

				$nom_champ_formulaire = $metadonnees[$tableau_mappage[1][$cle]]['amc_abreviation'];
				$valeur = $valeurs_annuaire[$nom_champ] ;				
				
				if($cle == 'champ_nom') {
					$valeur = AppControleur::formaterMotEnMajuscule($valeur);
				}
				
				if($cle == 'champ_prenom') {
					 $valeur = AppControleur::formaterMotPremiereLettreChaqueMotEnMajuscule($valeur);
				}
				
				if(isset($valeurs_metadonnees[$nom_champ_formulaire])) {
					if(isset($valeurs_metadonnees[$nom_champ_formulaire]['amv_valeur']) && $valeurs_metadonnees[$nom_champ_formulaire]['amv_valeur'] != '') {
						$valeur = $valeurs_metadonnees[$nom_champ_formulaire]['amv_valeur'];
					} 
					$informations_champ = array('amv_valeur' => $valeur,'amc_id_champ' => $tableau_mappage[1][$cle]) ;
					$valeurs_metadonnees[$nom_champ_formulaire] = array_merge($valeurs_metadonnees[$nom_champ_formulaire],$informations_champ);
				} else {
					$informations_champ = array('amv_valeur' => $valeur,'amc_id_champ' => $tableau_mappage[1][$cle]) ;
					$valeurs_metadonnees[$nom_champ_formulaire] = $informations_champ;
				}
			}
		}
		
		foreach($valeurs_metadonnees as $nom_champ => $valeur) {
			$verificateur = new VerificationControleur();
			$valeurs_metadonnees[$nom_champ] = $verificateur->verifierEtRemplacerValeurChampPourAffichage($valeur['amc_ce_type_affichage'],$valeur, 1);
		}
		
		return $valeurs_metadonnees;
	}
	
	public function obtenirInfosUtilisateur($id_annuaire,$id, $mail = true) {

		$this->chargerModele('AnnuaireModele');

		if($mail) {
			$id = $this->AnnuaireModele->obtenirIdParMail($id_annuaire,$id);
		}

		$champs_mappage = $this->AnnuaireModele->obtenirChampsMappageAnnuaire($id_annuaire);

		$valeurs = $this->AnnuaireModele->obtenirValeursUtilisateur($id_annuaire,$id, $champs_mappage[0]);

		// TODO: valeurs incomplètes, voir ce qu'on renvoie obligatoirement
		// et ce qu'on ne renvoie pas
		$valeurs = array('fullname' => $valeurs[$champs_mappage[0]['champ_prenom']].' '.$valeurs[$champs_mappage[0]['champ_nom']],
                           'nickname' => $valeurs[$champs_mappage[0]['champ_nom']],
                           'dob' => '',
                           'email' => $valeurs[$champs_mappage[0]['champ_mail']],
                           'gender' => '',
                           'postcode' => $valeurs[$champs_mappage[0]['champ_code_postal']],
                           'country' => '',
                           'language' => 'fr',
                           'timezone' => 'Europe/Paris');

		return $valeurs;
	}

	public function comparerIdentifiantMotDePasse($id_annuaire,$id_utilisateur,$mot_de_passe, $utilise_mail = true, $mdp_deja_crypte = true) {

		$this->chargerModele('AnnuaireModele');

		if($utilise_mail) {
			$id_utilisateur = $this->AnnuaireModele->obtenirIdParMail($id_annuaire,$id_utilisateur);
		}

		if(!$mdp_deja_crypte) {
			$mot_de_passe = VerificationControleur::encrypterMotDePasseStatic($mot_de_passe);
		}

		return $this->AnnuaireModele->comparerIdentifiantMotDePasse($id_annuaire,$id_utilisateur,$mot_de_passe);
	}
	
}

?>