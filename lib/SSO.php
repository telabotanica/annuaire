<?php

// composer
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Méthodes métier du mécanisme SSO - utilisées notamment par le service Auth
 */
class SSO {

	/** Chemin du fichier de clef */
	const CHEMIN_CLEF_AUTH = __DIR__ . "/../config/clef-auth.ini";

	/** Clef utilisée pour signer les jetons JWT */
	private $clef;

	/** Durée en secondes du jeton (doit être faible en l'absence de mécanisme d'invalidation) */
	protected $dureeJeton = 900;

	/** Configuration passée par l'Annuaire */
	protected $config;

	/** Nom du cookie */
	protected $nomCookie = "this_is_not_a_good_cookie_name";

	public function __construct($config) {
		$this->config = $config;

		$this->dureeJeton = $this->config['auth']['duree_jeton'];
		$this->nomCookie = $this->config['auth']['nom_cookie'];

		$this->clef = file_get_contents(self::CHEMIN_CLEF_AUTH);
		if (strlen($this->clef) < 16) {
			throw new Exception("Clef trop courte - placez une clef d'au moins 16 caractères dans configurations/clef-auth.ini");
		}
	}

	// getter pour $this->dureeJeton (pour le service Auth)
	public function getDureeJeton() {
		return $this->dureeJeton;
	}

	// getter pour $this->nomCookie (pour le service Auth)
	public function getNomCookie() {
		return $this->nomCookie;
	}

	/**
	 * Retourne l'utilisateur en cours : renvoie le contenu de son jeton SSO
	 * sous forme d'un tableau, ou false si aucun jeton n'a été fourni ou si le
	 * jeton est invalide
	 */
	public function getUtilisateur() {
		$jeton = $this->identite();
		if ($jeton) {
			$jetonDecode = $this->decoderJeton($jeton);
			return $jetonDecode;
		}
		return false;
	}

	/**
	 * Décode le jeton $jwt; retourne son contenu sous forme de tableau si tout
	 * se passe bien, jette une exception sinon (jeton expiré, signature
	 * invalide)
	 * 
	 * @param string $jwt un jeton JWT à décoder
	 * @return array le jeton décodé
	 * @throws Exception
	 */
	public function decoderJeton($jwt) {
		// vérifie que le jeton provient bien d'ici,
		// et qu'il est encore valide (date)
		$jeton = JWT::decode($jwt, $this->clef, array('HS256'));
		$jeton = (array) $jeton;
		return $jeton;
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
		$payload = $this->urlsafeB64Decode($payload);
		$payload = json_decode($payload, true);

		return $payload;
	}

	/**
	 * Méthode compatible avec l'encodage base64 "urlsafe" de la lib JWT
	 */
	protected function urlsafeB64Decode($input) {
		$remainder = strlen($input) % 4;
		if ($remainder) {
			$padlen = 4 - $remainder;
			$input .= str_repeat('=', $padlen);
		}
		return base64_decode(strtr($input, '-_', '+/'));
	}

	/**
	 * Encode un jeton JWT contenant les infos $infos, et le signe avec la clef
	 * $this->clef
	 * 
	 * @param type $infos un tableau de champs à placer dans le jeton (payload)
	 * @param int $exp date d'expiration; si null (par défaut), le jeton sera
	 *		  valide durant $this->dureeJeton secondes
	 * 
	 * @return string un jeton JWT signé
	 */
	public function encoderJeton($infos, $exp = null) {
		// date d'expiration
		if ($exp === null) {
			$exp = time() + $this->dureeJeton;
		}
		$infos['exp'] = $exp;
		// encodage
		$jwtSortie = JWT::encode($infos, $this->clef);

		return $jwtSortie;
	}

	/**
	 * Crée un jeton JWT signé avec la clef
	 * 
	 * @param mixed $sub subject: l'id utilisateur du détenteur du jeton si authentifié, null sinon
	 * @param array $donnees les données à ajouter au jeton (infos utilisateur)
	 * @param string $exp la date d'expiration du jeton, par défaut la date actuelle plus $this->dureeJeton
	 * 
	 * @return string un jeton JWT signé
	 */
	public function creerJeton($sub, $donnees=array(), $exp=null) {
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
		$jwt = $this->encoderJeton($jeton, $exp);

		return $jwt;
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
	public function identite(&$erreur='') {
		$cookieAvecJetonValide = false;
		$jetonRetour = null;
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
				if (! empty($_REQUEST['token'])) {
					$jwt = $_REQUEST['token'];
				}
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
		return $jetonRetour;
	}

	/**
	 * Essaye de trouver un jeton JWT non vide dans l'entête HTTP $nomHeader (par
	 * défaut "Authorization")
	 * 
	 * @param string $nomHeader nom de l'entête dans lequel chercher le jeton
	 * @return String un jeton JWT ou null
	 */
	public function lireJetonDansHeader($nomHeader="Authorization") {
		$jwt = null;
		$headers = apache_request_headers(); // @TODO redéfinir pour utilisation dans nginx par ex.
		if (isset($headers[$nomHeader]) && ($headers[$nomHeader] != "")) {
			$jwt = $headers[$nomHeader];
		}
		return $jwt;
	}

	/**
	 * Reçoit un jeton JWT, et s'il est non-vide ("sub" != null), lui redonne
	 * une période de validité de $this->dureeJeton; si $ignorerExpiration
	 * vaut true, rafraîchira le jeton même s'il a expiré
	 * (attention à ne pas appeler cette méthode n'importe comment : on ne doit
	 * pas rafraîchir un jeton expiré si l'utilisateur n'est pas en possession
	 * d'un cookie, par exemple);
	 * jette une exception si le jeton est vide, mal signé ou autre erreur,
	 * ou s'il a expiré et que $ignorerExpiration est différent de true
	 * 
	 * @param string $jwt le jeton JWT
	 * @return string le jeton rafraîchi
	 */
	public function rafraichirJeton($jwt, $ignorerExpiration=false) /* throws Exception */ {
		$infos = array();
		// vérification avec lib JWT
		try {
			$infos = $this->decoderJeton($jwt);
		} catch (ExpiredException $e) {
			if ($ignorerExpiration === true) { // on se fiche qu'il soit expiré
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
		$jwtSortie = $this->encoderJeton($infos);

		return $jwtSortie;
	}
}


/**
 * Compatibilité nginx / certaines versions de PHP (CGI)
 * merci http://php.net/manual/fr/function.getallheaders.php
 */
if (! function_exists('apache_request_headers')) {
	function apache_request_headers() {
		$headers = '';
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}
