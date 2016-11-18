<?php

require_once __DIR__ . '/AnnuaireInterface.php';

/**
 * Impémente l'interface AnnuaireInterface en mettant en jeu un design-pattern
 * "adapter" qui permet de changer d'implémentation par un simple paramètre
 * dans la config
 * https://fr.wikipedia.org/wiki/Adaptateur_(patron_de_conception)
 */
class Annuaire implements AnnuaireInterface {

	/** Config en JSON */
	protected $config = array();
	public static $CHEMIN_CONFIG = __DIR__ . "/config/config.json";

	/** Implémentation de l'interface AnnuaireInterface par un adapteur */
	protected $adapter;

	public function __construct() {
		// config
		if (file_exists(self::$CHEMIN_CONFIG)) {
			$contenuConfig = file_get_contents(self::$CHEMIN_CONFIG);
			// dé-commentarisation du pseudo-JSON @TODO valider cette stratégie cheloute
			$contenuConfig = preg_replace('`^[\t ]*//.*\n`m', '', $contenuConfig);
			$this->config = json_decode($contenuConfig, true);
		} else {
			throw new Exception("fichier de configuration " . self::$CHEMIN_CONFIG . " introuvable");
		}

		// adapteur
		$adapterName = $this->config['adapter'];
		$adapterPath = __DIR__ . '/adapters/' . $adapterName . '.php';
		if (strpos($adapterName, "..") != false || $adapterName == '' || ! file_exists($adapterPath)) {
			throw new Exception ("adapteur " . $adapterPath . " introuvable");
		}
		require $adapterPath;
		// on passe la config à l'adapteur - à lui de stocker ses paramètres
		// dans un endroit correct (adapters.nomdeladapteur par exemple)
		$this->adapter = new $adapterName($this->config);
	}

	public function idParCourriel($courriel) {
		return $this->adapter->idParCourriel($courriel);
	}

	public function getDateDerniereModifProfil($id) {
		return $this->adapter->getDateDerniereModifProfil($id);
	}

	public function inscrireUtilisateur($donneesProfil) {
		return $this->adapter->inscrireUtilisateur($donneesProfil);
	}

	public function getAllRoles() {
		return $this->adapter->getAllRoles();
	}

	// -------------- rétrocompatibilité (11/2016) -------------------

	/**
	 * Vérifie l'accès en se basant sur $id et $mdp si ceux-ci sont fournis; sinon,
	 * lit les valeurs transmises par l'authentification HTTP BASIC AUTH
	 */
	public function verifierAcces($courriel = null, $mdp = null) {
		return $this->adapter->verifierAcces($courriel, $mdp);
	}

	/**
	 * Vérifie si un utilisateur ayant l'adresse email $courriel existe, et si
	 * son mot de passe est bien $mdpHache; retourne true si ces conditions
	 * sont réunies, false sinon
	 */
	public function identificationCourrielMdp($courriel, $mdp) {
		return $this->adapter->identificationCourrielMdp($courriel, $mdp);
	}

	/**
	 * Vérifie si un utilisateur ayant l'adresse email $courriel existe, et si
	 * son mot de passe haché est bien $mdpHache; retourne true si ces conditions
	 * sont réunies, false sinon
	 */
	public function identificationCourrielMdpHache($courriel, $mdpHache) {
		return $this->adapter->identificationCourrielMdpHache($courriel, $mdpHache);
	}

	/**
	 * Renvoie le nombre d'inscrits
	 */
	public function nbInscrits() {
		return $this->adapter->nbInscrits();
	}

	public function infosParIds($unOuPlusieursIds) {
		return $this->adapter->infosParIds($unOuPlusieursIds);
	}

	public function infosParCourriels($unOuPlusieursCourriels) {
		return $this->adapter->infosParCourriels($unOuPlusieursCourriels);
	}
}
