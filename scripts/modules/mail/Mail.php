<?php
// Encodage : UTF-8
// +-------------------------------------------------------------------------------------------------------------------+
/**
* Traitement des mails en attente de modération de l'annuaire
*
* Description : classe permettant de gérer l'envoi des mails en attente de modération dans l'annuaire
* Utilisation : php script.php mail
*
//Auteur original :
* @author       Aurélien PERONNET <jpm@tela-botanica.org>
* @copyright	Tela-Botanica 1999-2014
* @licence		GPL v3 & CeCILL v2
* @version		$Id$
*/

class Mail extends Script {
	//TODO: cette classe est en doublon avec du code de l'annuaire
	// une fois passé à la dernière version du framework, il faudrait factoriser ces fonctions 
	// dans une lib commune accessible aux scripts et au code standard
	const STATUT_A_TRAITER = 'a_traiter';
	const STATUT_EN_TRAITEMENT = 'en_traitement';
	const STATUT_EN_ECHEC = 'en_echec';
	
	// Définit le délai au bout du quel on remet des mails en traitement à traiter
	// au format (avec la syntaxe utilisée avec INTERVAL en SQL)
	// http://dev.mysql.com/doc/refman/5.0/fr/date-and-time-functions.html
	const DELAI_MAX_TRAITEMENT = '10 HOUR';
	
	private $modele = null;
	
	public function executer() {	
		$this->bdd = new Bdd();
		// élargissement du timeout car le traitement est long;
		// évite les erreurs 2006 "MySQL has gone away"
		$this->bdd->executer("SET wait_timeout=300");
		
		$cmd = $this->getParametre('a');
		$this->mode_verbeux = $this->getParametre('v');
		
		$retour = array();
		
		switch($cmd) {
			case "tous":
				$retour = $this->traiterMailsEnAttente();
			break;
			// TODO: case supplémentaire pour traiter un mail par son id ?
			// TODO: option "force" pour traiter les mails quel que soit leur statut ?
			default:	
		}
		
		if($this->mode_verbeux) {
			// echo pour que bash capte la sortie et stocke dans le log
			echo 'Identifiants des mails traites : '.implode(',', $retour)."--";
		}
	}
	
	private function traiterMailsEnAttente() {
		// Gaston Lagaffe
		$mails_en_retard = $this->traiterMailsEnRetard();
		$mails_a_traiter = $this->obtenirMailsEnAttente();
		
		$retour = array();
		if(count($mails_a_traiter) > 0 && $this->mettreMailsEnCoursDeTraitement()) {
			foreach($mails_a_traiter as $donnees_brutes_mail) {
				$mail_a_moderer = $this->decoderDonneeTemporaire($donnees_brutes_mail);
				$id_mail = $donnees_brutes_mail['adt_id'];
				
				$resultat_envoi = true;
				$envois_echoues = $this->envoyerMail($mail_a_moderer['expediteur'],
						$mail_a_moderer['destinataires'],
						$mail_a_moderer['sujet'],
						$mail_a_moderer['message']);
				
				if(empty($envois_echoues)) {
					$this->supprimerMailTraite($id_mail);
				} else {
					// TODO: supprimer les destinataires qui ont fonctionné, et mettre à jour 
					// le mail dans les données temporaire avec les destinataires qui restent 
					// pour pouvoir finir de l'envoyer
					$this->avertirModerateurEchecEnvoi($envois_echoues, $mail_a_moderer);
					$resultat_envoi = false;
				}
				
				// TODO: logger également erreur d'envoi ?
				$retour[$id_mail] = $resultat_envoi;
			}
		}
		return $retour;
	}
	
	private function avertirModerateurEchecEnvoi($envois_echoues, $mail_a_moderer) {
		
		$corps_mail_echoue = "L'envoi d'un mail modéré à échoué pour les destinataires suivants (".count($envois_echoues)." au total) : <br />";
		$corps_mail_echoue .= implode(", ", $envois_echoues);
		$corps_mail_echoue .= "<br /><br /><br />";
		$corps_mail_echoue .= "--- <i> Message original ---</i><br />";
		$corps_mail_echoue .= "Expéditeur  : ".$mail_a_moderer['expediteur']."<br />";
		$corps_mail_echoue .= "Sujet  : ".$mail_a_moderer['sujet']."<br />";
		$corps_mail_echoue .= "Message original : ".$mail_a_moderer['message']."<br />";
		
		$sujet = "L'envoi d'un mail modéré a échoué pour un ou plusieurs destinataires";
			
		// TODO: Que faire si l'envoi de mail d'avertissement échoue également ?
		$envoi_avertissement = $this->envoyerMail(Config::get('adresse_mail_annuaire'),
				Config::get('mail_moderateur'),
				$sujet,
				$corps_mail_echoue);
		// echo pour que bash capte la sortie et stocke dans le log
		echo 'Envoi du mail au moderateur pour signaler un echec '."--";
		return $envoi_avertissement;
	}
	
	private function avertirModerateurEchecTraitement($mails_en_echec) {
		
		$ids_mails_en_echec = array();
		foreach($mails_en_echec as $mail_echec) {
			$ids_mails_en_echec[] = $mail_echec['adt_id'];
		}
	
		$corps_mail_mal_traite = "Échec de traitement pour : ".implode(',', $ids_mails_en_echec)." depuis plus de ".self::DELAI_MAX_TRAITEMENT." <br />";
		$sujet = "Un ou plusieurs mails sont en échec de traitement";
			
		$envoi_avertissement = $this->envoyerMail(Config::get('adresse_mail_annuaire'),
				Config::get('mail_moderateur'),
				$sujet,
				$corps_mail_mal_traite);
		
		// echo pour que bash capte la sortie et stocke dans le log
		echo 'Envoi du mail au moderateur pour signaler un traitement en echec depuis trop longtemps '."--";
		return $envoi_avertissement;
	}
	
	private function obtenirMailsEnAttente() {
		$requete = "SELECT * FROM annu_donnees_temp WHERE adt_statut = '".self::STATUT_A_TRAITER."' ";
		$retour = $this->bdd->recupererTous($requete);
		// echo pour que bash capte la sortie et stocke dans le log
		echo 'Il y a '.count($retour).' mails en attente '."--";
		return $retour;
	}
	
	private function mettreMailsEnCoursDeTraitement() {
		$requete = "UPDATE annu_donnees_temp SET adt_statut = '".self::STATUT_EN_TRAITEMENT."', adt_date_debut_traitement = NOW() ".
					"WHERE adt_statut = '".self::STATUT_A_TRAITER."' ";
		$maj = $this->bdd->executer($requete);
		// echo pour que bash capte la sortie et stocke dans le log
		echo $maj.' mails ont été mis en traitement '."--";
		return ($maj !== false);
	}
	
	private function mettreAJourMailMalTraite($id_mail_mal_traite, $mail_mal_traite, $envois_echoues) {	
		// TODO: utiliser cette fonction lors de l'echec de plusieurs destinataires et renvoyer le lien
		// de confirmation
		$mail_mal_traite['destinataires'] = $envois_echoues;
		$mail_mal_traite = $this->encoderDonneeTemporaire($mail_mal_traite);
		
		$requete = "UPDATE annu_donnees_temp ".
					"SET adt_donnees = '".$mail_mal_traite."' ".
					"WHERE adt_id = '".$mail_a_moderer['adt_id']."'";
		
		$maj = $this->bdd->executer($requete);
		return $maj;
	}
	
	private function supprimerMailTraite($id) {
		$requete = "DELETE FROM annu_donnees_temp WHERE adt_statut = '".self::STATUT_EN_TRAITEMENT."' ".
					"AND adt_id = '".$id."'";
		// echo pour que bash capte la sortie et stocke dans le log
		echo'Suppression du mail '.$id.' qui a ete traite '."--";
		$supp = $this->bdd->executer($requete);
		return $supp;
	}
	
	private function supprimerMailsEnCoursDeTraitement() {
		$requete = "DELETE FROM annu_donnees_temp WHERE adt_statut = '".self::STATUT_EN_TRAITEMENT."' ";
		$supp = $this->bdd->executer($requete);
		return $supp;
	}
	
	private function traiterMailsEnRetard() {
		// Les mails a traiter depuis plus de 10 heures sont considérés comme échoués et donc remis à traiter
		// (en cas de plantage du script ou du serveur de mail pendant leur traitement)
		$requete = "UPDATE annu_donnees_temp SET adt_statut = '".self::STATUT_EN_ECHEC."' ".
				"WHERE adt_statut = '".self::STATUT_EN_TRAITEMENT."' ".
				"AND adt_date_debut_traitement < (DATE_SUB(now(), INTERVAL ".self::DELAI_MAX_TRAITEMENT.")) ";
		
		$maj = $this->bdd->executer($requete);
		// echo pour que bash capte la sortie et stocke dans le log
		echo 'Gestion des mails en retard '."--";
		if($maj !== false && $maj != 0) {
			$requete = "SELECT * FROM annu_donnees_temp WHERE adt_statut = '".self::STATUT_EN_ECHEC."' AND adt_date_debut_traitement IS NOT NULL";
			$mails_en_echec = $this->bdd->recupererTous($requete);
			
			// echo pour que bash capte la sortie et stocke dans le log
			echo 'Avertissement, des mails sont en retard : '.count($mails_en_echec)."--";
			$this->avertirModerateurEchecTraitement($mails_en_echec);
			
			// Réinitialisation de la date pour éviter que l'avertissement soit réenvoyé plusieurs fois
			$requete = "UPDATE annu_donnees_temp SET adt_date_debut_traitement = NULL ".
					"WHERE adt_statut = '".self::STATUT_EN_ECHEC."' ";
			$maj = $this->bdd->executer($requete);
		}
		
		return $maj;
	}
	
	private function encoderDonneeTemporaire($donnee) {
		return base64_encode(serialize($donnee));
	}
	
	private function decoderDonneeTemporaire($donnee_encodee) {
		return unserialize(base64_decode($donnee_encodee['adt_donnees']));
	}
	
	/** Envoie un mail avec l'adresse de l'utilisateur donné en paramètre, à l'adresse donnée en paramètre.
	 * ATTENTION : le sujet et le contenu envoyer à cette méthode doivent avoir le même encodage que l'application.
	 *
	 * @param string $expediteur l'expediteur du message
	 * @param mixed $destinataires un string ou un tableau de mails qui contiennent les destinataire
	 * @param string $sujet sujet du mail
	 * @return boolean true ou false suivant le succès ou non de l'envoi
	 */
	public function envoyerMail($expediteur, $destinataires, $sujet, $message_html, $message_texte = '', $adresse_reponse = null) {
		if (!is_array($destinataires)) {
			$destinataires = array($destinataires);
		}
		if ($message_texte == '') {
			$message_texte = $this->filtrerChaine($message_html);
		}
	
		$encodage = Config::get('appli_encodage');
		$limite = "_----------=_parties_".md5(uniqid(rand()));
		$eol = "\n";
	
		$entetes = '';
		// Définition d'un mail en texte simple et html
		// multipart/alternative signifie même contenu de la forme la plus simple à la plus complexe
		$entetes .= "X-Sender: <http://www.tela-botanica.org>".$eol.
		"X-Mailer: PHP-ANNUAIRE-HTML".$eol.
		"X-auth-smtp-user: annuaire@tela-botanica.org ".$eol.
		"X-abuse-contact: annuaire@tela-botanica.org ".$eol.
		'Date: '.date('r').$eol.
		'From: '.$expediteur.$eol.
		'MIME-Version: 1.0'.$eol;
		if ($adresse_reponse !== null) {
			$entetes .= 'Reply-To: '.$adresse_reponse.$eol;
		}
		$entetes .= "Content-Type: multipart/alternative; boundary=\"$limite\";".$eol.$eol;
	
		// message en texte simple
		$contenu = "--$limite".$eol.
		"Content-Type: text/plain; charset=\"$encodage\";".$eol.
		"Content-Transfer-Encoding: 8bit;".$eol.$eol.
		$message_texte.$eol.$eol.
		// le message en html est préféré s'il est lisible
		"--$limite".$eol.
		"Content-Type: text/html; charset=\"$encodage\";".$eol.
		"Content-Transfer-Encoding: 8bit;".$eol.$eol.
		$message_html.$eol.$eol.
		"--$limite--".$eol.$eol;
	
		$sujetEncode = mb_encode_mimeheader($sujet, mb_internal_encoding(), "B", "\n");
		$resultats_envois_echoues = array();
		$ok = true;
		foreach ($destinataires as $destinataire) {
			$ok = mail($destinataire, $sujetEncode, $contenu, $entetes);
			if (!$ok) {
				// echo pour que bash capte la sortie et stocke dans le log
				echo'Echec envoi a '.$destinataire."\n";
				$resultats_envois_echoues[] = $destinataire;
			}
		}
		return $resultats_envois_echoues;
	}
	
	/** Transforme automatiquement le message html en message txt.
	 *
	 * Réalise un strip_tags et avant ça un remplacement des liens sur mesure pour les mettre au format email txt.
	 */
	private function filtrerChaine($messageHtml) {
		$messageTxt = strip_tags($messageHtml);
		if ($messageHtml != $messageTxt) {
			$html = $this->ajouterHrefDansBalise($messageHtml);
			$messageAvecEntites = strip_tags($html);
			// TODO : en précisant l'encodage de l'appli dans html_entity_decode un double encodage UTF-8 se produit...
			$messageTxt = html_entity_decode($messageAvecEntites, ENT_QUOTES);
		}
		return $messageTxt;
	}
	
	/**
	 * Extrait la valeur de l'attribut href des balises HTML de liens (a) et ajoute le lien entre
	 * chevrons (<>) dans le contenu de la balise "a".
	 */
	private function ajouterHrefDansBalise($html) {
		$dom = new DOMDocument;
		$dom->loadHTML($html);
		foreach ($dom->getElementsByTagName('a') as $node) {
			if ($node->hasAttribute( 'href' )) {
				$href = $node->getAttribute('href');
				$node->nodeValue = $node->nodeValue." < $href >";
			}
		}
		$html = $dom->saveHtml();
		return $html;
	}
}
?>