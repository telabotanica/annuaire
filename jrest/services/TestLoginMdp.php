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
class TestLoginMdp extends JRestService {

	public function getElement($uid){
	   	if (!isset($uid[0]) || $uid[0] == '' || !isset($uid[1]) || $uid[1] == '') {
	   		$this->envoyer(false);
	   		return;
	   	}

	   	$mail_utilisateur = $uid[0];
	   	$pass = $uid[1];

	   	// TODO vérifier que le mot de passe est crypté !

	    $id_annuaire = Config::get('annuaire_defaut');

	    $controleur = new AnnuaireControleur();
		$id_match_pass = $controleur->comparerIdentifiantMotDePasse($id_annuaire,$mail_utilisateur, $pass, true, true);

		$this->envoyer($id_match_pass);
	}
}
?>