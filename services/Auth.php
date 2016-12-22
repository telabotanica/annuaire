<?php

/**
 * Tentative de service d'authentification / SSO bien organisé
 * SSO - Mettez un tigre dans votre annuaire !
 * @author mathias
 * © Tela Botanica 2015 - 2016
 */
class Auth extends BaseRestServiceTB {

	/** Si true, refusera une connexion non-HTTPS */
	protected $forcerSSL = true;

	/** Durée en secondes du cookie */
	protected $dureeCookie = 31536000; // 3600 * 24 * 365

	/** Domaine du cookie - lire la doc de set_cookie() */
	protected $domaineCookie = null;

	/** Si true, passera secure=true à setCookie; le cookie sera reçu en HTTPS seulement */
	protected $cookieSecurise = true;

	/** Bibliothèque de gestion des utilisateurs */
	protected $annuaire;

	/** Bibliothèque SSO */
	protected $lib;

	public function __construct($config, $annuaire) {
		parent::__construct($config);
		// Auth est un sous-service : suppression de la première ressource,
		// qui a servi au service parent (Annuaire) pour aiguiller ici
		// @TODO faire une classe de sous-service plus astucieuse un jour
		array_shift($this->resources);

		$this->forcerSSL = ($this->config['auth']['forcer_ssl'] == "1");
		$this->dureeCookie = $this->config['auth']['duree_cookie'];
		// le nom du cookie est défini dans la lib SSO (pas pratique mais dur de
		// faire mieux)
		$this->cookieSecurise = $this->config['auth']['cookie_securise'];
		if (! empty($this->config['auth']['domaine_cookie'])) {
			$this->domaineCookie = $this->config['auth']['domaine_cookie'];
		}

		// lib annuaire
		$this->annuaire = $annuaire;
		// lib SSO - raccourci
		$this->lib = $this->annuaire->getSSO();
	}

	/**
	 * Retourne la bibliothèque annuaire (pour les classes partenaires)
	 */
	public function getAnnuaire() {
		return $this->annuaire;
	}

	/**
	 * Notice d'utilisation succincte
	 * @TODO essayer de choisir entre anglais et français
	 */
	protected function infosService() {
		$protocole = $this->isHTTPS ? 'https://' : 'http://';
		$uri = $protocole . $this->config['domain_root'] . $this->config['base_uri'];
		$uri = $uri . $this->firstResourceSeparator . "auth/";

		$infos = array(
			'service' => 'TelaBotanica/annuaire/auth',
			'methodes' => array(
				'connexion' => array(
					"uri" => $uri . "connexion",
					"parametres" => array(
						"login" => "adresse email (ex: name@domain.com)",
						"password" => "mot de passe",
						"partner" => "nom du partenaire (ex: plantnet)"
					),
					"alias" => $uri . "login",
					"description" => "connexion avec login et mot de passe; renvoie un jeton et un cookie " . $this->lib->getNomCookie()
				),
				'deconnexion' => array(
					"uri" => $uri . "deconnexion",
					"parametres" => null,
					"alias" => $uri . "logout",
					"description" => "déconnexion; renvoie un jeton null et supprime le cookie " . $this->lib->getNomCookie()
				),
				'identite' => array(
					"uri" => $uri . "identite",
					"parametres" => array(
						"token" => "jeton JWT (facultatif)",
					),
					"alias" => array(
						$uri . "identity",
						$uri . "rafraichir",
						$uri . "refresh"
					),
					"description" => "confirme l'authentification et la session; rafraîchit le jeton fourni (dans le cookie " . $this->lib->getNomCookie() . ", le header Authorization ou en paramètre)"
				),
				'verifierjeton' => array(
					"uri" => $uri . "verifierjeton",
					"parametres" => array(
						"token" => "jeton JWT",
					),
					"alias" => $uri . "verifytoken",
					"description" => "retourne true si le jeton fourni en paramètre ou dans le header Authorization est valide, une erreur sinon"
				)
			)
		);
		$this->sendJson($infos);
	}

	public function get() {
		if (count($this->resources) == 0) {
			// sans élément d'URL, on sort du service
			$this->infosService();
		} else {
			// Achtétépéèch portouguech lolch
			$this->verifierSSL();
			// le premier paramètre d'URL définit la méthode (non-magique)
			switch ($this->resources[0]) {
				case 'login':
				case 'connexion':
					$this->connexion();
					break;
				case 'logout':
				case 'deconnexion':
					$this->deconnexion();
					break;
				case 'identity':
				case 'identite':
				case 'rafraichir':
				case 'refresh':
					$this->identite();
					break;
				case 'verifytoken':
				case 'verifierjeton':
					$this->verifierJeton();
					break;
				case 'info':
				default:
					$this->infosService();
			
			}
		}
	}

	/**
	 * Lors d'un POST avec au moins une donnée dans le body (data);
	 * les paramètres GET sont ignorés
	 * @TODO faire un point d'entrée POST qui renvoie vers les méthodes GET
	 * 
	 * @param array $ressources les éléments d'URL
	 * @param array $pairs les paramètres POST
	 */
	public function updateElement($ressources, $pairs) {
		//echo "update element\n";
		$this->nonImplemente();
	}

	/**
	 * Lors d'un PUT (les éléments d'URL sont ignorés) ou d'un POST avec au moins
	 * un élément d'URL; dans tous les cas les paramètres GET sont ignorés
	 * 
	 * @param array $pairs les paramètres POST
	 */
	public function createElement($pairs) {
		//echo "create element\n";
		$this->nonImplemente();
	}

	/**
	 * Lors d'un DELETE avec au moins un élément d'URL
	 * @TODO utiliser pour invalider un jeton (nécessite stockage)
	 * 
	 * @param array $ressources les éléments d'URL
	 */
	public function deleteElement($ressources) {
		//echo "delete element\n";
		$this->nonImplemente();
	}

	/**
	 * Vérifie l'identité d'un utilisateur à partir de son courriel et son
	 * mot de passe ou d'un cookie; lui accorde un jeton et un cookie si
	 * tout va bien, sinon renvoie une erreur et détruit le cookie
	 * @WARNING si vous n'utilisez pas urlencode() pour fournir le mot de passe,
	 * le caractère "&" posera problème en GET
	 */
	protected function connexion() {
		$login = $this->getParam('login');
		$password = $this->getParam('password', null);
		$partenaire = $this->getParam('partner');
		if ($login == '' || $password == '') {
			$this->sendError("parameters <login> and <password> required");
		}
		$acces = false;
		// connexion à un partenaire ?
		$infosPartenaire = array();
		if ($partenaire != '') {
			$classeAuth = "AuthPartner" . ucfirst(strtolower($partenaire));
			try {
				$fichierClasse = __DIR__ . "/auth/$classeAuth.php";
				if (! file_exists($fichierClasse)) {
					$this->sendError("unknown partner '$partenaire'");
				}
				require $fichierClasse;
				$authPartenaire = new $classeAuth($this, $this->config);
				// authentification par le partenaire
				$acces = $authPartenaire->verifierAcces($login, $password);
				if ($acces === true) {
					// copie des infos dans l'annuaire si besoin
					$authPartenaire->synchroniser();
				}
				// détails à ajouter au jeton local
				$infosPartenaire['partenaire'] = $partenaire;
				$infosPartenaire['jetonPartenaire'] = $authPartenaire->getJetonPartenaire();
				// remplacement du login par le courriel (chez certains partenaires,
				// le login peut ne pas être un courriel
				$login = $authPartenaire->getCourriel();
			} catch(Exception $e) {
				$this->sendError($e->getMessage(), 500);
			}
		} else {
			// authentification locale
			$acces = $this->annuaire->verifierAcces($login, $password);
		}
		if ($acces === false) {
			$this->detruireCookie();
			// redirection si demandée - se charge de sortir du script en cas de succès
			$this->rediriger();
			// si la redirection n'a pas eu lieu
			$this->sendError("authentication failed", 401);
		}
		// infos utilisateur
		$infos = $this->annuaire->infosParCourriels($login);
		// getIdentiteParCourriel retourne toujours le courriel comme clef de tableau en lowercase
		$login = strtolower($login);
		if (count($infos) == 0 || empty($infos[$login])) {
			// redirection si demandée - se charge de sortir du script en cas de succès
			$this->rediriger();
			// si la redirection n'a pas eu lieu
			$this->sendError("could not get user info");
		}
		$infos = $infos[$login];
		// date de dernière modification du profil
		$dateDerniereModif = $this->annuaire->getDateDerniereModifProfil($infos['id'], true);
		$infos['dateDerniereModif'] = $dateDerniereModif;
		// infos partenaire
		$infos = array_merge($infos, $infosPartenaire);
		// création du jeton
		$jwt = $this->lib->creerJeton($login, $infos);
		// création du cookie
		$this->creerCookie($jwt);
		// redirection si demandée - se charge de sortir du script en cas de succès
		$this->rediriger($jwt);
		// envoi
		$this->sendJson(array(
			"session" => true,
			"token" => $jwt,
			"duration" => intval($this->lib->getDureeJeton()),
			"token_id" => $this->lib->getNomCookie(),
			"last_modif" => $infos['dateDerniereModif']
		));
	}

	/**
	 * Détruit le cookie et renvoie un jeton vide ou NULL - le client
	 * devrait toujours remplacer son jeton par celui renvoyé par les
	 * méthodes de l'annuaire
	 */
	protected function deconnexion() {
		// suppression du cookie
		$this->detruireCookie();
		// envoi d'un jeton null
		$jwt = null;
		// redirection si demandée - se charge de sortir du script en cas de succès
		$this->rediriger();
		// si la redirection n'a pas eu lieu
		$this->sendJson(array(
				"session" => false,
				"token" => $jwt,
				"token_id" => $this->lib->getNomCookie()
		));
	}

	/**
	 * Renvoie un jeton rafraîchi (durée de validité augmentée de $this->dureeJeton
	 * si l'utilisateur est reconnu comme détenteur d'une session active (cookie valide,
	 * header HTTP "Authorization" ou jeton valide); renvoie une erreur si le cookie
	 * et/ou le jeton sont expirés;
	 * cela permet en théorie de forger des cookies avec des jetons expirés pour se les
	 * faire rafraîchir frauduleusement, mais le canal HTTPS fait qu'un client ne peut
	 * être en possession que de ses propres jetons... au pire on peut se faire prolonger
	 * à l'infini même si on n'est plus inscrit à l'annuaire... @TODO faire mieux un jour
	 * Priorité : cookie > header "Authorization" > paramètre "token" @TODO vérifier cette
	 * stratégie, l'inverse est peut-être plus malin
	 */
	protected function identite() {
		$erreur = '';
		$jetonRetour = $this->lib->identite($erreur);
		// redirection si demandée - se charge de sortir du script en cas de succès
		$this->rediriger($jetonRetour);
		// renvoi jeton
		if ($jetonRetour === null) {
			$this->sendError($erreur);
		} else {
			$this->sendJson(array(
					"session" => true,
					"token" => $jetonRetour,
					"duration" => intval($this->lib->getDureeJeton()),
					"token_id" => $this->lib->getNomCookie()
			));
		}
	}

	/**
	 * Si $_GET['redirect_url'] est non-vide, redirige vers l'URL qu'il contient et sort du programme;
	 * sinon, ne fait rien et passe la main
	 * 
	 * @param string $jetonRetour jeton JWT à passer à l'URL de destination
	 * en GET; par défaut null
	 */
	protected function rediriger($jetonRetour=null) {
		if (!empty($_GET['redirect_url'])) {
			// dans le cas où une url de redirection est précisée,
			// on précise le jeton dans le get
			$url_redirection = $_GET['redirect_url'];

			// même si le jeton est vide, on ajoute un paramètre GET Authorization
			// pour spécifier à la cible qu'on a bien traité sa requête - permet
			// aussi de gérer les déconnexions en renvoyant un jeton vide
			$separateur = (parse_url($url_redirection, PHP_URL_QUERY) == NULL) ? '?' : '&';
			$url_redirection .= $separateur.'Authorization=' . $jetonRetour;

			// retour à l'envoyeur !
			header('Location: ' . $url_redirection);
			exit;
		}
	}

	/**
	 * Vérifie si un jeton est valide; retourne true si oui, une erreur avec
	 * des détails si non;
	 * Priorité : header "Authorization" > paramètre "token"
	 */
	protected function verifierJeton() {
		// vérifie que le jeton provient bien d'ici,
		// et qu'il est encore valide (date)
		$jwt = $this->lib->lireJetonDansHeader();
		if ($jwt == null) {
			$jwt = $this->getParam('token');
			if ($jwt == '') {
				$this->sendError("parameter <token> or Authorization header required");
			}
		}
		try {
			$this->lib->decoderJeton($jwt);
		} catch (Exception $e) {
			$this->sendError($e->getMessage());
			exit;
		}
		$this->sendJson(true);
	}

	/**
	 * Crée un cookie de durée $this->dureeCookie, nommé
	 * $this->lib->getNomCookie() et contenant $valeur
	 * 
	 * @param string $valeur le contenu du cookie (de préférence un jeton JWT)
	 */
	protected function creerCookie($valeur) {
		setcookie($this->lib->getNomCookie(), $valeur, time() + $this->dureeCookie, '/', $this->domaineCookie, $this->cookieSecurise);
	}

	/**
	 * Renvoie le cookie avec une valeur vide et une date d'expiration dans le
	 * passé, afin que le navigateur le détruise au prochain appel
	 * @TODO envisager l'envoi d'un jeton vide plutôt que la suppression du cookie
	 * 
	 * @param string $valeur la valeur du cookie, par défaut ""
	 */
	protected function detruireCookie() {
		setcookie($this->lib->getNomCookie(), "", -1, '/', $this->domaineCookie, $this->cookieSecurise);
		// mode transition: supprime l'ancien cookie posé sur "www.tela-botanica.org" sans quoi on ne peut plus se déconnecter!
		// @TODO supprimer au bout d'un moment
		setcookie($this->lib->getNomCookie(), "", -1, '/', null, $this->cookieSecurise);
	}

	/**
	 * Message succinct pour méthodes / actions non implémentées
	 */
	protected function nonImplemente() {
		$this->sendError("not implemented");
	}
	
	/**
	 * Si $this->forcerSSL vaut true, envoie une erreur et termine le programme si SSL n'est pas utilisé
	 */
	protected function verifierSSL() {
		if ($this->forcerSSL === true && ! $this->isHTTPS) {
			$this->sendError("HTTPS required");
			exit;
		}
	}
}
