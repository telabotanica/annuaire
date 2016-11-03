<?php
/**
 * Classe mère abstraite contenant les méthodes génériques des services.
 * Encodage en entrée : utf8
 * Encodage en sortie : utf8
 *
 * @author Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @license GPL v3 <http://www.gnu.org/licenses/gpl.txt>
 * @license CECILL v2 <http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt>
 * @version $Id$
 * @copyright 2009
 */
abstract class JRestService {

	public $config;
	protected $bdd;
	protected $ressources;
	protected $log = array();
	protected $messages = array();
	protected $debug = array();
	protected $distinct = false;
	protected $orderby = null;
	protected $formatRetour = 'objet';
	protected $start = 0;
	protected $limit = 150;

	/** pour l'envoi de XML : éventuelle balise dans laquelle placer tout le contenu */
	protected $baliseMaitresse;

	public function __construct($config, $demarrer_session = true) {
		// Tableau contenant la config de Jrest
		$this->config = $config;

		// Connection à la base de données
		$this->bdd = $this->connecterPDO($this->config, 'appli');

		// Nettoyage du $_GET (sécurité)
		if (isset($_GET)) {
			$get_params = array('orderby', 'distinct', 'start', 'limit', 'formatRetour');
			foreach ($get_params as $get) {
				$verifier = array('NULL', "\n", "\r", "\\", "'", '"', "\x00", "\x1a", ';');
				if (isset($_GET[$get])) {
					$_GET[$get] = str_replace($verifier, '', $_GET[$get]);
					if ($_GET[$get] != '') {
						$this->$get = $_GET[$get];
					}
				} else {
					$_GET[$get] = null;
				}
			}
		}
	}

	/**
	 * Méthode appelée quand aucun paramètre n'est passé dans l'url et avec une requête de type GET.
	 */
	public function getRessource() {
		$this->getElement(array());
	}

	//+----------------------------------------------------------------------------------------------------------------+
	// GESTION de l'ENVOI au NAVIGATEUR pas la PEINE de CRIER

	protected function envoyerJson($donnees, $encodage = 'utf-8') {
		$contenu = json_encode($donnees);
		$this->envoyer($contenu, 'application/json', $encodage, false);
	}

	/** à l'arrache pour rétrocompatibilité avec le service "annuaire_tela" de eFlore_chatin */
	protected function envoyerXml($donnees, $encodage = 'utf-8') {
		$xml = '<?xml version="1.0" encoding="' . strtoupper($encodage) . '"?>';
		if ($this->baliseMaitresse) {
			$xml .= '<' . $this->baliseMaitresse . '>';
		}
		$xml .= $this->genererXmlAPartirDeTableau($donnees);
		if ($this->baliseMaitresse) {
			$xml .= '</' . $this->baliseMaitresse . '>';
		}
		$this->envoyer($xml, 'application/xml', $encodage, false);
	}

	/**
	 * Génère un XML minimaliste à partir d'un tableau associatif
	 * Note : gère mal les indices numériques
	 * @TODO utiliser une vraie lib 
	 */ 
	protected function genererXmlAPartirDeTableau($tableau) {
		$xml = '';
		foreach ($tableau as $balise => $donnee) {
			$xml .= '<' . $balise . '>';
			if (is_array($donnee)) {
				// récurer, balayer, que ce soit toujours pimpant
				$xml .= $this->genererXmlAPartirDeTableau($donnee);
			} else {
				$xml .= $donnee;
			}
			$xml .= '</' . $balise . '>';
		}
		return $xml;
	}

	protected function envoyerJsonVar($variable, $donnees = null, $encodage = 'utf-8') {
		$contenu = "var $variable = ".json_encode($donnees);
		$this->envoyer($contenu, 'text/html', $encodage, false);
	}

	protected function envoyerJsonp($donnees = null, $encodage = 'utf-8') {
		$contenu = $_GET['callback'].'('.json_encode($donnees).');';
		$this->envoyer($contenu, 'text/html', $encodage, false);
	}

	protected function envoyerTxt($donnees, $encodage = 'utf-8') {
		$this->envoyer($contenu, 'text/html', $encodage, false);
	}

	protected function envoyer($donnees = null, $mime = 'text/html', $encodage = 'utf-8', $json = true) {
		// Traitements des messages d'erreurs et données
		if (count($this->messages) != 0) {
			header('HTTP/1.1 500 Internal Server Error');
			$mime = 'text/html';
			$encodage = 'utf-8';
			$json = true;
			$sortie = $this->messages;
		} else {
			$sortie = $donnees;
			if (is_null($donnees)) {
				$sortie = 'OK';
			}
		}

		// Gestion de l'envoie du déboguage
		$this->envoyerDebogage();

		// Encodage au format et JSON et envoie sur la sortie standard
		$contenu = $json ? json_encode($sortie) : $sortie;
		$this->envoyerContenu($encodage, $mime, $contenu);
	}

	private function envoyerDebogage() {
		if (!is_array($this->debug)) {
			$this->debug[] = $this->debug;
		}
		if (count($this->debug) != 0) {
			foreach ($this->debug as $cle => $val) {
				if (is_array($val)) {
					$this->debug[$cle] = print_r($val, true);
				}
			}
			header('X-DebugJrest-Data:'.json_encode($this->debug));
		}
	}

	private function envoyerContenu($encodage, $mime, $contenu) {
		if (!is_null($mime) && !is_null($encodage)) {
			header("Content-Type: $mime; charset=$encodage");
		} else if (!is_null($mime) && is_null($encodage)) {
			header("Content-Type: $mime");
		}
		print $contenu;
	}

	private function envoyerAuth($message_accueil, $message_echec) {
		header('HTTP/1.0 401 Unauthorized');
		header('WWW-Authenticate: Basic realm="'.mb_convert_encoding($message_accueil, 'ISO-8859-1', 'UTF-8').'"');
		header('Content-type: text/plain; charset=UTF-8');
		print $message_echec;
		exit(0);
	}

	protected function envoyerMessageErreur($msg, $code) {
		$textHttp = $this->getCodeHttpText($code);
		header("HTTP/1.0 $code $textHttp");
		header("Content-Type: text/plain; charset=utf-8");
		die($msg);
	}

	private function getCodeHttpText($code) {
		$text = '';
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
			default:
				exit('Unknown http status code "' . htmlentities($code) . '"');
			break;
		}
		return $text;
	}

	//+----------------------------------------------------------------------------------------------------------------+
	// GESTION de la BASE de DONNÉES

	private function connecterPDO($config, $base = 'database') {
  		$cfg = $config[$base];
		// ATTENTION : la connexin à la bdd peut échouer si l'host vaut localhost. Utiliser 127.0.0.1 à la place.
		$dsn = $cfg['phptype'].':dbname='.$cfg['database'].';host='.$cfg['hostspec'];
		try {
		// Création de la connexion en UTF-8 à la BDD
			$PDO = new PDO($dsn, $cfg['username'], $cfg['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));
		} catch (PDOException $e) {
			echo 'La connexion à la base de donnée via PDO a échouée : ' .$dsn. $e->getMessage();
		}
		// Affiche les erreurs détectées par PDO (sinon mode silencieux => aucune erreur affiché)
		$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $PDO;
	}

	protected function getTxt($id) {
		$sortie = '';
		switch ($id) {
			case 'sql_erreur' : $sortie = 'Requête echec. Fichier : "%s". Ligne : "%s". Message : %s'; break;
			default : $sortie = $id;
		}
		return $sortie;
	}

	//+----------------------------------------------------------------------------------------------------------------+
	// TRAITEMENT des URLs et des PARAMÊTRES

	protected function traiterNomMethodeGet($nom) {
		$methode = 'get';
		$methode .= str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($nom))));
		return $methode;
	}

	protected function traiterNomMethodePost($nom) {
		$methode = 'update';
		$methode .= str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($nom))));
		return $methode;
	}

	protected function traiterNomMethodePut($nom) {
		$methode = 'create';
		$methode .= str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($nom))));
		return $methode;
	}

	protected function traiterParametresUrl($params_attendu, $params, $pourBDD = true) {
		$sortie = array();
		foreach ($params_attendu as $num => $nom) {
			if (isset($params[$num]) && $params[$num] != '*') {
				if ($pourBDD) {
					$params[$num] = $this->bdd->quote($params[$num]);
				}
				$sortie[$nom] = $params[$num];
			}
		}
		return $sortie;
	}

	protected function traiterParametresPost($params) {
		$sortie = array();
		foreach ($params as $cle => $valeur) {
			$sortie[$cle] = $this->bdd->quote($valeur);
		}
		return $sortie;
	}

	//+----------------------------------------------------------------------------------------------------------------+
	// GESTION DE L'IDENTIFICATION

	public function controlerIpAutorisees() {
		$ipsAutorisees = $this->config['jrest_admin']['ip_autorisees'];

		$remoteIp = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
		$serverIp = filter_input(INPUT_SERVER, 'SERVER_ADDR', FILTER_VALIDATE_IP);
		if (in_array($remoteIp, $ipsAutorisees) == false) {
			if ($remoteIp != $serverIp) {// ATTENTION : maintenir ce test à l'intérieur du précédent
				$message = "Accès interdit. \n".
					"Vous n'êtes pas autorisé à accéder à ce service depuis '$remoteIp' !\n";
				$this->envoyerMessageErreur($message, 401);
			}
		}
		return true;
	}

	protected function getIdentification(&$params) {
		// Initialisation des variables
		$utilisateur = array(0, session_id());

		// L'id utilisateur est soit passé par le POST soit dans l'url
		if (is_array($params) && isset($params['cmhl_ce_modifier_par'])) {
		   	$utilisateur[0] = $params['cmhl_ce_modifier_par'];
			unset($params['cmhl_ce_modifier_par']);
		} else if (is_string($params)) {
			$utilisateur[0] = $params;
		}

		return $utilisateur;
	}

	protected function etreAutorise($id_utilisateur) {
		$autorisation = false;
		if (($_SESSION['coel_utilisateur'] != '') && $_SESSION['coel_utilisateur']['id'] != $id_utilisateur) {
			$this->messages[] = 'Accès interdit.';
		} else if ($_SESSION['coel_utilisateur'] == '') {
			$this->messages[] = 'Veuillez vous identifiez pour accéder à cette fonction.';
		} else {
			$autorisation = true;
		}
		return $autorisation;
	}

	// WTF coel en dur ??
	private function gererIdentificationPermanente() {
		// Pour maintenir l'utilisateur tjrs réellement identifié nous sommes obligé de recréer une SESSION et de le recharger depuis la bdd
		if ($this->getUtilisateur() == ''
				&& isset($_COOKIE['coel_login'])
				&& ($utilisateur = $this->chargerUtilisateur($_COOKIE['coel_login'], $_COOKIE['coel_mot_de_passe']))) {
			$this->setUtilisateur($utilisateur, $_COOKIE['coel_permanence']);
		}
	}

	// WTF coel en dur ??
	protected function getUtilisateur() {
		return (isset($_SESSION['coel_utilisateur']) ? $_SESSION['coel_utilisateur'] : '');
	}

	protected function authentifier() {
		// @TODO @WARNING @ACHTUNG @ ALARM enlever le patch CGI quand on aura mis à jour Apache/PHP !!
		if (JRest::$cgi === false) { // si on est en CGI, accès libre pour tous (pas trouvé mieux)
			if (!isset($_SERVER['PHP_AUTH_USER'])) {
				header('WWW-Authenticate: Basic realm="www.tela-botanica.org"');
				header('HTTP/1.0 401 Unauthorized');
				header('Content-type: text/html; charset=UTF-8');
				echo 'Accès interdit';
				exit;
			} else {
				if ($this->verifierAcces()) {
					return ;
				} else {
					header('WWW-Authenticate: Basic realm="www.tela-botanica.org"');
					header('HTTP/1.0 401 Unauthorized');
					header('Content-type: text/html; charset=UTF-8');
					echo 'Accès interdit';
					exit ;
				}
			}
		}
	}

	/**
	 * Vérifie l'accès en se basant sur $id et $mdp si ceux-ci sont fournis; sinon,
	 * lit les valeurs transmises par l'authentification HTTP BASIC AUTH
	 */
	protected function verifierAcces($id = null, $mdp = null) {
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

	/**
	 * Envoie une demande d'authentification HTTP puis compare le couple
	 * login / mot de passe envoyé par l'utilisateur, à ceux définis dans
	 * la config (section database_ident).
	 * En cas d'erreur, sort du programme avec un entête HTTP 401
	 * @TODO redondant avec les trucs du dessus :'(
	 */
	protected function authentificationHttpSimple() {
		$autorise = true;
		// contrôle d'accès @TODO @WARNING @ACHTUNG @ ALARM enlever le patch CGI quand on aura mis à jour Apache/PHP !!
		if (JRest::$cgi === false) { // si on est en CGI, accès libre pour tous (pas trouvé mieux)
			$nomUtil = $_SERVER['PHP_AUTH_USER'];
			$mdp = $_SERVER['PHP_AUTH_PW'];
			$autorise = (($nomUtil == $this->config['database_ident']['username']) && ($mdp == $this->config['database_ident']['password']));
		}
		// entêtes HTTP
		if (! $autorise) {
			header('WWW-Authenticate: Basic realm="Annuaire de Tela Botanica"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'Veuillez vous authentifier pour utiliser ce service';
			exit;
		}
	}

	protected function creerCookiePersistant($duree = null, $id = null, $mdp = null) {
		$id = is_null($id) ? $_SERVER['PHP_AUTH_USER'] : $id;
		$mdp = is_null($mdp) ? $_SERVER['PHP_AUTH_PW'] : $mdp;
		$duree = (int) is_null($duree) ? time()+3600*24*30 : $duree;

		$nomCookie = $this->config['database_ident']['nom_cookie_persistant'];
		$valeurCookie = md5($mdp).$id;

		setcookie($nomCookie, $valeurCookie, $duree, '/');
	}

	protected function creerCookieUtilisateur($duree = null, $id = null, $mdp = null) {
		$id = is_null($id) ? $_SERVER['PHP_AUTH_USER'] : $id;
		$mdp = is_null($mdp) ? $_SERVER['PHP_AUTH_PW'] : $mdp;
		$duree = (int) is_null($duree) ? 0 : $duree;

		$nomCookie = $this->config['database_ident']['nom_cookie_utilisateur'];
		$valeurCookie = md5($mdp).$id;

		setcookie($nomCookie, $valeurCookie, $duree, '/');
	}
	
	protected function supprimerCookieUtilisateur() {
		session_destroy();		
		setcookie($this->config['database_ident']['nom_cookie_utilisateur'], "", time()-7200, "/");
		setcookie($this->config['database_ident']['nom_cookie_persistant'], "", time()-7200, "/");
	}

	protected function estAutoriseMessagerie($adresse) {
		$utilisateurs_messagerie = explode(',', $this->config['messagerie']['utilisateurs_autorises']);
		return in_array($adresse, $utilisateurs_messagerie);
	}

	//+----------------------------------------------------------------------------------------------------------------+
	// GESTION DES SQUELETTES PHP

	/**
	 * Méthode prenant en paramètre un chemin de fichier squelette et un tableau associatif de données,
	 * en extrait les variables, charge le squelette et retourne le résultat des deux combinés.
	 *
	 * @param String $fichier	le chemin du fichier du squelette
	 * @param Array  $donnees	un tableau associatif contenant les variables a injecter dans le squelette.
	 *
	 * @return boolean false si le squelette n'existe pas, sinon la chaine résultat.
	 */
	public static function traiterSquelettePhp($fichier, Array $donnees = array()) {
		$sortie = false;
		if (file_exists($fichier)) {
			// Extraction des variables du tableau de données
			extract($donnees);
			// Démarage de la bufferisation de sortie
			ob_start();
			// Si les tags courts sont activés
			if ((bool) @ini_get('short_open_tag') === true) {
				// Simple inclusion du squelette
				include $fichier;
			} else {
				// Sinon, remplacement des tags courts par la syntaxe classique avec echo
				$html_et_code_php = self::traiterTagsCourts($fichier);
				// Pour évaluer du php mélangé dans du html il est nécessaire de fermer la balise php ouverte par eval
				$html_et_code_php = '?>'.$html_et_code_php;
				// Interprétation du html et du php dans le buffer
				echo eval($html_et_code_php);
			}
			// Récupèration du contenu du buffer
			$sortie = ob_get_contents();
			// Suppression du buffer
			@ob_end_clean();
		} else {
			$msg = "Le fichier du squelette '$fichier' n'existe pas.";
			trigger_error($msg, E_USER_WARNING);
		}
		// Retourne le contenu
		return $sortie;
	}

	/**
	 * Fonction chargeant le contenu du squelette et remplaçant les tags court php (<?= ...) par un tag long avec echo.
	 *
	 * @param String $chemin_squelette le chemin du fichier du squelette
	 *
	 * @return string le contenu du fichier du squelette php avec les tags courts remplacés.
	 */
	private static function traiterTagsCourts($chemin_squelette) {
		$contenu = file_get_contents($chemin_squelette);
		// Remplacement de tags courts par un tag long avec echo
		$contenu = str_replace('<?=', '<?php echo ',  $contenu);
		// Ajout systématique d'un point virgule avant la fermeture php
		$contenu = preg_replace("/;*\s*\?>/", "; ?>", $contenu);
		return $contenu;
	}

	/**
	 * Crée un nom Wiki (de la forme "JeanTalus") à partir des données de l'utilisateur;
	 * gère l'utilisation du pseudo mais pas la collision de noms Wiki @TODO s'en occuper
	 * 
	 * @param array $infos des infos de profil utilisateur - on admet qu'elles contiennent "intitule"
	 * @return string un nom wiki correspondant à l' "intitulé" de l'utilisateur (prénom-nom ou pseudo)
	 * 		ou la valeur par défaut de $defaut si celui-ci est fourni et si le nom Wiki n'a pu être construit
	 */
	public function formaterNomWiki($intitule, $defaut="ProblemeNomWiki") {
		$nw = $this->convertirEnCamelCase($intitule);
		// on sait jamais
		if ($nw == "") {
			$nw = $defaut;
		}

		return $nw;
	}
	
	protected function convertirEnCamelCase($str) {
		// Suppression des accents
		$str = $this->supprimerAccents($str);
		// Suppression des caractères non alphanumériques
		$str = preg_replace('/[^\da-z]/i', '', ucwords(strtolower($str)));
		return $str;
	}

	protected function supprimerAccents($str, $charset='utf-8') {
		$str = htmlentities($str, ENT_NOQUOTES, $charset);

		$str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
		$str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

		return $str;
	}
}
?>