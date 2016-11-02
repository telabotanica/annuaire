<?php
// declare(encoding='UTF-8');
/**
 * classe Controleur du module Carte.
 *
 * @package	 Collection
 * @category	Php5
 * @author	  Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @author	  Aurélien Peronnet <aurelien@tela-botanica.org>
 * @copyright   2010 Tela-Botanica
 * @license	 http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	 http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 */
class CartoControleur extends AppControleur {

	// identifiant de la france pour l'accès direct
	private $id_france = 'fr';

	// nom du fond de carte en cours
	private $nom_fond = '';

	private $niveau = 0;

	// contient le tableau de données sur les continents une fois chargé
	private $donnees_continents = array();

	// contient le tableau de données sur les pays une fois chargé
	private $donnees_pays = array();

	// contient le tableau de données sur les departements une fois chargé
	private $donnees_departements = array();

	// contient le nombre total d'inscrits dans la zone en cours de consultation
	private $total_inscrits_zone = 0;

	//+----------------------------------------------------------------------------------------------------------------+
	// Méthodes

	/**
	 * Fonction d'affichage par défaut, elle appelle la cartographie
	 */
	public function executerActionParDefaut() {
		return $this->cartographier(1);
	}

	/**
	 * Cartographier un annuaire.
	 * @param int $id_annuaire l'identitifiant de l'annuaire à cartographier
	 * @param int $continent l'identitifiant du continent sur lequel on se trouve
	 * @param string $pays l'identitifiant du pays sur lequel on se trouve (normalement seulement la france si présent)
	 * @return string la vue correspondante
	 */
	public function cartographier($id_annuaire, $continent= null , $pays = null) {
		// Initialisation de variable
		$donnees = array();

		// la présence d'un pays (non) et d'un continent (ou non) détermine le niveau de carte à afficher
		$this->niveau = $this->calculerNiveau($continent, $pays);

		// suivant le niveau, continent et pays, on renvoie un template html différent
		$fond = $this->renvoyerPrefixePourNiveau($this->niveau, $continent, $pays);

		$carte = '';

		// chaque continent possède un fond de carte différent
		if ($this->niveau == 1) {
			$carte = $this->renvoyerSuffixePourContinent($this->niveau, $continent, $pays);
		}

		// Création de la carte
		$options = array(
			'carte_nom' => $fond.$carte,
			'formule' => Cartographie::FORMULE_PROPORTIONNEL,
			'couleur_claire' => Config::get('carte_couleur_claire'),
			'couleur_foncee' => Config::get('carte_couleur_foncee'),
			'fond_fichier' => Config::get('carte_base_nom_'.$fond).$carte,
			'fond_dossier' => Application::getChemin().Config::get('carte_fonds_chemin'),
			'stock_dossier' => Config::get('carte_stockage_chemin'),
			'stock_url' => Config::get('carte_stockage_url'),
			'debug' => Config::get('carte_mode_debug'));
		$cartographie = Composant::fabrique('cartographie', $options);

		$this->nom_fond = Config::get('carte_base_nom_'.$fond).$carte;

		// Construction des données nécessaires à la cartographie
		$zones = $cartographie->getCarteZones();
		$this->chargerZonesNbre($id_annuaire,$zones, $this->niveau);
		$this->chargerZonesUrls($id_annuaire, $zones, $continent, $pays, $this->niveau);

		$navigation = new NavigationControleur();
		$donnees_navigation = $this->obtenirUrlsNavigation($id_annuaire, $continent, $pays, null);
		$donnees['infos_pays'] = $donnees_navigation;
		$donnees['navigation'] = $navigation->afficherBandeauNavigationCartographie($donnees_navigation);
		$donnees['nb_resultats'] = $this->total_inscrits_zone;

		$cartographie->setCarteZones($zones);

		$cartographie->creerCarte();
		$donnees['map'] = $cartographie->getImageMap();

		$resultat = $this->getVue('cartes/'.$fond, $donnees);
		return $resultat;
	}

	/**
	 * Charge le nombre d'inscrit par zone pour un annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param array $zones les zones à cartographier (attention, passage par référence, donc les zones sont modifiées)
	 * @param int $niveau le niveau de la carto (monde, continent, ou pays)
	 */
	private function chargerZonesNbre($id_annuaire, &$zones, $niveau = 0) {
		$metaModele = $this->getModele('AnnuaireModele');
		// on charge les inscrits pour le niveau donné
		$zones_infos = $this->chargerNombreInscritsParNiveauGeographique($id_annuaire, $niveau);

		foreach ($zones as $id => &$infos) {
			// si l'on a des données pour la zone, on renseigne le nombre d'inscrits
			if (isset($zones_infos[$id])) {
				$nbre = $zones_infos[$id];
				$infos['info_nombre'] = $nbre;
				$this->total_inscrits_zone += $nbre;
			} else {
				// sinon on le met à 0
				$infos['info_nombre'] = 0;
			}
		}
	}

	/**
	 * Charge les des zones pour un annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param array $zones les zones à cartographier (attention, passage par référence, donc les zones sont modifiées)
	 * @param int $continent l'identitifiant du continent sur lequel on se trouve
	 * @param string $pays l'identitifiant du pays sur lequel on se trouve (normalement seulement la france si présent)
	 * @param int $niveau le niveau de la carto (monde, continent, ou pays)
	 */
	private function chargerZonesUrls($id_annuaire, &$zones, $continent = null, $pays = null, $niveau = 0) {

		$url = new URL(Config::get('url_base'));

		$url->setVariableRequete('id_annuaire', $id_annuaire);

		foreach ($zones as $id => &$infos) {

			switch ($niveau) {
				// niveau 0 de la carte : on affiche tous les continents
				// l'url va pointer vers un continent en particulier
				case 0:
					$url->setVariableRequete('m', 'annuaire_afficher_carte');
					$url->setVariableRequete('continent', $id);
				break;

				// niveau 1, on est sur un continent en particulier : on affiche le détail du continent demandé
				// l'url pointe sur des pays
				case 1:
					$url->setVariableRequete('continent', $continent);

					// si le pays c'est la france alors l'url pointera vers la carte des départements
					if($id == $this->id_france) {
						$url->setVariableRequete('m', 'annuaire_afficher_carte');
					} else {
						// sinon l'url pointe vers la liste des inscrits de ce pays
						$url->setVariableRequete('m', 'annuaire_inscrits_carto');
					}
					$url->setVariableRequete('pays', $id);

				break;

				// niveau 2, si on a cliqué sur la france pour afficher les départements :
				case 2:
					$url->setVariableRequete('m','annuaire_inscrits_carto');
					$url->setVariableRequete('continent', $continent);
					$url->setVariableRequete('pays', $pays);
					$url->setVariableRequete('departement', $id);
				break;
			}
			$infos['url'] = sprintf($url, $id);
		}
	}

	/**
	 * Renvoie le niveau auquel on se trouve suivant la présence ou non de certains paramètres
	 * @param int $continent l'identitifiant du continent sur lequel on se trouve
	 * @param string $pays l'identitifiant du pays sur lequel on se trouve (normalement seulement la france si présent)
	 */
	private function calculerNiveau($continent, $pays) {

		// le niveau 0 c'est la carte de base
		$niveau = 0;

		// le niveau 1 on consulte un continent en particulier (ex. Amérique du Sud)
		if($continent != null) {
			$niveau = 1;
		}

		// le niveau 2 c'est un pays en particulier (ce cas là n'arrive que pour la france)
		if($pays != null) {
			$niveau = 2;
		}

		return $niveau;
	}

	/**
	 * Renvoie le type de template à utiliser suivant le niveau de certains paramètres
	 * @param int $niveau le niveau de la carto
	 * @return string le type de template
	 */
	private function renvoyerPrefixePourNiveau($niveau) {
		switch ($niveau) {
			case 0:
				$fond = 'continents';
			break;

			case 1:
				$fond = 'pays';
			break;

			case 2 :
				$fond = 'france';
			break;

			default:
				$fond = '';
			break;
		}

		return $fond;
	}

	/**
	 * Renvoie le suffixe de fond de carte à utiliser pour un continent donné
	 * @param int $niveau le niveau de la carto
	 * @param int $niveau l'identifiant de continent
	 * @return string le suffixe
	 */
	private function renvoyerSuffixePourContinent($niveau, $continent) {

		switch ($continent) {
			case 1:
				$carte = '_afrique';
			break;

			case 2:
				$carte = '_nord_amerique';
			break;

			case 3:
				$carte = '_asie';
			break;

			case 4:
				$carte = '_europe';
			break;

			case 5:
				$carte = '_oceanie';
			break;

			case 6:
				$carte = '_sud_amerique';
			break;

			case 7:
				$carte = '_moyen_orient';
			break;

			default:
				$carte = '';
			break;
		}

		return $carte;
	}

	/**
	 * renvoie tous les noms templates pour chaque zone du monde
	 * @return array un tableau associatif indexé par les identifiants de zone et contenant les noms de templates
	 */
	private function renvoyerTousTemplates() {
		return array(1 => 'pays_afrique', 2 => 'pays_nord_amerique', 3 => 'pays_asie', 4 => 'pays_europe', 5 => 'pays_oceanie', 6 => 'pays_sud_amerique', 7 => 'pays_moyen_orient');
	}

	/**
	 * Charge la liste des inscrits par zone pour un niveau géographique donné
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param int $niveau le niveau où l'on se situe
	 * @return array un tableau associatif indexé par les identifiants de zone et contenant le nombre d'inscrits pour chaque zone
	 */
	private function chargerNombreInscritsParNiveauGeographique($id_annuaire, $niveau) {
		if ($niveau == 0) {
			// si on est au niveau des continents
			$zones_ids = array();
			// il faut faire la somme des inscrits par zones géographique
			$templates = $this->renvoyerTousTemplates();
		} else {
			// sinon on appelle la fonction pour la zone demandée
			$zones_ids = $this->chargerZonesParCsv(Application::getChemin().Config::get('carte_fonds_chemin').$this->nom_fond);
		}

		$annuaire_controleur = new AnnuaireControleur();
		$nb_inscrits = array();
		switch ($niveau) {
			case 0 : // niveau de la carte du monde
				// pour chacun des continents, on fait la somme des membres de sa zone
				foreach ($templates as $id_continent => $template) {
					$zones_continent_ids = $this->chargerZonesParCsv(Application::getChemin().Config::get('carte_fonds_chemin').$template);
					$nb_inscrits[$id_continent] = array_sum($annuaire_controleur->chargerNombreAnnuaireListeInscritsParPays($id_annuaire, $zones_continent_ids));
				}
				break;
			case 1 : // niveau de la carte des pays d'un continent
				$nb_inscrits = $annuaire_controleur->chargerNombreAnnuaireListeInscritsParPays($id_annuaire, $zones_ids);
				break;
			case 2 : // détail d'un pays
				$nb_inscrits = $annuaire_controleur->chargerNombreAnnuaireListeInscritsParDepartement($id_annuaire);
				break;
		}
		return $nb_inscrits;
	}

	public function obtenirUrlsNavigation($id_annuaire ,$continent = null, $pays = null, $departement = null) {
		$url_carte_monde = new URL(Config::get('url_base'));
		$url_carte_monde->setVariableRequete('id_annuaire', $id_annuaire);
		$url_carte_monde->setVariableRequete('m', 'annuaire_afficher_carte');
		$donnees = array();
		$donnees['url_carte_monde'] =  $url_carte_monde;
		$donnees['nom_carte_monde'] =  'Carte du monde';

		if ($continent != null && trim($continent) != '') {
			$infos_continents = $this->chargerInformationsContinentCsv();
			$url_continent = new URL(Config::get('url_base'));
			$url_continent->setVariableRequete('id_annuaire', $id_annuaire);
			$url_continent->setVariableRequete('m', 'annuaire_afficher_carte');
			$url_continent->setVariableRequete('continent', $continent);
			$donnees['url_continent'] =  $url_continent;
			$donnees['nom_continent'] =  $infos_continents[$continent][2];
		}

		if ($pays != null && trim($pays) != '') {
			$templates_continents = $this->renvoyerTousTemplates();
			$infos_continents = $this->chargerInformationsPaysDuContinentsCsv($templates_continents[$continent]);
			$infos_pays = $infos_continents[$pays];
			$url_pays = new URL(Config::get('url_base'));
			$url_pays->setVariableRequete('id_annuaire', $id_annuaire);
			if ($pays == $this->id_france) {
				$url_pays->setVariableRequete('m', 'annuaire_afficher_carte');
			} else {
				// sinon l'url pointe vers la liste des inscrits de ce pays
				$url_pays->setVariableRequete('m', 'annuaire_inscrits_carto');
			}
			$url_pays->setVariableRequete('continent', $continent);
			$url_pays->setVariableRequete('pays', $pays);
			$donnees['url_pays'] =  $url_pays;
			$donnees['nom_pays'] =  $infos_pays[2];
		}

		if ($departement != null && trim($departement) != '') {
			$infos_departement = $this->chargerInformationsDepartementsFranceCsv();
			$url_departement = new URL(Config::get('url_base'));
			$url_departement->setVariableRequete('id_annuaire', $id_annuaire);
			$url_departement->setVariableRequete('m', 'annuaire_afficher_carte');
			$url_departement->setVariableRequete('continent', $continent);
			$url_departement->setVariableRequete('departement', $departement);
			$url_departement->setVariableRequete('pays', $pays);
			$donnees['url_departement'] =  $url_departement;
			$donnees['nom_departement'] =  $infos_departement[$departement][2];
		}

		$donnees['nb_resultats'] = $this->total_inscrits_zone;
		return $donnees;
	}

	public function chargerInformationsContinentCsv() {
		$nom_csv = Application::getChemin().Config::get('carte_fonds_chemin').'continents';
		return $this->chargerInformationsCompletesParCsv($nom_csv);
	}

	public function chargerInformationsPaysDuContinentsCsv($continent) {
		$nom_csv = Application::getChemin().Config::get('carte_fonds_chemin').$continent;
		return $this->chargerInformationsCompletesParCsv($nom_csv);
	}

	public function chargerInformationsDepartementsFranceCsv() {
		$nom_csv = Application::getChemin().Config::get('carte_fonds_chemin').'france';
		return $this->chargerInformationsCompletesParCsv($nom_csv);
	}

	public function chargerInformationsCompletesParCsv($nom_csv) {
		$fichier_csv = $nom_csv.'.csv';
		$infos = array();

		if (($handle = fopen($fichier_csv, 'r')) !== false) {
			$ligne = 0;
			while (($donnees = fgetcsv($handle, 1000, ',')) !== false) {
				if($ligne != 0 && trim($donnees[0]) != '') {
					$infos[$donnees[0]] = $donnees;
				}
				$ligne++;
			}
			fclose($handle);
		}
		return $infos;
	}

	/**
	 * Récupère les identifiants de zone dans un fichier csv donné
	 * @param string $nom_csv chemin vers le fichier csv (sans extension) qui contient les données
	 * @return array un tableau contenant les identifiants des zones
	 */
	private function chargerZonesParCsv($nom_csv) {
		$fichier_csv = $nom_csv.'.csv';
		$zones_id = array();
		if (($handle = fopen($fichier_csv, 'r')) !== false) {
			$ligne = 0;
			while (($donnees = fgetcsv($handle, 1000, ',')) !== false) {
				if($ligne != 0 && trim($donnees[0]) != '') {
					$zones_id[] = "'".$donnees[0]."'";
				}
				$ligne++;
			}
			fclose($handle);
		}
		return $zones_id;
	}
}