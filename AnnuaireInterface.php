<?php

/**
 * Liste des méthodes publiques de l'annuaire
 * @TODO documenter proprement la liste des champs que l'annuaire doit retourner
 */
interface AnnuaireInterface {

	// -------------- méthodes modernes ----------------------------------------

	public function idParCourriel($courriel);
	public function courrielParId($id);
	public function courrielParLogin($login);
	public function verifierCourrielOuConvertirDepuisLogin($courrielOuLogin);
	public function getDateDerniereModifProfil($id);
	public function inscrireUtilisateur($donneesProfil);
	public function getAllRoles();

	// -------------- rétrocompatibilité (11/2016) -----------------------------

	public function identificationCourrielMdpHache($courriel, $mdpHache);
	public function identificationCourrielMdp($courriel, $mdp);
	public function nbInscrits();
	public function infosParids($unOuPlusieursIds);
	public function infosParCourriels($unOuPlusieursCourriels);
	public function envoyerMessage($destinataire, $sujet, $contenu);
}
