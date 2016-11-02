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
class Gestion extends JRestService {

	public function getElement($uid){
	    $id_utilisateur = $uid[0];
	    $mail_utilisateur = $uid[1];

	    if (isset($uid[2])) {
	    	$id_annuaire = $uid[2];
	    } else {
	    	$id_annuaire = Config::get('annuaire_defaut');
	    }

	    $controleur = new LettreControleur();
		$est_abonne	= $controleur->estAbonneLettreActualite($id_annuaire, $id_utilisateur);

		$resume['titre'] = 'Lettre d\'actualit&eacute;';

		if ($est_abonne == '1') {
			$message = "Vous &ecirc;tes abonn&eacute; &agrave; la lettre d'actualit&eacute;";
			$intitule_lien = 'Se desinscrire';
		} else {
			$message = "Vous n'&ecirc;tes pas abonn&eacute; &agrave; la lettre d'actualit&eacute;";
			$intitule_lien = "S'inscrire";
		}
		
		$base_url_application = $controleur->getUrlBaseComplete();
		
		$cible_lien_desinscrire = $base_url_application.'/jrest/GestionLettreActu/'.$id_utilisateur.DS.$mail_utilisateur.DS.$id_annuaire;
		$cible_lien = $base_url_application.'/jrest/GestionLettreActu/'.$id_utilisateur.DS.$mail_utilisateur.DS.$id_annuaire;
		$resume_item = array(
			'element' => $message, 
			'intitule_lien' => $intitule_lien, 
			'lien_desinscrire' => $cible_lien_desinscrire, 
			'lien' => $cible_lien);
		$resume['elements'][] = $resume_item;

		$this->envoyer($resume);
	}
}
?>