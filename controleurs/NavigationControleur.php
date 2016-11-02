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

class NavigationControleur extends AppControleur {
	
	public function afficherContenuMenu($id_annuaire, $admin = false) {
				
		$donnees['id_annuaire'] = $id_annuaire; 
		
		if($admin) {
			$menu = $this->getVue(Config::get('dossier_squelettes_navigation').'menu_admin', $donnees);
		} else {
			$menu = $this->getVue(Config::get('dossier_squelettes_navigation').'menu', $donnees);
		}
		
		return $menu;
	}
	
	public function afficherBandeauNavigationUtilisateur($id_annuaire, $id_utilisateur = null, $page = 'fiche') {
		
		if($id_utilisateur == null) {
			$id_utilisateur = Registre::getInstance()->get('identification_id');
		}
				
		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['id_utilisateur'] = $id_utilisateur;
		$donnees['page'] = $page;
		
		$navigation = $this->getVue(Config::get('dossier_squelettes_navigation').'bandeau', $donnees);
		
		return $navigation;
		
	}
	
	public function afficherBandeauNavigationCartographie($donnees) {
		
		$navigation = $this->getVue(Config::get('dossier_squelettes_navigation').'chemin_cartographie', $donnees);
		
		return $navigation;
		
	}
}
?>