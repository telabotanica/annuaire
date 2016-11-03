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
class ExecuteurLien extends JRestService {

	public function getElement($uid){
		if (isset($uid[0])) {
			$lien_code = $uid[0];
			$lien = base64_decode(str_replace('_', '/', $lien_code));
		} else {
			return;
		}

	    if (!isset($uid[1])) {
	    	$retour_ajax = true;
	    } else {
	    	$adresse_retour = base64_decode(str_replace('_', '/', $uid[1]));
	    }

	    $requete = file_get_contents($lien);

		if ($retour_ajax) {
			if ($requete) {
				$resultat = 'ok';
			} else {
				$resultat = false;
			}
			$this->envoyer($resultat);
		} else {
			header('Location: http://'.$adresse_retour);
			exit;
		}
	}
}
?>