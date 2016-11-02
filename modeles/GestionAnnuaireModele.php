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
class GestionAnnuaireModele extends Modele {
	
	public function verifierPresenceTable($bdd, $nom_table) {
		
		if(!$bdd || !$nom_table) {
			return false;
		}
		
		$requete_presence_table = 'SELECT * FROM information_schema.tables
		WHERE TABLE_SCHEMA = '.$this->proteger($bdd).' AND TABLE_NAME = '.$this->proteger($nom_table);
		
		$presence_table = $this->requeteUn($requete_presence_table);
		
		return $presence_table;
	}
	
	public function verifierPresenceChamps($bdd, $nom_table, $champs_a_verifier) {
		
		$tableau_champs_table = $this->obtenirListeNomsChampsAnnuaireParBddNomTable($bdd, $nom_table);

		foreach($champs_a_verifier as $champ) {
			if(!in_array($champ, $tableau_champs_table)) {
				return false;
			}
		}
		
		return $resultat;
	}
	
	public function obtenirListeNomsChampsAnnuaireParBddNomTable($bdd, $nom_table) {
		
		if(!$bdd || !$nom_table) {
			return false;
		}

		$requete = 'DESCRIBE '.$bdd.'.'.$nom_table;
		
		$resultat = $this->requeteTous($requete);
		
		if(!$resultat) {
			return false;
		}
		
		$tableau_champs_table = array();;
		
		foreach($resultat as $champ_table) {
			$tableau_champs_table[] = $champ_table['Field'];
		}
		
		return $tableau_champs_table;
	}
	
	public function obtenirListeNomsChampsAnnuaireParIdAnnuaire($id_annuaire) {
		
		
		$requete_selection_bdd_table =  'SELECT aa_bdd, aa_table FROM annu_annuaire '.
					' WHERE aa_id_annuaire = '.$this->proteger($id_annuaire);
		
		$resultat_selection_bdd_table = $this->requeteUn($requete_selection_bdd_table);
			
		if(!$resultat_selection_bdd_table) {
			return array();
		}
		
		return $this->obtenirListeNomsChampsAnnuaireParBddNomTable($resultat_selection_bdd_table['aa_bdd'], $resultat_selection_bdd_table['aa_table']);
	}
	
	public function ajouterAnnuaire($informations) {
		
		if(!$informations) {
			return false;
		}
								
		$valeurs_prot = array_map(array($this,'proteger'),$informations);
		
		$valeurs = implode(',',$valeurs_prot);
		$champs = implode(',',array_keys($informations));

		$requete_insertion_annuaire =  'INSERT INTO annu_annuaire '.
					'('.$champs.') '.
					'VALUES ('.$valeurs.')';
		
		$resultat_insertion_annuaire = $this->requete($requete_insertion_annuaire);
		
		$id_annuaire = false;
		
		if($resultat_insertion_annuaire) {
			
			$requete_selection_annuaire =  'SELECT aa_id_annuaire FROM annu_annuaire '.
					' WHERE aa_code = '.$this->proteger($informations['aa_code']);
			
			$resultat_selection_annuaire = $this->requeteUn($requete_selection_annuaire);
			
			if($resultat_selection_annuaire) {
				$id_annuaire = $resultat_selection_annuaire['aa_id_annuaire'];
			}
		}
		
		return $id_annuaire;
	}
	
	public function creerTableAnnuaire($informations_table, $informations_champs) {
				
		$nom_bdd = $informations_table['aa_bdd'];
		$nom_table = $informations_table['aa_table'];
		
		$champ_id = $informations_champs['aa_champ_id'];
		$champ_nom = $informations_champs['aa_champ_nom'];
		$champ_mail = $informations_champs['aa_champ_mail'];
		$champ_mot_de_passe = $informations_champs['aa_champ_pass'];
		
		$requete_creation_table = 'CREATE TABLE '.$nom_bdd.'.'.$nom_table.' '.
									'('.$champ_id.' INT NOT NULL AUTO_INCREMENT PRIMARY KEY,'. 
									$champ_nom.' VARCHAR(255) NOT NULL, '. 
									$champ_mail.' VARCHAR(255) NOT NULL, '. 
									$champ_mot_de_passe.' VARCHAR(255) NOT NULL)';
		
		return $this->requete($requete_creation_table);
		
	}
	
	/**
	 * Charge la liste complète des champs d'un annuaire
	 * @param int $identifiant l'identifiant de l'annuaire demandé
	 * @param boolean $charger_liste_champs indique si l'on doit ou non charger la liste des noms des champs
	 * @return array un tableau contenant des objets d'informations sur les annuaires
	 */
   	public function chargerAnnuaire($identifiant, $charger_liste_champs = true) {

		$requete = 	'SELECT * '.
					'FROM  annu_annuaire '.
					'WHERE aa_id_annuaire = '.$identifiant.' ';
		$resultat = $this->requeteTous($requete);
		$annuaire = array();
		foreach ($resultat as $ligne) {
			$annuaire['informations'] = $ligne;
		}

		if($charger_liste_champs) {
			$requete = 'DESCRIBE '.$annuaire['informations']['aa_bdd'].'.'.$annuaire['informations']['aa_table'];
			$resultat = $this->requeteTous($requete);
			foreach ($resultat as $colonne) {
				$annuaire['colonnes'][] = $colonne;
			}
		}

		return $annuaire;
	}
}
?>