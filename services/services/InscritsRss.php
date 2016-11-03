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
class InscritsRss extends JRestService {

	public function getElement($uid){
		$mail_utilisateur = $uid[0];
		$admin = (isset($uid[1])) ? $uid[1] : false;
		$id_annuaire = Config::get('annuaire_defaut');
		
		if ($admin) {
			$this->authentifier();		
		}

		$controleur = new RSSControleur();
		$inscrits = $controleur->obtenirDerniersInscritsRSS($id_annuaire, $admin);

		$this->envoyer($inscrits, 'text/xml',Config::get('sortie_encodage'), false);
	}
}
?>