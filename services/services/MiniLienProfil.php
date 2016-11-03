<?php
// declare(encoding='UTF-8');
/**
 * Service renvoyant un lien vers le profil d'un utilisateur sous la forme de son nom'
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
class MiniLienProfil extends JRestService {

	public function getRessource(){
		if(!isset($_COOKIE['pap-annuaire_tela-utilisateur']) && isset($_COOKIE['pap-annuaire_tela-memo'])) {
			$_COOKIE['pap-annuaire_tela-utilisateur'] = $_COOKIE['pap-annuaire_tela-memo'];
		}

		if (isset($_COOKIE['pap-annuaire_tela-utilisateur'])) {

			$username = $_COOKIE['pap-annuaire_tela-utilisateur'];
			// le cookie de papyrus contient un md5 concaténé à l'email utilisateur
			$username = substr($username , 32, strlen($username));
			$controleur = new AnnuaireControleur();
			$valeurs = $controleur->obtenirInfosUtilisateur('1', $username, true);
			
			$nom_affiche_lien = $valeurs['fullname'];
			
			$tableau_nom_prenom = split(" ", $nom_affiche_lien, 2);
			
			if(strlen($nom_affiche_lien) > 20) {
				$nom_affiche_lien = substr($nom_affiche_lien,0,20).'...';
			}
			
			$lien = 'Bienvenue <a href="http://www.tela-botanica.org/page:mon_inscription_au_reseau"> '.ucwords(strtolower($nom_affiche_lien)).'</a>';
			echo json_encode($lien);
		} else  {
			$lien = '';
			echo json_encode($lien);
		}
	}
}
?>