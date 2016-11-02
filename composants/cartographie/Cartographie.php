<?php
// declare(encoding='UTF-8');
/**
 * Composant Cartographie gérant les images représentant le fond de carte à insérer dans un fichier html contenant une
 * image map.
 * Avantage :
 *  - pas de base de données liée au composant (simplicité d'utilisation dans les applications)
 *  - facilite l'utilisation du Javascript et CSS pour intéragir avec la carte (affichage du nom des zones au survol)
 *  - l'application qui utilise le composant définie elle-même l'intéraction avec le clic sur une zone
 * Inconvénient :
 *  - l'utilisation d'une balise map alourdie la page à renvoyer
 *
 * Il est possible de créer des fond de carte en vraies couleurs
 * (16 millions de zones maxi sur la carte) ou en couleurs indexées (255 zones maxi sur la carte).
 * Les couleurs réservées et a ne pas utiliser pour créer l'image png de fond sont :
 * - le blanc (rvb : 255-255-255)
 * - le noir (rvb : 0-0-0)
 * - le gris (rvb : 128-128-128)
 * - le rouge (rvb : 255-0-0)
 * Pour activer le cache indiquer la date de dernière mise à jour des données servant à créer la carte de cette façon :
 * $Carte->setCarteInfo(array('donnees_date_maj' => $date_maj_donnees));
 *
 * @category	PHP5
 * @package		Collection
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	2010 Tela-Botanica
 * @license		GPL-v3 et CECILL-v2
 * @version		SVN:$Id$
 */

class Cartographie {
	/*** Constantes : ***/
	const FORMULE_PROPORTIONNEL = 'proportionnel';
	const FORMULE_LEGENDE = 'legende';

	//+----------------------------------------------------------------------------------------------------------------+
	/*** Attributs : ***/
	/**
	* L'image de la carte.
	* @var string l'image de la carte.
	*/
	private $carte;

	/**
	* Le nom du fichier contenant la carte sans l'extension.
	* @var string le nom du fichier de la carte.
	*/
	private $carte_nom;

	/**
	* @var string le chemin et le nom du fichier de l'image de la carte générée.
	*/
	private $carte_fichier;

	/**
	* Tableaux associatif contenant les informations sur la carte.
	* donnees_date_maj = date de dernière mise à jour des données servant à créer la carte, si plus récente que la carte
	* déjà créée getCarteCache renvoie false.
	*
	* @var array le tableau des infos sur la carte.
	*/
	private $carte_info = array();

	/**
	* Indique si la carte existe déjà et à besoin ou pas d'être créée.
	* @var bool true si la carte existe..
	*/
	private $carte_cache = false;

	/**
	* Le nom du fichier de la carte de fond.
	* @var string nom du fichier de la carte de fond.
	*/
	private $carte_fond_fichier;

	/**
	* Le nom du dossier contenant les cartes de fond.
	* @var string nom du dossier contenant les cartes de fond.
	*/
	private $carte_fond_dossier;

	/**
	* Le nom du dossier où stocker les cartes créer via la classe Cartographie.
	* @var string nom du dossier contenant les cartes créées.
	*/
	private $carte_stockage_dossier;

	/**
	* L'url correspondant au dossier où sont stockées les cartes crées via la classe Cartographie.
	* L'url est passé à la fonction sprintf est doit donc contennir %s afin d'indiquer l'endroite où ajouter le nom du
	* fichier de la carte.
	* @var string url des cartes créées.
	*/
	private $carte_stockage_url;

	/**
	* Format du tableau :
	* carte_zone est un tableau de tableaux associatifs.
	* Chaque zone de la carte doit avoir son entrée dans ce tableau. Le code de la zone sert de clé.
	* Chaque zone est représentée par :
	* - nom : (string)
	* 	le nom de la zone qui sera affiché dans l'attribut title de la balise map html.
	* - rvb_fond : (string) Exemple : 255-255-255.
	* 	les valeurs entre 0 et 255 séparées par des tirets (-) de la couleur de la zone sur la carte de fond
	* 	Ne pas utiliser le blanc (255-255-255) et utiliser le noir pour les contours (0-0-0).
	* - poly : (string)
	* 	les coordonnées pour la balise map html. Si une même zone à plusieurs polygones, les séparer par le
	* 	caractère pipe "|".
	* - info_nombre : (int) Exemple : nombre de personnes présentent dans un département.
	* 	nombre d'occurence dans cette zone.
	* - url : (string) l'url qui doit être appelée sur un clic dans la zone.
	* - rvb_carte : (string) Exemple : 255-255-255.
	* 	les valeurs entre 0 et 255 séparées par des tirets (-) de la couleur de remplacement dans le cas de la formule
	* 	de coloriage de type "légende".
	* @var array les informations sur les zones de la carte.
	*/
	private $carte_zones = null;

	/**
	* Tableau contenant la valeur RVB de la zone du fond de carte en clé et la valeur RVB venant la remplacer en valeur.
	* @var array valeur RVB de la zone du fond de carte en clé et valeur RVB venant la remplacer en valeur.
	*/
	private $carte_correspondance_couleurs = array();

	/**
	* La valeur RVB, sous forme de chaine de nombres séparées par des tirets (-), de la zone géographique à mettre en
	* surbrillance.
	* @var string la valeur RVB de la zone à repérer.
	*/
	private $zone_marker;

	/**
	* La formule de coloriage de la carte. Les formules disponibles sont : légende, proportionnel.
	* @var string la formule de coloriage.
	*/
	private $formule_coloriage;

	/**
	* Les valeurs RVB séparés par des virgules pour la couleur la plus foncée utilisée, entre autre, par la formule de
	* coloriage "proportionnel".
	* @var string les valeurs RVB séparées par des virgules.
	*/
	private $coloriage_couleur_max;

	/**
	* Les valeurs RVB séparés par des virgules pour la couleur la plus claire utilisée, entre autre, par la formule de
	* coloriage "proportionnel".
	* @var string les valeurs RVB séparées par des virgules.
	*/
	private $coloriage_couleur_min;

	/**
	* Contient le nombre de couleurs différentes utilisées par le coloriage pour créer l'image finale.
	* @var int le nombre de couleurs.
	*/
	private $coloriage_couleurs;

	/**
	* Contient le tableau des fréquences et des couleurs correspondantes.
	* @var array les frequences et leurs couleurs.
	*/
	private $coloriage_tableau_frequence = array();

	/**
	* Permet de savoir si la cartographie est en mode déboguage ou pas.
	* @var bool true si on est en mode débug, sinon false.
	*/
	private $mode_debug;

	//+----------------------------------------------------------------------------------------------------------------+
	/*** Constructeur : ***/
	public function __construct($options = array()) {
		// Initialisation de l'objet Cartographie
		$this->setCarteNom(isset($options['carte_nom']) ? $options['carte_nom'] : '');
		$this->setFormuleColoriage(isset($options['formule']) ? $options['formule'] : '');
		$this->setColoriageCouleurClaire(isset($options['couleur_claire']) ? $options['couleur_claire'] : '');
		$this->setColoriageCouleurFoncee(isset($options['couleur_foncee']) ? $options['couleur_foncee'] : '');
		$this->setCarteFondFichier(isset($options['fond_fichier']) ? $options['fond_fichier'] : '');
		$this->setCarteFondDossier(isset($options['fond_dossier']) ? $options['fond_dossier'] : '');
		$this->setCarteStockageDossier(isset($options['stock_dossier']) ? $options['stock_dossier'] : '');
		$this->setCarteStockageUrl(isset($options['stock_url']) ? $options['stock_url'] : '');
		$this->setCarteZones(isset($options['zones']) ? $options['zones'] : null);
		$this->setZoneMarker(isset($options['zone_marker']) ? $options['zone_marker'] : '');
		$this->setModeDebug(isset($options['debug']) ? $options['debug'] : false);
	}

	//+----------------------------------------------------------------------------------------------------------------+
	/*** Accesseur : ***/
	public function getTableauFrequence() {
		ksort($this->coloriage_tableau_frequence);
		return $this->coloriage_tableau_frequence;
	}

	public function getCarteCache() {
		// Gestion du cache
		if ($this->getCarteNom() != '') {
			$fichier_carte = $this->carte_stockage_dossier.$this->getCarteNom().'.png';
			if (file_exists($fichier_carte)) {
				//echo filemtime($fichier_carte).'-'.strtotime($this->carte_info['donnees_date_maj']);
				if (filemtime($fichier_carte) < strtotime($this->carte_info['donnees_date_maj'])) {
					$this->carte_cache = false;
				} else {
					$this->carte_cache = true;
				}
			}
		}
		return $this->carte_cache;
	}

	public function getCarteInfo() {
		return $this->carte_info;
	}
	public function setCarteInfo($ci) {
		$this->carte_info = $ci;
	}

	public function getColoriageCouleurClaire() {
		return $this->coloriage_couleur_min;
	}
	public function setColoriageCouleurClaire($ccmi) {
		$this->coloriage_couleur_min = $ccmi;
	}

	public function getColoriageCouleurFoncee() {
		return $this->coloriage_couleur_max;
	}
	public function setColoriageCouleurFoncee($ccma) {
		$this->coloriage_couleur_max = $ccma;
	}

	public function getFormuleColoriage() {
		return $this->formule_coloriage;
	}
	public function setFormuleColoriage($fc) {
		$this->formule_coloriage = $fc;
	}

	public function getCarteNom() {
		return $this->carte_nom;
	}
	public function setCarteNom($cn) {
		$this->carte_nom = $cn;
	}

	public function getCarteFichier() {
		return $this->carte_fichier;
	}
	public function setCarteFichier($cf) {
		$this->carte_fichier = $cf;
	}

	public function getCarteFondFichier() {
		return $this->carte_fond_fichier;
	}
	public function setCarteFondFichier($cff) {
		$this->carte_fond_fichier = $cff;
	}

	public function getCarteFondDossier() {
		return $this->carte_fond_dossier;
	}
	public function setCarteFondDossier($cfd) {
		$this->carte_fond_dossier = $cfd;
	}

	public function getCarteStockageDossier() {
		return $this->carte_stockage_dossier;
	}
	public function setCarteStockageDossier($csd) {
		$this->carte_stockage_dossier = $csd;
	}

	public function getCarteStockageUrl() {
		return $this->carte_stockage_url;
	}
	public function setCarteStockageUrl($csu) {
		$this->carte_stockage_url = $csu;
	}

	public function getCarteZones() {
		if (is_null($this->carte_zones)) {
			$this->chargerZones();
		}
		return $this->carte_zones;
	}
	public function setCarteZones($cz) {
		$this->carte_zones = $cz;
	}

	public function getZoneMarker() {
		return $this->zone_marker;
	}
	public function setZoneMarker($zm) {
		$this->zone_marker = $zm;
	}

	public function getModeDebug() {
		return $this->mode_debug;
	}
	public function setModeDebug($md) {
		$this->mode_debug = $md;
	}

	//+----------------------------------------------------------------------------------------------------------------+
	/*** Méthodes PUBLIQUES : ***/

	public function creerCarte() {

		// Création de la carte car aucun cache ou cache à vider
		$carte_fond_fichier = $this->carte_fond_dossier.$this->getCarteFondFichier().'.png';

		$this->carte = imagecreatefrompng($carte_fond_fichier);
		// Vérification que la création à fonctionnée
		if (!$this->carte) {
			// Une erreur est survenue : création d'une image blanche
			$this->carte = imagecreatetruecolor(520, 60);
			$bgc = imagecolorallocate($this->carte, 255, 255, 255);
			$tc  = imagecolorallocate($this->carte, 0, 0, 0);
			imagefilledrectangle($this->carte, 0, 0, 520, 60, $bgc);
			// Affichage d'un message d'erreur
			imagestring($this->carte, 1, 5, 5, "Erreur de chargement de l'image :", $tc);
			imagestring($this->carte, 1, 5, 15, $carte_fond_fichier, $tc);
		} else {
			// Nous construisons le tableau de correspondance entre les couleurs présente sur l'image de fond
			// et les couleurs qui doivent les remplacer.
			$this->construireCorrespondanceCouleur();

			// Nous lançons la création de la carte
			$this->colorierCarte();
		}

		// Nous chercons à créer une image indéxées en sortie
		if (imageistruecolor($this->carte) && $this->formule_coloriage != 'legende') {
			if ($this->coloriage_couleurs <= 253) {
				//imagetruecolortopalette(&$this->carte, false, ($this->coloriage_couleurs + 2));// + 2 car noir et blanc réservés.
			} else {
				// On force la création d'une palette... si cela pose problème ajouter un attribut permettant de désactiver
				// ce fonctionnement.
				imagetruecolortopalette($this->carte, false, 255);
			}
		}

		// Nous écrivons le fichier de la carte.
		if ($this->getCarteNom() == '') {
			$this->setCarteNom(md5($this->carte));
		}

		$fichier_image_carte = $this->carte_stockage_dossier.$this->getCarteNom().'.png';
		$this->setCarteFichier($fichier_image_carte);

		if(file_exists($fichier_image_carte)) {
			//echo 'suppression du fichier de carte : '.$fichier_html_carte;
			unlink($fichier_image_carte);
		}

		imagepng($this->carte, $this->getCarteFichier());
		return true;
	}

	public function getImageMap() {
		// Initialisation de variables
		$carte_map = '';

		// Gestion de l'image map
		$chemin_carte_map_fond = $this->getCarteFondDossier().$this->getCarteFondFichier().'.tpl.html';
		$chemin_carte_map = $this->getCarteStockageDossier().$this->getCarteNom().'.html';

		if(file_exists($chemin_carte_map)) {
			unlink($chemin_carte_map);
		}

		if (file_exists($chemin_carte_map)) {
			$carte_map = file_get_contents($chemin_carte_map);
		} else {
			$nom_carte_png = $this->getCarteNom().'.png';
			$chemin_carte_png = $this->getCarteStockageDossier().$nom_carte_png;
			$donnees['carte_url'] = sprintf($this->getCarteStockageUrl(), $nom_carte_png);
			$donnees['carte_alt'] = 'info';
			$donnees['zones'] = $this->getCarteZones();
			//Debug::printr($donnees);
			$carte_map = SquelettePhp::analyser($chemin_carte_map_fond, $donnees);
			if (!file_put_contents($chemin_carte_map, $carte_map)) {
				$e = "Écriture du fichier contenant le html de la carte impossible : $chemin_carte_map";
				trigger_error($e, E_USER_WARNING);
			}
		}

		return $carte_map;
	}

	//+----------------------------------------------------------------------------------------------------------------+
	/*** Méthodes PRIVÉES : ***/

	/**
	 * Charge en mémoire les données du fichier csv des zones géographique de la carte
	 */
	private function chargerZones() {
		$fichier_csv = $this->getCarteFondDossier().$this->getCarteFondFichier().'.csv';
		$zones = array();
		if (($handle = fopen($fichier_csv, 'r')) !== false) {
			$ligne = 1;
			$cles = array();
			while (($donnees = fgetcsv($handle, 1000, ',')) !== false) {
				$cle = array_shift($donnees);
				if ($ligne == 1) {
					// Ligne 1 : les noms des champs
					$cles = $donnees;

				} else {
					// Ligne > 1 : traitements des données
					$zones[$cle] = array_combine($cles, $donnees);
				}
				$ligne++;
			}
			fclose($handle);
		}
		$this->setCarteZones($zones);
	}

	private function construireCorrespondanceCouleur() {
		switch ($this->formule_coloriage) {
			case self::FORMULE_LEGENDE :
				$this->construireCorrespondanceCouleurLegende();
				break;
			case self::FORMULE_PROPORTIONNEL :
				$this->construireCorrespondanceCouleurProportionnel();
				break;
			default :
				$e = 	"Aucune formule de coloriage n'a été définie parmis : ".
						self::FORMULE_LEGENDE.' et '.self::FORMULE_PROPORTIONNEL.'. '.
						"Veuillez la définir avec la méthode setFormuleColoriage().";
				trigger_error($e, E_USER_ERROR);
		}
	}

	private function construireCorrespondanceCouleurProportionnel() {
		// Création d'un tableau contenant seulement les nombres d'information pour chaque zone.
		$tab_valeurs = array();
		foreach ($this->getCarteZones() as $cle => $valeur) {
			//Nous recherchons le minimum, le maximum et le la valeur médium juste au dessous du maximum.
			if (isset($valeur['info_nombre'])) {
				$tab_valeurs[] = $valeur['info_nombre'];
				if ($valeur['info_nombre'] == 0){
					//trigger_error($valeur['nom'], E_USER_NOTICE);
				}
			}
		}

		//Nombre d'entrées dans le tableau de valeurs non nulles :
		$valeurs_nbre = count($tab_valeurs);
		$valeurs_somme = array_sum($tab_valeurs);
		// Tabeau des fréquences trié de la plus petite à la plus grande clé.
		$tab_frequences = array_count_values($tab_valeurs);
		krsort($tab_frequences);
		//trigger_error(print_r($tab_frequences, true), E_USER_NOTICE);
		$frequences_nbre = count($tab_frequences);
		if ($valeurs_nbre > 0){
			// Nous trions le tableau dans l'ordre croissant :
			sort($tab_valeurs);
			// Nous récupérons la valeur la plus petite :
			$mini = $tab_valeurs[0];
			$maxi = $tab_valeurs[$valeurs_nbre - 1];
			$medium = isset($tab_valeurs[$valeurs_nbre - 2]) ? $tab_valeurs[$valeurs_nbre - 2] : 0;
			$moyenne = $valeurs_somme / $valeurs_nbre;
			$ecart_au_carre_moyen = 0;
			for ($i = 0; $i < $valeurs_nbre; $i++) {
				$ecart_au_carre_moyen += pow(($tab_valeurs[$i] - $moyenne), 2);
			}
			$variance = $ecart_au_carre_moyen / $valeurs_nbre;
			$ecart_type = round(sqrt($variance), 0);
			$moyenne = round($moyenne, 0);
			$variance = round($variance, 0);
		}

		// Calcul de l'écart moyen pour chaque élément R, V et B.
		list($r_min, $v_min, $b_min) = explode(',', $this->coloriage_couleur_max);
		list($r_max, $v_max, $b_max) = explode(',', $this->coloriage_couleur_min);
		$r_diff = $r_min - $r_max;
		$r_ecart_moyen = abs($r_diff / $frequences_nbre);

		$v_diff = $v_min - $v_max;
		$v_ecart_moyen = abs($v_diff / $frequences_nbre);

		$b_diff = $b_min - $b_max;
		$b_ecart_moyen = abs($b_diff / $frequences_nbre);

		// Pour chaque fréquence nous attribuons une couleur.
		$i = 1;
		foreach ($tab_frequences as $cle => $valeur){
			if ($cle == 0) {
				$this->coloriage_tableau_frequence[$cle] = '255-255-255';
			} else {
				$r = $r_min + round(($i * $r_ecart_moyen), 0);

				$v = $v_min + round(($i * $v_ecart_moyen), 0);
				$b = $b_min + round(($i * $b_ecart_moyen), 0);
				$this->coloriage_tableau_frequence[$cle] = $r.'-'.$v.'-'.$b;
			}
			$i++;
		}

		// Attribution du nombre de couleurs utilisé pour réaliser la carte
		$this->coloriage_couleurs = count(array_count_values($this->coloriage_tableau_frequence));
		//trigger_error('<pre>'.print_r($this->coloriage_couleurs, true).'</pre>', E_USER_ERROR);

		// Nous attribuons les couleurs à chaque zone géographique
		foreach ($this->getCarteZones() as $cle => $zg) {
			if ($this->getModeDebug() && !isset($zg['rvb_fond'])) {
				$e = "La zone ".print_r($zg, true).") ne possède pas de clé 'rvb_fond'.";
				trigger_error($e, E_USER_WARNING);
				continue;
			}
			if (isset($this->coloriage_tableau_frequence[$zg['info_nombre']])) {
				$this->carte_correspondance_couleurs[$zg['rvb_fond']] = $this->coloriage_tableau_frequence[$zg['info_nombre']];
			} else {
				$this->carte_correspondance_couleurs[$zg['rvb_fond']] = '128-128-128';
				if ($this->getModeDebug()) {
					$e = "La zone ".$zg['nom']." (".$zg['rvb_fond'].") ne possède pas de couleur RVB pour la remplir. ".
					 "La valeur 128-128-128 lui a été attribué.";
					trigger_error($e, E_USER_WARNING);
				}
			}
		}
	}

	private function construireCorrespondanceCouleurLegende() {
		$tab_couleurs = array();
		foreach ($this->getCarteZones() as $cle => $zg) {
			if ($zg['rvb_carte'] != '') {
				$this->carte_correspondance_couleurs[$zg['rvb_fond']] = $zg['rvb_carte'];
			} else {
				$this->carte_correspondance_couleurs[$zg['rvb_fond']] = '128-128-128';
				if ($this->getModeDebug()) {
					$e = "La zone ".$zg['nom']." (".$zg['rvb_fond'].") ne possède pas d'information pour la légende dans le champ".
					 " rvb_carte. La valeur 128-128-128 lui a été attribué.";
					trigger_error($e, E_USER_WARNING);
				}
			}
			if (!isset($tab_couleurs[$this->carte_correspondance_couleurs[$zg['rvb_fond']]])) {
				$tab_couleurs[$this->carte_correspondance_couleurs[$zg['rvb_fond']]] = 1;
			}
		}
		// Attribution du nombre de couleurs utilisé pour réaliser la carte
		$this->coloriage_couleurs = count($tab_couleurs);
	}

	private function colorierCarte() {
		if (imageistruecolor($this->carte)) {
			//+--------------------------------------------------------------------------------------------------------+
			// Remplacement des couleurs sur la carte en mode vraies couleurs (RGB)
			$this->colorierCarteModeVraiCouleur();
		} else {
			//+--------------------------------------------------------------------------------------------------------+
			// Remplacement des couleurs sur la carte en mode couleurs indexées (palette de couleurs)
			$this->colorierCarteModeIndexe();
		}
	}

	private function colorierCarteModeVraiCouleur() {
		// Nous commençons le rempalcement des couleurs sur la carte de fond.
		$hauteur = imagesy($this->carte);
		$largeur = imagesx($this->carte);

		// Tableau contenant les couleurs traitées, pour éviter de traiter plusieurs fois la même couleur
		$tab_rvb_ok = array();
		for ($x = 0; $x < $largeur; $x++) {
			for ($y = 0; $y < $hauteur; $y++) {
				$rvb = ImageColorAt($this->carte, $x, $y);
				if (!isset($tab_rvb_ok[$rvb])) {
	   				// Récupération de la couleur rvb au format xxx-xxx-xxx
	   				$cle = (($rvb >> 16) & 0xFF).'-'.(($rvb >> 8) & 0xFF).'-'.($rvb & 0xFF);
	   				// Si nous n'avons pas à faire à la couleur noire (utilisé pour délimité les zones), nous continuons
	   				if ($cle != '255-255-255') {
		   				$rvb_final = null;
		   				if (isset($this->carte_correspondance_couleurs[$cle])) {
		   					if ($this->zone_marker != '' && $cle == $this->zone_marker) {
								$rvb_final = '255'<<16 | '0'<<8 | '0';
							} else {
		   						list($rouge, $vert, $bleu) = explode('-', $this->carte_correspondance_couleurs[$cle]);
		   						$rvb_final = $rouge<<16 | $vert<<8 | $bleu;
		   					}
		   					// Si le nombre de couleurs sur la carte finale est infèrieur à 255 nous créons une image indexée
		   					imagefill($this->carte, $x, $y, $rvb_final);
		   				} else {
		   					$rvb_final = '128'<<16 | '128'<<8 | '128';
		   					imagefill($this->carte, $x, $y, $rvb_final);
		   				}
	   					// Nous ajoutons la couleur ajoutée à la carte dans le tableau des couleurs traitées
	   					$tab_rvb_ok[$rvb_final] = true;
	   				}
	   				// Nous ajoutons la couleur trouvées sur la carte de fond dans le tableau des couleurs traitées
	   				$tab_rvb_ok[$rvb] = true;
				}
			}
		}
	}

	private function colorierCarteModeIndexe() {
		// Nous attribuons à chaque zone présente dans le tableau $this->getCarteZones() la valeur de l'index
		// de la couleur RVB représentant cette zone sur la carte de fond.
		$this->construireAssociationIndexZone();

		foreach ($this->getCarteZones() as $zg) {
			if (isset($this->carte_correspondance_couleurs[$zg['rvb_fond']])) {

				//Dans le cas où nous voulons repérer une zone sur la carte :
				if ($this->getZoneMarker() != '' && $zg['rvb_fond'] == $this->getZoneMarker()) {
					$rouge = 255;
					$vert = 0;
					$bleu = 0;
				} else {
					list($rouge, $vert, $bleu) = explode('-', $this->carte_correspondance_couleurs[$zg['rvb_fond']]);
				}
				if (isset($zg['index'])) {
					imagecolorset($this->carte, $zg['index'], $rouge, $vert, $bleu);
				} else if ($this->getModeDebug()) {
					$e = "La zone '{$zg['nom']}' n'est pas présente sur la carte.";
					trigger_error($e, E_USER_WARNING);
				}
			}
		}
	}

	private function construireAssociationIndexZone() {
		// Nous récupérons le nombre de couleur différentes contenues dans l'image.
		$taille_palette = imagecolorstotal($this->carte);
		// Pour chaque couleur contenue dans l'image, nous cherchons l'objet correspondant
		// dans le tableau $this->getCarteZones(), qui contient des informations sur chaque zone de l'image,
		// et nous attribuons la valeur de l'index de sa couleur sur la carte de fond.
		for ($i = 0; $i < $taille_palette; $i++) {
			$rvb = array();
			$rvb = imagecolorsforindex($this->carte, $i);
			$rvb_cle = $rvb['red'].'-'.$rvb['green'].'-'.$rvb['blue'];
			// La couleur ne doit pas correspondre au noir ou au blanc car ces couleurs ne sont pas traitées
			if ($rvb_cle != '255-255-255' && $rvb_cle != '0-0-0') {
				$index_ok = false;
				foreach($this->getCarteZones() as $cle => $zg) {
					if (isset($zg['rvb_fond']) && $zg['rvb_fond'] == $rvb_cle) {
						$this->carte_zones[$cle]['index'] = $i;
						$index_ok = true;
						break;
					}
				}
				if (!$index_ok && $rvb_cle != '0-0-0' && $this->getModeDebug()) {
					$e = "Aucune information n'est fournie pour la zone sur la carte d'index $i : $rvb_cle";
					trigger_error($e, E_USER_WARNING);
					//$this->carte_zones[] = array('rvb_fond' => $rvb_cle, 'rvb_carte' => '128-128-128', 'index' => $i);
				}
			}
		}
	}

}
?>