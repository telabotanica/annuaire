<?php
/**
 * Tentative de service d'authentification / SSO bien organisé
 * SSO - Mettez un tigre dans votre annuaire !
 * @author mathias
 * © Tela Botanica 2015 - 2016
 */
class Auth extends BaseRestServiceTB {

	/** Chemin du fichier de clef */
	const CHEMIN_CLEF_AUTH = "config/clef-auth.ini";

	/** Clef utilisée pour signer les jetons JWT */
	private $clef;

	/** Si true, refusera une connexion non-HTTPS */
	protected $forcerSSL = true;

	/** Durée en secondes du jeton (doit être faible en l'absence de mécanisme d'invalidation) */
	protected $dureeJeton = 900;

	/** Durée en secondes du cookie */
	protected $dureeCookie = 31536000; // 3600 * 24 * 365

	/** Nom du cookie */
	protected $nomCookie = "this_is_not_a_good_cookie_name";

	/** Domaine du cookie - lire la doc de set_cookie() */
	protected $domaineCookie = null;

	/** Si true, passera secure=true à setCookie; le cookie sera reçu en HTTPS seulement */
	protected $cookieSecurise = true;

	/** Bibliothèque de gestion des utilisateurs */
	protected $annuaire;

	public function __construct($config, $annuaire) {
		parent::__construct($config);
		// Auth est un sous-service : suppression de la première ressource,
		// qui a servi au service parent (Annuaire) pour aiguiller ici
		// @TODO faire une classe de sous-service plus astucieuse un jour
		array_shift($this->resources);

		$this->clef = file_get_contents(self::CHEMIN_CLEF_AUTH);
		if (strlen($this->clef) < 16) {
			throw new Exception("Clef trop courte - placez une clef d'au moins 16 caractères dans configurations/clef-auth.ini");
		}

		$this->forcerSSL = ($this->config['auth']['forcer_ssl'] == "1");
		$this->dureeJeton = $this->config['auth']['duree_jeton'];
		$this->dureeCookie = $this->config['auth']['duree_cookie'];
		$this->nomCookie = $this->config['auth']['nom_cookie'];
		$this->cookieSecurise = $this->config['auth']['cookie_securise'];
		if (! empty($this->config['auth']['domaine_cookie'])) {
			$this->domaineCookie = $this->config['auth']['domaine_cookie'];
		}

		// lib annuaire
		$this->annuaire = $annuaire;
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
		$uri = $this->config['settings']['baseAlternativeURL'];
		if ($uri == '') {
			$uri = $this->config['settings']['baseURL'];
		}
		$uri = $uri . "auth/";

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
					"description" => "connexion avec login et mot de passe; renvoie un jeton et un cookie " . $this->nomCookie
				),
				'deconnexion' => array(
					"uri" => $uri . "deconnexion",
					"parametres" => null,
					"alias" => $uri . "logout",
					"description" => "déconnexion; renvoie un jeton null et supprime le cookie " . $this->nomCookie
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
					"description" => "confirme l'authentification et la session; rafraîchit le jeton fourni (dans le cookie " . $this->nomCookie . ", le header Authorization ou en paramètre)"
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
		$this->envoyerJson($infos);
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
			$this->erreur("parameters <login> and <password> required");
		}
		$acces = false;
		// connexion à un partenaire ?
		$infosPartenaire = array();
		if ($partenaire != '') {
			$classeAuth = "AuthPartner" . ucfirst(strtolower($partenaire));
			try {
				$fichierClasse = getcwd() . "/services/auth/$classeAuth.php"; // @TODO vérifier si getcwd() est fiable dans ce cas
				if (! file_exists($fichierClasse)) {
					$this->erreur("unknown partner '$partenaire'");
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
				$this->erreur($e->getMessage(), 500);
			}
		} else {
			// authentification locale
			$acces = $this->verifierAcces($login, $password);
		}
		if ($acces === false) {
			$this->detruireCookie();
			// redirection si demandée - se charge de sortir du script en cas de succès
			$this->rediriger();
			// si la redirection n'a pas eu lieu
			$this->erreur("authentication failed", 401);
		}
		// infos utilisateur
		$infos = $this->annuaire->getIdentiteParCourriel($login);
		//var_dump($infos); exit;
		// getIdentiteParCourriel retourne toujours le courriel comme clef de tableau en lowercase
		$login = strtolower($login);
		if (count($infos) == 0 || empty($infos[$login])) {
			// redirection si demandée - se charge de sortir du script en cas de succès
			$this->rediriger();
			// si la redirection n'a pas eu lieu
			$this->erreur("could not get user info");
		}
		$infos = $infos[$login];
		// date de dernière modification du profil
		$dateDerniereModif = $this->annuaire->getDateDerniereModifProfil($infos['id'], true);
		$infos['dateDerniereModif'] = $dateDerniereModif;
		// infos partenaire
		$infos = array_merge($infos, $infosPartenaire);
		// création du jeton
		$jwt = $this->creerJeton($login, $infos);
		// création du cookie
		$this->creerCookie($jwt);
		// redirection si demandée - se charge de sortir du script en cas de succès
		$this->rediriger($jwt);
		// envoi
		$this->envoyerJson(array(
			"session" => true,
			"token" => $jwt,
			"duration" => intval($this->dureeJeton),
			"token_id" => $this->nomCookie,
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
		$this->envoyerJson(array(
				"session" => false,
				"token" => $jwt,
				"token_id" => $this->nomCookie
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
		$cookieAvecJetonValide = false;
		$jetonRetour = null;
		$erreur = '';
		// lire cookie
		if (isset($_COOKIE[$this->nomCookie])) {
			$jwt = $_COOKIE[$this->nomCookie];
			try {
				// rafraîchir jeton quelque soit son état - "true" permet
				// d'ignorer les ExpiredException (on rafraîchit le jeton
				// expiré car le cookie est encore valide)
				$jetonRetour = $this->rafraichirJeton($jwt, true);
				// on ne tentera pas de lire un jeton fourni en paramètre
				$cookieAvecJetonValide = true;
			} catch (Exception $e) {
				// si le rafraîchissement a échoué (jeton invalide - hors expiration - ou vide)
				// on ne fait rien et on tente la suite (jeton fourni hors cookie ?)
				$erreur = "invalid token in cookie";
			}
		}
		// si le cookie n'existait pas ou ne contenait pas un jeton
		if (! $cookieAvecJetonValide) {
			// lire jeton depuis header ou paramètre
			$jwt = $this->lireJetonDansHeader();
			if ($jwt == null) {
				// dernière chance
				$jwt = $this->getParam('token');
			}
			// toutes les possibilités ont été essayées
			if ($jwt != null) {
				try {
					// rafraîchir jeton si non expiré
					$jetonRetour = $this->rafraichirJeton($jwt);
				} catch (Exception $e) {
					// si le rafraîchissement a échoué (jeton invalide, expiré ou vide)
					$erreur = "invalid or expired token in Authorization header or parameter <token>";
				}
			} else {
				// pas de jeton valide passé en paramètre
				$erreur = ($erreur == "" ? "no token or cookie" : "invalid token in cookie / invalid or expired token in Authorization header or parameter <token>");
			}
		}
		// redirection si demandée - se charge de sortir du script en cas de succès
		$this->rediriger($jetonRetour);
		// renvoi jeton
		if ($jetonRetour === null) {
			$this->erreur($erreur);
		} else {
			$this->envoyerJson(array(
					"session" => true,
					"token" => $jetonRetour,
					"duration" => intval($this->dureeJeton),
					"token_id" => $this->nomCookie
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
			$url_redirection .= $separateur.'Authorization='.$jetonRetour;

			// retour à l'envoyeur !
			header('Location: '.$url_redirection);
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
		$jwt = $this->lireJetonDansHeader();
		if ($jwt == null) {
			$jwt = $this->getParam('token');
			if ($jwt == '') {
				$this->erreur("parameter <token> or Authorization header required");
			}
		}
		try {
			$jeton = JWT::decode($jwt, $this->clef, array('HS256'));
			$jeton = (array) $jeton;
		} catch (Exception $e) {
			$this->erreur($e->getMessage());
			exit;
		}
		$this->envoyerJson(true);
	}

	/**
	 * Reçoit un jeton JWT, et s'il est non-vide ("sub" != null), lui redonne
	 * une période de validité de $this->dureeJeton; si $ignorerExpiration
	 * vaut true, rafraîchira le jeton même s'il a expiré
	 * (attention à ne pas appeler cette méthode n'importe comment !);
	 * jette une exception si le jeton est vide, mal signé ou autre erreur,
	 * ou s'il a expiré et que $ignorerExpiration est différent de true
	 * 
	 * @param string $jwt le jeton JWT
	 * @return string le jeton rafraîchi
	 */
	protected function rafraichirJeton($jwt, $ignorerExpiration=false) /* throws Exception */ {
		$infos = array();
		// vérification avec lib JWT
		try {
			$infos = JWT::decode($jwt, $this->clef, array('HS256'));
			$infos = (array) $infos;
		} catch (ExpiredException $e) {
			if ($ignorerExpiration === true) {
				// on se fiche qu'il soit expiré
				// décodage d'un jeton expiré 
				// @WARNING considère que la lib JWT jette ExpiredException en dernier (vrai 12/05/2015),
				// ce qui signifie que la signature et le domaine sont tout de même valides - à surveiller !
				$infos = $this->decoderJetonManuellement($jwt);
			} else {
				// on renvoie l'exception plus haut
				throw $e;
			}
		}
		// vérification des infos
		if (empty($infos['sub'])) {
			// jeton vide (wtf?)
			throw new Exception("empty token (no <sub>)");
		}
		// rafraîchissement
		$infos['exp'] = time() + $this->dureeJeton;
		$jwtSortie = JWT::encode($infos, $this->clef);

		return $jwtSortie;
	}

	/**
	 * Décode manuellement un jeton JWT, SANS VÉRIFIER SA SIGNATURE OU
	 * SON DOMAINE ! @WARNING ne pas utiliser hors du cas d'un jeton
	 * correct (vérifié avec la lib JWT) mais expiré !
	 * Public car utilisé par les classes AuthPartner (@TODO stratégie à valider)
	 * @param string $jwt un jeton vérifié comme valide, mais expiré
	 */
	public function decoderJetonManuellement($jwt) {
		$parts = explode('.', $jwt);
		$payload = $parts[1];
		$payload = base64_decode($payload);
		$payload = json_decode($payload, true);

		return $payload;
	}

	/**
	 * Crée un jeton JWT signé avec la clef
	 * 
	 * @param mixed $sub subject: l'id utilisateur du détenteur du jeton si authentifié, null sinon
	 * @param string $exp la date d'expiration du jeton, par défaut la date actuelle plus $this->dureeJeton
	 * @param array $donnees les données à ajouter au jeton (infos utilisateur)
	 * 
	 * @return string un jeton JWT signé
	 */
	protected function creerJeton($sub, $donnees=array(), $exp=null) {
		if ($exp === null) {
			$exp = time() + $this->dureeJeton;
		}
		$jeton = array(
			"iss" => "https://www.tela-botanica.org",
			"token_id" => $this->nomCookie,
			//"aud" => "http://example.com",
			"sub" => $sub,
			"iat" => time(),
			"exp" => $exp,
			//"nbf" => time() + 60,
			"scopes" => array("tela-botanica.org")
		);
		if (! empty($donnees)) {
			$jeton = array_merge($jeton, $donnees);
		}
		$jwt = JWT::encode($jeton, $this->clef);

		return $jwt;
	}

	/**
	 * Essaye de trouver un jeton JWT non vide dans l'entête HTTP $nomHeader (par
	 * défaut "Authorization")
	 * 
	 * @param string $nomHeader nom de l'entête dans lequel chercher le jeton
	 * @return String un jeton JWT ou null
	 */
	protected function lireJetonDansHeader($nomHeader="Authorization") {
		$jwt = null;
		$headers = apache_request_headers();
		if (isset($headers[$nomHeader]) && ($headers[$nomHeader] != "")) {
			$jwt = $headers[$nomHeader];
		}
		return $jwt;
	}

	/**
	 * Crée un cookie de durée $this->dureeCookie, nommé $this->nomCookie et
	 * contenant $valeur
	 * 
	 * @param string $valeur le contenu du cookie (de préférence un jeton JWT)
	 */
	protected function creerCookie($valeur) {
		setcookie($this->nomCookie, $valeur, time() + $this->dureeCookie, '/', $this->domaineCookie, $this->cookieSecurise);
	}

	/**
	 * Renvoie le cookie avec une valeur vide et une date d'expiration dans le
	 * passé, afin que le navigateur le détruise au prochain appel
	 * @TODO envisager l'envoi d'un jeton vide plutôt que la suppression du cookie
	 * 
	 * @param string $valeur la valeur du cookie, par défaut ""
	 */
	protected function detruireCookie() {
		setcookie($this->nomCookie, "", -1, '/', $this->domaineCookie, $this->cookieSecurise);
		// mode transition: supprime l'ancien cookie posé sur "www.tela-botanica.org" sans quoi on ne peut plus se déconnecter!
		// @TODO supprimer au bout d'un moment
		setcookie($this->nomCookie, "", -1, '/', null, $this->cookieSecurise);
	}

	// ---------------- Méthodes à génériciser ci-dessous ----------------------------------

	/**
	 * Message succinct pour méthodes / actions non implémentées
	 */
	protected function nonImplemente() {
		$this->erreur("not implemented");
	}
	
	/**
	 * Si $this->forcerSSL vaut true, envoie une erreur et termine le programme si SSL n'est pas utilisé
	 */
	protected function verifierSSL() {
		if ($this->forcerSSL === true) {
			if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
				$this->erreur("HTTPS required");
				exit;
			}
		}
	}

	protected function getParamChain($names) {
		if (! is_array($names)) {
			// Hou ? (cri de chouette solitaire)
		}
	}

	/**
	 * Capture un paramètre de requête ($_REQUEST)
	 * 
	 * @param string $name nom du paramètre à capturer
	 * @param string $default valeur par défaut si le paramètre n'est pas défini (ou vide, voir ci-dessous)
	 * @param bool $traiterVideCommeDefaut si le paramètre est défini mais vide (''), le considèrera comme non défini
	 * 
	 * @return string la valeur du paramètre si défini, sinon la valeur par défaut
	 */
	protected function getParam($name, $default=null, $traiterVideCommeDefaut=false) {
		$ret = $default;
		if (isset($_REQUEST[$name])) {
			if ($traiterVideCommeDefaut === false || $_REQUEST[$name] !== '') {
				$ret = $_REQUEST[$name];
			}
		}
		return $ret;
	}

	/**
	 * Capture un paramètre GET
	 * 
	 * @param string $name nom du paramètre GET à capturer
	 * @param string $default valeur par défaut si le paramètre n'est pas défini (ou vide, voir ci-dessous)
	 * @param bool $traiterVideCommeDefaut si le paramètre est défini mais vide (''), le considèrera comme non défini
	 * 
	 * @return string la valeur du paramètre si défini, sinon la valeur par défaut
	 */
	protected function getGetParam($name, $default=null, $traiterVideCommeDefaut=false) {
		$ret = $default;
		if (isset($_GET[$name])) {
			if ($traiterVideCommeDefaut === false || $_GET[$name] !== '') {
				$ret = $_GET[$name];
			}
		}
		return $ret;
	}

	/**
	 * Capture un paramètre POST
	 * 
	 * @param string $name nom du paramètre POST à capturer
	 * @param string $default valeur par défaut si le paramètre n'est pas défini (ou vide, voir ci-dessous)
	 * @param bool $traiterVideCommeDefaut si le paramètre est défini mais vide (''), le considèrera comme non défini
	 * 
	 * @return string la valeur du paramètre si défini, sinon la valeur par défaut
	 */
	protected function getPostParam($name, $default=null, $traiterVideCommeDefaut=false) {
		$ret = $default;
		if (isset($_POST[$name])) {
			if ($traiterVideCommeDefaut === false || $_POST[$name] !== '') {
				$ret = $_POST[$name];
			}
		}
		return $ret;
	}

	/**
	 * Envoie une erreur HTTP $code (400 par défaut) avec les données $data en JSON
	 * 
	 * @param mixed $data données JSON de l'erreur - généralement array("error" => "raison de l'erreur") - si
	 * 		seule une chaîne est transmise, sera convertie en array("error" => $data)
	 * @param number $code code HTTP de l'erreur, par défaut 400 (bad request)
	 * @param boolean $exit si true (par défaut), termine le script après avoir envoyé l'erreur
	 */
	protected function erreur($data, $code=400, $exit=true) {
		if (! is_array($data)) {
			$data = array(
				"error" => $data
			);
		}
		http_response_code($code);
		$this->envoyerJson($data);
		if ($exit === true) {
			exit;
		}
	}
}

/**
 * Mode moderne pour PHP < 5.4
 */
if (!function_exists('http_response_code')) {
	function http_response_code($code = NULL) {
		if ($code !== NULL) {
			switch ($code) {
				case 100: $text = 'Continue'; break;
				case 101: $text = 'Switching Protocols'; break;
				case 200: $text = 'OK'; break;
				case 201: $text = 'Created'; break;
				case 202: $text = 'Accepted'; break;
				case 203: $text = 'Non-Authoritative Information'; break;
				case 204: $text = 'No Content'; break;
				case 205: $text = 'Reset Content'; break;
				case 206: $text = 'Partial Content'; break;
				case 300: $text = 'Multiple Choices'; break;
				case 301: $text = 'Moved Permanently'; break;
				case 302: $text = 'Moved Temporarily'; break;
				case 303: $text = 'See Other'; break;
				case 304: $text = 'Not Modified'; break;
				case 305: $text = 'Use Proxy'; break;
				case 400: $text = 'Bad Request'; break;
				case 401: $text = 'Unauthorized'; break;
				case 402: $text = 'Payment Required'; break;
				case 403: $text = 'Forbidden'; break;
				case 404: $text = 'Not Found'; break;
				case 405: $text = 'Method Not Allowed'; break;
				case 406: $text = 'Not Acceptable'; break;
				case 407: $text = 'Proxy Authentication Required'; break;
				case 408: $text = 'Request Time-out'; break;
				case 409: $text = 'Conflict'; break;
				case 410: $text = 'Gone'; break;
				case 411: $text = 'Length Required'; break;
				case 412: $text = 'Precondition Failed'; break;
				case 413: $text = 'Request Entity Too Large'; break;
				case 414: $text = 'Request-URI Too Large'; break;
				case 415: $text = 'Unsupported Media Type'; break;
				case 500: $text = 'Internal Server Error'; break;
				case 501: $text = 'Not Implemented'; break;
				case 502: $text = 'Bad Gateway'; break;
				case 503: $text = 'Service Unavailable'; break;
				case 504: $text = 'Gateway Time-out'; break;
				case 505: $text = 'HTTP Version not supported'; break;
				case 666: $text = 'Couscous overheat'; break;
				default:
					exit('Unknown http status code "' . htmlentities($code) . '"');
					break;
			}

			$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
			header($protocol . ' ' . $code . ' ' . $text);
			$GLOBALS['http_response_code'] = $code;
		} else {
			$code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
		}
		return $code;
	}
}
