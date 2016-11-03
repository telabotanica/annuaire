<?php

class AnnuaireService extends BaseRestServiceTB {

	/** Bibliothèque Annuaire */
	protected $lib;

	/** Autodocumentation en JSON */
	public static $AUTODOC_PATH = "autodoc.json";

	/** Configuration du service en JSON */
	public static $CONFIG_PATH = "config/service.json";

	/** Motif d'expression régulière pour détecter les références de fichiers */
	public static $REF_PATTERN = '`https?://`';

	public function __construct() {
		// config
		$config = null;
		if (file_exists(self::$CONFIG_PATH)) {
			$config = json_decode(file_get_contents(self::$CONFIG_PATH), true);
		} else {
			throw new Exception("file " . self::$CONFIG_PATH . " doesn't exist");
		}

		// lib Cumulus
		$this->lib = new Cumulus();

		// ne pas indexer - placé ici pour simplifier l'utilisation avec nginx
		// (pas de .htaccess)
		header("X-Robots-Tag: noindex, nofollow", true);

		parent::__construct($config);
	}
}
