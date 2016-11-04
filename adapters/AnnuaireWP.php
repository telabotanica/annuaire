<?php

require_once __DIR__ . '/../AnnuaireAdapter.php';

/**
 * Implémentation de référence de l'Annuaire sur la base de données Wordpress
 */
class AnnuaireWP extends AnnuaireAdapter {

	public function idParCourriel($courriel) {
		throw new Exception("idParCourriel: pas encore implémenté");
	}

	public function getDateDerniereModifProfil($id) {
		throw new Exception("getDateDerniereModifProfil: pas encore implémenté");
	}

	public function inscrireUtilisateur($donneesProfil) {
		throw new Exception("inscrireUtilisateur: pas encore implémenté");
	}

	public function identificationCourrielMdp($courriel, $mdp) {
		return true;
		//throw new Exception("identificationCourrielMdp: pas encore implémenté");
	}

	public function identificationCourrielMdpHache($courriel, $mdpHache) {
		throw new Exception("identificationCourrielMdpHache: pas encore implémenté");
	}

	public function nbInscrits() {
		throw new Exception("nbInscrits: pas encore implémenté");
	}

	public function infosParIds($unOuPlusieursIds) {
		throw new Exception("infosParIds: pas encore implémenté");
	}

	public function infosParCourriels($unOuPlusieursCourriels) {
		throw new Exception("infosParCourriels: pas encore implémenté");
	}
}
