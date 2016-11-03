<?php

require_once 'Annuaire.php';

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
			$contenuConfig = file_get_contents(self::$CONFIG_PATH);
			// dé-commentarisation du pseudo-JSON @TODO valider cette stratégie cheloute
			$contenuConfig = preg_replace('`^[\t ]*//.*\n`m', '', $contenuConfig);
			$config = json_decode($contenuConfig, true);
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
		//var_dump($this->config);
		//var_dump($this->params);
		//var_dump($this->resources);

		// réponse positive par défaut;
		http_response_code(200);

		$nomService = strtolower($this->resources[0]);
		// @TODO strtolower
		//var_dump($nomService);
		switch($nomService) {
			case 'testloginmdp':
				$this->testLoginMdp();
				break;
			case 'nbinscrits':
				$this->nbInscrits();
				break;
			case 'utilisateur':
				break;
			case 'auth':
				array_shift($this->resources);
				if (count($this->resources) > 0) {
					$nextResource = $this->resources[0];
					switch($nextResource) {
						case "get-folders":
							$this->getFolders();
							break;
					}
				}
				break;
			default:
				usage();
		}
	}

	// -------------- rétrocompatibilité (11/2016) -------------------

	protected function infosParIds($unOuPlusieursIds) {
		
	}

	protected function identiteParCourriel($unOuPlusieursCourriels) {
		
	}

	protected function identiteCompleteParCourriel($courriel, $format="json") {
		
	}

	public function prenomNomParCourriel($unOuPlusieursCourriels) {
		
	}

	protected function nbInscrits() {
		
	}

	protected function testLoginMdp() {
		if (count($this->resources) < 2) {
			$this->sendError("ressource manquante");
		}
		$courriel = $this->resources[0];
		$mdpHache = $this->resources[1];

		$retour = $this->lib->testLoginMdp($courriel, $mdpHache);
		$this->sendJson($retour);
	}

	/**
	 * POST
	 * 	http://www.tela-botanica.org/service:annuaire:utilisateur/24604/message
	 */
	protected function message() {
		
	}

	/**
	 * POST
	 * http://www.tela-botanica.org/service:annuaire:utilisateur (POST: methode=connexion, courriel, mdp, persistance)
	 */
	protected function connexion() {
		
	}
}
