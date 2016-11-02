<?php
// declare(encoding='UTF-8');
/**
 * Modèle d'accès à la base de données des listes
 * d'ontologies
 *
 * PHP Version 5
 *
 * @package   Framework
 * @category  Class
 * @author	aurelien <aurelien@tela-botanica.org>
 * @copyright 2009 Tela-Botanica
 * @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license   http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version   SVN: $$Id: ListeAdmin.php 128 2009-09-02 12:20:55Z aurelien $$
 * @link	  /doc/framework/
 *
 */
class OntologieModele extends Modele {

	private $config = array();

	/**
	 * Charge la liste complète des listes
	 * return array un tableau contenant des objets d'informations sur les listes
	 * @return array un tableau d'objets contenant la liste des listes
	 */
   	public function chargerListeListes() {
		return $this->chargerListeOntologie(0);
	}


	/**
	 * Charge une liste d'ontologie par son identifiant donné en paramètres
	 * @param int l'identifiant de la liste dont on veut charger les élements
	 * @return array un tableau contenant les éléments de la liste
	 */
   public function chargerListeOntologie($identifiant) {
   	
   		$listes = array();
   		if(trim($identifiant) != '') {
   	
			$requete = 	'SELECT * '.
						'FROM  annu_meta_ontologie '.
						'WHERE amo_ce_parent = '.$identifiant.' '.
						'ORDER BY amo_id_ontologie';
			$resultat = $this->requeteTous($requete);
			foreach ($resultat as $ligne) {
				$listes[] = $ligne;
			}
   		}
		
		return $listes;
	}

	/**
	 * Charge les informations concernant une liste d'ontologie
	 * @param int l'identifiant de la liste dont on veut les informations
	 * @return array un tableau contenant les infos sur la liste
	 */
   	public function chargerInformationsOntologie($identifiant) {
   		
   		if(trim($identifiant) == '') {
   			return array();
   		}
   		
   		$requete = 	'SELECT * '.
					'FROM  annu_meta_ontologie '.
					'WHERE amo_id_ontologie = '.$identifiant;
		$resultat = $this->requeteTous($requete);
		$ontologie = array();

		if(!$resultat) {
		} else {
			foreach ($resultat as $ligne) {
				$ontologie = $ligne;
			}
		}
		return $ontologie;
	}

	/**
	 * Ajoute une nouvelle liste d'ontologie
	 * @param array un tableau de valeurs
	 * @return boolean true ou false selon le succès de la requete
	 */
	public function ajouterNouvelleListeOntologie($valeurs) {

		$parent = $this->proteger($valeurs['amo_ce_parent']);
		$nom = $this->proteger($valeurs['amo_nom']);
		$abreviation = $this->proteger($valeurs['amo_abreviation']);
		$description = $this->proteger($valeurs['amo_description']);

		$requete = 'INSERT INTO annu_meta_ontologie '.
					'(amo_ce_parent, amo_nom, amo_abreviation, amo_description) '.
					'VALUES ('.$parent.', '.$nom.','.$abreviation.','.$description.')';

		return $this->requete($requete);
	}

	/**
	 * Modifie une liste d'ontologie
	 * @param array un tableau de valeurs
	 * @return boolean true ou false selon le succès de la requete
	 */
	public function modifierListeOntologie($valeurs) {

		$id = $this->proteger($valeurs['amo_id_ontologie']);
		$nom = $this->proteger($valeurs['amo_nom']);
		$abreviation = $this->proteger($valeurs['amo_abreviation']);
		$description = $this->proteger($valeurs['amo_description']);

		$requete = 'UPDATE annu_meta_ontologie '.
					'SET '.
					'amo_nom='.$nom.', '.
					'amo_abreviation='.$abreviation.', '.
					'amo_description='.$description.' '.
					'WHERE amo_id_ontologie ='.$id;

		return $this->requete($requete);
	}

	/**
	 * Supprime une liste d'ontologie et toutes ses valeurs filles
	 * @param array un identifiant de liste
	 * @return boolean true ou false selon le succès de la requete
	 */
	public function supprimerListeOntologie($id) {

		$id = $this->proteger($id);

		$requete_suppression_liste = 'DELETE FROM annu_meta_ontologie '.
					'WHERE amo_id_ontologie ='.$id;

		$requete_suppression_fils = 'DELETE FROM annu_meta_ontologie '.
					'WHERE amo_ce_parent ='.$id;

		return ($this->requete($requete_suppression_liste) && $this->requete($requete_suppression_fils));

	}

}
?>