<?php

interface AnnuaireInterface {

	//

	public function idParCourriel($courriel);
	public function getDateDerniereModifProfil($id);
	public function inscrireUtilisateur($donneesProfil);

	// -------------- rétrocompatibilité (11/2016) -------------------

	public function testLoginMdp($courriel, $mdpHache);
	public function nbInscrits();
	public function infosParids($unOuPlusieursIds);
	public function infosParCourriels($unOuPlusieursCourriels);
}
