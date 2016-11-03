<?php
// declare(encoding='UTF-8');
/**
 * Service 
 *
 * @category	php 5.2
 * @package		Annuaire::Services
 * @author		Aurélien PERONNET <aurelien@tela-botanica.org>
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version		$Id$
 */
class StatistiquesAnnuaire extends JRestService {

	public function getElement($uid){
		if (!isset($uid[0])) {
			$id_annuaire = $uid[0];
		} else {
			$id_annuaire = Config::get('annuaire_defaut');
		}

		if (isset($uid[1])) {
			$type_stat = $uid[1];
		} else {
			$type_stat = '';
		}

		$controleur = new StatistiqueControleur();

		switch($type_stat) {
			case 'annee' :
				$annee = isset($uid[2]) ? $uid[2] : null;
				$graph = $controleur->obtenirStatistiquesPourAnnee($id_annuaire, $annee);
			break;
			case 'annees' :
				$graph = $controleur->obtenirStatistiquesParAnnees($id_annuaire);
				break;
			case 'continents' :
				$graph = $controleur->obtenirStatistiquesInscritsParContinents($id_annuaire);
				break;
			case 'europe' :
				$graph = $controleur->obtenirStatistiquesInscritsEurope($id_annuaire);
				break;
			case 'modification' :
				$graph = $controleur->obtenirStatistiquesModificationsProfil($id_annuaire);
				break;
			default:
				$graph = $controleur->obtenirStatistiquesParCritere($id_annuaire,$type_stat, '');
		}
		
		// Envoi d'une image png
		header("Content-type: image/png charset=utf-8\n\n");
		imagepng($graph);
	}
}
?>