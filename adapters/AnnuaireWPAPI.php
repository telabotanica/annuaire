<?php

require_once __DIR__ . '/../AnnuaireAdapter.php';

/**
 * Implémentation de référence de l'Annuaire sur la base de données Wordpress,
 * en utilisant l'API Wordpress, ce qui permet de :
 * - déclencher les hooks lors de l'inscription d'un utilisateur
 * - éviter de dupliquer la config de la base de données
 * - être moins dépendant de la stratégie de stockage WP/BP
 */
class AnnuaireWPAPI extends AnnuaireAdapter {

	public function __construct($config, $SSO) {
		parent::__construct($config, $SSO);

		// inclusion de l'API
		$cheminWordpress = $this->config['adapters']['AnnuaireWPAPI']['chemin_wp'];
		require_once $cheminWordpress . "/wp-load.php";
	}

	// ------------- implémentation de la classe abstraite ---------------------

	/**
	 * Retourne l'id utilisateur WP en fonction du champ "email" du profil
	 */
	public function idParCourriel($courriel) {
		$user = get_user_by('email', $courriel);

		if ($user === false) {
			return false;
		} else {
			return $user->ID;
		}
	}

	/**
	 * Retourne l'adresse email de l'utilisateur WP en fonction de son ID
	 * 
	 * @WARNING gère le cas où l'adresse email de l'utilisateur n'est pas
	 * renseignée, mais où c'est son "login" qui contient son adresse email; ne
	 * devrait pas se produire avec une base d'utilisateurs propre; mécanisme
	 * à supprimer dès que possible
	 */
	public function courrielParId($id) {
		$user = get_user_by('ID', $id);
	
		if ($user === false) {
			return false;
		} else {
			// bulletproof cracra
			$retour = null;
			if (! empty($user->user_email)) {
				$retour = $user->user_email;
			} elseif (strpos($user->user_login, '@') !== false) {
				$retour = $user->user_login;
			}
			return $retour;
		}
	}

	/**
	 * Retourne l'adresse email de l'utilisateur WP en fonction de son login
	 * (colonne "user_login")
	 */
	public function courrielParLogin($login) {
		$user = get_user_by('login', $login);
	
		if ($user === false) {
			return false;
		} else {
			return $user->user_email;
		}
	}

	/**
	 * Retourne la date de dernièr emodification du profil, en se basant sur le
	 * champ last_updated du profil étendu BP, qui est mise à jour même si ce
	 * sont des champs de profil WP qu ont été modifiés
	 * @TODO vérifier si c'est fiable
	 * @TODO vérifier si ça marche même lorsqu'aucun champ de profil étendu BP
	 *		n'est défini (y a des chances que non - mais on ne devrait pas se
	 *		trouver dans ce cas)
	 */
	public function getDateDerniereModifProfil($id) {
		global $wpdb;
		$ddm = $wpdb->get_var("SELECT MAX(last_updated) as date FROM {$wpdb->prefix}bp_xprofile_data WHERE user_id = $id");

		if ($ddm === false) {
			return 0; // @TODO 0 pour rester compatible ou false pour indiquer que /i ?
		} else {
			$ddm = strtotime($ddm);
			return $ddm;
		}
	}

	/**
	 * Retourne true si l'utilisateur ayant l'adresse courriel $courriel a un
	 * mot de passe égal à $mdp, false sinon
	 * 
	 * Gère également la connexion par nom d'utilisateur : si $courriel ne
	 * contient pas le caractère "@", tentera de trouver l'utilisateur ayant
	 * pour "user_login" $courriel
	 * 
	 * Compatible avec les mots de passe hachés en MD5 (ancienne méthode) ou
	 * avec PHPass (nouvelle méthode); met à jour le haché si besoin :
	 * https://developer.wordpress.org/reference/functions/wp_check_password/
	 * 
	 */
	public function identificationCourrielMdp($courriel, $mdp) {
		// détection : courriel ou nom d'utilisateur
		if (strpos($courriel, '@') !== false) {
			$user = get_user_by('email', $courriel);
		} else {
			// tentative de connexion par le nom d'utilisateur
			$user = get_user_by('login', $courriel);
		}
		if ($user) {
			// met à jour le hash si besoin
			return wp_check_password($mdp, $user->data->user_pass, $user->ID);
		} else {
			return false;
		}
	}

	/**
	 * Retourne true si l'utilisateur ayant l'adresse courriel $courriel a un
	 * mot de passe haché (dans la BDD WP) égal à $mdpHaché, false sinon
	 */
	public function identificationCourrielMdpHache($courriel, $mdpHache) {
		$utilisateur = get_user_by('email', $courriel);
		if ($utilisateur) {
			return ($utilisateur->data->user_pass == $mdpHache);
		} else {
			return false;
		}
	}

	/**
	 * Retourne le nombre d'utilisateurs actifs ayant au moins un rôle
	 * Attention, compte aussi les utilisateurs déclarés comme "SPAM"
	 */
	public function nbInscrits() {
		$utilisateurs = count_users();
		return intval($utilisateurs['total_users']);
	}

	/**
	 * Inscrit un utilisateur dans l'annuaire WP - utilisé par le service Auth
	 * pour synchroniser les comptes partenaires, notamment
	 * 
	 * $donneesProfil doit toujours contenir au moins :
	 *  - nom
	 *  - prenom
	 *  - pseudo
	 *  - email
	 *  - mdp (null pour un partenaire)
	 * 
	 * Pour un compte partenaire, il doit contenir également
	 *  - partenaire : code du partenaire (ex: "plantnet", "recolnat")
	 *  - id_partenaire : identifiant de l'utilisateur dans le SI partenaire
	 * 
	 * Si $id est fourni, l'utilisateur portant cet id sera mis à jour
	 */
	public function inscrireUtilisateur($donneesProfil, $id=null) {

		$donnees = array(
			'user_login' => $donneesProfil['pseudo'],
			'user_nicename' => $donneesProfil['pseudo'], // automatiquement slugifié ? sinon, utiliser sanitize_user()
			'nickname' => $donneesProfil['pseudo'],
			'first_name' => $donneesProfil['prenom'],
			'last_name' => $donneesProfil['nom'],
			'user_email' => $donneesProfil['email'],
			'user_registered' => date('Y-m-d H:i:s'),
			'user_pass' => isset($donneesProfil['mdp']) ? $donneesProfil['mdp'] : null
		);
		// pour une mise à jour :
		if ($id !== null) {
			$donnees['ID'] = $id;
		}
		// insertion, avec déclenchement des hooks !
		// l'id est toujours retourné, qu'il soit nouveau ou = au $id précédent
		$id = wp_insert_user($donnees);

		if (is_wp_error($id)) {
			$errorCode = array_key_first( $id->errors );
			$errorMessage = $id->errors[$errorCode][0];
			throw new Exception("Impossible de mettre à jour le compte utilisateur: ".$errorMessage);
		}

		// métadonnées partenaire
		if (!empty($donneesProfil['partenaire'])) {
			update_user_meta($id, 'partenaire', $donneesProfil['partenaire']);
		}
		if (!empty($donneesProfil['id_partenaire'])) {
			update_user_meta($id, 'id_partenaire', $donneesProfil['id_partenaire']);
		}

		return true;
	}

	/**
	 * Retourne tous les rôles disponibles; ici la liste des rôles de WP
	 */
	public function getAllRoles() {
		global $wp_roles;
		$roles = $wp_roles->roles;
		return array_keys($roles);
	}

	protected function infosUtilisateurParId($id) {
		return $this->infosUtilisateur($id);
	}

	/**
	 * Retourne tout ce qu'il y a à savoir sur l'utilisateur, organisé selon les
	 * tables Wordpress / Buddypress :
	 * - champs de la table WP_users (toujours)
	 * - "_roles": liste des rôles (toujours)
	 * - "_meta": métadonnées de la table WP_usermeta (si $usermeta=true)
	 * - "_xprofile": profil étendu des tables WP_bp_xprofile* (si $xprofile=true)
	 * - "_groups": infos de groupes de la table WP_bp_groups_members (si $groups=true)
	 */
	protected function infosUtilisateur($id, $usermeta=true, $xprofile=true, $groups=true) {

		global $wpdb;
		$infos = array();

		// 1) utilisateur
		$utilisateur = get_user_by('id', $id);
		if ($utilisateur === false) {
			throw new Exception("Impossible de trouver l'utilisateur [$id]");
		}
		// le compte est-il activé
		// @TODO trouver un meilleur moyende le vérifer
		$enAttente = $wpdb->get_results("SELECT signup_id "
			. "FROM {$wpdb->prefix}signups "
			. "WHERE active = 0 AND user_email = '{$utilisateur->data->user_email}'"
		);
		if (! empty($enAttente)) {
			throw new Exception("Le compte est en attente d'activation");
		}
		
		// garnir les infos
		$infos = (array) $utilisateur->data;
		// choper l'URL de l'avatar
		$infos['avatar_url'] = html_entity_decode(bp_core_fetch_avatar('html=false&item_id=' . $id));

		// 1bis) rôles SSO (permissions)
		$infos['_roles'] = array_keys((array) $utilisateur->caps);

		if ($usermeta) {
			$infos['_meta'] = array();
			// 2) métadonnées
			$meta = get_user_meta($id);
			if ($meta !== false) {
				// on ne garde que certaines meta @TODO vérifier qu'on n'oublie rien
				$metaAGarder = array(
					"nickname" => (! empty($meta['nickname']) ? $meta['nickname'][0] : ''),
					"first_name" => $meta['first_name'][0],
					"last_name" => $meta['last_name'][0],
					"description" => $meta['description'][0],
					"{$wpdb->prefix}capabilities" => (! empty($meta['test_capabilities']) ? $meta['test_capabilities'][0] : ''),
					"{$wpdb->prefix}user_level" => (! empty($meta['test_user_level']) ? $meta['test_user_level'][0] : ''),
					"last_activity" => (! empty($meta['last_activity'][0]) ? $meta['last_activity'][0] : 0)
				);
				$infos['_meta'] = $metaAGarder;
			}
		}

		// @TODO obtenir ça avec l'API BP mais c'est une telle bousasse atomique
		// qu'il n'y a pas une p*tain de fonction qui fasse ça clairement :(
		if ($xprofile) {
			$infos['_xprofile'] = array();
			// 3) profil étendu
			$xprofile = $wpdb->get_results("SELECT xd.user_id, xf.name, xd.value "
				. "FROM {$wpdb->prefix}bp_xprofile_fields xf "
				. "LEFT JOIN {$wpdb->prefix}bp_xprofile_data xd ON xd.field_id = xf.id "
				. "WHERE xd.user_id = $id"
			);
			if ($xprofile !== false) {
				foreach ($xprofile as $champ) {
					$infos['_xprofile'][$champ->name] = $champ->value;
				}
			}
		}

		if ($groups) {
			$infos['_groups'] = array();
			// 4) groupes
			// le tableau permet de retirer les filtres par défaut
			if (function_exists('bp_get_user_groups')) {
				$groupes = bp_get_user_groups($id, array(
					"is_admin" => null,
					"is_mod" => null
				));
				if ($groupes !== false) {
					foreach ($groupes as $groupe) {
						// infos du groupe (nom et slug)
						$bp_group = groups_get_group($groupe->group_id);
						// on ne garde que certaines infos @TODO vérifier qu'on n'oublie rien
						if ($bp_group->id) {
							$infos['_groups'][$bp_group->id] = array(
								"id" => $bp_group->id,
								"slug" => $bp_group->slug,
								"name" => $bp_group->name,
								"is_admin" => $groupe->is_admin,
								"is_mod" => $groupe->is_mod
							);
						}
					}
				}
			}
		}
		//echo "<pre>"; var_dump($infos); echo "</pre>"; exit;

		return $infos;
	}

	/**
	 * Renvoie les données de l'utilisateur conformément à la liste de champs
	 * attendue par le service @TODO formaliser cette liste quelque part
	 * 
	 * @param infos Array infos utilisateur produites par infosUtilisateur()
	 */
	protected function formaterInfosUtilisateur(array $infos) {
		// Le prénom et le nom sont définis dans _xprofile (profil BP); ils sont
		// censés être répercutés dans WP usermeta (first_name & last_name) mais
		// si la synchronisation échoue pour une raison X, on surcharge ici
		// @TODO envisager d'abandonner cette stratégie
		// @WARNING ne pas modifier la config du profil BP (noms des champs)
		$prenom = $infos['_meta']['first_name'];
		$nom = $infos['_meta']['last_name'];
		if (! empty($infos['_xprofile']['Prénom'])) {
			$prenom = $infos['_xprofile']['Prénom'];
		}
		if (! empty($infos['_xprofile']['Nom'])) {
			$nom = $infos['_xprofile']['Nom'];
		}

		$retour = array(
			"id" => $infos['ID'],
			// le courriel ne devrait jamais être exposé sans être déjà connu
			// (identité-par-courriel) ou si l'on n'est pas admin
			//"courriel" => $infos['user_email'], // @TODO vérifier que c'est rétrocompatible

			// "prenom", "nom", "pseudo" et "pseudoUtilise" devraient
			// disparaître à terme des infos publiques au profit de "intitule",
			// et ne s'afficher que si l'utilisateur est admin ou demande son
			// propre profil
			"prenom" => $prenom, 
			"nom" => $nom,
			// Le "pseudonyme" est le "display_name" de WP; dans le monde WP il
			// n'est pas forcément égal au "nickname", mais dans le monde TB on
			// souhaite pousser à l'utilisation d'un champ unique pour représenter
			// l'utilisateur, et éviter d'avoir un pseudo différent du display_name
			"pseudo" => $infos['display_name'],
			"pseudoUtilise" => true, // rétrocompat', obsolète

			// l'intitulé doit toujours rester le "display_name" pour que
			// l'affichage soit cohérent entre WP (qui affiche toujours celui-ci)
			// et les applications externes (qui devraient toutes supporter le
			// champ "intitule")
			"intitule" => $infos['display_name'],
			"avatar" => $infos['avatar_url'],
			"groupes" => array()
		);

		// rôles @TODO valider la formalisation des permissions
		$retour['permissions'] = $infos['_roles'];

		// groupes @TODO valider la formalisation des permissions
		foreach($infos['_groups'] as $groupe) {
			$niveau = '';
			if ($groupe['is_admin']) {
				$niveau = 'adm';
			} else if ($groupe['is_mod']) {
				$niveau = 'mod';
			}
			$retour['groupes']['projet:' . $groupe['id']] = $niveau;
		}

		return $retour;
	}

	/**
	 * Envoie un message (email) à l'utilisateur identifié par $idOuCourriel;
	 * nécessite d'être identifié sur le SSO (cookie ou jeton); l'utilisateur en
	 * cours est défini comme expéditeur
	 * 
	 * @TODO permettre de choisir l'adresse d'expéditeur (afficher le véritable
	 * expéditeur ou une adresse choisie, plutôt que no-reply@)
	 * 
	 * @param mixed $destinataires adresse courriel du destinataire, ou un
	 *        tableau d'adresses pour de multiples destinataires
	 * @param type $sujet sujet du message
	 * @param type $contenu contenu du message, accepte le HTML et le texte simple
	 */
	public function envoyerMessage($destinataires, $sujet, $contenu, $expediteur=null) {
		// utilisation du service d'authentification SSO pour détecter
		// l'utilisateur en cours
		$utilisateur = $this->SSO->getUtilisateur();
		//var_dump($utilisateur); exit;

		// L'expéditeur est-il identifié par un jeton SSO ?
		if (! empty($utilisateur['sub'])) {
			$adresseExpediteur = $utilisateur['sub'];
			$nomExpediteur = $utilisateur['intitule'];
		} else {
			// La machine expéditrice est-elle autorisée ?
			if ($this->machineAutorisee()) {
				if ($expediteur) {
						$adresseExpediteur = $expediteur;
						$nomExpediteur = $expediteur;
				} else {
					throw new Exception("Vous devez être identifié ou désigner l'expéditeur dans [utilisateur_courriel] pour poster un message");
				}
			} else {
				throw new Exception("Vous devez être identifié pour poster un message");
			}
		}
		$masquerExpediteur = $this->config['adapters']['AnnuaireWPAPI']['messages']['masquer_expediteur'];
		
		if (empty($adresseExpediteur) || $masquerExpediteur) {
			$adresseExpediteur = $this->config['adapters']['AnnuaireWPAPI']['messages']['expediteur'];
			$nomExpediteur = 'anonyme';
		}

		// formatage du message
		$mail = new PHPMailer();

		$mail->setFrom($adresseExpediteur, "$nomExpediteur via Tela Botanica");
		$sujet = trim($sujet);
		if (empty($sujet)) {
			// sujet par défaut
			$sujet = $this->config['adapters']['AnnuaireWPAPI']['messages']['sujet_par_defaut'];
		}
		$mail->Subject = $sujet;
		// nettoyage
		$contenu = stripslashes($contenu);
		// le texte est-il en HTML ?
		$mail->Body = $contenu;
		$estEnHTML = ($contenu != strip_tags($contenu));
		$mail->isHTML($estEnHTML);
		/*if ($estEnHTML) {
			// d'après http://stackoverflow.com/questions/25873068/converting-phpmailer-html-message-to-text-message
			// le convertisseur HTML => texte de PHPMailer a été supprimé, il
			// faut en utiliser un manuellement, ou dans une closure comme 3e
			// paramètre de ->msgHtml() @TODO le faire un jour
			// $mail->AltBody = ;
		}*/
		// utilisera PHP mail() pour envoyer
		$mail->isMail();
		// encodage
		$mail->CharSet = 'UTF-8';
		$mail->setLanguage('fr');

		// @WARNING RÉTROCOMPATIBILITÉ - aucune idée de si ces entêtes sont
		// utiles ou nécessaires
		$mail->addCustomHeader('X-Mailer', 'Annuaire Tela Botanica');
		$mail->addCustomHeader('X-abuse-contact', 'annuaire@tela-botanica.org');
		// $mail->addReplyTo(...); // @TODO expéditeur ou no-reply ?

		// ajout des destinataires
		if (!is_array($destinataires)) {
			$destinataires = [$destinataires];
		}
		foreach ($destinataires as $destinataire) {
			$mail->addAddress($destinataire);
		}
		// envoi
		if(! $mail->send()) {
			throw new Exception('Erreur PHPMailer: ' . $mail->ErrorInfo);
		}
		return true;
	}

	/**
	 * Retourne true si l'IP du client est dans la liste "ips_autorisees" de la
	 * configuration de l'adapteur
	 */
	protected function machineAutorisee() {
		$retour = false;
		$ip = $_SERVER['REMOTE_ADDR'];
		$ips = $sujet = $this->config['adapters']['AnnuaireWPAPI']['ips_autorisees'];
		if (is_array($ips)) {
			$retour = in_array($ip, $ips);
		}
		return $retour;
	}
}
