<?php

require_once __DIR__ . '/../AnnuaireAdapter.php';

/**
 * Implémentation de référence de l'Annuaire sur la base de données Wordpress
 */
class AnnuaireWP extends AnnuaireAdapter {

	/** Préfixe des tables Wordpress */
	protected $prefixe;

	/** Handler PDO */
	protected $bdd;

	public function __construct($config) {
		parent::__construct($config);

		// connexion BDD
		$configBdd = $this->config['adapters']['AnnuaireWP']['bdd'];
		$dsn = $configBdd['protocole'] . ':host=' . $configBdd['hote'] . ';dbname=' . $configBdd['base'] . ";charset=utf8";
		$this->bdd = new PDO($dsn, $configBdd['login'], $configBdd['mdp']);
		$this->bdd->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

		// préfixe
		$this->prefixe = $configBdd['prefixe'];
	}

	// ------------- implémentation de la classe abstraite ---------------------

	// @TODO À TESTER
	public function idParCourriel($courriel) {
		// protection
		$courrielP = $this->bdd->quote($courriel);
		// requête
		$q = "SELECT ID FROM {$this->prefixe}users "
			. "WHERE user_email = $courrielP "
			. "AND user_status != 1 "
			. "AND id NOT IN (SELECT user_id FROM test_usermeta WHERE meta_key = 'activation_key')"
		;
		$r = $this->bdd->query($q);
		$d = $r->fetch();

		return intval($d['ID']);
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
		if (! empty($r['last_updated'])) {
			return $r['last_updated'];
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

	// micro-optimisation (économise 1 requête)
	protected function infosUtilisateurParCourriel($courriel) {
		return $this->infosUtilisateur($courriel);
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
	protected function infosUtilisateur($idOuCourriel, $usermeta=true, $xprofile=true, $groups=true) {
		// 0) ID ou courriel ?
		$idUtilisateur = false;
		$courrielUtilisateur = false;
		$infos = array();

		if (is_numeric($idOuCourriel)) {
			$idUtilisateur = $idOuCourriel;
			$clauseUtilisateur = "ID = " . $this->bdd->quote($idUtilisateur);
		} else {
			$courrielUtilisateur = $idOuCourriel;
			$clauseUtilisateur = "user_email = " . $this->bdd->quote($courrielUtilisateur);
		}
		//var_dump($idUtilisateur);
		//var_dump($courrielUtilisateur);

		// 1) utilisateur
		$q = "SELECT * FROM {$this->prefixe}users "
			. "WHERE user_status != 1 "
			. "AND id NOT IN (SELECT user_id FROM {$this->prefixe}usermeta WHERE meta_key = 'activation_key') "
			. "AND " . $clauseUtilisateur
		;
		$r = $this->bdd->query($q);
		$d = $r->fetch();
		if ($d === false) {
			throw new Exception("Impossible de trouver l'utilisateur [$idOuCourriel]");
		}
		// récupérer l'ID
		$idUtilisateur = $d['ID'];
		// garnir les infos
		$infos = $d;

		if ($usermeta) {
			$infos['_meta'] = array();
			// 2) métadonnées
			$q = "SELECT user_id, meta_key, meta_value FROM {$this->prefixe}usermeta "
				. "WHERE meta_key IN ('nickname','first_name','last_name','description','{$this->prefixe}user_level','{$this->prefixe}capabilities','last_activity') "
				. "AND user_id = $idUtilisateur"
			;
			$r = $this->bdd->query($q);
			if ($r !== false) {
				$d = $r->fetchAll();
				foreach ($d as $meta) {
					$infos['_meta'][$meta['meta_key']] = $meta['meta_value'];
				}
			}
		}

		if ($xprofile) {
			$infos['_xprofile'] = array();
			// 3) profil étendu
			$q = "SELECT xd.user_id, xf.name, xd.value "
				. "FROM {$this->prefixe}bp_xprofile_fields xf "
				. "LEFT JOIN {$this->prefixe}bp_xprofile_data xd ON xd.field_id = xf.id "
				. "WHERE xd.user_id = $idUtilisateur"
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
			$q = "SELECT bg.id, bg.slug, bg.name, bgm.is_admin, bgm.is_mod "
				. "FROM {$this->prefixe}bp_groups bg "
				. "LEFT JOIN {$this->prefixe}bp_groups_members bgm ON bgm.group_id = bg.id "
				. "WHERE bgm.is_confirmed = 1 "
				. "AND bgm.is_banned = 0 "
				. "AND bgm.user_id = $idUtilisateur"
			;
			$r = $this->bdd->query($q);
			if ($r !== false) {
				$d = $r->fetchAll();
				foreach ($d as $group) {
					$infos['_groups'][$group['id']] = $group;
				}
			}
		}

		return $infos;
	}

	/**
	 * Renvoie les données de l'utilisateur conformément à la liste de champs
	 * attendue par le service
	 * @param infos Array infos utilisateur produites par infosUtilisateur()
	 */
	protected function formaterInfosUtilisateur(array $infos) {
		//var_dump($infos);
		$pseudo = (! empty($infos['_meta']['nickname'])) ? $infos['_meta']['nickname'] : null;
		$retour = array(
			"id" => $infos['ID'],
			"courriel" => $infos['user_email'],
			"prenom" => $infos['_meta']['first_name'],
			"nom" => $infos['_meta']['last_name'],
			"pseudo" => $pseudo,
			"pseudoUtilise" => ($pseudo == $infos['display_name']), // obsolète
			"intitule" => $infos['display_name']
		);

		//var_dump($retour);
		return $retour;
	}
}
