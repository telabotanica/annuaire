<?php
/**
 * Encodage en entrée : utf8
 * Encodage en sortie : utf8
 * 
 * @author Grégoire Duché <jpm@tela-botanica.org>
 * @license GPL v3 <http://www.gnu.org/licenses/gpl.txt>
 * @license CECILL v2 <http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt>
 * @version $Id$
 * @copyright 2009
 */

class TelaUtilisateurs extends JRestService {
	
	/**
	 * Méthode appelée quand aucun paramêtre n'est passée dans l'url et avec une requête de type GET.
	 */
	public function getRessource() {
		$this->getElement(array());
	}
	
	public function getElement($params = array())	{
		
		$id_annuaire = Config::get('annuaire_defaut');
		if(isset($uid[0])) {
			$id_annuaire = $uid[0]; 
		}

		$controleur = new AnnuaireControleur();
		$nb_inscrits = $controleur->chargerNombreAnnuaireListeInscrits($id_annuaire);
			
		$info[] = $nb_inscrits;

		//TODO externaliser ceci
		$en_ligne = file_get_contents('/home/telabotap/www/nb_sessions_active.json');
		$info[] = json_decode($en_ligne);
		
		$this->envoyer($info);
	}
}
?>