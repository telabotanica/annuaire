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

class OntologieControleur extends AppControleur {


/**--------Fonctions de gestion des ontologies --------------------------------*/

	/**
	 * charge et renvoie la vue contenant la liste des listes
	 * @return string le html contenant la liste des listes
	 */
	public function chargerListeListe() {

		$this->chargerModele('OntologieModele');
		$data['ontologie'] = $this->OntologieModele->chargerListeListes();
		$data['titre'] = 'Liste des listes';
		$liste_des_listes = $this->getVue(Config::get('dossier_squelettes_ontologies').'liste_des_listes', $data);

		return $liste_des_listes;
	}

	/**
	 * charge et renvoie la vue contenant la liste ontologie et ses éléments dont l'identifiant est passé en paramètre
	 * @param int $identifiant l'identifiant de la liste d'ontologie
	 * @return string le html contenant la liste et ses éléments
	 */
	public function chargerListeOntologie($identifiant) {

		$this->chargerModele('OntologieModele');

		// On charge les informations de la liste (nom description etc...)
		$data['informations'] =  $this->OntologieModele->chargerInformationsOntologie($identifiant);
		$data['ontologie'] = $this->OntologieModele->chargerListeOntologie($identifiant);

		$liste_ontologie = $this->getVue(Config::get('dossier_squelettes_ontologies').'liste_ontologie', $data);

		return $liste_ontologie;
	}

	/**
	 * Affiche le formulaire d'ajout de liste ontologie
	 * @param Array $valeurs un tableau de valeurs (dans le cas du retour erreur)
	 * @return string le formulaire de liste d'ontologie
	 */
	public function afficherFormulaireAjoutListeOntologie($valeurs) {

		if(!isset($valeurs['amo_nom'])) {
			$valeurs['amo_nom'] = '';
		}

		if(!isset($valeurs['amo_abreviation'])) {
				$valeurs['amo_abreviation'] = '';
		}

		if(!isset($valeurs['amo_description'])) {
				$valeurs['amo_description'] = '';
		}

		if(!isset($valeurs['amo_ce_parent'])) {
				$valeurs['amo_ce_parent'] = '';
		}

		$liste_ontologie_ajout = $this->getVue(Config::get('dossier_squelettes_ontologies').'liste_ontologie_ajout', $valeurs);

		return $liste_ontologie_ajout;
	}

	/**
	 * Affiche le formulaire de modification de liste ontologie
	 * @param Array un tableau de valeurs contenant l'id de la liste (et les élements pour le retour erreur)
	 * @return string le formulaire de modification ou la liste des liste si l'id est invalide
	 */
	public function afficherFormulaireModificationListeOntologie($id_ontologie) {

		if(trim($id_ontologie) != '') {
			$this->chargerModele('OntologieModele');
			$data['valeurs'] = $this->OntologieModele->chargerInformationsOntologie($id_ontologie);
			$liste_ontologie_modification = $this->getVue(Config::get('dossier_squelettes_ontologies').'liste_ontologie_modification', $data);
			return $liste_ontologie_modification;
		} else {
			return $this->chargerListeListe();
		}
	}

	/**
	 * Ajoute une nouvelle liste d'ontologie
	 * @param Array $valeurs les valeurs à ajouter
	 * @return string la vue contenant la liste des liste, ou bien le formulaire d'ajout en cas d'erreur
	 */
	public function ajouterNouvelleListeOntologie($valeurs) {

		if(isset($valeurs['amo_nom'])
			&& isset($valeurs['amo_abreviation'])
			&& isset($valeurs['amo_description'])
			&& isset($valeurs['amo_ce_parent'])) {
			$this->chargerModele('OntologieModele');
			$this->OntologieModele->ajouterNouvelleListeOntologie($valeurs);
		} else  {
			return $this->afficherFormulaireAjoutListeOntologie($valeurs);
		}
		
		return $this->chargerListeOntologie($valeurs['amo_ce_parent']);
	}

	/**
	 * Affiche le formulaire d'ajout ou de modification de liste ontologie
	 * @param Array $valeurs les valeurs à modifier
	 * @return String la vue contenant liste des liste, ou le formulaire de modification si erreur
	 */
	public function modifierListeOntologie($valeurs) {

		if(isset($valeurs['amo_nom']) &&isset($valeurs['amo_abreviation']) && isset($valeurs['amo_description'])) {
			$this->chargerModele('OntologieModele');
			$this->OntologieModele->modifierListeOntologie($valeurs);
		} else  {
			// TODO: afficher une erreur si la modification n'a pas fonctionné
			return $this->afficherFormulaireListeOntologie($valeurs, true);
		}
		
		if($valeurs['amo_id_ontologie'] != 0) {
			return $this->chargerListeOntologie($valeurs['amo_ce_parent']);
		} else {	
			return $this->chargerListeListe();
		}
	}

	/**
	 * Supprime une liste d'ontologie
	 * @param int $id_ontologie l'identifant de la liste à supprimer
	 * @return string la vue contenant la liste des listes
	 */
	public function supprimerListeOntologie($id_ontologie) {
		
		$id_ontologie_parent = 0;

		if(trim($id_ontologie) != '') {
			$this->chargerModele('OntologieModele');
			$infos_ontologie = $this->OntologieModele->chargerInformationsOntologie($id_ontologie);
			
			$id_ontologie_parent = $infos_ontologie['amo_ce_parent'];
			
			$this->OntologieModele->supprimerListeOntologie($id_ontologie);
		} else  {
			// TODO: afficher une erreur si la suppression n'a pas fonctionné
			return $this->chargerListeListe();
		}
		
		return $this->chargerListeOntologie($id_ontologie_parent);
	}
}
?>