<?php

// composer
require_once '../vendor/autoload.php';

/** Chemin du fichier de clef - à synchroniser avec le service Auth ! */
const CHEMIN_CLEF_AUTH = "../config/clef-auth.ini";

$actions = array("forger_jeton_admin");

function usage() {
	global $argv;
	global $actions;
	echo "Utilisation: " . $argv[0] . " action\n";
	echo "\t" . "action: " . implode(" | ", $actions) . "\n";
	exit;
}

if ($argc < 2 || !in_array($argv[1], $actions)) {
	usage();
}

$action = $argv[1];
// arguments de l'action : tout moins le nom du script et le nom de l'action
array_shift($argv);
array_shift($argv);
$argc -= 2;

// action en fonction du 1er argument de la ligne de commande
switch($action) {
	case "forger_jeton_admin":
		forger_jeton_admin($argc, $argv);
		break;
	default:
		throw new Exception('une action déclarée dans $actions devrait avoir un "case" correspondant dans le "switch"');
}

function forger_jeton_admin() {
	// jeton longue durée
	$exp = strtotime('2050-01-15'); // le goût qui dure jusqu'à Vladivostok
	// signature compatible avec le service Auth
	$clef = file_get_contents(CHEMIN_CLEF_AUTH);
	// données "admin"
	$sub = 'admin@tela-botanica.org';
	$jeton = array(
		"iss" => "https://www.tela-botanica.org",
		"token_id" => 'tb_auth_admin_token',
		"sub" => $sub,
		"iat" => time(),
		"exp" => $exp,
		"scopes" => array("tela-botanica.org"), // @TODO scopes explicites
		"id" => 0,
		"prenom" => "TB",
		"nom" => "Admin",
		"pseudo" => "",
		"pseudoUtilise" => false,
		"intitule" => "TB Admin",
		"nomWiki" => "TBAdmin",
		"dateDerniereModif" => 0,
		
	);
	$jwt = JWT::encode($jeton, $clef);

	// sortie polie
	echo " note : ce jeton donne TOUS LES POUVOIRS, PARTOUT, jusqu'en 2050 - ne JAMAIS laisser traîner !" . PHP_EOL;
	echo "  sub : $sub" . PHP_EOL;
	echo "jeton : $jwt" . PHP_EOL;
}