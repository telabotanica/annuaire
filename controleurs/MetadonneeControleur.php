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

class MetadonneeControleur extends AppControleur {

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

/**--------Fonctions de gestion des métadonnées associées à un annuaire--------*/
	/**
	 * Affiche le formulaire d'ajout d'une metadonnee
	 * @param Array $valeurs les valeurs à inclure dans le formulaire (dans le cas du retour erreur)
	 * @return string la vue contenant le formulaire
	 */
	public function afficherFormulaireAjoutMetadonnee($valeurs) {

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
		$data['types'] = $this->MetadonneeModele->chargerListeDesTypesDeChamps();
		$data['listes'] = $this->MetadonneeModele->chargerListeDesListes();
		$metadonnee_ajout = $this->getVue(Config::get('dossier_squelettes_metadonnees').'metadonnee_ajout',$data);

		return $metadonnee_ajout;
	}

	/**
	 * Affiche le formulaire de modification d'une metadonnee
	 * @param Array $valeurs les valeurs à inclure dans le formulaire
	 * @return string la vue contenant le formulaire
	 */
	public function afficherFormulaireModificationMetadonnee($valeurs) {

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
	 * Ajoute un nouveau champ de métadonnée à un annuaire
	 * @param Array $valeurs les valeurs à ajouter
	 * @return string la vue contenant l'annuaire associé, ou le formulaire en cas d'échec
	 */
	public function ajouterNouvelleMetadonnee($valeurs) {

		if(isset($valeurs['amc_nom'])
			&& isset($valeurs['amc_abreviation'])
			&& isset($valeurs['amc_description'])
			&& isset($valeurs['amc_ce_annuaire'])
			&& isset($valeurs['amc_ce_type_affichage'])) {
			$this->chargerModele('MetadonneeModele');
			$this->MetadonneeModele->ajouterNouvelleMetadonnee($valeurs);
		} else  {
			return $this->afficherFormulaireAjoutMetadonnee($valeurs);
		}
		return $this->chargerAnnuaire($valeurs['amc_ce_annuaire']);
	}

	/**
	 * Modifie un champ de métadonnée associé à un annuaire
	 * @param Array $valeurs les valeurs à modifier
	 * @return string la vue contenant l'annuaire associé, ou le formulaire en cas d'échec
	 */
	public function modifierMetadonnee($valeurs) {

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
	public function supprimerMetadonnee($id_annuaire, $id_metadonnee) {

		if($id_metadonnee != '') {
			$this->chargerModele('MetadonneeModele');
			$this->MetadonneeModele->supprimerMetadonneeParId($id_metadonnee);
		} else  {
			return false;
		}
		return $this->chargerAnnuaire($id_annuaire);
	}
	
	public function obtenirIdChampMetadonneeParAbreviation($id_annuaire, $abreviation) {
		if(!$id_annuaire || !$abreviation) {
			return false;
		} else  {
			$this->chargerModele('MetadonneeModele');
			return $this->MetadonneeModele->obtenirIdChampMetadonneeParAbreviation($id_annuaire, $abreviation);
		}
	}

}
?>