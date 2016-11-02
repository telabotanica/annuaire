<?php
/**
* La fonction __autoload() charge dynamiquement les classes trouvées dans le code.
*
* Cette fonction est appelée par php5 quand il trouve une instanciation de classe dans le code.
*
*@param string le nom de la classe appelée.
*@return void le fichier contenant la classe doit être inclu par la fonction.
*/
function __autoloadJRest($classe)
{
	if (class_exists($classe)) {
		return null;
	}

	$chemins = array('', 'services/', 'bibliotheque/');
	foreach ($chemins as $chemin) {
		$chemin = $chemin.$classe.'.php';
		if (file_exists($chemin)) {
			require_once $chemin;
		}
	}
}

spl_autoload_register('__autoloadJRest');

require_once('../initialisation.php');

$jRest = new JRest();
$jRest->exec();
?>