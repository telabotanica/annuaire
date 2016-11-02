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

class StatistiqueModele extends Modele {

	public function obtenirInscriptionsParDate($id_annuaire, $annee) {

		$requete_annee_inscrit = 'SELECT COUNT(*) FROM annuaire_tela '.
								' WHERE id_annuaire '.$this->proteger($id_annuaire);

	}
	
	public function obtenirIdDernieresModificationsProfil($id_annuaire, $limite = 10) {
		
		$this->obtenirDerniersEvenementsStatistiques($id_annuaire, 'modification', $limite);
		
	}
	
	public function obtenirDerniersEvenementsStatistique($id_annuaire, $type, $limite = 10) {
		
		$requete_derniers_evenements = 'SELECT at_ressource as id_utilisateur, at_action as evenement, at_valeur as date_evenement '.
											'FROM annu_triples '.
											'WHERE at_ce_annuaire = '.$this->proteger($id_annuaire).' '.
											'AND at_action = '.$this->proteger($type).' '.
											'ORDER BY at_valeur DESC ';
		
		if($limite != 0) {
			$requete_derniers_evenements .= 'LIMIT 0,'.$limite;
		}

		
		$resultat_derniers_evenements = $this->requeteTous($requete_derniers_evenements);
		
		return $resultat_derniers_evenements;
	}
	
	public function obtenirEvenementsDansIntervalle($id_annuaire, $type, $date_debut, $date_fin) {
		
		$requete_nb_modif_intervalle = 'SELECT COUNT(*) as nb '.
										'FROM annu_triples '.
										'WHERE at_ce_annuaire = '.$this->proteger($id_annuaire).' '.
										'AND at_action = '.$this->proteger($type).' '.
										'AND at_valeur >= "'.date('Y-m-d H:i:s', $date_debut).'" '.
										'AND at_valeur < "'.date('Y-m-d H:i:s', $date_fin).'" ';
		
		$resultat_nb_modif_intervalle = $this->requeteUn($requete_nb_modif_intervalle);
		
		if(!$resultat_nb_modif_intervalle) {
			return 0;
		}

		return $resultat_nb_modif_intervalle['nb'];
	}
	
	public function ajouterEvenementStatistique($id_annuaire, $id_utilisateur, $type) {
		
		$date_courante = AppControleur::genererDateCouranteFormatMySql();
		
		$requete_insertion_evenenement = 'INSERT INTO annu_triples (at_ce_annuaire, at_ressource, at_action, at_valeur) '.
							 'VALUES ('.$this->proteger($id_annuaire).', '.$this->proteger($id_utilisateur).', '.$this->proteger($type).', '.$this->proteger($date_courante).')';
		
		$resultat_insertion_evenement = $this->requete($requete_insertion_evenenement);
		
		return $resultat_insertion_evenement;
		
	}

}
?>