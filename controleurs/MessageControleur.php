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

Class MessageControleur extends AppControleur {

	/**
	 * Les mails doivent ils être modérés ?
	 */
	private $moderer_mail = false;

	/**
	 * Doit on envoyer une copie des message au modérateur
	 */
	private $moderation_copie = false;

	/**
	 * Le ou les mails des modérateurs, si ce sont plusieurs mails,
	 * ils doivent être séparés par des virgules
	 */
	private $mail_moderateur = '';

	/**
	 * Nombre de destinataires au dessus duquel on modère les mails
	 */
	private $seuil_moderation = 10;

	/**
	 * Adresse mail de l'expéditeur à partir laquelle sont envoyée les mails de modération
	 */
	private $adresse_mail_annuaire = '';

	/**
	 * Tableau recapitulatif de la derniere recherche effectuée pour envoyer un message
	 */
	private $criteres_recherche_effectuee = null;
	
	/**
	 * Définit si les messages doivent être traités immédiatement (au risque de faire planter l'appli
	 * en cas de trop grand nombre de destinataires ou bien si un script "cronné" les traitera
	 */
	private $traitement_messages_differe = false;

	 /**
	  *
	  * Constructeur sans paramètres
	 */
	public function MessageControleur() {
		$this->__construct();

		// doit on modérer ?
		if (Config::get('moderer_mail') != null) {
			$this->moderer_mail = Config::get('moderer_mail');
		}

		// doit on envoyer des copies des messages ?
		if (Config::get('moderation_copie') != null) {
			$this->moderation_copie = Config::get('moderation_copie');
		}

		// mail du modérateur pour l'envoi de messages au dessus d'un certain seuil
		if ($this->moderer_mail && Config::get('mail_moderateur') != null) {
			$this->mail_moderateur = Config::get('mail_moderateur');
		}

		// seuil de modération
		if ($this->moderer_mail && Config::get('seuil_moderation_messages') != null) {
			$this->seuil_moderation = Config::get('seuil_moderation_messages');
		}

		// adresse d'expéditeur
		if (Config::get('adresse_mail_annuaire') != null) {
			$this->adresse_mail_annuaire = Config::get('adresse_mail_annuaire');
		}
		
		// adresse d'expéditeur
		if (Config::get('traitement_messages_differe') != null) {
			$this->traitement_messages_differe = Config::get('traitement_messages_differe');
		}
	}

/** -------------------Fonctions pour l'inscription et l'oubli de mot de passe  -----------------------*/

	/**
	 * En cas de tentative d'inscription, envoie un mail contenant un lien de confirmation à l'utilisateur
	 * @param string $adresse_mail adresse mail
	 * @param string $nom nom
	 * @param string $prenom prénom
	 * @param string $code_confirmation_inscription code de confirmation à inclure dans le mail
	 *
	 * @return boolean le succès ou l'échec de l'envoi du mail
	 */
	public function envoyerMailConfirmationInscription($adresse_mail, $nom, $prenom, $code_confirmation_inscription) {
		$lien_confirmation_inscription = AppControleur::getUrlConfirmationInscription($code_confirmation_inscription);

		$donnees = array('nom' => $nom, 'prenom' => $prenom, 'lien_confirmation_inscription' => $lien_confirmation_inscription);
		$contenu_mail = $this->getVue(Config::get('dossier_squelettes_mails').'mail_confirmation_inscription',$donnees);

		// en attendant de gérer mieux l'envoi en mode texte
		// remplacement du &amp dans les urls
		$contenu_mail = str_replace('&amp;', '&', $contenu_mail);

	    return $this->envoyerMail(Config::get('adresse_mail_annuaire'),$adresse_mail,'Inscription à l\'annuaire',$contenu_mail);
	}

	 /** En cas d'oubli de mot de passe, régénère le mot de passe et envoie un mail à l'utilisateur
	 * @param int $id_annuaire l'identifiant d'annuaire
	 * @param string $adresse_mail adresse mail
	 * @return boolean le succès ou l'échec de l'envoi du mail
	 */
	public function envoyerMailOubliMdp($id_annuaire,$mail, $nouveau_mdp) {

		$base_url = clone(Registre::getInstance()->get('base_url_application'));

		$url_cette_page = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$url_base = $url_cette_page;
		$base_url = new URL($url_base);
		$base_url->setVariablesRequete(array());
		$base_url->setVariableRequete('m','annuaire_fiche_utilisateur_consultation');
		$base_url->setVariableRequete('id_annuaire',$id_annuaire);

		$donnees['nouveau_mdp'] = $nouveau_mdp;
		$donnees['lien_profil'] = $base_url;

		$contenu_mail = $this->getVue(Config::get('dossier_squelettes_mails').'mail_oubli_mdp',$donnees);

		return $this->envoyerMail(Config::get('adresse_mail_annuaire'),$mail,'Demande de réinitialisation de mot de passe',$contenu_mail);
	}


/** -------------------Fonctions pour la messagerie entre utilisateurs -----------------------*/

	/** Effectue une recherche dans la base de données et envoie un mail à tous les inscrits correspondants,
	 * à l'adresse donnée en paramètre
	 * @param string $expediteur l'expediteur du message
	 * @param mixed $destinataires un string ou un tableau de mails qui contiennent les destinataire
	 * @param string $sujet sujet du mail
	 * @return boolean true ou false suivant le succès ou non de l'envoi
	 */
	public function envoyerMailParRequete($id_annuaire, $expediteur, $criteres, $sujet, $message) {
		if (isset($criteres['exclusive'])) {
			$exclusive = $criteres['exclusive'];
		} else {
			$exclusive = true;
		}

		unset($criteres['id_annuaire']);
		unset($criteres['page']);
		unset($criteres['taille_page']);

		$collecteur = new VerificationControleur();

		$tableau_valeur_collectees = $collecteur->collecterValeursRechercheMoteur($criteres, $this->obtenirChampsMappageAnnuaire($id_annuaire));
		$this->criteres_recherche_effectuee = $collecteur->convertirTableauRechercheVersChaine($id_annuaire, $criteres);

		$valeurs_recherchees = $tableau_valeur_collectees['valeurs_recherchees'];
		$valeurs_mappees = $tableau_valeur_collectees['valeurs_mappees'];
		$valeurs_get = $tableau_valeur_collectees['valeurs_get'];

		if(isset($criteres['tous']) && $criteres['tous'] == 1) {
			$this->chargerModele('AnnuaireModele');
			$resultat_annuaire_mappe = $this->AnnuaireModele->chargerAnnuaireListeInscrits($id_annuaire,0,0);
		} else {

			// on recherche dans les métadonnées
			$this->chargerModele('MetadonneeModele');
			// le résultat est un ensemble d'identifiants
			$resultat_metadonnees = $this->MetadonneeModele->rechercherDansValeurMetadonnees($id_annuaire,$valeurs_recherchees, $exclusive);

			// on recherche les infos dans la table annuaire mappée
			// en incluant ou excluant les id déjà trouvées dans les metadonnées
			// suivant le critères d'exclusivité ou non
			$this->chargerModele('AnnuaireModele');
			$resultat_annuaire_mappe = $this->AnnuaireModele->rechercherInscritDansAnnuaireMappe($id_annuaire,$valeurs_mappees, $resultat_metadonnees, $exclusive, 0, 0);
		}
		$resultat_recherche = $resultat_annuaire_mappe['resultat'];
		$nb_resultats = $resultat_annuaire_mappe['total'];
		$destinataires = $this->aplatirTableauSansPreserverCles($resultat_recherche);

		return $this->envoyerMailDirectOuModere($id_annuaire, $expediteur, $destinataires, $sujet, $message);
	}

	/** Envoie un mail au format texte avec l'adresse de l'utilisateur donné en paramètre,
	 * à l'adresse donnée en paramètre
	 *
	 * ATTENTION : le sujet et le contenu envoyés à cette méthode doivent avoir le même encodage que l'application.
	 *
	 * @param string $expediteur l'expediteur du message
	 * @param mixed $destinataires un string ou un tableau de mails qui contiennent les destinataire
	 * @param string $sujet sujet du mail
	 * @return boolean true ou false suivant le succès ou non de l'envoi
	 */
	public function envoyerMailText($expediteur, $destinataires, $sujet, $message, $adresse_reponse = null) {
		if (!is_array($destinataires)) {
			$destinataires = array($destinataires);
		}

		// Définition d'un mail en texte simple
		$entetes =
			"X-Sender: <http://www.tela-botanica.org>\n".
			"X-Mailer: PHP-ANNUAIRE-TXT\n".
			"X-auth-smtp-user: annuaire@tela-botanica.org \n".
			"X-abuse-contact: annuaire@tela-botanica.org \n".
			"Date: ".date('r')."\n".
			"From: $expediteur\n".
			'Content-Type: text/plain; charset="'.Config::get('appli_encodage').'";'."\n";
		if ($adresse_reponse !== null) {
			$entetes .= 'Reply-To: '.$adresse_reponse."\n";
		}
		$entetes .=	"Content-Transfer-Encoding: 8bit;\n\n";

		$sujetEncode = mb_encode_mimeheader($this->encoderChainePourEnvoiMail($sujet), mb_internal_encoding(), "B", "\n");
		$contenu = $this->encoderChainePourEnvoiMail($message);

		foreach ($destinataires as $destinataire) {
			if (!mail($destinataire, $sujetEndode, $contenu, $entetes)) {
				return false;
			}
		}
		return true;
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
		$message_html = $this->encoderChainePourEnvoiMail($message_html);
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

		$sujetEncode = mb_encode_mimeheader($this->encoderChainePourEnvoiMail($sujet), $encodage, "B", "\n");
		$ok = true;
		foreach ($destinataires as $destinataire) {
			$ok = mail($destinataire, $sujetEncode, $contenu, $entetes);
			if (!$ok) {
				break;
			}
		}
		return $ok;
	}

	/**
	 * ATTENTION : le sujet et le contenu envoyer à cette méthode doivent avoir le même encodage que l'application.
	 */
	public function envoyerMailAvecPieceJointe($expediteur, $destinataires, $sujet, $message, $piece_jointe = null, $nom_fichier, $type_mime = 'text/plain', $adresse_reponse = null) {
		if (!is_array($destinataires)) {
			$destinataires = array($destinataires);
		}

		$message_antislashe = $this->encoderChainePourEnvoiMail($message);
		$message_texte = $this->filtrerChaine($message);
		$message_html = nl2br($message_antislashe);

		$limite = "_----------=_parties_".md5(uniqid (rand()));
		$limite_partie_message = "_----------=_parties_".md5(uniqid (rand() + 1));

		// Définition d'un mail avec différents type de contenu
		$entetes = "X-Sender: <http://www.tela-botanica.org>\n".
			"X-Mailer: PHP-ANNUAIRE-PJ\n".
			"X-auth-smtp-user: annuaire@tela-botanica.org \n".
			"X-abuse-contact: annuaire@tela-botanica.org \n".
			"Date: ".date('r')."\n".
			"From: $expediteur\n".
			'MIME-Version: 1.0' . "\n";
		if ($adresse_reponse !== null) {
			$entetes .= 'Reply-To: '.$adresse_reponse.$eol;
		}
		// Définition d'un type de contenu mixed (mail (texte + html) + piece jointe)
		$entetes .= "Content-Type: multipart/mixed; boundary=\"$limite\";\n\n";

		// Première sous partie : contenu du mail
		$contenu = "\n".
			"--$limite\n".
			// Définition d'un type de contenu alternatif pour l'envoi en html et texte
			"Content-Type: multipart/alternative; boundary=\"$limite_partie_message\";\n".
			// Version texte
			"\n".
			"--$limite_partie_message\n".
			"Content-Type: text/plain; charset=\"".Config::get('appli_encodage')."\";\n".
			"Content-Transfer-Encoding: 8bit;\n".
			"\n".
			"$message_texte\n".
			// Version html
			"--$limite_partie_message\n".
			"Content-Type: text/html; charset=\"".Config::get('appli_encodage')."\";\n".
			"Content-Transfer-Encoding: 8bit;\n".
			"\n".
			$message_html."\n".
			"\n".
			"--$limite_partie_message--\n".
			"--$limite\n";

		// Seconde sous partie : pièce jointe
		if ($piece_jointe != null) {
			$attachment = chunk_split(base64_encode($piece_jointe));

			$contenu .= "Content-Type: $type_mime; name=\"$nom_fichier\"\n".
				"Content-Transfer-Encoding: base64\n".
				"Content-Disposition: attachment; filename=\"$nom_fichier\"\n".
				"X-Attachment-Id: ".md5($attachment)."\n\n".
				"$attachment\n".
				"--$limite--\n";
		}

		$sujetEncode = mb_encode_mimeheader($sujet, mb_internal_encoding(), "B", "\n");
		foreach ($destinataires as $destinataire) {
			if (!mail($destinataire, $sujetEncode, $contenu, $entetes)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Avec des l'informations d'expéditions données en paramètres, décide si un mail peut être envoyé directement
	 * ou bien s'il doit être stocké et soumis à modération
	 * @param int $id_annuaire l'identifiant de l'annuaire à utiliser
	 * @param string $expediteur l'expéditeur du mail
	 * @param array $destinataires les destinataires du mail
	 * @param string $sujet sujet du message
	 * @param string $message corps du message
	 * @param string $donnees_sup tableau d'informations supplémentaires à joindre au message
	 */
	public function envoyerMailDirectOuModere($id_annuaire, $expediteur, $destinataires, $sujet = '', $message = '') {
		$donnees['erreurs'] = false;

		if (!Registre::getInstance()->get('est_admin')) {
			$donnees['contenu_message'] = $this->filtrerChaine($message);
		} else {
			$donnees['contenu_message'] = nl2br($message);
		}
		$sujet = $this->filtrerChaine($sujet);

		if (count($destinataires) == 0) {
			$donnees['erreurs'] = true;
			$donnees['pas_de_destinataire'] = true;
		}

		if (trim($sujet) == '') {
			$donnees['erreurs'] = true;
			$donnees['pas_de_sujet'] = true;
		} else {
			$donnees['sujet_message'] = $sujet;
		}

		if (trim($message) == '') {
			$donnees['erreurs'] = true;
			$donnees['pas_de_message'] = true;
		}

		if (!$donnees['erreurs'])  {
			$template_mail = 'mail_messagerie';

			if (Registre::getInstance()->get('est_admin')) {
				$template_mail = 'mail_messagerie_admin';
			}

			$destinataires_mail = $this->obtenirMailParTableauId($id_annuaire, $destinataires);
			$message = $this->getVue(Config::get('dossier_squelettes_mails').$template_mail,$donnees);

			// si on modere les mails et s'il y a trop de destinataires
			if ($this->moderer_mail && count($destinataires_mail) >= $this->seuil_moderation) {
				$stockage_mail = $this->stockerMailPourModeration($expediteur, $destinataires_mail, $sujet, $message);
				$donnees['moderation'] = true;

				if (!$stockage_mail) {
					$donnees['erreurs'] = true;
				}
			} else {
				// sinon, envoi direct
				$envoi_mail_direct = $this->envoyerMail($expediteur, $destinataires_mail, $sujet, $message);

				if ($this->moderation_copie) {
					$this->envoyerCopieMessageAuModerateur($id_annuaire, $expediteur, $sujet, $destinataires_mail, $message);
				}

				if (!$envoi_mail_direct) {
					$donnees['erreurs'] = true;
				}

				$donnees['moderation'] = false;
			}
		}

		$resultat = $this->getVue(Config::get('dossier_squelettes_annuaires').'message_envoi_confirmation',$donnees);

		return $resultat;
	}

	public function obtenirMailParTableauId($id_annuaire, $destinataires) {
		// on remplace les identifiants par leurs destinataires
		$this->chargerModele('AnnuaireModele');
		$destinataires_mails = $this->AnnuaireModele->obtenirMailParTableauId($id_annuaire, $destinataires);

		return $destinataires_mails;
	}

	private function envoyerCopieMessageAuModerateur($id_annuaire, $expediteur, $sujet, $destinataires, $message) {
		$donnees['expediteur_message'] = $expediteur;
		$donnees['sujet_message'] = $sujet;
		$donnees['contenu_message'] = $message;

		if (is_array($destinataires)) {
			$destinataires = implode(', ', $destinataires);
		}

		$donnees['destinataires_message'] = $destinataires;
		if ($this->criteres_recherche_effectuee != null) {
			$donnees['criteres'] = $this->criteres_recherche_effectuee;
		}

		$contenu_mail_copie = $this->getVue(Config::get('dossier_squelettes_mails').'mail_moderation_copie',$donnees);

		return $this->envoyerMail($this->adresse_mail_annuaire, $this->mail_moderateur, 'Un message a été envoyé à travers l\'annuaire', $contenu_mail_copie);
	}

	/**
	 * Retrouve les informations d'un mail en attente de modération et envoie le mail
	 * @param string $code_confirmation le code associé au données en attente
	 */
	public function envoyerMailModere($code_confirmation) {
		// chargement des données temporaire
		$message_modele = $this->getModele('DonneeTemporaireModele');
		$mail_a_moderer = $message_modele->chargerDonneeTemporaire($code_confirmation);
		
		if ($mail_a_moderer) {	
			if($this->traitement_messages_differe) {
				// envoi différé à travers un script tournant en permanence
				$mise_en_traitement = $message_modele->mettreDonneeTemporaireATraiter($code_confirmation);
				$donnees = ($mise_en_traitement) ? array('mise_en_traitement_reussie' => true) : array('mise_en_traitement_echouee' => true);
			} else {
				// envoi classique (immédiat)
				$resultat_envoi = $this->envoyerMail($mail_a_moderer['expediteur'],
					$mail_a_moderer['destinataires'],
					$mail_a_moderer['sujet'],
					$mail_a_moderer['message']);
	
				$donnees =  ($resultat_envoi) ? array('envoi_reussi' => true) : array('envoi_echoue' => true);
				$message_modele->supprimerDonneeTemporaire($code_confirmation);
			}
		} else {
			$donnees = array('message_inexistant' => true);
		}

		$resultat = $this->getVue(Config::get('dossier_squelettes_annuaires').'message_moderation_confirmation',$donnees);
		return $resultat;
	}

	/**
	 * Supprime un mail en attente de modération grâce au code donné en paramètre
	 * @param string $code_confirmation le code associé au données en attente
	 */
	public function supprimerMailModere($code_confirmation) {
		$message_modele = $this->getModele('DonneeTemporaireModele');
		$message_modele->supprimerDonneeTemporaire($code_confirmation);
		$donnees = array('message_supprime' => true);
		$resultat = $this->getVue(Config::get('dossier_squelettes_annuaires').'message_moderation_confirmation',$donnees);
		return $resultat;
	}

	/**
	 * Stocke un mail dans la base des données temporaires et envoie un mail au modérateur
	 * @param string $expediteur l'expéditeur du mail
	 * @param array $destinataires les destinataires du mail
	 * @param string $sujet sujet du message
	 * @param string $message corps du message
	 */
	private function stockerMailPourModeration($expediteur ,$destinataires, $sujet, $message) {
		$mail = array('expediteur' => $expediteur,
			'destinataires' => $destinataires,
			'sujet' => $sujet,
			'message' => $message);

		$message_modele = $this->getModele('DonneeTemporaireModele');
		$id_stockage = $message_modele->stockerDonneeTemporaire($mail, true);

		if ($id_stockage) {
			$this->envoyerMailModeration($id_stockage, $expediteur ,$destinataires, $sujet , $message);
			return true;
		}

		return false;

	}

	/**
	 * Envoie un mail au modérateur contenant les liens pour, au choix, refuser ou bien accepter l'envoi du mail
	 * @param int $id_mail_a_moderer identifiant du mail à modérer (dans la table des données temporaires)
	 * @param string $sujet_message_a_moderer sujet du message
	 * @param string $message_a_moderer corps du message
	 */
	private function envoyerMailModeration($id_mail_a_moderer, $expediteur, $destinataires, $sujet_message_a_moderer, $message_a_moderer) {
		$url_cette_page = $this->getUrlCettePage();
		$url_base = $url_cette_page;

		$base_url = new URL($url_base);

		$base_url->setVariablesRequete(array());

		$donnees = array();

		$base_url->setVariableRequete('id',$id_mail_a_moderer);

		$lien_accepter_mail = clone($base_url);
		$lien_refuser_mail = clone($base_url);

		$lien_accepter_mail->setVariableRequete('m','message_moderation_confirmation');
		$lien_refuser_mail->setVariableRequete('m','message_moderation_suppression');

		$donnees['lien_accepter_mail'] = $lien_accepter_mail;
		$donnees['lien_refuser_mail'] = $lien_refuser_mail;
		$donnees['expediteur_message'] = $expediteur;
		$donnees['sujet_message'] = $sujet_message_a_moderer;
		$donnees['contenu_message'] = $message_a_moderer;

		if (is_array($destinataires)) {
			$destinataires = implode(', ', $destinataires);
		}
		$donnees['destinataires_message'] = $destinataires;
		if ($this->criteres_recherche_effectuee != null) {
			$donnees['criteres'] = $this->criteres_recherche_effectuee;
		}

		$contenu_mail = $this->getVue(Config::get('dossier_squelettes_mails').'mail_moderation_message',$donnees);

		return $this->envoyerMail($this->adresse_mail_annuaire, $this->mail_moderateur, 'Un message est en attente de modération', $contenu_mail);
	}


	public function afficherMailsEnAttenteModeration() {

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

	private function encoderChainePourEnvoiMail($chaine) {
		// TODO: fonction vide, à scinder en deux fonctions une pour les admins et l'autres
		// pour les utilisateurs normaux (genre filtrer html ou non)
		return $chaine;
	}
}