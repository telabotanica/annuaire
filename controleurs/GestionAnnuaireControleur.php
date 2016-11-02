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

class GestionAnnuaireControleur extends AppControleur {

	private $id_liste_champs = 30768;
	
/**--------Fonctions d'ajout et de modification des annuaires --------*/	
	
	/**
	 * Charge la vue contenant les informations d'un annuaire donné en paramètre
	 * @param int $id l'identifiant de l'annuaire
	 * @return string la vue contenant les informations sur l'annuaire
	 */
	public function chargerAnnuaire($id) {
		$this->chargerModele('AnnuaireModele');
		$this->chargerModele('MetadonneeModele');
		$data['erreurs'] = array();
		$data['champs_mappage'] = $this->obtenirChampsMappageAnnuaire($id);
		$data['annuaire'] = $this->AnnuaireModele->chargerAnnuaire($id, true);
		$data['metadonnees'] = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id);
		$annuaire = $this->getVue(Config::get('dossier_squelettes_gestion_annuaires').'annuaire', $data);

		return $annuaire;
	}
	
	/**
	 * Affiche le formulaire d'ajout d'un annuaire
	 * @param Array $valeurs les valeurs à inclure dans le formulaire (dans le cas du retour erreur)
	 * @return string la vue contenant le formulaire
	 */
	public function afficherFormulaireAjoutAnnuaire($donnees) {

		$champs = array(
			"aa_nom" => '',
			"aa_description" => '',
			"aa_bdd" => '',
			"aa_table" => '',
			"aa_code" => '',
			"aa_champ_id" => '',
			"aa_champ_nom" => '',
			"aa_champ_mail" => '',
			"aa_champ_pass" => ''
		);
		$donnees = $donnees;

		$formulaire_ajout_annuaire = $this->getVue(Config::get('dossier_squelettes_gestion_annuaires').'annuaire_ajout',$donnees);

		return $formulaire_ajout_annuaire;
	}
	
	public function ajouterAnnuaire($valeurs) {
		
		$champs = array(
			"aa_nom" => '',
			"aa_description" => '',
			"aa_bdd" => '',
			"aa_table" => '',
			"aa_code" => '',
			"aa_champ_id" => '',
			"aa_champ_nom" => '',
			"aa_champ_mail" => '',
			"aa_champ_pass" => ''
		);
		
		$donnees = array('erreurs_champs' => array());
		
		// vérification de la présence de tous les champs
		foreach($champs as $nom_champ => $valeur) {
			if(!isset($valeurs[$nom_champ]) || $valeurs[$nom_champ] == '') {
				$donnees['erreurs_champs'][$nom_champ] = 'Ce champ est obligatoire';
			}
		}
		
		// si il y a une erreur on réaffiche le formulaire
		if(!empty($donnees['erreurs_champs'])) {
			return $this->afficherFormulaireAjoutAnnuaire($donnees);
		}
		
		$champs_a_verifier = array($valeurs[aa_champ_id],$valeurs[aa_champ_nom],$valeurs[aa_champ_mail],$valeurs[aa_champ_pass]);
		
		$informations_annuaire = array(
			"aa_nom" => $valeurs['aa_nom'],
			"aa_description" => $valeurs['aa_description'],
			"aa_bdd" => $valeurs['aa_bdd'],
			"aa_table" => $valeurs['aa_table'],
			"aa_code" => $valeurs['aa_code'],
		);
		
		$informations_champs = array(
			"aa_champ_id" => $valeurs['aa_champ_id'],
			"aa_champ_nom" => $valeurs['aa_champ_nom'],
			"aa_champ_mail" => $valeurs['aa_champ_mail'],
			"aa_champ_pass" => $valeurs['aa_champ_pass']
		);
		
		$this->chargerModele('GestionAnnuaireModele');
		$annuaire_existe = $this->GestionAnnuaireModele->verifierPresenceTable($valeurs['aa_bdd'], $valeurs['aa_table']);
		
		// si l'annuaire existe déjà
		if($annuaire_existe) {
			$champs_existent = $this->GestionAnnuaireModele->verifierPresenceChamps($valeurs['aa_bdd'], $valeurs['aa_table'], $champs_a_verifier);
			// si l'annuaire existe déjà
			if($champs_existent) {
				// tout existe déjà, rien à créer
			}
		} else {
			
			$creation_table = $this->creerTableAnnuaire($informations_annuaire, $informations_champs);
			if(!$creation_table) {
				$donnees['erreurs'] = 'Impossible de créer la table '.$informations_annuaire['aa_table'].' dans la base '.$informations_annuaire['aa_bdd'];
			}
		}
		
		// on insere un nouvel enregistrement dans la table des annuaire
		$id_annuaire = $this->GestionAnnuaireModele->AjouterAnnuaire($informations_annuaire);
		
		if(!$id_annuaire) {
			$donnees['erreurs_champs'][$nom_champ] = 'Impossible d\'ajouter les infos de la table '.$valeurs['aa_table'].' dans la base de données '.$valeurs['aa_bdd'] ;
		}
				
		// on cree un set de métadonnées minimal
		$this->creerEtMapperChampsMetadonneeMinimaux($id_annuaire, $informations_champs);
		
		return $this->chargerAnnuaire($id_annuaire);
		
	}
	
	private function creerTableAnnuaire($informations_annuaire,  $information_champs) {
		$this->chargerModele('GestionAnnuaireModele');
		
		$this->GestionAnnuaireModele->creerTableAnnuaire($informations_annuaire, $information_champs);
	}
	
	private function creerEtMapperChampsMetadonneeMinimaux($id_annuaire, $informations_champs) {
		
		$metadonnee_controleur = new MetadonneeControleur();
		$this->chargerModele('MappageModele');
		$this->chargerModele('MetadonneeModele');
		
		foreach($informations_champs as $type_champ => $nom) {
			
			$role = str_replace('aa_','',$type_champ);
			
			if($role == 'champ_id') {
				$valeurs_mappage = array(
					'id_annuaire' => $id_annuaire,
					'id_champ_annuaire' => $nom,
					'role' => 'champ_id',
					'id_champ_metadonnee' => ''
				);
				$creation = $this->MappageModele->ajouterNouveauMappage($id_annuaire, $nom, 'champ_id', '0');

			} else {
				
				$valeurs_insertion = $this->renvoyerInformationChampPourType($id_annuaire, $role, $nom);
				
				$metadonnee_controleur->ajouterNouvelleMetadonnee($valeurs_insertion);
				
				$id_champ_metadonnee = $this->MetadonneeModele->renvoyerIdChampMetadonneeParAbreviation($id_annuaire, $valeurs_insertion['amc_abreviation']);
				
				// on affecte à chaque champ son role
				$this->MappageModele->ajouterNouveauMappage($id_annuaire, $nom, $role, $id_champ_metadonnee);
				// et on le rend obligatoire
				$this->MappageModele->ajouterNouveauMappage($id_annuaire, $nom, 'champ_obligatoire', $id_champ_metadonnee);

			}
		}
		
		return true;
	}
	
	private function renvoyerInformationChampPourType($id_annuaire, $type, $nom) {
		
		$valeurs = array();
		$this->chargerModele('MetadonneeModele');
		
		$id_liste_champs = $this->id_liste_champs;
		
		switch($type) {
			case 'champ_nom':
				
				$affichage = $this->MetadonneeModele->renvoyerCorrespondanceIdParAbreviation('text',$id_liste_champs);
				
				$valeurs = array('amc_nom' => $nom,
				'amc_abreviation' => 'nom' ,
				'amc_description' => 'Nom',
				'amc_ce_annuaire' => $id_annuaire,
				'amc_ce_type_affichage' => $affichage,
				'amc_ce_ontologie' => '0'
				);
			break;
			
			case 'champ_mail':
				
				$affichage = $this->MetadonneeModele->renvoyerCorrespondanceIdParAbreviation('mail',$id_liste_champs);
				$valeurs = array('amc_nom' => $nom,
				'amc_abreviation' => 'mail',
				'amc_description' => 'Adresse electronique',
				'amc_ce_annuaire' => $id_annuaire,
				'amc_ce_type_affichage' => $affichage,
				'amc_ce_ontologie' => '0'
				);
			break;
			
			case 'champ_pass':
				
				$affichage = $this->MetadonneeModele->renvoyerCorrespondanceIdParAbreviation('password',$id_liste_champs);
				$valeurs = array('amc_nom' => $nom,
				'amc_abreviation' => 'pass',
				'amc_description' => 'Mot de passe',
				'amc_ce_annuaire' => $id_annuaire,
				'amc_ce_type_affichage' => $affichage,
				'amc_ce_ontologie' => '0'
				);
			break;
		}
		
		return $valeurs;
	}
}
?>