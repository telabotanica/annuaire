<?php
// In : utf8 url_encoded (get et post)
// Out : utf8

// TODO : gerer les retours : dans ce controleur : code retour et envoi ...
class JRest {

 	/** Parsed configuration file */
    private $config;

	/** The HTTP request method used. */
	private $method = 'GET';

	/** The HTTP request data sent (if any). */
	private $requestData = NULL;

	/** Array of strings to convert into the HTTP response. */
	private $output = array();

	/** Nom resource. */
	private $resource = NULL;

	/** Identifiant unique resource. */
	private $uid = NULL;

	/** True si le type d'api est CGI / FastCGI, false si on a un module Apache... ou autre ? */
	public static $cgi;

	/**
	 * Constructor. Parses the configuration file "JRest.ini", grabs any request data sent, records the HTTP
	 * request method used and parses the request URL to find out the requested resource
	 * @param str iniFile Configuration file to use
	 */
	public function JRest($iniFile = 'jrest.ini.php') {
		$this->config = parse_ini_file($iniFile, TRUE);
		if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD']) && isset($_SERVER['QUERY_STRING'])) {
			if (isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
				$this->requestData = '';
				$httpContent = fopen('php://input', 'r');
				while ($data = fread($httpContent, 1024)) {
					$this->requestData .= $data;
				}
				fclose($httpContent);
			}
			if (strlen($_SERVER['QUERY_STRING']) == 0) {
				$len = strlen($_SERVER['REQUEST_URI']);
			} else {
				$len = -(strlen($_SERVER['QUERY_STRING']) + 1);
			}

			$urlString = '';
			if  (substr_count($_SERVER['REQUEST_URI'], $this->config['settings']['baseURL']) > 0) {
				$urlString = substr($_SERVER['REQUEST_URI'], strlen($this->config['settings']['baseURL']), $len);
			} else if (substr_count($_SERVER['REQUEST_URI'], $this->config['settings']['baseAlternativeURL']) > 0) {
				$urlString = substr($_SERVER['REQUEST_URI'], strlen($this->config['settings']['baseAlternativeURL']), $len);
			}
			$urlParts = explode('/', $urlString);

			if (isset($urlParts[0])) $this->resource = $urlParts[0];
			if (count($urlParts) > 1 && $urlParts[1] != '') {
				array_shift($urlParts);
				foreach ($urlParts as $uid) {
					if ($uid != '') {
						$this->uid[] = urldecode($uid);
					}
				}
			}

			// détection du type d'API : CGI ou module Apache - le CGI ne permet pas
			// d'utiliser l'authentification HTTP Basic :-(
			self::$cgi = substr(php_sapi_name(), 0, 3) == 'cgi';

			$this->method = $_SERVER['REQUEST_METHOD'];
		} else {
			trigger_error('I require the server variables REQUEST_URI, REQUEST_METHOD and QUERY_STRING to work.', E_USER_ERROR);
		}
	}

	/**
	 * Execute the request.
	 */
	function exec() {
		switch ($this->method) {
			case 'GET':
				$this->get();
				break;
			case 'POST':
				$this->post();
				break;
			case 'DELETE':
				$this->delete();
				break;
			case 'PUT':
				$this->add();
				break;
		}
	}

	/**
	 * Execute a GET request. A GET request fetches a list of resource when no resource name is given, a list of element
	 * when a resource name is given, or a resource element when a resource and resource unique identifier are given. It does not change the
	 * database contents.
	 */
	private function get() {
		if ($this->resource) {
			$resource_file = 'services/'.ucfirst($this->resource).'.php';
			$resource_class = ucfirst($this->resource);
			if (file_exists($resource_file))  {
				include_once $resource_file;
				if (class_exists($resource_class)) {
					$service = new $resource_class($this->config);
					if ($this->uid) { // get a resource element
						if (method_exists($service, 'getElement')) {
							$service->getElement($this->uid);
						}
					} elseif (method_exists($service, 'getRessource')) { // get all elements of a ressource
						$service->getRessource();
					}
				}
			}
		} else { // get resources
			// include set.jrest.php, instanticiation et appel
		}
	}

	private function post() {
	   	$pairs = array();
		// Récupération des paramètres passés dans le contenu de la requête HTTP (= POST)
	   	if ($this->requestData) {
			$pairs = $this->parseRequestData();
		}

		// Ajout des informations concernant l'upload de fichier passées dans la variable $_FILE
		if(isset($_FILES)) {
			foreach ($_FILES as $v) {
				$pairs[$v['name']] = $v;
			}

			// Ne pas effacer cette ligne ! Elle est indispensable pour les services du Carnet en ligne
			// qui n'utilisent que le tableau pairs dans les posts
			$pairs = array_merge($pairs, $_POST);
		}

		// gestion du contenu du post
		if(isset($_POST))
		{
			// Safari ne sait pas envoyer des DELETE avec gwt...
			// Nous utilisons le parametre "action" passé dans le POST qui doit contenir DELETE pour lancer la supression
			if ($pairs['action'] == 'DELETE') {
				$this->delete();
				return;
			}

			if (count($pairs) != 0) {
				if ($this->uid) { // get a resource element
					$resource_file = 'services/'.ucfirst($this->resource).'.php';
					$resource_class = ucfirst($this->resource);
					if (file_exists($resource_file)) {
						include_once $resource_file;
						if (class_exists($resource_class)) {
							$service = new $resource_class($this->config);
							if (method_exists($service,'updateElement')) { // Update element
								// TODO : a voir le retour ...
								if ($service->updateElement($this->uid, $pairs)) {
									$this->created();
								}
							}
						}
					}
				} else { // get all elements of a ressource
					$this->add($pairs);
				}
			} else {
				$this->lengthRequired();
			}
		}
	}

	private function delete() {
		$resource_file = 'services/'.ucfirst($this->resource).'.php';
		$resource_class = ucfirst($this->resource);
		if (file_exists($resource_file)) {
			include_once $resource_file;
			if (class_exists($resource_class)) {
				$service = new $resource_class($this->config);
				if ($this->uid) { // get a resource element
		 			if (method_exists($service, 'deleteElement')) { // Delete element
						if ($service->deleteElement($this->uid)) {
							$this->noContent();
						}
	 				}
				}
			}
		}
	}

	private function add($pairs = null) {
		if (is_null($pairs)) {
			$pairs = array();
			// Récupération des paramètres passés dans le contenu de la requête HTTP (= POST)
			// FIXME : vérifier que l'on récupère bien les données passées par PUT
		   	if ($this->requestData) {
				$pairs = $this->parseRequestData();
			}
		}

		if (count($pairs) != 0) {
			$resource_file = 'services/'.ucfirst($this->resource).'.php';
			$resource_class = ucfirst($this->resource);
			if (file_exists($resource_file)) {
				include_once $resource_file;
				if (class_exists($resource_class)) {
					$service = new $resource_class($this->config);
					if (method_exists($service,'createElement')) { // Create a new element
						if ($service->createElement($pairs)) {
							$this->created();
						}
					}
				}
			}
		} else {
			$this->lengthRequired();
		}
	}

	/**
	 * Parse the HTTP request data.
	 * @return str[] Array of name value pairs
	 */
	private function parseRequestData() {
		$values = array();
		$pairs = explode('&', $this->requestData);
		foreach ($pairs as $pair) {
			$parts = explode('=', $pair);
			if (isset($parts[0]) && isset($parts[1])) {
				$parts[1] = rtrim(urldecode($parts[1]));
				$values[$parts[0]] = $parts[1];
			}
		}
		return $values;
	}

	/**
	 * Send a HTTP 201 response header.
	 */
	private function created($url = FALSE) {
		header('HTTP/1.0 201 Created');
		if ($url) {
			header('Location: '.$url);
		}
	}

	/**
	 * Send a HTTP 204 response header.
	 */
	private function noContent() {
		header('HTTP/1.0 204 No Content');
	}

	/**
	 * Send a HTTP 400 response header.
	 */
	private function badRequest() {
		header('HTTP/1.0 400 Bad Request');
	}

	/**
	 * Send a HTTP 401 response header.
	 */
	private function unauthorized($realm = 'JRest') {
		if (self::$cgi === false) { // si on est en CGI, accès libre pour tous (pas trouvé mieux)
			if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
				header('WWW-Authenticate: Basic realm="'.$realm.'"');
			}
			header('HTTP/1.0 401 Unauthorized');
		}
	}

	/**
	 * Send a HTTP 404 response header.
	 */
	private function notFound() {
		header('HTTP/1.0 404 Not Found');
	}

	/**
	 * Send a HTTP 405 response header.
	 */
	private function methodNotAllowed($allowed = 'GET, HEAD') {
		header('HTTP/1.0 405 Method Not Allowed');
		header('Allow: '.$allowed);
	}

	/**
	 * Send a HTTP 406 response header.
	 */
	private function notAcceptable() {
		header('HTTP/1.0 406 Not Acceptable');
		echo join(', ', array_keys($this->config['renderers']));
	}

	/**
	 * Send a HTTP 411 response header.
	 */
	private function lengthRequired() {
		header('HTTP/1.0 411 Length Required');
	}

	/**
	 * Send a HTTP 500 response header.
	 */
	private function internalServerError() {
		header('HTTP/1.0 500 Internal Server Error');
	}
}
?>