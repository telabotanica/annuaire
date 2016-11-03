<?php

/**
 * API REST de l'annuaire (poit d'entrée des services)
 */
class AnnuaireService extends BaseRestServiceTB {

	/** Bibliothèque Annuaire */
	protected $lib;

	/** Autodocumentation en JSON */
	//public static $AUTODOC_PATH = "autodoc.json";

	/** Configuration du service en JSON */
	public static $CONFIG_PATH = "config/service.json";

	public function __construct() {
		// config
		$config = null;
		if (file_exists(self::$CONFIG_PATH)) {
			$config = json_decode(file_get_contents(self::$CONFIG_PATH), true);
		} else {
			throw new Exception("fichier de configuration " . self::$CONFIG_PATH . " introuvable");
		}

		// lib Annuaire
		$this->lib = new Annuaire();

		// ne pas indexer - placé ici pour simplifier l'utilisation avec nginx
		// (pas de .htaccess)
		header("X-Robots-Tag: noindex, nofollow", true);

		parent::__construct($config);
	}

	protected function get() {
		var_dump($_REQUEST);
		$this->sendJson("coucou");
	}
}
