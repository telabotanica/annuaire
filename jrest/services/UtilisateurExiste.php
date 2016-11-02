<?php
// declare(encoding='UTF-8');
/**
 * Service vérifiant que l'utilisateur dont le courriel est passé en paramètre existe dans l'annuaire. 
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
class UtilisateurExiste extends JRestService {

	public function getElement($uid){
	    $mail_utilisateur = $uid[0];
	    $id_annuaire = Config::get('annuaire_defaut');

	    $controleur = new AnnuaireControleur();
		$existe	= $controleur->UtilisateurExiste($id_annuaire,$mail_utilisateur, true);

		$this->envoyer($existe);
	}
}
?>