<?php
// declare(encoding='UTF-8');
/**
 * Service d'envoie de courriel.
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
class EnvoiMessage extends JRestService {

	public function getElement($uid){
		$identificateur = new IdentificationControleur();
		$login = $identificateur->obtenirLoginUtilisateurParCookie();

		$identification = $login;

		if (!$identification || trim($identification) == '') {
			print 'false';
		} else {
			$id_annuaire = Config::get('annuaire_defaut');
			$contenu_message = $_GET['contenu_message'];
			$sujet_message = $_GET['sujet_message'];
			$destinataire = $_GET['destinataire'];
			$redirect = $_GET['redirect'];

			$messagerie = new MessageControleur();
			// Remplacement les identifiants par leurs destinataires
			$destinataire_mail = $messagerie->obtenirMailParTableauId($id_annuaire, array($destinataire));
			if (empty($destinataire_mail)) {
				print 'false';
			} else {
				$destinataire_mail = $destinataire_mail[0];

				$retour = $messagerie->envoyerMail($identification, $destinataire_mail, $sujet_message, $contenu_message);
				if ($retour) {
					if ($redirect != null && $redirect != '') {
						header('Location: '.'http://'.$redirect);
						exit;
					} else {
						print 'OK';
					}
				} else {
					print 'false';
				}
			}
		}
	}
}
?>