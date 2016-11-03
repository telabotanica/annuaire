<?php

require_once 'AnnuaireInterface.php';

class Annuaire implements AnnuaireInterface {

	/** Config en JSON */
	protected $config = array();
	public static $CHEMIN_CONFIG = "config/config.json";

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
		$adapterPath = 'adapters/' . $adapterName . '.php';
		if (strpos($adapterName, "..") != false || $adapterName == '' || ! file_exists($adapterPath)) {
			throw new Exception ("adapteur " . $adapterPath . " introuvable");
		}
		require $adapterPath;
		// on passe la config à l'adapteur - à lui de stocker ses paramètres
		// dans un endroit correct (adapters.nomdeladapteur par exemple)
		$this->adapter = new $adapterName($this->config);
	}
	// -------------- rétrocompatibilité (11/2016) -------------------

	/**
	 * Vérifie si un utilisateur ayant l'adresse email $courriel existe, et si
	 * son mot de passe haché est bien $mdpHache; retourne true si ces conditions
	 * sont réunies, false sinon
	 */
	public function testLoginMdp($courriel, $mdpHache) {
		return $this->adapter->testLoginMdp($courriel, $mdpHache);
	}
}
