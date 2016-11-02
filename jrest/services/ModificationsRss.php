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
class ModificationsRss extends JRestService {

	public function getElement($uid){
		$id_annuaire = (isset($uid[0])) ? $uid[0] : Config::get('annuaire_defaut');
		
		$this->authentifier();		

		$controleur = new RSSControleur();
		$modifications = $controleur->obtenirDernieresModificationsProfil($id_annuaire);

		$this->envoyer($modifications, 'text/xml',Config::get('sortie_encodage'), false);
	}
}
?>