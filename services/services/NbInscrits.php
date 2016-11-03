<?php
// declare(encoding='UTF-8');
/**
 * Service 
 *
 * @category	php 5.2
 * @package		Annuaire::Services
 * @author		Aurélien PERONNET <aurelien@tela-botanica.org>
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version		$Id$
 */
class NbInscrits extends JRestService {

	public function getRessource() {
		$this->getElement(array());
	}
	
	public function getElement($uid){
		$id_annuaire = Config::get('annuaire_defaut');
		
		if (isset($uid[0])) {
			$id_annuaire = $uid[0]; 
		}
		
		$json = true;
		if (isset($uid[1]) && $uid[1] == 'html') {
			$json = false;
		} 

	    $controleur = new AnnuaireControleur();
		$valeurs = $controleur->chargerNombreAnnuaireListeInscrits($id_annuaire);
		
		if (!$json) {
			$valeurs = 
			'<html>'."\n".
			'	</head>'."\n".
			'		<meta content="text/html; charset='.Config::get('sortie_encodage').'" http-equiv="Content-Type">'."\n".
			'	</head>'."\n".
			'	<body>'."\n".
			'		<div id="contenu">'.$valeurs.'</div>'."\n".
			'	</body>'."\n".
			'</html>';
		} 

		$this->envoyer($valeurs, 'text/html', Config::get('sortie_encodage'), $json);
	}
}
?>