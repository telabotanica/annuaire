<?php
/**
* PHP Version 5
*
* @category  PHP
* @package   annuaire
* @author    aurelien <aurelien@tela-botanica.org>
* @copyright 2010 Tela-Botanica
* @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
* @version   SVN: <svn_id>
* @link      /doc/annuaire/
*/

// La fonction autolad doit être appelée avant tout autre chose dans l'application.
// Sinon, rien ne sera chargé.
// possibilité:
// $ ln -s ~/framework/branches/v0.2-buhl/framework/autoload.inc.php framework.php
require_once 'framework.php';
Application::setChemin(__FILE__);
Application::setInfo(Config::get('info'));
mb_internal_encoding(Config::get('appli_encodage'));
date_default_timezone_set(Config::get('fw_timezone'));

// Autoload pour cette application
function __autoload($nom_classe) {
    // Tableau des chemins à inclure pour trouver une classe relative à ce fichier
    $chemins = array(
    	'plugins',
        'composants',
        'composants'.DS.'cartographie',
        'composants'.DS.'openid');
    foreach ($chemins as $chemin) {
        $fichier_a_inclure = dirname(__FILE__).DS.$chemin.DS.$nom_classe.'.php';

        if (file_exists($fichier_a_inclure)) {
            include_once $fichier_a_inclure;
            return null;
        }
    }
}

?>