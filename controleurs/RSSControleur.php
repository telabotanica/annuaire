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

class RssControleur extends AppControleur {

	public function obtenirDerniersInscritsRSS($id_annuaire = 1, $admin = false) {

		$annuaire_controleur = new AnnuaireControleur();
		
		$tableau_valeurs = $annuaire_controleur->obtenirTableauDerniersInscrits($id_annuaire);
		
		$donnees['derniers_inscrits'] = $tableau_valeurs;
		$donnees['id_annuaire'] = $id_annuaire;
		
		if($admin) {
			$retour_rss = $this->getVue(Config::get('dossier_squelettes_rss').'derniers_inscrits_admin',$donnees);
		} else {
			$retour_rss = $this->getVue(Config::get('dossier_squelettes_rss').'derniers_inscrits',$donnees);
		}

		return $retour_rss;
	}
	
	public function obtenirDernieresModificationsProfil($id_annuaire = 1, $limite = 10) {
		
		$stat_controleur = new StatistiqueControleur();
		$tableau_id_dernieres_modifs = $stat_controleur->obtenirDerniersEvenementsStatistique($id_annuaire, 'modification', $limite);
		
		$dernieres_modif = array();
		
		foreach($tableau_id_dernieres_modifs as $modif) {
			$id_utilisateur = $modif['id_utilisateur'];
			$date_modif = $modif['date_evenement'];
			
			$id_infos_date = array('id_utilisateur' => $id_utilisateur,
									'informations' => $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur),
									'date_evenement' => AppControleur::formaterDateMysqlVersDateAnnuaire($date_modif));
			$dernieres_modif[] = $id_infos_date;
		}
		
		$donnees['dernieres_modifications'] = $dernieres_modif;
		$donnees['id_annuaire'] = $id_annuaire;
		
		$retour_rss = $this->getVue(Config::get('dossier_squelettes_rss').'dernieres_modifications',$donnees);
		
		return $retour_rss; 
	}

}
?>