<?php
class Cotisation extends AppControleur {
	
	public function Cotisation() {
		
		parent::__construct();
	}
	
    /**
     * Initialisation du controleur principal en fonction des paramètres de l'url.
     */
    public function executerPlugin() {
    	
    	$plugin_action = Config::get('plugin_variable_action');
    	    	               
        if (isset($_GET[$plugin_action])) {
            $action = $_GET[$plugin_action];
        } else {
        	$action = Config::get('plugin_action_defaut');
        }
        
        if(!$this->controleAccesAdmin($action)) {
        	return;
        }
        
        unset($_GET[$plugin_action]);
       
        $resultat_action_plugin = $this->$action($_GET);
        
        return $resultat_action_plugin ;
    }
	   
    /**
     * Méthode appelée pour ajouter un élément.
     */
    public function ajouterCotisation() {
    	
    	$params = $_POST;
    	
    	$elements_requis = array('id_cotisant','date_cotisation', 'montant_cotisation', 'mode_cotisation');
    	$erreurs = array();
        
    	foreach($elements_requis as $requis) {
    		if(!isset($params[$requis])) {
    			//$erreurs[$requis] = 'erreur ';
    		}
    	}
    	
    	if(!empty($erreurs)) {
    		$this->envoyer($erreurs);
    	}
    	
    	$annuaire_modele = $this->getModele('AnnuaireModele');
    	$params['infos_utilisateur'] = $annuaire_modele->obtenirInfosUtilisateurParId($elements_requis['id_cotisant']);
    	
    	$params['date_cotisation'] = $this->formaterVersDateMysql($params['date_cotisation']);

    	$cotisation_modele = new CotisationModele(); 
    	$ajout_cotisation = $cotisation_modele->ajouterCotisation($params);
		
		if(!$ajout_cotisation) {
    		$retour['erreurs'] = 'erreur d\'insertion';
    	}
    	
    	$retour['id_utilisateur'] = $params['id_cotisant'];
    	
    	return $this->afficherInformationsCotisationPourInscrit($retour);
    	
    }
   
    /**
     * Méthode appelée pour mettre à jour une cotisation
     */
    public function mettreAJourCotisation()    {
    	
    	$params = $_POST;
    	
    	$id_cotisation = $params['id_cotisation'];
    	
    	$elements_requis = array('id_cotisation','id_cotisant','date_cotisation', 'montant_cotisation', 'mode_cotisation');
    	$erreurs = array();
        
    	foreach($elements_requis as $requis) {
    		if(!isset($params[$requis])) {
    			//$erreurs[$requis] = 'erreur ';
    		}
    	}
    	
    	if(!empty($erreurs)) {
    		$this->envoyer($erreurs);
    	}
    	    	
    	$params['date_cotisation'] = $this->formaterVersDateMysql($params['date_cotisation']);
    	
    	$cotisation_modele = new CotisationModele(); 
    	$modification_cotisation = $cotisation_modele->mettreAJourCotisation($id_cotisation, $params);
    	
		if(!$modification_cotisation) {
    		$retour['erreurs'] = 'erreur d\'insertion';
    	}
    	
    	$retour['id_utilisateur'] = $params['id_cotisant'];
    	
    	return $this->afficherInformationsCotisationPourInscrit($retour);
    }
   
    /**
     * Méthode appelée pour supprimer un élément
     */
    public function supprimerCotisation() {
		
    	$id_cotisation = $_GET['id_cotisation'];
    	
    	$cotisation_modele = new CotisationModele(); 
    	$suppression_cotisation = $cotisation_modele->supprimerCotisation($id_cotisation);
    	
    	if(!$suppression_cotisation) {
    		// TODO: comment gère t'on les erreurs ?
    	}
    	
    	$param['id_utilisateur'] = $_GET['id_utilisateur'];
    	
    	return $this->afficherInformationsCotisationPourInscrit($param);
    	
    }
    
     /**
     * 
	 * Affiche un tableau récapitulant les informations de l'historique des cotisations pour un membre donné 
     */
    private function afficherInformationsCotisationPourInscrit($param) {
    	
    	$id_inscrit = $param['id_utilisateur'];
    	
    	if(!Registre::getInstance()->get('est_admin')) {
    		$id_inscrit = Registre::getInstance()->get('identification_id');
    	}
    	
    	$donnees['cotisations'] = $this->obtenirInformationsCotisationPourInscrit($param);
    	
    	$donnees['infos_utilisateur'] = $this->getInformationsUtilisateur($id_inscrit);
    	
    	$donnees['url_formulaire_ajout_cotisation'] = $this->getUrlFormulaireAjoutCotisation($id_inscrit);
    	
    	if(isset($param['message'])) {
    		$donnees['message'] = $param['message'];
    	}
    	
    	if(Registre::getInstance()->get('est_admin')) {
    		$squelette = 'liste_cotisations_admin';
    	} else {
    		$squelette = 'liste_cotisations_inscrit';
    	}
    	
    	$liste_cotisations_html = $this->renvoyerSquelette($squelette, $donnees);
    	
    	return $liste_cotisations_html;
    }
        
    /**
     * 
	 *  Renvoie les informations de cotisation pour un membre donné 
     */
	private function obtenirInformationsCotisationPourInscrit($param) {
			
		$id_inscrit = $param['id_utilisateur'];
		
		if(!Registre::getInstance()->get('est_admin')) {
    		$id_inscrit = Registre::getInstance()->get('identification_id');
    	}
		
		$cotisation_modele = new CotisationModele(); 
		$infos_cotisation_inscrit = $cotisation_modele->obtenirInformationsCotisationsPourInscrit($id_inscrit);
		
    	$infos_cotisation_inscrit_formatees = array();
    	
    	foreach($infos_cotisation_inscrit as $cotisation_inscrit) {
    		$infos_cotisation_inscrit_formatees[] = $this->formaterInformationsCotisationPourEnvoi($cotisation_inscrit);
    	}
    	
    	return $infos_cotisation_inscrit_formatees;
	}
    
	
	private function afficherFormulaireAjoutCotisation($param) {
    	
		$donnees['id_cotisant'] = $param['id_cotisant'];
		
    	$donnees['cotisations'] = $this->obtenirInformationsCotisationPourInscrit($param);
    	
    	$cotisation_modele = new CotisationModele(); 
    	$donnees['modes_cotisation'] = $cotisation_modele->obtenirListeModesCotisation();
    	
    	$donnees['url_liste_cotisations'] = $this->getUrlVoirListeCotisations();
    	
    	$donnees['url_ajout_cotisation'] = $this->getUrlAjoutCotisation();
    	
    	$donnees['url_retour'] = $this->urlService();
    	
    	$donnees['infos_utilisateur'] = $this->getInformationsUtilisateur($donnees['id_cotisant']);
    	
    	$liste_cotisations_html = $this->renvoyerSquelette('formulaire_ajout_cotisation', $donnees);
    	
    	return $liste_cotisations_html;
    }
    
    private function afficherFormulaireModificationCotisation() {

    	$param = $_GET;
    	
    	$donnees['id_utilisateur'] = $param['id_utilisateur'];
    	$donnees['id_cotisation_a_modifer'] = $param['id_cotisation'];
    	
    	$donnees['cotisations'] = $this->obtenirInformationsCotisationPourInscrit($param);
    	
    	$cotisation_modele = new CotisationModele(); 
    	$donnees['modes_cotisation'] = $cotisation_modele->obtenirListeModesCotisation();
    	
    	$donnees['url_liste_cotisations'] = $this->getUrlVoirListeCotisations();
    	
    	$donnees['url_modification_cotisation'] = $this->getUrlModificationCotisation($donnees['id_cotisation_a_modifer']);
    	
    	$donnees['url_retour'] = $this->urlService();
    	
    	$donnees['infos_utilisateur'] = $this->getInformationsUtilisateur($donnees['id_utilisateur']);
    	
    	$liste_cotisations_html = $this->renvoyerSquelette('formulaire_modification_cotisation', $donnees);
    	
    	return $liste_cotisations_html;
    }
    
    private function getInformationsUtilisateur($id_utilisateur) {
    	$id_annuaire = !empty($_GET['id_annuaire']) ? $_GET['id_annuaire'] : Config::get('annuaire_defaut');
    	$annuaire_modele = $this->getModele('AnnuaireModele');
    	return $annuaire_modele->obtenirInfosUtilisateurParId($id_annuaire, $id_utilisateur);
    }
    
    // +---------------------------------------------------------------------------------------------------------------+
    // METHODES D'ACCES A LA BASE DE DONNEES
    
    private function getInformationsHistoriqueCotisation() {
    	
    	$requete_infos_historique_cotisation = 'SELECT * FROM annuaire_COTISATION';

    	$infos_historique_cotisation = $this->executerRequete($requete_infos_historique_cotisation);
    	
    	$infos_historique_cotisation_formatees = array();
    	
    	foreach($infos_historique_cotisation as $cotisation) {
    		$infos_historique_cotisation_formatees[] = $this->formaterInformationsCotisationPourEnvoi($cotisation);
    	}
    	
    	return $infos_historique_cotisation_formatees;
    }
    
    private function obtenirNumeroRecuCotisation($param) {
    	
    	$id_cotisation = $param['id_cotisation'];
    	$id_utilisateur = $param['id_utilisateur'];
    	
		if(!Registre::getInstance()->get('est_admin')) {
    		$id_utilisateur = Registre::getInstance()->get('identification_id');
    	}
    	
    	$id_annuaire = Config::get('annuaire_defaut');
    	
    	if(isset($_GET['id_annuaire'])) {
    		$id_annuaire = $_GET['id_annuaire'];
    	}
    	
    	$utilisateur = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);
    	
	    if(!isset($id_cotisation)) {
    		return;
    	}
    	
    	$cotisation_modele = new CotisationModele(); 
    	$infos_cotisation = $cotisation_modele->obtenirInformationsPourIdCotisation($id_cotisation, $id_utilisateur);
    	    	
    	if(empty($infos_cotisation)) {
    		return;
    	}
    	
    	$infos_cotisation_formatees = $this->formaterInformationsCotisationPourEnvoi($infos_cotisation);
    	
	    if(!$this->recuEstGenere($infos_cotisation_formatees)) {
	    	
    		$numero_nouveau_recu = $this->calculerNouvelOrdreRecuPourCotisation($infos_cotisation_formatees);
    		$infos_cotisation_formatees['recu_envoye'] = $numero_nouveau_recu;
    		$this->mettreAJourNumeroRecu($infos_cotisation_formatees['id_cotisation'],$numero_nouveau_recu);
    	
	    }    

	    return $infos_cotisation_formatees;
    }
    
    private function initialiserInformationsAnnuaireUtilisateur($param) {
    	
    	$id_cotisation = $param['id_cotisation'];
    	$id_utilisateur = $param['id_utilisateur'];
    	
		if(!Registre::getInstance()->get('est_admin')) {
    		$param['id_utilisateur'] = Registre::getInstance()->get('identification_id');
    	}
    	
    	$id_annuaire = Config::get('annuaire_defaut');
    	
    	if(isset($_GET['id_annuaire'])) {
    		$param['id_annuaire'] = $_GET['id_annuaire'];
    	}
    	
    	$param['utilisateur'] = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);
    	
    	return $param;
    }
        
	private function envoyerRecuCotisation($param) {
		
		$param = $this->initialiserInformationsAnnuaireUtilisateur($param);
		
		$infos_cotisation_formatees = $this->obtenirNumeroRecuCotisation($param);
		
		if(!$this->recuEstEnvoye($infos_cotisation_formatees)) {
	    	$infos_cotisation_formatees['date_envoi_recu'] = $this->mettreAJourDateEnvoiRecuPourCotisation($infos_cotisation_formatees);
	    }
		
    	$recu = new Recu();
    	$recu_pdf = $recu->renvoyerRecuPdf($param['utilisateur'], $infos_cotisation_formatees);
    	
    	$messagerie = new MessageControleur();
    	$donnees['url_voir_recu'] = $this->getUrlTelechargementRecuPourMail($param['id_cotisation']);
    	$contenu_message = $this->renvoyerSquelette('message_recu_cotisation', $donnees);
    	$envoi = $messagerie->envoyerMailAvecPieceJointe(Config::get('adresse_mail_cotisation'), $param['utilisateur']['mail']['amv_valeur'], 'Recu pour votre don à tela botanica', $contenu_message, $recu_pdf, 'Recu.pdf', 'application/pdf');
   
    	$param['message'] = 'Votre reçu a bien été envoyé à l\'adresse '.$param['utilisateur']['mail']['amv_valeur'];
    	
    	return $this->afficherInformationsCotisationPourInscrit($param);
    }
    
    private function voirRecuCotisation($param) {
    	
		$param = $this->initialiserInformationsAnnuaireUtilisateur($param);
		    	
	    if(!isset($param['id_cotisation'])) {
    		return;
    	}
    	
    	$cotisation_modele = new CotisationModele(); 
    	$infos_cotisation = $cotisation_modele->obtenirInformationsPourIdCotisation($param['id_cotisation'], $param['id_utilisateur']);

    	if(empty($infos_cotisation)) {
    		return;
    	}
    	
    	$infos_cotisation_formatees = $this->formaterInformationsCotisationPourEnvoi($infos_cotisation);    	    	
    	$infos_cotisation_formatees = $this->obtenirNumeroRecuCotisation($param);
    	
    	// tant que le recu n'est pas envoyé sa date est celle du jour courant
    	if(!$this->recuEstEnvoye($infos_cotisation_formatees)) {
    		$infos_cotisation_formatees['date_envoi_recu'] = date('d/m/Y');
    	}
    	    	
    	$recu = new Recu();
    	
    	$recu->afficherRecuPdf($param['utilisateur'], $infos_cotisation_formatees);
    	
    	return true;
    }
    
    private function recuEstGenere($cotisation) {
    	
    	if($cotisation['recu_envoye'] != null && $cotisation['recu_envoye'] != 0) {
    		return true;
    	}
    	
    	return false;
    }
    
	private function recuEstEnvoye($cotisation) {
    	
    	if($cotisation['date_envoi_recu'] != null && $cotisation['date_envoi_recu'] != 0) {
    		return true;
    	}
    	
    	return false;
    }
    
    private function calculerNouvelOrdreRecuPourCotisation($cotisation) {
    	
    	$cotisation_modele = new CotisationModele();
    	
    	$annee_recu = $cotisation['annee_cotisation'];
    	$numero_ordre = $cotisation_modele->calculerNouvelOrdreRecuEtIncrementer($annee_recu);
    	
    	return $numero_ordre;
    }
    
    private function mettreAJourDateEnvoiRecuPourCotisation($cotisation) {
    	
    	$cotisation_modele = new CotisationModele();
    	$cotisation_modele->mettreAJourDateEnvoiRecu($cotisation['id_cotisation']);
    	
    	$date_envoi_recu = $this->genererDateCouranteFormatAnnuaire();
    	
    	return $date_envoi_recu;
    }
    
	private function mettreAJourNumeroRecu($id_cotisation, $numero_recu) {
       	
    	$cotisation_modele = new CotisationModele();
    	$maj_cotisation_num_recu = $cotisation_modele->mettreAJourNumeroRecu($id_cotisation, $numero_recu);
    	
    	return $maj_cotisation_num_recu;
    }
    
    private function formaterInformationsCotisationPourEnvoi($cotisation) {
    	
    	$cotisation_modele = new CotisationModele(); 
    	
    	$cotisation_champs_formates = array(
		    	'id_cotisation' => $cotisation['IC_ID'],
		    	'id_inscrit' => $cotisation['IC_ANNU_ID'],
    			'annee_cotisation' => $this->formaterAnneeDateCotisationMySql($cotisation['IC_DATE']),
				'date_cotisation' => $this->formaterDateMysqlVersDateAnnuaire($cotisation['IC_DATE']),
    			'montant_cotisation' => $cotisation['IC_MONTANT'],
				'mode_cotisation' => $cotisation_modele->obtenirModeCotisationParId($cotisation['IC_MC_ID']),
				'id_mode_cotisation' => $cotisation['IC_MC_ID'],
				'recu_envoye' => $cotisation['IC_RECU'],
    			'date_envoi_recu' => $this->formaterDateMysqlVersDateCotisation($cotisation['IC_DATE_ENVOIE_RECU']),
    			'url_voir_recu' => $this->getUrlVoirRecuCotisation($cotisation['IC_ID']),
    	    	'url_envoyer_recu' => $this->getUrlEnvoiRecuCotisation($cotisation['IC_ID']),
    			'url_formulaire_modification' => $this->getUrlFormulaireModificationCotisation($cotisation['IC_ID']),
    			'url_suppression' => $this->getUrlSuppressionCotisation($cotisation['IC_ID'])
		    );
		    
		return $cotisation_champs_formates;
    }
    
    private function renvoyerSquelette($squelette, $donnees) {
    	
    	$chemin_squelette = Config::get('chemin_appli').'composants'.DS.ANNUAIRE_PLUGIN.DS.'squelettes'.DS.$squelette.'.tpl.html';
    	
    	$sortie = SquelettePhp::analyser($chemin_squelette, $donnees);
    	//$squelette_dossier = .'squelettes'.DS;
    	return $sortie;
    }
    
	private function urlService($params = array()) {
		
    	$url_service = new Url(Url::getDemande()->getUrl());
    	    	    	
    	$variables_requetes = $url_service->getVariablesRequete();
    	
    	$variables_plugins = array(Config::get('plugin_variable_action'), 'id_cotisation', 'id_cotisant');
    	
    	foreach($params as $cle => $valeur) {
    		$variables_requetes[$cle] = $valeur;
    	}
    	
    	$url_service->setVariablesRequete($variables_requetes);
    	
    	return $url_service->getUrl();
    }
    
	private function getUrlVoirListeCotisations() {
		
		$params = array('action_cotisation' => 'afficherInformationsCotisationPourInscrit');
		
    	return $this->urlService($params);
    }
    
	private function getUrlVoirRecuCotisation($id_cotisation) {
		
		$params = array('action_cotisation' => 'voirRecuCotisation',
						'id_cotisation' => $id_cotisation);
		
    	return $this->urlService($params);
    }
    
    private function getUrlTelechargementRecuPourMail($id_cotisation) {
    	return Config::get('base_url_telechargement').'?m=annuaire_afficher_page&page=cotisations&id_annuaire=1&action_cotisation=voirRecuCotisation&id_cotisation='.$id_cotisation;
    }
    
	private function getUrlEnvoiRecuCotisation($id_cotisation) {
		
		$params = array('action_cotisation' => 'envoyerRecuCotisation',
						'id_cotisation' => $id_cotisation);
		
    	return $this->urlService($params);
    }
    
    private function getUrlFormulaireAjoutCotisation($id_cotisant) {
    	
    	$params = array('action_cotisation' => 'afficherFormulaireAjoutCotisation',
						'id_cotisant' => $id_cotisant);
    	
    	return $this->urlService($params);
    }
    
	private function getUrlAjoutCotisation() {
		
    	$params = array('action_cotisation' => 'ajouterCotisation');
    	
    	return $this->urlService($params);
    }
    
    private function getUrlFormulaireModificationCotisation($id_cotisation) {
    	
    	$params = array('action_cotisation' => 'afficherFormulaireModificationCotisation',
						'id_cotisation' => $id_cotisation);
    	
    	return $this->urlService($params);
    }
    
   	private function getUrlModificationCotisation($id_cotisation) {
   		
   		$params = array('action_cotisation' => 'mettreAjourCotisation',
					'id_cotisation' => $id_cotisation);
   		
   		return $this->urlService($params);
   	}
    
	private function getUrlSuppressionCotisation($id_cotisation) {
		
		$params = array('action_cotisation' => 'supprimerCotisation',
					'id_cotisation' => $id_cotisation);
		
    	return $this->urlService($params);
    }
    
    private function controleAccesAdmin($fonction) {
    	
    	$fonction_admins = array(
    		'ajouterCotisation',
    		'mettreAJourCotisation',
    		'supprimerCotisation',
    		'afficherFormulaireAjoutCotisation',
    		'envoyerRecuCotisation'
    	);
    	
    	if(in_array($fonction, $fonction_admins) && !Registre::getInstance()->get('est_admin')) {
    		return false;
    	}
    	
    	return true;
    }
    
    private function formaterAnneeDateCotisationMySql($date_cotisation) {
    	
    	$annee_cotisation = '0';
    	
    	if($date_cotisation != '0') {
    		$annee_cotisation = date('Y',strtotime($date_cotisation));
    	}
    	
    	return $annee_cotisation;
    }
    
    private function formaterDateMysqlVersDateCotisation($date) {
    	if($date == '0000-00-00') {
    		return 0;
    	}
    	return $this->formaterDateMysqlVersDateAnnuaire($date);
    }
}
?>