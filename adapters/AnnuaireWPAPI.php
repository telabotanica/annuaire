<?php

require_once __DIR__ . '/../AnnuaireAdapter.php';

/**
 * Implémentation de référence de l'Annuaire sur la base de données Wordpress,
 * en utilisant l'API Wordpress, ce qui permet de déclencher les hooks lors de
 * l'inscription d'un utilisateur, par exemple.
 * 
 * @WARNING devrait être sensiblement plus lent, à comparer avec AnnuaireWP pour
 * trouver le meilleur rapport qualité/prix
 */
class AnnuaireWPAPI extends AnnuaireAdapter {

	/** Préfixe des tables Wordpress */
	protected $prefixe;

	/** Handler PDO */
	protected $bdd;

	public function __construct($config) {
		parent::__construct($config);

		// connexion BDD
		$cheminWordpress = $this->config['adapters']['AnnuaireWPAPI']['chemin_wp'];
		// inclusion de l'API
		require_once $cheminWordpress . "/wp-load.php";

		// connexion BDD @TODO essayer de s'en débarrasser
		$configBdd = $this->config['adapters']['AnnuaireWP']['bdd'];
		$dsn = $configBdd['protocole'] . ':host=' . $configBdd['hote'] . ';dbname=' . $configBdd['base'] . ";charset=utf8";
		$this->bdd = new PDO($dsn, $configBdd['login'], $configBdd['mdp']);
		$this->bdd->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

		// préfixe
		$this->prefixe = $configBdd['prefixe'];
	}

	// ------------- implémentation de la classe abstraite ---------------------

	public function idParCourriel($courriel) {
		$user = get_user_by('email', $courriel);

		if ($user === false) {
			return false;
		} else {
			return $user->ID;
		}
	}

	// @TODO A TESTER
	public function getDateDerniereModifProfil($id) {
		// protection
		$idP = $this->bdd->quote($id);
		// requête !! nécessite BP
		$q = "SELECT last_updated FROM {$this->prefixe}bp_xprofile_data "
			. "WHERE user_id = $idP"
		;
		$r = $this->bdd->query($q);
		if ($r === false) {
			return false;
		}
		$d = $r->fetch();
		if (! empty($d['last_updated'])) {
			$ddm = strtotime($d['last_updated']);
			return $ddm;
		} else {
			return false;
		}
	}

	// OK
	public function identificationCourrielMdp($courriel, $mdp) {
		// - pourquoi "8" et "false" ?
		// - on s'en fout, c'est écrit ça dans la doc et ça marche
		$passwordHasher = new Hautelook\Phpass\PasswordHash(8, false);

		// protection
		$courrielP = $this->bdd->quote($courriel);
		// requête
		$q = "SELECT user_pass FROM {$this->prefixe}users "
			. "WHERE user_email = $courrielP "
			. "AND user_status != 1 "
			. "AND id NOT IN (SELECT user_id FROM {$this->prefixe}usermeta WHERE meta_key = 'activation_key')"
		;
		$r = $this->bdd->query($q);
		if ($r === false) {
			return false;
		}
		$d = $r->fetch();
		if (empty($d['user_pass'])) {
			return false;
		} else {
			$mdpHache = $d['user_pass'];
			$passwordMatch = $passwordHasher->CheckPassword($mdp, $mdpHache);
			// rétrocompatibilité MD5 @TODO mettre à jour toute la base
			// d'utilisateurs et virer ça un jour
			$correspondanceMD5 = (md5($mdp) == $mdpHache);

			return ($passwordMatch || $correspondanceMD5);
		}
	}

	// OK
	public function identificationCourrielMdpHache($courriel, $mdpHache) {
		// un compte activé non-spam a un "user_status" != 1 dans "users" (non
		// spam) et n'a pas de clef "activation_key" dans "usermeta"

		// protection
		$courrielP = $this->bdd->quote($courriel);
		$mdpHacheP = $this->bdd->quote($mdpHache);
		// requête
		$q = "SELECT ID FROM {$this->prefixe}users "
			. "WHERE user_email = $courrielP "
			. "AND user_pass = $mdpHacheP "
			. "AND user_status != 1 "
			. "AND id NOT IN (SELECT user_id FROM {$this->prefixe}usermeta WHERE meta_key = 'activation_key')"
		;
		$r = $this->bdd->query($q);
		if ($r === false) {
			return false;
		} else {
			$d = $r->fetch();
			return (! empty($d['ID']));
		}
	}

	// OK
	public function nbInscrits() {
		// un compte activé non-spam a un "user_status" != 1 dans "users" (non
		// spam) et n'a pas de clef "activation_key" dans "usermeta"

		$q = "SELECT count(*) as nb FROM {$this->prefixe}users "
			. "WHERE user_status != 1 "
			. "AND id NOT IN (SELECT user_id FROM {$this->prefixe}usermeta WHERE meta_key = 'activation_key')"
		;
		$r = $this->bdd->query($q);
		$d = $r->fetch();

		return intval($d['nb']);
	}

	public function inscrireUtilisateur($donneesProfil) {
		throw new Exception("inscrireUtilisateur: pas encore implémenté");
		// Attention aux hooks
		// https://fr.wordpress.org/plugins/json-api-user/
	}

	protected function infosUtilisateurParId($id) {
		return $this->infosUtilisateur($id);
	}

	/**
	 * Retourne tout ce qu'il y a à savoir sur l'utilisateur, organisé selon les
	 * tables Wordpress / Buddypress :
	 * - champs de la table WP_users (toujours)
	 * - "_meta": métadonnées de la table WP_usermeta (si $usermeta=true)
	 * - "_xprofile": profil étendu des tables WP_bp_xprofile* (si $xprofile=true)
	 * - "_groups": infos de groupes de la table WP_bp_groups_members (si $groups=true)
	 */
	protected function infosUtilisateur($id, $usermeta=true, $xprofile=true, $groups=true) {

		$infos = array();

		// 1) utilisateur
		$utilisateur = get_user_by('id', $id);
		if ($utilisateur === false) {
			throw new Exception("Impossible de trouver l'utilisateur [$id]");
		}
		// garnir les infos
		$infos = (array) $utilisateur->data;

		// 1bis) rôles SSO (permissions)
		$infos['_roles'] = array_keys((array) $utilisateur->caps);

		if ($usermeta) {
			$infos['_meta'] = array();
			// 2) métadonnées
			$meta = get_user_meta($id);
			if ($meta !== false) {
				// on ne garde que certaines meta @TODO vérifier qu'on n'oublie rien
				$metaAGarder = array(
					"nickname" => $meta['nickname'][0],
					"first_name" => $meta['first_name'][0],
					"last_name" => $meta['last_name'][0],
					"description" => $meta['description'][0],
					"{$this->prefix}capabilities" => $meta['test_capabilities'][0],
					"{$this->prefix}user_level" => $meta['test_user_level'][0],
					"last_activity" => $meta['last_activity'][0]
				);
				$infos['_meta'] = $metaAGarder;
			}
		}

		// @TODO obtenir ça avec l'API BP mais c'est une telle bousasse atomique
		// qu'il n'y a pas une p*tain de fonction qui fasse ça clairement :(
		if ($xprofile) {
			$infos['_xprofile'] = array();
			// 3) profil étendu
			//$xprofile = "prout";
			//echo "<pre>"; var_dump($xprofile); echo "</pre>"; exit;

			$q = "SELECT xd.user_id, xf.name, xd.value "
				. "FROM {$this->prefixe}bp_xprofile_fields xf "
				. "LEFT JOIN {$this->prefixe}bp_xprofile_data xd ON xd.field_id = xf.id "
				. "WHERE xd.user_id = $id"
			;
			$r = $this->bdd->query($q);
			if ($r !== false) {
				$d = $r->fetchAll();
				foreach ($d as $xprofile) {
					$infos['_xprofile'][$xprofile['name']] = $xprofile['value'];
				}
			}
		}

		if ($groups) {
			$infos['_groups'] = array();
			// 4) groupes
			// le tableau permet de retirer les filtres par défaut
			$groupes = bp_get_user_groups($id, array(
				"is_admin" => null,
				"is_mod" => null
			));

			if ($groupes !== false) {
				foreach ($groupes as $groupe) {
					// infos du groupe (nom et slug)
					$bp_group = groups_get_group(array('group_id' => $groupe->id ));
					// on ne garde que certaines infos @TODO vérifier qu'on n'oublie rien
					$infos['_groups'][$groupe->id] = array(
						"id" => $groupe->id,
						"slug" => $bp_group->slug,
						"name" => $bp_group->name,
						"is_admin" => $groupe->is_admin,
						"is_mod" => $groupe->is_mod
					);
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
		//var_dump($infos);
		$pseudo = (! empty($infos['_meta']['nickname'])) ? $infos['_meta']['nickname'] : null;
		$retour = array(
			"id" => $infos['ID'],
			// le courriel ne devrait jamais être exposé sans être déjà connu
			// (identité-par-courriel) ou si l'on n'est pas admin
			//"courriel" => $infos['user_email'], // @TODO vérifier que c'est rétrocompatible
			"prenom" => $infos['_meta']['first_name'],
			"nom" => $infos['_meta']['last_name'],
			"pseudo" => $pseudo,
			"pseudoUtilise" => ($pseudo == $infos['display_name']), // obsolète
			"intitule" => $infos['display_name'],
			"groupes" => array()
		);
		// groupes @TODO valider la formalisation des permissions
		foreach($infos['_groups'] as $groupe) {
			$niveau = '';
			if ($groupe['is_admin']) {
				$niveau = 'adm';
			} else if ($groupe['is_mod']) {
				$niveau = 'mod';
			}
			$retour['groupes'][$groupe['id']] = $niveau;
		}

		//var_dump($retour);
		return $retour;
	}
}
