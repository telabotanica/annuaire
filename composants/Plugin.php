<?php
class Plugin extends AppControleur {
	
	private static $instance = null;
	
	public $plugin = '';	

	public static function initialiser($plugin, $options = array()) {
		
		define('ANNUAIRE_PLUGIN', $plugin);
		define('CHEMIN_PLUGIN_EN_COURS',dirname(__FILE__).DS.$plugin);
								
		// Autoload pour ce plugin
		function autoload_plugin($nom_classe) {
		    // Tableau des chemins à inclure pour trouver une classe relative à ce fichier
		    $chemins = array(
		        ANNUAIRE_PLUGIN.DS.'controleurs',
		    	ANNUAIRE_PLUGIN.DS.'modeles',
		    	ANNUAIRE_PLUGIN.DS.'configurations',
				ANNUAIRE_PLUGIN.DS.'bibliotheque'
		    );
		    
		    foreach ($chemins as $chemin) {
		        $fichier_a_inclure = dirname(__FILE__).DS.$chemin.DS.$nom_classe.'.php';
		
		        if (file_exists($fichier_a_inclure)) {
		        	
		            include_once $fichier_a_inclure;
		            return null;
		        }
		    }
		}
		
		if(is_dir(dirname(__FILE__).DS.$plugin)) {
			spl_autoload_register('autoload_plugin');
			Config::parserFichierIni(dirname(__FILE__).DS.$plugin.DS.'configurations'.DS.'config.ini');
		}
	}
}
?>