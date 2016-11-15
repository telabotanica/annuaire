<?php

//require_once "config.php";

$actions = array("forger_jeton");

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
	case "forger_jeton":
		forger_jeton($argc, $argv);
		break;
	default:
		throw new Exception('une action déclarée dans $actions devrait avoir un "case" correspondant dans le "switch"');
}

function forger_jeton() {
	echo "coucou le jeton forgé !";
}