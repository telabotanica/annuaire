<?php 

class CotisationModele extends AnnuaireModele {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function obtenirInformationsCotisationsPourInscrit($id_inscrit) {
		
		$requete_informations_cotisation = 'SELECT * FROM '.Config::get('cotisation_bdd').'.'.Config::get('cotisation_table').' '.
    								'WHERE IC_ANNU_ID = '.$this->proteger($id_inscrit).' '.
    								'ORDER BY IC_DATE ';

    	$infos_cotisation_inscrit = $this->requeteTous($requete_informations_cotisation);
    	
    	return $infos_cotisation_inscrit;
	}
	
	public function obtenirInformationsPourIdCotisation($id_cotisation, $id_inscrit) {
		
		$requete_informations_cotisation = 'SELECT * FROM '.Config::get('cotisation_bdd').'.'.Config::get('cotisation_table').' '.
    								'WHERE '.
    								'IC_ID = '.$this->proteger($id_cotisation).' '.
									'AND IC_ANNU_ID = '.$this->proteger($id_inscrit);
		
    	$infos_cotisation = $this->requeteUn($requete_informations_cotisation);
    	
    	return $infos_cotisation;
	}
	
	public function ajouterCotisation($params) {
	
		$requete_creation_cotisation = 'INSERT INTO '.Config::get('cotisation_bdd').'.annuaire_COTISATION '.
						'(IC_MC_ID, IC_ANNU_ID, IC_DATE, IC_MONTANT) '.
						'VALUES ('.
	    						$this->proteger($params['mode_cotisation']).','.	
								$this->proteger($params['id_cotisant']).','.
								$this->proteger($params['date_cotisation']).','.
								$this->proteger($params['montant_cotisation']).' '.
						')';		
												
		return $this->requete($requete_creation_cotisation);
	}
	
	public function MettreAJourCotisation($id_cotisation, $params) {
		
		$requete_modification_cotisation = 'UPDATE '.Config::get('cotisation_bdd').'.annuaire_COTISATION '.
			'SET '.
			'IC_MC_ID ='.$this->proteger($params['mode_cotisation']).','.
			'IC_DATE ='.$this->proteger($params['date_cotisation']).','.
			'IC_MONTANT ='.$this->proteger($params['montant_cotisation']).' '.
			'WHERE IC_ID = '.$this->proteger($id_cotisation);
							
		return $this->requete($requete_modification_cotisation);
	}
	
	public function supprimerCotisation($id_cotisation) {
		
		$requete_suppression_cotisation = 'DELETE FROM '.Config::get('cotisation_bdd').'.annuaire_COTISATION '.
    	'WHERE IC_ID = '.$this->proteger($id_cotisation);
    	
    	return $this->requete($requete_suppression_cotisation);
	}
	
    public function obtenirListeModesCotisation() {
    	
    	    $modes_cotisation = array(0 => 'Chèque',
							    	1 => 'Espèce',
							    	2 => 'Virement',
							    	3 => 'Paypal',
							    	4 => 'Prélèvement'
    						);
			
    	//FIXME : en attendant de savoir si on déplace le contenu de la table mode_cotisation dans les triples					
    	return $modes_cotisation;
    }
	
    public function obtenirModeCotisationParId($id_mode) {
    	
    	$modes_cotisation = array(0 => 'Chèque',
							    	1 => 'Espèce',
							    	2 => 'Virement',
							    	3 => 'Paypal',
							    	4 => 'Prélèvement'
    						);

    						
    	//FIXME : en attendant de savoir si on déplace le contenu de la table mode_cotisation dans les triples					
    	return $modes_cotisation[$id_mode];
    	
    	$requete_infos_mode = 'SELECT MC_LABEL as mode_label FROM MODE_COTISATION WHERE MC_ID = '.$this->proteger($id_mode);
    	
    	$infos_mode = $this->requete($requete_infos_mode);
    	
    	if(!empty($infos_mode)) {
    		$infos_mode = $infos_mode[0];
    	}
    	
    	return $infos_mode['mode_label'];
    }
    
    public function calculerNouvelOrdreRecuEtIncrementer($annee) {
		
		$numero_recu = $this->numeroRecuExistePourAnnee($annee);

    	if($numero_recu == null) {
    		$numero_recu = $this->initialiserNumeroRecuPourAnnee($annee);
    	}
		
		$requete_incrementation_ordre = 'UPDATE '.Config::get('cotisation_bdd').'.COMPTEUR_COTISATION '.
										'SET COMPTEUR = COMPTEUR + 1 '.
										'WHERE ANNEE = "'.$annee.'"';

		$resultat_requete_incrementation_ordre = $this->requete($requete_incrementation_ordre) ;
		
		return $numero_recu;
    }
    
    private function numeroRecuExistePourAnnee($annee) {
    	
    	$requete_selection_num_recu_annee = 'SELECT COMPTEUR ' .
    										'FROM '.Config::get('cotisation_bdd').'.COMPTEUR_COTISATION ' .
    											'WHERE ANNEE = "'.$annee.'"';
    											
    	$resultat_selection_num_recu_annee = $this->requeteUn($requete_selection_num_recu_annee);
    	
    	$num_recu = null;
    	
    	if($resultat_selection_num_recu_annee) {
    		$num_recu = $resultat_selection_num_recu_annee['COMPTEUR'];
    	}  
    	
    	return $num_recu;   	
    }
    
    private function initialiserNumeroRecuPourAnnee($annee) {
    	
    	$requete_insertion_num_recu_annee = 'INSERT INTO '.Config::get('cotisation_bdd').'.COMPTEUR_COTISATION ' .
    										'(COMPTEUR, ANNEE) '.
											'VALUES ('.
										    	'1,'.									
						    					$this->proteger($annee).	
											')';
					
    	$resultat_insertion_num_recu_annee = $this->requete($requete_insertion_num_recu_annee);
    
    	$num_nouveau_recu = 1;  	
    	if(!$resultat_insertion_num_recu_annee) {
    		$num_nouveau_recu = null;
    	}
    	
    	return $num_nouveau_recu;
    }
    
	public function mettreAJourNumeroRecu($id_cotisation, $id_recu) {
    	
    	$requete_maj_num_recu_cotisation = 'UPDATE '.Config::get('cotisation_bdd').'.annuaire_COTISATION '.
			'SET '.
			'IC_RECU = '.$this->proteger($id_recu).' '.
			'WHERE IC_ID = '.$this->proteger($id_cotisation);
    	
    	$resultat_maj_envoi_num_cotisation = $this->requete($requete_maj_num_recu_cotisation) ;
    	
    	return $id_recu;
    }
    
    public function mettreAJourDateEnvoiRecu($id_cotisation) {
    	
    	$requete_maj_envoi_recu_cotisation = 'UPDATE '.Config::get('cotisation_bdd').'.annuaire_COTISATION '.
			'SET '.
			'IC_DATE_ENVOIE_RECU = NOW() '.
			'WHERE IC_ID = '.$this->proteger($id_cotisation);
    	
    	$resultat_maj_envoi_recu_cotisation = $this->requete($requete_maj_envoi_recu_cotisation) ;
    	
    	return $resultat_maj_envoi_recu_cotisation;
    }
    
	protected function renvoyerDernierIdInsere() {
		//TODO: cela marche t'il partout ? 
		// à faire: tester le type de driver pdo et faire une fonction portable 
		return $this->connexion->lastInsertId();
	}
}
?>