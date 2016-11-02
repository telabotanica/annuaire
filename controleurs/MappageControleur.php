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

class MappageControleur extends AppControleur {

	private $id_liste_champs = 30768;
	
	/**
	 * Charge la vue contenant les informations d'un annuaire géré par l'application
	 * @param int $id l'annuaire dont on veut consulter les informations
	 * @return string la vue contenant les informations
	 */
	public function chargerAnnuaire($id) {
		$this->chargerModele('AnnuaireModele');
		$this->chargerModele('MetadonneeModele');
		$data['erreurs'] = array();
		$data['annuaire'] = $this->AnnuaireModele->chargerAnnuaire($id,true);
		$data['metadonnees'] = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id);
		$annuaire = $this->getVue(Config::get('dossier_squelettes_gestion_annuaires').'annuaire', $data);

		return $annuaire;
	}
	
/**--------Fonctions de gestion des champs de mappage associées à un annuaire et des formaulaires associés --------*/
	/**
	 * Affiche le formulaire d'ajout d'une metadonnee
	 * @param Array $valeurs les valeurs à inclure dans le formulaire (dans le cas du retour erreur)
	 * @return string la vue contenant le formulaire
	 */
	public function afficherFormulaireAjoutMappage($id_annuaire, $donnees = array()) {

		$this->chargerModele('MetadonneeModele');
		$this->chargerModele('GestionAnnuaireModele');
		
		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		
		$liste_champs_mappage = array('champ_id' => 'Champ identifiant',
		'champ_pass' => 'Champ mot de passe',
		'champ_nom' => 'Champ nom',  
  		'champ_prenom' => 'Champ prénom' , 
  		'champ_mail' => 'Champ mail',  
  		'champ_pays' => 'Champ pays',  
  		'champ_code_postal' => 'Champ code postal', 
  		'champ_adresse' => 'Champ adresse',  
  		'champ_adresse_comp' => 'Champ adresse complémentaire',
		'champ_adresse_comp' => 'Champ adresse complémentaire'); 
		
		$champs_metadonnees = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id_annuaire);
		$champs_annuaire = $this->GestionAnnuaireModele->obtenirListeNomsChampsAnnuaireParIdAnnuaire($id_annuaire);
				
		$roles_deja_affectes = array_intersect_key($liste_champs_mappage, $champs_mappage[0]);
		
		//Debug::printr($champs_mappage[0]);
		//Debug::printr($liste_champs_mappage);
			
		$champs_deja_mappe_annuaire = array_intersect_key($champs_mappage[0], $liste_champs_mappage);
		
		$champs_deja_mappe_metadonnees = array_intersect_key($champs_mappage[1], $liste_champs_mappage);
	
		// on retire les roles déjà affectés dans la liste des roles
		$liste_champs_mappage = array_diff_key($liste_champs_mappage, $roles_deja_affectes);
		
		// on retire les champs de l'annuaire qui sont déjà mappés
		$champs_annuaire = array_diff($champs_annuaire, $champs_deja_mappe_annuaire);
				
		// on retire les champ de metadonnées qui mappent déjà un champ
		$champs_metadonnees = array_diff_key($champs_metadonnees, array_flip($champs_deja_mappe_metadonnees));
		
		$data['champs_mappage'] = $liste_champs_mappage;
		$data['champs_metadonnees'] = $champs_metadonnees;
		$data['champs_annuaire'] = $champs_annuaire;
		
		$data['id_annuaire'] = $id_annuaire;
		
		$mappage_ajout = $this->getVue(Config::get('dossier_squelettes_metadonnees').'mappage_ajout',$data);

		return $mappage_ajout;
	}

	/**
	 * Affiche le formulaire de modification d'une metadonnee
	 * @param Array $valeurs les valeurs à inclure dans le formulaire
	 * @return string la vue contenant le formulaire
	 */
	public function afficherFormulaireModificationMappage($id_mappage) {

		if(!isset($valeurs['amc_nom'])) {
			$valeurs['amc_nom'] = '';
		}

		if(!isset($valeurs['amc_abreviation'])) {
				$valeurs['amc_abreviation'] = '';
		}

		if(!isset($valeurs['amc_description'])) {
				$valeurs['amc_description'] = '';
		}
		$data['valeur'] = $valeurs;

		$this->chargerModele('MetadonneeModele');
		$data['valeur'] = $this->MetadonneeModele->chargerInformationsMetadonnee($valeurs['amc_id_champ']);
		$data['types'] = $this->MetadonneeModele->chargerListeDesTypesDeChamps();
		$data['listes'] = $this->MetadonneeModele->chargerListeDesListes();
		$metadonnee_modification = $this->getVue(Config::get('dossier_squelettes_metadonnees').'metadonnee_modification',$data);

		return $metadonnee_modification;
	}

	/**
	 * Ajoute un nouveau champ de mappage à un annuaire
	 * @param Array $valeurs les valeurs à ajouter
	 * @return string la vue contenant l'annuaire associé, ou le formulaire en cas d'échec
	 */
	public function ajouterNouveauMappage($valeurs) {

		$this->ChargerModele('MappageModele');
		
		if(isset($valeurs['id_annuaire'])
			&& isset($valeurs['at_valeur'])
			&& isset($valeurs['at_ressource'])
			&& isset($valeurs['at_action'])) {
	
			$id_annuaire = $valeurs['id_annuaire'];
			$id_champ_metadonnee = $valeurs['at_valeur'];
			$nom_champ_annuaire = $valeurs['at_ressource'];
			$role = $valeurs['at_action'];	
				
			$this->MappageModele->ajouterNouveauMappage($id_annuaire, $nom_champ_annuaire, $role, $id_champ_metadonnee);
		} else  {
			return $this->afficherFormulaireAjoutMappage($valeurs);
		}
		return $this->chargerAnnuaire($valeurs['id_annuaire']);
	}

	/**
	 * Modifie un champ de mapagge associé à un annuaire
	 * @param Array $valeurs les valeurs à modifier
	 * @return string la vue contenant l'annuaire associé, ou le formulaire en cas d'échec
	 */
	public function modifierMappage($valeurs) {

		if(isset($valeurs['amc_id_champ'])
			&& isset($valeurs['amc_nom'])
			&& isset($valeurs['amc_abreviation'])
			&& isset($valeurs['amc_description'])
			&& isset($valeurs['amc_ce_annuaire'])
			&& isset($valeurs['amc_ce_type_affichage'])) {
			$this->chargerModele('MetadonneeModele');
			$this->MetadonneeModele->modifierMetadonnee($valeurs);
		} else  {
			return $this->afficherFormulaireModificationMetadonnee($valeurs);
		}
		return $this->chargerAnnuaire($valeurs['amc_ce_annuaire']);
	}

	/**
	 * Supprime un champ de métadonnée associé à un annuaire
	 * @return string la vue contenant l'annuaire associé, ou le formulaire en cas d'échec
	 */
	public function supprimerMappage($id_annuaire, $id_mappage) {

		if($id_metadonnee != '') {
			$this->chargerModele('MetadonneeModele');
			$this->MetadonneeModele->supprimerMetadonneeParId($id_metadonnee);
		} else  {
			return false;
		}
		return $this->chargerAnnuaire($id_annuaire);
	}

}
?>