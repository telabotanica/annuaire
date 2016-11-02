<?php
/**
 * Tentative de truc mieux que JRest
*/
class MiniRest /* == "Micro-sieste" :-) */ {

	const PO_HTTP_VERB_FIRST = "PO_HTTP_VERB_FIRST";
	const PO_RESOURCE_FIRST = "PO_RESOURCE_FIRST";

	/** How to process */
	protected $processingOrder = self::PO_HTTP_VERB_FIRST;

	/** HTTP verb received (GET, POST, PUT, DELETE, OPTIONS) */
	protected $verb;

	/** Parameters received */
	protected $params = array();

	/** Query string */
	protected $query;

	/** Base URL for request parser */
	protected $baseURL;

	public function __construct() {
		// database @TODO use lib
		$dsn = "mysql:host=venus;dbname=mchouet";
		$this->db = new PDO($dsn, "mchouet", "Mat87UM2");

		$this->verb = $_SERVER['REQUEST_METHOD'];
		//echo "Method: " . $this->verb . PHP_EOL;
		$requete = explode("/", substr(@$_SERVER['PATH_INFO'], 1));
		$requete2 = $_SERVER['QUERY_STRING'];
		echo $requete . "<br/>";
		echo $requete2 . "<br/>";

		// @TODO read from config
		$this->baseURL = "/~mchouet/pdcc/stor.php/";
		//echo "Base URL: " . $this->baseURL . PHP_EOL;

		$this->init();
		$this->getParams(); // @TODO maybe move to init() ?
		print_r($this->params);

		$this->run();
	}
	
	function retrouverInputData() {
		$input_fmt = "";
		$input = file_get_contents('php://input', 'r');	

		return json_decode($input, true);
	}
	
	function error($code, $texte) {
		http_response_code($code);
		echo $texte;
		exit;
	}

	/** Post-constructor adjustments */
	protected function init() {
	}

	/** Reads the request and runs the appropriate method */
	protected function run() {
		switch ($this->processingOrder) {
			case self::PO_HTTP_VERB_FIRST:
				switch($this->verb) {
					case "GET":
						$this->get();
						break;
					case "POST":
						$this->post();
						break;
					case "PUT":
						$this->put();
						break;
					case "DELETE":
						$this->delete();
						break;
					case "OPTIONS":
						$this->options();
						break;
					case "HEAD":
						$this->head();
						break;
					case "COPY":
						$this->copy();
						break;
					default:
						http_response_code(500);
						echo "unrecognized HTTP verb: $this->verb" . PHP_EOL;
				}
				break;
			case self::PO_RESOURCE_FIRST:
				// magic method based on 1st resource
				break;
			default:
				echo "unknown processing order: " . $this->processingOrder;
		}
	}
}

// Pour compenser d'éventuels manques des anciennes version de php
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