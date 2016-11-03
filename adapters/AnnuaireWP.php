<?php

require_once 'AnnuaireInterface.php';

/**
 * Implémentation de référence de l'Annuaire sur la base de données Wordpress
 */
class AnnuaireWP implements AnnuaireInterface {

	public function testLoginMdp($courriel, $mdpHache) {
		throw new Exception("pas encore implémenté");
	}

	public function nbInscrits() {
		throw new Exception("pas encore implémenté");
	}

	public function infosParIds($unOuPlusieursIds) {
		throw new Exception("pas encore implémenté");
	}

	public function infosParCourriels($unOuPlusieursCourriels) {
		throw new Exception("pas encore implémenté");
	}
}
