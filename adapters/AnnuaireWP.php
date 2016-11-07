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
		$dsn = $configBdd['protocole'] . ':host=' . $configBdd['hote'] . ';dbname=' . $configBdd['base'];
		$this->bdd = new PDO($dsn, $configBdd['login'], $configBdd['mdp']);

		// préfixe
		$this->prefixe = $configBdd['prefixe'];
	}

	public function idParCourriel($courriel) {
		throw new Exception("idParCourriel: pas encore implémenté");
		// SELECT ID FROM test_users WHERE user_email = 'mathias@tela-botanica.org' AND user_status != 1 AND id NOT IN (SELECT user_id FROM test_usermeta WHERE meta_key = 'activation_key');
	}

	public function getDateDerniereModifProfil($id) {
		throw new Exception("getDateDerniereModifProfil: pas encore implémenté");
		// !! nécessite BP
		// SELECT last_updated FROM test_bp_xprofile_data WHERE user_id  =1
	}

	public function inscrireUtilisateur($donneesProfil) {
		throw new Exception("inscrireUtilisateur: pas encore implémenté");
		// Attention aux hooks
		// https://fr.wordpress.org/plugins/json-api-user/
	}

	// OK
	public function identificationCourrielMdp($courriel, $mdp) {
		// - pourquoi "8" et "false" ?
		// - on s'en fout, c'est écrit ça dans la doc et ça marche
		$passwordHasher = new Hautelook\Phpass\PasswordHash(8, false);
		
		$q = "SELECT user_pass FROM {$this->prefixe}users "
			. "WHERE user_email = '$courriel' "
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
		$q = "SELECT ID FROM {$this->prefixe}users "
			. "WHERE user_email = '$courriel' "
			. "AND user_pass = '$mdpHache' "
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

	public function nbInscrits() {
		// un compte activé non-spam a un "user_status" != 1 dans "users" (non
		// spam) et n'a pas de clef "activation_key" dans "usermeta"
		// 
		// SELECT count(*) FROM test_users WHERE user_status != 1 AND id NOT IN (SELECT user_id FROM test_usermeta WHERE meta_key = 'activation_key');
		throw new Exception("nbInscrits: pas encore implémenté");
	}

	public function infosParIds($unOuPlusieursIds) {
		throw new Exception("infosParIds: pas encore implémenté");
	}

	public function infosParCourriels($unOuPlusieursCourriels) {
		throw new Exception("infosParCourriels: pas encore implémenté");
	}

	/**
	 * Retourne tout ce qu'il y a à savoir sur l'utilisateur :
	 * - champs de la table WP_users (toujours)
	 * - métadonnées de la table WP_usermeta (si $usermeta=true)
	 * - profil étendu des tables WP_bp_xprofile* (si $xprofile=true)
	 * - infos de groupes de la table WP_bp_groups_members (si $groups=true)
	 */
	protected function infosUtilisateur($idOuCourriel, $usermeta=true, $xprofile=true, $groups=true) {
		// 0) ID ou courriel ?
		$idUtilisateur = false;
		$courrielUtilisateur = false;
		if (is_numeric($idOuCourriel)) {
			$idUtilisateur = $idOuCourriel;
			$clauseUtilisateur = "ID = $idUtilisateur";
		} else {
			$courrielUtilisateur = $idOuCourriel;
			$clauseUtilisateur = "user_email = '$courrielUtilisateur'";
		}

		// 1) utilisateur
		$q = "SELECT * FROM test_users "
			. "WHERE user_status != 1 "
			. "AND id NOT IN (SELECT user_id FROM test_usermeta WHERE meta_key = 'activation_key') "
			. $clauseUtilisateur;
		
		// récupérer l'ID
		// $idUtilisateur = ?

		// 2) métadonnées
		// SELECT user_id, meta_key, meta_value FROM test_usermeta WHERE meta_key IN ('nickname','first_name','last_name','description','test_user_level','test_capabilities','last_activity') AND user_id = 1;

		// 3) profil étendu
		// SELECT xd.user_id, xf.name, xd.value FROM test_bp_xprofile_fields xf LEFT JOIN test_bp_xprofile_data xd ON xd.field_id = xf.id WHERE xd.user_id = 1;

		// 4) groupes
		// SELECT bg.id, bg.slug, bg.name, bgm.is_admin, bgm.is_mod FROM test_bp_groups bg LEFT JOIN test_bp_groups_members bgm ON bgm.group_id = bg.id WHERE bgm.is_confirmed = 1 AND bgm.is_banned = 0 AND bgm.user_id = 1;
	}
}
