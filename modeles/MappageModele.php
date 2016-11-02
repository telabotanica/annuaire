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
class MappageModele extends Modele {

	private $config = array();

	public function ajouterNouveauMappage($id_annuaire, $nom_champ, $role, $id_metadonnee) {
		
		$requete_insertion = 'INSERT INTO annu_triples (at_ce_annuaire, at_ressource, at_action, at_valeur) '.
							 'VALUES ('.$this->proteger($id_annuaire).', '.$this->proteger($nom_champ).', '.$this->proteger($role).', '.$this->proteger($id_metadonnee).')';
		
		return $this->requete($requete_insertion);
	}
	
	public function modifierMappage($id_annuaire, $nom_champ, $role, $id_metadonnee,$id_triple) {
		
		$requete_modification = 'UPDATE annu_triples SET ' .
							 'at_ce_annuaire = '.$this->proteger($id_annuaire).', '.
							 'at_ressource, = '.$this->proteger($nom_champ).', '.
							 'at_action = '.$this->proteger($role).', '.
							 'at_valeur = '.$this->proteger($id_metadonnee).' '.
							 'WHERE at_id = '.$this->proteger($id_triple);
		
		return $this->requete($requete_modification);
	}

}
?>