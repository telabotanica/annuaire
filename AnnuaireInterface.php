<?php

/**
 * Liste des méthodes publiques de l'annuaire
 * @TODO documenter proprement la liste des champs que l'annuaire doit retourner
 */
interface AnnuaireInterface {

	//

	public function idParCourriel($courriel);
	public function getDateDerniereModifProfil($id);
	public function inscrireUtilisateur($donneesProfil);

	// -------------- rétrocompatibilité (11/2016) -------------------

	public function identificationCourrielMdpHache($courriel, $mdpHache);
	public function identificationCourrielMdp($courriel, $mdp);
	public function nbInscrits();
	public function infosParids($unOuPlusieursIds);
	public function infosParCourriels($unOuPlusieursCourriels);
}
