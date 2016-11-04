<?php

require_once 'AnnuaireInterface.php';

/**
 * Implémentation de référence de l'Annuaire sur la base de données Wordpress
 */
class AnnuaireWP implements AnnuaireInterface {

	public function idParCourriel($courriel) {
		throw new Exception("idParCourriel: pas encore implémenté");
	}

	public function getDateDerniereModifProfil($id) {
		throw new Exception("getDateDerniereModifProfil: pas encore implémenté");
	}

	public function inscrireUtilisateur($donneesProfil) {
		throw new Exception("inscrireUtilisateur: pas encore implémenté");
	}

	public function testLoginMdp($courriel, $mdpHache) {
		throw new Exception("testLoginMdp: pas encore implémenté");
	}

	public function nbInscrits() {
		throw new Exception("nbInscrits: pas encore implémenté");
	}

	public function infosParIds($unOuPlusieursIds) {
		throw new Exception("infosParIds: pas encore implémenté");
	}

	public function infosParCourriels($unOuPlusieursCourriels) {
		throw new Exception("infosParCourriels: pas encore implémenté");
	}

	/**
	 * Vérifie l'accès en se basant sur $id et $mdp si ceux-ci sont fournis; sinon,
	 * lit les valeurs transmises par l'authentification HTTP BASIC AUTH
	 */
	public function verifierAcces($id = null, $mdp = null) {
		$basicAuth = false;
		if ($id == null && $mdp == null) {
			$id = is_null($id) ? $_SERVER['PHP_AUTH_USER'] : $id;
			$mdp = is_null($mdp) ? $_SERVER['PHP_AUTH_PW'] : $mdp;
			$basicAuth = true;			
		}
		// mode super admin debug super sioux - attention aux fuites de mdp !
		$mdpMagiqueHache = $this->config['jrest_admin']['mdp_magique_hache'];
		if ($mdpMagiqueHache != '') {
			if (md5($mdp) === $mdpMagiqueHache) {
				return true;
			}
		}
		// mode pour les gens normaux qu'ont pas de passe-droits
		if ($basicAuth === false || JRest::$cgi === false) { // en mode non-CGI ou pour une identification $id / $mdp

			// si une appli ISO (Papyrus) fournit un mdp contenant des caractères
			// non-ISO, eh ben /i !
			if (! preg_match('//u', $mdp)) {
                $mdp = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $mdp);
            }
			if (! preg_match('//u', $id)) {
                $id = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $id);
            }

			$requete = 'SELECT '.$this->config['database_ident']['ann_id'].' AS courriel '.
				'FROM '.$this->config['database_ident']['database'].'.'.$this->config['database_ident']['annuaire'].' '.
				'WHERE '.$this->config['database_ident']['ann_id'].' = '.$this->bdd->quote($id).' '.
				'	AND '.$this->config['database_ident']['ann_pwd'].' = '.$this->config['database_ident']['pass_crypt_funct'].'('.$this->bdd->quote($mdp).')' ;

			$resultat = $this->bdd->query($requete)->fetch();
	
			$identifie = false;
			if (isset($resultat['courriel'])) {
				$identifie = true;
			}
			return $identifie;
		} else { // si on est en CGI, accès libre pour tous (pas trouvé mieux)
			return true; // ça fait un peu mal...
		}
	}
}
