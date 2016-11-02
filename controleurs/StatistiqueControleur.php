<?php
/**
* PHP Version 5
*
* @category  PHP
* @package   annuaire
* @author    aurelien <aurelien@tela-botanica.org>
* @copyright 2010 Tela-Botanica
* @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
* @version   SVN: <svn_id>
* @link      /doc/annuaire/
*/

class StatistiqueControleur extends AppControleur {

	private $statistiques = null;

	private $champ_pays = '12';
	private $champ_rapport_activite_bota = '102';
	private $champ_experience_bota = '4';
	private $tab_mois = array('Jan','Fev','Mar','Avr','Mai','Juin','Juil','Aout','Sep','Oct','Nov','Dec');
	
	public function StatistiqueControleur() {
		$this->__construct();
		$this->statistiques = Composant::fabrique('statistiques', array());
	}
	
	public function obtenirStatistiquesInscritsParContinents($id_annuaire) {
		$cartographe = new CartoControleur();
		$annuaire_controleur = new AnnuaireControleur();
		
		$continents = array(
			'Afrique (%1.2f%%)' => 'pays_afrique',
			'Amerique du nord (%1.2f%%)' => 'pays_nord_amerique', 
			'Asie (%1.2f%%)' => 'pays_asie', 
			'Europe (%1.2f%%)' => 'pays_europe', 
			'Oceanie (%1.2f%%)' => 'pays_oceanie', 
			'Amerique du sud (%1.2f%%)' => 'pays_sud_amerique', 
			'Moyen Orient (%1.2f%%)' => 'pays_moyen_orient');
				
		// pour chacun des continents, on fait la somme des membres de sa zone
		foreach($continents as $id_continent => $continent) {
			$zones_continent_ids = $cartographe->chargerInformationsPaysDuContinentsCsv($continent);
			$zones_continent_ids = array_map(array($this,'miniQuote'), array_keys($zones_continent_ids));    		
			$nb_inscrits[$id_continent] = array_sum($annuaire_controleur->chargerNombreAnnuaireListeInscritsParPays($id_annuaire, $zones_continent_ids));
		}

		$graph = $this->statistiques->genererGraphique(Statistiques::GRAPH_CAMEMBERT,$nb_inscrits,'', array(650, 500));

		return $this->dessinerGraph($graph);
	}
	
	public function obtenirStatistiquesInscritsEurope($id_annuaire) {
		$cartographe = new CartoControleur();
		$annuaire_controleur = new AnnuaireControleur();
		$cartographe = new CartoControleur();
		
		$ids_zones_europe = $cartographe->chargerInformationsPaysDuContinentsCsv('pays_europe');
		
		$codes_zones_europe = array_map(array($this, 'miniQuote'), array_keys($ids_zones_europe));    

		$titre_zone = $this->convertirPourLegende($zone[2]);
		$nb_inscrits_par_code = $annuaire_controleur->chargerNombreAnnuaireListeInscritsParPays($id_annuaire, $codes_zones_europe);
		
		$nb_inscrits_par_legende = array();

		$inscrits_france = $nb_inscrits_par_code['fr'];

		unset($nb_inscrits_par_code['fr']);
		$somme_autres_pays = 0;

		foreach ($nb_inscrits_par_code as $code_pays => $inscrits_pays) {
			$label_pays = $this->convertirPourLegende($ids_zones_europe[$code_pays][2].' (%1.2f%%)');
			$nb_inscrits_par_legende[$label_pays] = $inscrits_pays;
			$somme_autres_pays += $inscrits_pays;
		}

		$tableau_france_autres = array('France (%1.2f%%)' => $inscrits_france, 'Autres (%1.2f%%)' => $somme_autres_pays);

		$graph_france = $this->statistiques->genererGraphique(Statistiques::GRAPH_CAMEMBERT, $tableau_france_autres, '', array(320, 200));
		$graph_autres = $this->statistiques->genererGraphique(Statistiques::GRAPH_CAMEMBERT, $nb_inscrits_par_legende, '', array(930, 900));
		$graph = $this->statistiques->combinerGraphiques($graph_france, $graph_autres, array(940, 1110));
		
		return $this->dessinerGraph($graph);
	}

	public function obtenirStatistiquesParPays($id_annuaire) {
		$controleur = new AnnuaireControleur();

		//$controleur = new AnnuaireControleur();
		$modele_meta = new MetadonneeModele();
		$modele_onto = new OntologieModele();
		$id_champ = 12;
		$valeurs = $modele_meta->obtenirNombreValeurMetadonnee($id_champ);
		$id_onto = $modele_meta->obtenirOntologieLieeAChampParId($id_champ);
		$legendes = $modele_onto->chargerListeOntologie($id_onto);

		$valeurs_a_stat_code = array();
		$valeurs_a_stat_legende = array();

		foreach ($valeurs as $valeur) {
			$valeurs_a_stat_code[$valeur['amv_valeur']] = $valeur['nb'];
		}

		foreach ($legendes as $legende) {
			$legende_nom = $legende['amo_nom'];
			$legende_code = $legende['amo_id_ontologie'];

			if (isset($valeurs_a_stat_code[$legende_code])) {
				$valeurs_a_stat_legende[$legende_nom] = $valeurs_a_stat_code[$legende_code];
			}
		}

		$graph = $this->statistiques->genererGraphique(Statistiques::GRAPH_CAMEMBERT,$valeurs_a_stat_legende);
		return $this->dessinerGraph($graph);
	}

	public function obtenirStatistiquesParCritere($id_annuaire, $code_champ, $titre = '') {
		$modele_meta = new MetadonneeModele();
		$modele_onto = new OntologieModele();
		$id_champ = $modele_meta->renvoyerIdChampMetadonneeParAbreviation($id_annuaire, $code_champ);
		$valeurs = $modele_meta->obtenirNombreValeurMetadonnee($id_champ);
		$id_onto = $modele_meta->obtenirOntologieLieeAChampParId($id_champ);
		$legendes = $modele_onto->chargerListeOntologie($id_onto);

		$valeurs_a_stat_code = array();
		$valeurs_a_stat_legende = array();

		$titre = $this->convertirPourLegende($titre);

		foreach ($valeurs as $valeur) {
			$valeurs_a_stat_code[$valeur['amv_valeur']] = $valeur['nb'];
		}

		foreach ($legendes as $legende) {
			$legende_nom = $this->convertirPourLegende($legende['amo_nom']);
			$legende_code = $legende['amo_id_ontologie'];

			if (isset($valeurs_a_stat_code[$legende_code])) {
				$valeurs_a_stat_legende[$legende_nom] = $valeurs_a_stat_code[$legende_code];
			}
		}

		$graph = $this->statistiques->genererGraphique(Statistiques::GRAPH_CAMEMBERT,$valeurs_a_stat_legende, $titre, array(650, 400));
		return $this->dessinerGraph($graph);
	}

	public function obtenirStatistiquesPourAnnee($id_annuaire, $annee = null) {
		$annee = ($annee == null) ? date("Y") : $annee; 
		$annuaire_modele = new AnnuaireModele();
		
		$valeurs_a_stat_code = array();
		$valeurs_a_stat_legende = array();
		
		$annee_debut = $annee;
		$mois = 1;
		$annee = $annee;
		$tps_debut = mktime(0,0,0,1,1,$annee);
		$tps_courant = $tps_debut;
		
		$annee_courante = date("Y");
		if ($annee_fin == $annee_courante) {
			$tps_fin = time();// jour courant
		} else {
			$tps_fin = mktime(0,0,0,1,1,$annee+1);			
		}
		
		//Requete par mois
		$i = 1;
		while ($tps_courant <= $tps_fin) {
			if ($mois/12 > 1) {
				$mois = 1;
				$annee = $annee+1;
			}
				
			$tps_mois_suivant = mktime(0,0,0,$mois+1,1,$annee);
			$nb_inscrits_dans_intervalle = $annuaire_modele->obtenirNombreInscriptionsDansIntervalleDate($id_annuaire, $tps_debut, $tps_courant);	
			$valeurs_a_stat_legende[$this->tab_mois[$mois-1].' '.$annee] = $nb_inscrits_dans_intervalle;
				
			$tps_courant = $tps_mois_suivant;
			$mois++;
			$i++;
		}
		
		$nom_axeX = 'Mois depuis le 1er janvier '.$annee;
		
		$graph = $this->statistiques->genererGraphique(Statistiques::GRAPH_COURBE,$valeurs_a_stat_legende, '', array(500,490), $nom_axeX, '');
		return $this->dessinerGraph($graph);
	}

	public function obtenirStatistiquesParAnnees($id_annuaire, $annee_fin = '') {
		$annuaire_modele = new AnnuaireModele();

		$valeurs_a_stat_code = array();
		$valeurs_a_stat_legende = array();

		$annee_debut = 2002;
		$mois = 4;
		$annee = 2002;
		$tps_debut = mktime(0,0,0,$mois,1,$annee);
		$tps_courant = $tps_debut;

		if ($annee_fin != '') {
			$tps_fin = mktime(0,0,0,1,1,$annee_fin);
		} else {
			$tps_fin = time();// jour courant
		}

		//Requete par mois
		$i = 1;
		while ($tps_courant <= $tps_fin) {
			if (($mois)/12 > 1) {
				$mois = 1;
				$annee = $annee+1;
			}

			$tps_mois_suivant = mktime(0,0,0,$mois+1,1,$annee);

			$nb_inscrits_dans_intervalle = $annuaire_modele->obtenirNombreInscriptionsDansIntervalleDate($id_annuaire, $tps_debut, $tps_courant);

			$valeurs_a_stat_legende[$this->tab_mois[$mois-1].' '.$annee] = $nb_inscrits_dans_intervalle;

			$tps_courant = $tps_mois_suivant;
			$mois++;
			$i++;
		}

		$nom_axeX = 'Mois depuis le 1er avril 2002'; 

		$graph = $this->statistiques->genererGraphique(Statistiques::GRAPH_COURBE,$valeurs_a_stat_legende, '', array(500,490), $nom_axeX, '');
		return $this->dessinerGraph($graph);
	}
	
	public function obtenirStatistiquesModificationsProfil($id_annuaire, $annee_fin = '') {
		$modele = new StatistiqueModele();

		$valeurs_a_stat_code = array();
		$valeurs_a_stat_legende = array();

		$annee_debut = 2010;
		$mois = 1;
		$annee = 2010;
		$tps_debut = mktime(0,0,0,$mois,1,$annee);
		$tps_courant = $tps_debut;

		if($annee_fin != '') {
			$tps_fin = mktime(0,0,0,1,1,$annee_fin);
		} else {
			$today = date_parse(date('Y-m-d H:i:s'));

			$annee_fin_today = $today['year'];
			$mois_fin_today = $today['month'];

			if($annee_debut == $annee_fin_today) {
				$tps_fin = mktime(0,0,0,$mois_fin_today+1,1,$annee_fin_today);
			} else {
				$tps_fin = time();// jour courant
			}
		}

		//Requete par mois
		$i = 1;
		while ($tps_courant <= $tps_fin) {
			if (($mois)/12 > 1) {
				$mois = 1;
				$annee = $annee+1;
			}

			$tps_mois_suivant = mktime(0,0,0,$mois+1,1,$annee);

			$nb_modif_dans_intervalle = $modele->obtenirEvenementsDansIntervalle($id_annuaire,'modification', $tps_debut, $tps_courant);

			$valeurs_a_stat_legende[$this->tab_mois[$mois-1].' '.$annee] = $nb_modif_dans_intervalle;

			$tps_courant = $tps_mois_suivant;
			$mois++;
			$i++;
		}

		$nom_axeX = 'Mois depuis le 1er juillet 2010'; 

		$graph = $this->statistiques->genererGraphique(Statistiques::GRAPH_COURBE,$valeurs_a_stat_legende, '', array(500,490), $nom_axeX, '');
		return $this->dessinerGraph($graph);
	}
	
	public function ajouterEvenementStatistique($id_annuaire, $id_utilisateur, $type) {
		$this->chargerModele('StatistiqueModele');	
		$this->StatistiqueModele->ajouterEvenementStatistique($id_annuaire, $id_utilisateur, $type);
		
	}
	
	public function obtenirDerniersEvenementsStatistique($id_annuaire, $type) {
		$this->chargerModele('StatistiqueModele');	
		return $this->StatistiqueModele->obtenirDerniersEvenementsStatistique($id_annuaire, $type);
		
	}
	
	private function dessinerGraph($graph) {
		return $this->statistiques->dessinerGraph($graph);
	}
	
	private function convertirPourLegende($texte) {
		if (trim($texte) == '') {
			$texte = '' ;
		}

		$texte = str_replace(
			array(
				'à', 'â', 'ä', 'á', 'ã', 'å',
				'î', 'ï', 'ì', 'í', 
				'ô', 'ö', 'ò', 'ó', 'õ', 'ø', 
				'ù', 'û', 'ü', 'ú', 
				'é', 'è', 'ê', 'ë', 
				'ç', 'ÿ', 'ñ',
				'À', 'Â', 'Ä', 'Á', 'Ã', 'Å',
				'Î', 'Ï', 'Ì', 'Í', 
				'Ô', 'Ö', 'Ò', 'Ó', 'Õ', 'Ø', 
				'Ù', 'Û', 'Ü', 'Ú', 
				'É', 'È', 'Ê', 'Ë', 
				'Ç', 'Ÿ', 'Ñ',
			),
			array(
				'a', 'a', 'a', 'a', 'a', 'a', 
				'i', 'i', 'i', 'i', 
				'o', 'o', 'o', 'o', 'o', 'o', 
				'u', 'u', 'u', 'u', 
				'e', 'e', 'e', 'e', 
				'c', 'y', 'n', 
				'A', 'A', 'A', 'A', 'A', 'A', 
				'I', 'I', 'I', 'I', 
				'O', 'O', 'O', 'O', 'O', 'O', 
				'U', 'U', 'U', 'U', 
				'E', 'E', 'E', 'E', 
				'C', 'Y', 'N', 
			),$texte);
		
		return $texte;
	}
	
	private function miniQuote($chaine) {
		return "'".$chaine."'";
	}
}
?>