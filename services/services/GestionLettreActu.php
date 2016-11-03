<?php
// declare(encoding='UTF-8');
/**
 * Service de gestion de la lettre d'actualité
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
class GestionLettreActu extends JRestService {

	public function getElement($uid){
		// TODO : rajouter controle d'accès !

	    $id_utilisateur = $uid[0];
	    $mail_utilisateur = $uid[1];

	    if (isset($uid[2])) {
	    	$id_annuaire = $uid[2];
	    } else {
	    	$id_annuaire = Config::get('annuaire_defaut');
	    }

	    $controleur = new LettreControleur();
		$est_abonne	= $controleur->estAbonneLettreActualite($id_annuaire,$id_utilisateur);
		$changement = $controleur->abonnerDesabonnerLettreActualite($id_annuaire, $id_utilisateur, !$est_abonne);
		echo 'OK';
	}
}
?>