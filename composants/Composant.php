<?php

class Composant {

	public static function fabrique($classe, $options = array()) {
		$classe_nom = implode('', array_map('ucfirst', explode('_', $classe)));
		require_once dirname(__FILE__).DS.$classe.DS.$classe_nom.'.php';
		$Composant = new $classe_nom($options);
		return $Composant;
	}
	
}
?>