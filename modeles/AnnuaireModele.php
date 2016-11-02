<?php
// declare(encoding='UTF-8');
/**
 * Modèle d'accès à la base de données des listes
 * d'ontologies
 * 
 * @TODO factoriser les 40 000 fonctions qui diffèrent d'un poil de Q
 *
 * @package   Framework
 * @category  Class
 * @author	aurelien <aurelien@tela-botanica.org>
 * @copyright 2009 Tela-Botanica
 * @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license   http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 */
class AnnuaireModele extends Modele {

	private $config = array();

	/**
	 * Charge la liste complète des annuaires gérés par l'application
	 * @return array un tableau contenant des informations sur les annuaires gérés par l'application
	 */
   	public function chargerListeAnnuaire() {
		$requete = 'SELECT * '.
			'FROM annu_annuaire '.
			'ORDER BY aa_id_annuaire '.
			'-- '.__FILE__.':'.__LINE__;
		$resultat = $this->requeteTous($requete);
		$annuaires = array();
		foreach ($resultat as $ligne) {
			$annuaires[] = $ligne;
		}
		return $annuaires;
	}

	/**
	 * Charge la liste complète des champs d'un annuaire
	 * @param int $identifiant l'identifiant de l'annuaire demandé
	 * @param boolean $charger_liste_champs indique si l'on doit ou non charger la liste des noms des champs
	 * @return array un tableau contenant des objets d'informations sur les annuaires
	 */
	public function chargerAnnuaire($identifiant, $charger_liste_champs = true) {
		$requete = 'SELECT * '.
			'FROM annu_annuaire '.
			"WHERE aa_id_annuaire = $identifiant ".
			'-- '.__FILE__.':'.__LINE__;
		$resultat = $this->requeteTous($requete);
		$annuaire = array();
		foreach ($resultat as $ligne) {
			$annuaire['informations'] = $ligne;
		}

		if ($charger_liste_champs) {
			$requete = 'DESCRIBE '.$annuaire['informations']['aa_bdd'].'.'.$annuaire['informations']['aa_table'];
			$resultat = $this->requeteTous($requete);
			foreach ($resultat as $colonne) {
				$annuaire['colonnes'][] = $colonne;
			}
		}
		return $annuaire;
	}

	/**
	 * Charge les champs de mappage d'un annuaire, c'est à dire les champs de metadonnées qui correspondent à un champ
	 * déjà présent dans la table mappée
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @return array un tableau contenant les noms de champs mappés et les ids des champs métadonnées correspondants
	 */
	public function obtenirChampsMappageAnnuaire($id_annuaire) {
		$requete = 'SELECT * '.
			'FROM annu_triples '.
			"WHERE at_ce_annuaire = {$this->proteger($id_annuaire)} ".
			"	AND at_action IN ('champ_id', 'champ_mail', 'champ_nom', 'champ_prenom', 'champ_pass', ".
			"		'champ_lettre','champ_pays', 'champ_code_postal', 'champ_ville', 'champ_adresse', ".
			"		'champ_adresse_comp', 'champ_date_inscription') ".
			'-- '.__FILE__.':'.__LINE__;
		$resultat_champs_mappage = $this->requeteTous($requete);
		if (!$resultat_champs_mappage) {
			trigger_error('impossible de récupérer les champs de mappage de l\'annuaire '.$id_annuaire);
		}

		$tableau_mappage = array();
		foreach ($resultat_champs_mappage as  $champ) {
			$tableau_mappage[0][$champ['at_action']] = $champ['at_ressource'];
			$tableau_mappage[1][$champ['at_action']] = $champ['at_valeur'];
		}
		return $tableau_mappage;
	}

	/**
	 * Charge les champs obligatoire d'un annuaire, c'est à dire les champs qui doivent être présents et remplis dans le
	 * formulaire
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @return un tableau contenant les ids des champs obligatoire
	 */
	public function obtenirChampsObligatoires($id_annuaire) {
		$requete = 'SELECT * '.
			'FROM annu_triples '.
			"WHERE at_ce_annuaire = {$this->proteger($id_annuaire)} ".
			"	AND at_action = 'champ_obligatoire' ".
			'-- '.__FILE__.':'.__LINE__;
		$resultats = $this->requeteTous($requete);

		// TODO faire une interface de gestion des champs obligatoires
		$tableau_obligatoire = array();
		if ($resultats) {
			foreach ($resultats as $champ) {
				// le tableau des champs obligatoires se présente sous la forme nom_champ_metadonnee => nom_champ_annuaire
				$tableau_obligatoire[$champ['at_valeur']] = $champ['at_ressource'];
			}
		}
		return $tableau_obligatoire ;
	}

	/**
	 * Charge les champs de cartographie d'un annuaire, c'est à dire les champs utilisées pour générer la carte des inscrits
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @return array un tableau contenant les identifiants ou les noms des champs cartographiques
	 */
	public function obtenirChampsCartographie($id_annuaire) {
		// TODO rendre les noms de champs plus générique
		$requete = 'SELECT * '.
			'FROM annu_triples '.
			'WHERE at_ce_annuaire = '.$this->proteger($id_annuaire).' '.
			"AND at_action IN ('champ_pays', 'champ_code_postal') ".
			'-- '.__FILE__.':'.__LINE__;

		$resultats = $this->requeteTous($requete);
		if(!$resultats) {
			trigger_error("Impossible de récupérer les champs cartographiques de l'annuaire $id_annuaire.");
		}
		$tableau_carto = array();
		foreach ($resultats as  $champ) {
			// le tableau des champs carto se présente sous la forme type de champ => [0] nom_champ_annuaire [1] nomù champ metadonnées
			$tableau_carto[$champ['at_action']][0] = $champ['at_ressource'];
			$tableau_carto[$champ['at_action']][1] = $champ['at_valeur'];
		}
		return $tableau_carto ;
	}

	/**
	 * Renvoie l'identifiant du champ associé à l'image de profil (Avatar) dans un annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @return string l'identifiant du champ avatar dans l'annuaire donné ou false s'il n'en existe pas
	 */
	public function obtenirChampAvatar($id_annuaire) {
		$idAnnuaireP = $this->proteger($id_annuaire);
		$requete = 'SELECT * '.
			'FROM annu_triples '.
			"WHERE at_ce_annuaire = $idAnnuaireP ".
			"AND at_action = 'champ_avatar' ".
			'-- '.__FILE__.':'.__LINE__;

		$resultat = $this->requeteUn($requete);
		if (!$resultat) {
			trigger_error("Impossible de récupérer le champ avatar de l'annuaire $id_annuaire.");
		}
		$champ_avatar = ($resultat) ? $resultat['at_valeur'] : false;
		return $champ_avatar;
	}

	 /** Renvoie l'identifiant du champ associé à l'image de profil (Avatar) dans un annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @return string l'identifiant du champ date inscription dans l'annuaire donné ou false s'il n'en existe pas
	 */
	public function obtenirChampDateEtValidite($id_annuaire) {
		$requete = 'SELECT * '.
			'FROM annu_triples '.
			'WHERE at_ce_annuaire = '.$this->proteger($id_annuaire).' '.
			"AND at_action IN ('champ_date_inscription', 'champ_date_desinscription', 'champ_validite_inscription') ".
			'-- '.__FILE__.':'.__LINE__;
		$resultats = $this->requeteTous($requete);

		foreach ($resultats as $champ) {
			$champs_date_validite[$champ['at_action']] = $champ['at_ressource'];
		}
		return $champs_date_validite ;
	}

	/**
	 * Charge tous les champs de description de l'annuaire
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @return array un tableau contenant les noms de champs mappés et les ids des champs métadonnées correspondants
	 */
	public function obtenirChampsDescriptionAnnuaire($id_annuaire) {
		$idAnnuaireP = $this->proteger($id_annuaire);
		$requete = 'SELECT * '.
			'FROM annu_triples '.
			"WHERE at_ce_annuaire = $idAnnuaireP ".
			'-- '.__FILE__.':'.__LINE__;

		$resultat_champs_mappage = $this->requeteTous($requete);
		if (!$resultat_champs_mappage) {
			trigger_error('impossible de récupérer les champs décrivant l\'annuaire '.$id_annuaire);
		}

		$tableau_mappage = array();
		foreach ($resultat_champs_mappage as  $champ) {
			$tableau_mappage[0][$champ['at_action']] = $champ['at_ressource'];
			$tableau_mappage[1][$champ['at_action']] = $champ['at_valeur'];
		}
		return $tableau_mappage ;
	}

	/**
	 * Charge la date de dernière modification du profil d'un utilisateur,
	 * depuis la table annu_triples, pour un annuaire donné
	 * 
	 * @param numericint $id_annuaire l'identifiant de l'annuaire
	 * @param numericint $id_utilisateur l'identifiant de l'utilisateur
	 * 
	 * @return string la date de dernière modification du profil de l'utilisateur,
	 * 		ou null si aucune date n'a été trouvée dans les "triples"
	 */
	public function obtenirDateDerniereModificationProfil($id_annuaire, $id_utilisateur) {
		$idAnnuaireP = $this->proteger($id_annuaire);
		$idUtilisateurP = $this->proteger($id_utilisateur);
		$requete = 'SELECT at_valeur '.
			'FROM annu_triples '.
			"WHERE at_ce_annuaire = $idAnnuaireP ".
			"AND at_action = 'modification' ".
			"AND at_ressource = $idUtilisateurP ".
			"ORDER BY at_id DESC LIMIT 1 ".
			'-- '.__FILE__.':'.__LINE__;

		$resultat = $this->requeteUn($requete);
		if ($resultat) {
			return $resultat['at_valeur'];
		} else {
			return null;
		}
	}

	/** Charge le nombre d'inscrits d'une table annuaire mappée
	 * @param int $identifiant l'identifiant de l'annuaire mappé
	 */
	public function chargerNombreAnnuaireListeInscrits($identifiant) {
		$requete_informations_annuaire = 'SELECT aa_bdd, aa_table '.
					'FROM  annu_annuaire '.
					'WHERE aa_id_annuaire = '.$identifiant.' ';
		$resultat_informations_annuaire = $this->requeteUn($requete_informations_annuaire);

		if (!$resultat_informations_annuaire) {
			trigger_error('impossible de récupérer les informations de la table '.$identifiant);
		}

		$requete_nombre_inscrits = 'SELECT COUNT(*) as nb '.
									'FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'];

		// Récupération des résultats
		try {
			$resultat_nb_inscrits = $this->requeteUn($requete_nombre_inscrits);
			if ($donnees === false) {
				$this->messages[] = "La requête n'a retourné aucun résultat.";
			} else {
				$nb_inscrits = $resultat_nb_inscrits['nb'];
			}
		} catch (Exception $e) {
			$this->messages[] = sprintf($this->getTxt('sql_erreur'), $e->getFile(), $e->getLine(), $e->getMessage());
		}

		return $nb_inscrits;
	}

	/** Charge le nombre d'inscrits d'une table annuaire mappée en les groupant par départements
	 * @param int $identifiant l'identifiant de l'annuaire mappé
	 * @return array un tableau indexé par les numéros de departement contenant le nombre d'inscrits à chacun
	 *
	 */
	public function chargerNombreAnnuaireListeInscritsParDepartement($identifiant) {
		$requete_informations_annuaire = 'SELECT aa_bdd, aa_table '.
					'FROM  annu_annuaire '.
					'WHERE aa_id_annuaire = '.$identifiant.' ';
		$resultat_informations_annuaire = $this->requeteUn($requete_informations_annuaire);

		if (!$resultat_informations_annuaire) {
			trigger_error('impossible de récupérer les informations de la table '.$identifiant);
		}

		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($identifiant);
		$champ_code_postal = $tableau_mappage[0]['champ_code_postal'];
		$champ_pays = $tableau_mappage[0]['champ_pays'];

		$requete_nombre_inscrits = 'SELECT IF ( SUBSTRING( '.$champ_code_postal.' FROM 1 FOR 2 ) >= 96, '.
			'		SUBSTRING( '.$champ_code_postal.' FROM 1 FOR 3 ), '.
			'		SUBSTRING( '.$champ_code_postal.' FROM 1 FOR 2 ) ) AS id, '.
			'	COUNT(*) AS nbre '.
			'FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'].' '.
			'WHERE '.$champ_pays.' = "FR" '.
			'GROUP BY IF ( SUBSTRING( '.$champ_code_postal.' FROM 1 FOR 2 ) >= 96, '.
			'	SUBSTRING( '.$champ_code_postal.' FROM 1 FOR 3 ), '.
			'	SUBSTRING( '.$champ_code_postal.' FROM 1 FOR 2 ) ) '.
			'ORDER BY id ASC ';

		// Récupération des résultats
		try {
			$donnees = $this->requeteTous($requete_nombre_inscrits);
			if ($donnees === false) {
				$this->messages[] = "La requête n'a retourné aucun résultat.";
			} else {
				foreach ($donnees as $donnee) {
					$resultat_nombre_inscrits[$donnee['id']] = $donnee['nbre'];
				}
			}
		} catch (Exception $e) {
			$this->messages[] = sprintf($this->getTxt('sql_erreur'), $e->getFile(), $e->getLine(), $e->getMessage());
		}

		if (!$resultat_informations_annuaire) {
			trigger_error('impossible de récupérer le nombre d\'inscrits de la table '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table']);
		}

		return $resultat_nombre_inscrits;
	}

	/** Charge le nombre d'inscrits d'une table annuaire mappée en les groupant par pays
	 * @param int $identifiant l'identifiant de l'annuaire mappé
	 * @param array $id_recherchees un tableau contenant les codes de pays à rechercher
	 * @return array un tableau indexé par les numéros de pays contenant le nombre d'inscrits à chacun
	 *
	 */
	public function chargerNombreAnnuaireListeInscritsParPays($id_annuaire, $ids_recherchees) {
		$requete = 'SELECT aa_bdd, aa_table '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' '.
			'-- '.__FILE__.':'.__LINE__;
		$infosAnnuaire = $this->requeteUn($requete);
		if (!$infosAnnuaire) {
			trigger_error("Impossible de récupérer les informations de la table $id_annuaire.");
		}

		$tableau_mappage = $this->obtenirChampsCartographie($id_annuaire);
		$champ_pays = $tableau_mappage['champ_pays'][0];
		$tableAnnuaire = $infosAnnuaire['aa_bdd'].'.'.$infosAnnuaire['aa_table'];
		$requete = "SELECT $champ_pays, COUNT(*) AS nbre ".
			"FROM $tableAnnuaire ".
			"WHERE $champ_pays IN (".implode(',',$ids_recherchees).') '.
			"GROUP BY $champ_pays ".
			"ORDER BY $champ_pays ASC ".
			'-- '.__FILE__.':'.__LINE__;

		// Récupération des résultats
		$nombreInscrits = array();
		try {
			$donnees = $this->requeteTous($requete);
			if ($donnees === false) {
				$this->messages[] = "La requête n'a retourné aucun résultat.";
			} else {
				foreach ($donnees as $donnee) {
					$codePays = strtolower($donnee[$champ_pays]);
					$nombreInscrits[$codePays] = $donnee['nbre'];
				}
			}
		} catch (Exception $e) {
			$this->messages[] = sprintf($this->getTxt('sql_erreur'), $e->getFile(), $e->getLine(), $e->getMessage());
		}
		return $nombreInscrits;
	}

	private function formaterAbreviationPaysPourRecherche($chaine) {
		return $this->proteger(strtoupper($chaine));
	}

	/**
	 * Recherche selon une valeur d'un champ qui peut être une valeur approximative (avec des %) dans un champ d'annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param string $champ_critere le champ qui servira de filtre
	 * @param string $valeur la valeur à rechercher
	 * @param boolean $modele indique si l'on veut recherche la valeur exacte ou non
	 * @return array un tableau contenant la liste des inscrits dans l'annuaire donné, correspondants à ce critère
	 */
	public function rechercherInscritDansAnnuaireMappeParTableauChamps($id_annuaire, $criteres, $modele = false, $numero_page = 1, $taille_page = 50) {
		$sep = ($modele) ? '%' : '';

		foreach ($criteres as $champ => $valeur) {
			$criteres[$champ] = $valeur.$sep;
		}

		return $this->rechercherInscritDansAnnuaireMappe($id_annuaire, $criteres, array(), true, $numero_page, $taille_page);
	}

	/**
	 * Charge les inscrits d'une table annuaire mappée, en ne conservant que les champs de mappage indiqués
	 * @param int $identifiant l'identifiant de l'annuaire mappé
	 * @param Array $champs_mappage les champs de mappage à retenir
	 * @param int $numero_page le numéro de la page demandée
	 * @param int $taille_page la taille de la page demandée
	 *
	 */
	public function chargerAnnuaireListeInscrits($id_annuaire, $numero_page = 1, $taille_page = 50) {
		$requete_informations_annuaire = 'SELECT aa_bdd, aa_table '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_informations_annuaire = $this->requeteUn($requete_informations_annuaire);

		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$requete_nb_inscrits = 'SELECT COUNT( * ) AS nb '.
			'FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'];

		$resultat_nb_inscrits = $this->requeteUn($requete_nb_inscrits);

		$nb_inscrits = 0;
		if ($resultat_nb_inscrits) {
			$nb_inscrits = $resultat_nb_inscrits['nb'];
		}

		$requete_recherche_inscrits = 'SELECT '.$champs_mappage[0]['champ_id'].' '.
			'FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'];

   		if ($taille_page != 0) {
			$requete_recherche_inscrits .= ' LIMIT '.(($numero_page-1)*$taille_page).','.($taille_page);
		}

		$resultat_recherche_inscrits = $this->requeteTous($requete_recherche_inscrits);

		if (!$resultat_recherche_inscrits) {
			$resultat_recherche_inscrits = array();
		}

		return array('total' => $nb_inscrits, 'resultat' => $resultat_recherche_inscrits) ;
	}

	/**
	 * Ajoute les valeurs données dans l'annuaire indiqué
	 * @param int $id_annuaire	l'identifiant de l'annuaire dans lequel on va travailler
	 * @param Array $valeurs_mappees un tableau de valeurs à ajouter
	 * @param string $nom_champs les noms des champs dans lesquels on va ajouter les données
	 * @return int l'identifiant du nouvel enregistrement
	 */
	public function ajouterInscriptionDansAnnuaireMappe($id_annuaire, $valeurs_mappees, $nom_champs) {
		$requete_infos_annuaire = 'SELECT * '.
			'FROM  annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat = $this->requeteUn($requete_infos_annuaire);
		$annuaire = array();

		//en cas d'erreur on renvoie false
		//TODO: lever une exception
		if (!$resultat) {
			return false;
		}

		$annuaire = $resultat;

		$champs_date = $this->obtenirChampDateEtValidite($id_annuaire);

		// si l'on fonctionne sur un modele de type champ inscription valide = 1
		// puis valide = 0 lors de la desinscrption sans suppression
		// on l'indique
		if(isset($champs_date['champ_validite_inscription'])) {
			$valeurs_mappees[$champs_date['champ_validite_inscription']] = '1';
		}

		$valeurs_prot = array_map(array($this,'proteger'),$valeurs_mappees);

		// si on a défini un champ date d'inscription, on l'ajoute à la liste des champs insérer
		// avec la valeur NOW
		if(isset($champs_date['champ_date_inscription'])) {
			$valeurs_mappees[$champs_date['champ_date_inscription']] = 'NOW()';
			$valeurs_prot[$champs_date['champ_date_inscription']] = 'NOW()';
		}

		$valeurs = implode(',',$valeurs_prot);
		$champs = implode(',',array_keys($valeurs_mappees));

		$requete_insertion_annuaire = 'INSERT INTO '.$annuaire['aa_bdd'].'.'.$annuaire['aa_table'].' '.
			'('.$champs.') '.
			'VALUES ('.$valeurs.')';

		$id_nouvel_enregistrement = false;

		//en cas d'erreur on renvoie false
		//TODO: lever une exception
		if(!$this->requete($requete_insertion_annuaire)) {
			return $id_nouvel_enregistrement;
		}

		// le mail est censé être unique donc on l'utilise pour faire une selection pour retrouver l'enregistrement
		// (Les requetes du style SELECT MAX(id)... ne garantissent pas qu'on récupère le bon id
		// si une autre insertion a eu lieu entre temps)
		// TODO utiliser du PDO pur et utiliser les fonctions last_insert_id générique
		$requete_nouvel_id = 'SELECT '.$nom_champs['champ_id'].' '.
			'FROM '.$annuaire['aa_bdd'].'.'.$annuaire['aa_table'].' '.
			'WHERE '.
			$nom_champs['champ_mail'].' = '.$this->proteger($valeurs_mappees[$nom_champs['champ_mail']]);

		$resultat_nouvel_id = $this->requeteUn($requete_nouvel_id);
		if (!$resultat_nouvel_id) {
			return $id_nouvel_enregistrement;
		}

		$id_nouvel_enregistrement = $resultat_nouvel_id[$nom_champs['champ_id']];

		return $id_nouvel_enregistrement;
	}

	/**
	 * Modifie les valeurs données dans l'annuaire indiqué
	 * @param int $id_annuaire	l'identifiant de l'annuaire dans lequel on va travailler
	 * @param int $id_annuaire	l'identifiant de l'utilisateur à qui à modifier
	 * @param Array $valeurs_mappees un tableau de valeurs à modifier
	 * @param string $nom_champs les noms des champs dans lesquels on va modifier les données
	 * @return boolean true ou false suivant le succès de l'operation
	 */
	public function modifierInscriptionDansAnnuaireMappe($id_annuaire, $id_utilisateur, $valeurs_mappees, $champs_mappage) {
		$requete_infos_annuaire = 'SELECT * '.
			'FROM  annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat = $this->requeteUn($requete_infos_annuaire);
		$annuaire = array();

		unset($valeurs_mappees[$champs_mappage['champ_id']]);

		//en cas d'erreur on renvoie false
		//TODO: lever une exception
		if(!$resultat) {
			return false;
		}

		$annuaire = $resultat;

		$requete_modification_annuaire = 'UPDATE '.$annuaire['aa_bdd'].'.'.$annuaire['aa_table'].' '.
		'SET ';
		foreach($valeurs_mappees as $cle => $valeur) {
			$requete_modification_annuaire .= $cle.' = '.$this->proteger($valeur).', ';
		}

		$requete_modification_annuaire = rtrim($requete_modification_annuaire,', ').' ' ;

		$requete_modification_annuaire .= 'WHERE '.$champs_mappage['champ_id'].' = '.$id_utilisateur ;

		//en cas d'erreur on renvoie false
		//TODO: lever une exception
		if(!$this->requete($requete_modification_annuaire)) {
			return false;
		} else {
			return true;
		}

	}

	public function obtenirValeurChampAnnuaireMappe($id_annuaire, $id_utilisateur, $champ) {
		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		// on ne garde que les champs de mappage correspondant au champ de l'annuaire principal
		$champs_mappage = $champs_mappage[0];

		$requete_infos_annuaire = 'SELECT * '.
					'FROM annu_annuaire '.
					'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}

		$champs_mappage_str = implode(',',$champs_mappage);
		$id_utilisateur = $this->proteger($id_utilisateur);

		$requete_selection_valeur = 'SELECT '.$champs_mappage[$champ].' as '.$champ.' '.
										 'FROM '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
										 'WHERE '.$champs_mappage['champ_id'].' = '.$id_utilisateur;

		$resultat_selection_valeur = $this->requeteUn($requete_selection_valeur);
		if (!$resultat_selection_valeur) {
			return false;
		} else {
			return $resultat_selection_valeur[$champ];
		}
	}

	public function modifierValeurChampAnnuaireMappe($id_annuaire, $id_utilisateur, $champ, $valeur) {
		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		// on ne garde que les champs de mappage correspondant au champ de l'annuaire principal
		$champs_mappage = $champs_mappage[0];

		$requete_infos_annuaire = 'SELECT * '.
			'FROM annu_annuaire '.
			"WHERE aa_id_annuaire = $id_annuaire ";

		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}

		$id_utilisateur = $this->proteger($id_utilisateur);
		$valeur = $this->proteger($valeur);

		$requete_modification_valeur = 'UPDATE '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
										 'SET '.$champ.' = '.$valeur.' '.
										 'WHERE '.$champs_mappage['champ_id'].' = '.$id_utilisateur;
		$resultat_modification_valeur = $this->requeteUn($requete_modification_valeur);
		return $resultat_modification_valeur;
	}

	/**
	 * Renvoie le mail associé à l'identifiant d'un utilisateur dans un annuaire donné
	 * @param int $id_annuair l'identifiant de l'annuaire
	 * @param int $id_utilisateur l'identifiant de l'utilisateur
	 * @return string le mail associé à cet identifiant ou false si l'utilisateur n'existe pas
	 */
	public function obtenirMailParId($id_annuaire, $id_utilisateur) {
		$requete_infos_annuaire = 'SELECT * '.
					'FROM annu_annuaire '.
					'WHERE aa_id_annuaire = '.$id_annuaire.' ';
		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}

		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		// on ne garde que les champs de mappage correspondant au champ de l'annuaire principal
		$champs_mappage = $champs_mappage[0];

		$id_utilisateur = $this->proteger($id_utilisateur);

		$requete_selection_utilisateur = 'SELECT '.$champs_mappage['champ_mail'].' '.
			'FROM '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
			'WHERE '.$champs_mappage['champ_id'].' = '.$id_utilisateur;
		//echo $requete_selection_utilisateur;
		$resultat_selection_utilisateur = $this->requeteUn($requete_selection_utilisateur);
		if (!$resultat_selection_utilisateur) {
			return false;
		} else {
			return $resultat_selection_utilisateur[$champs_mappage['champ_mail']];
		}

	}

	/**
	 * Renvoie les mail associés des identifiants d'utilisateur dans un annuaire donné
	 * @param int $id_annuair l'identifiant de l'annuaire
	 * @param array $ids_utilisateurs les identifiants des l'utilisateur
	 * @return array un tableau contenant les mails associés à ces identifiant ou false si les utilisateurs n'existent pas
	 */
	public function obtenirMailParTableauId($id_annuaire, $tableau_ids_utilisateurs) {
		$requete_infos_annuaire = 'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';
		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}

		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		// on ne garde que les champs de mappage correspondant au champ de l'annuaire principal
		$champs_mappage = $champs_mappage[0];
		$tableau_ids_utilisateurs_p = array_map(array($this, 'proteger'), $tableau_ids_utilisateurs);
		$str_ids_utilisateurs = implode(',',$tableau_ids_utilisateurs_p);

		$requete_selection_utilisateurs = 'SELECT '.$champs_mappage['champ_mail'].' '.
			'FROM '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
			'WHERE '.$champs_mappage['champ_id'].' IN ('.$str_ids_utilisateurs.')';

		$resultat_selection_utilisateurs = $this->requeteTous($requete_selection_utilisateurs);

		$resultat_utilisateurs = array();
		foreach ($resultat_selection_utilisateurs as $utilisateur) {
			 $resultat_utilisateurs[] = $utilisateur[$champs_mappage['champ_mail']];
		}
		if(!$resultat_selection_utilisateurs) {
			return false;
		} else {
			return $resultat_utilisateurs;
		}
	}

	/**
	 * Renvoie l'id associé au mail d'un utilisateur dans un annuaire donné
	 * @param int $id_annuair l'identifiant de l'annuaire
	 * @param int $mail_utilisateur le mail de l'utilisateur
	 * @return string l'id associé à ce mail ou false si l'utilisateur n'existe pas
	 */
	public function obtenirIdParMail($id_annuaire, $mail_utilisateur) {
		$requete_infos_annuaire = 'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}
		
		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		// on ne garde que les champs de mappage correspondant au champ de l'annuaire principal
		$champs_mappage = $champs_mappage[0];

		$requete_selection_utilisateur = 'SELECT '.$champs_mappage['champ_id'].' '.
			'FROM '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
			'WHERE '.$champs_mappage['champ_mail'].' = '.$this->proteger($mail_utilisateur);

		$resultat_selection_utilisateur = $this->requeteUn($requete_selection_utilisateur);
		if (!$resultat_selection_utilisateur) {
			return false;
		} else {
			return $resultat_selection_utilisateur[$champs_mappage['champ_id']];
		}
	}

	/**
	 * Renvoie le nom et prénom associé au mail d'un utilisateur dans un annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param array $courriels un tableau de courriel d'utilisateur
	 * @return array un tableau contenant en clé le courriel et en valeur un tableau avec le prénom dans le champ 'prenom' et le nom dans le champ 'nom'.
	 */
	public function obtenirPrenomNomParCourriel($id_annuaire, $courriels) {
		$requete = 	'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$annuaire = $this->requeteUn($requete);
		if (!$annuaire) {
			return false;
		}
		
		$mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		// on ne garde que les champs de mappage correspondant au champ de l'annuaire principal
		$mappage = $mappage[0];

		foreach ($courriels as $id => $courriel) {
			$courriels[$id] = $this->proteger($courriel);
		}

		$requete = 	'SELECT '.$mappage['champ_id'].', '.$mappage['champ_mail'].', '.$mappage['champ_prenom'].', '.$mappage['champ_nom'].' '.
			'FROM '.$annuaire['aa_bdd'].'.'.$annuaire['aa_table'].' '.
			'WHERE '.$mappage['champ_mail'].' IN ('.implode(',', $courriels).')';
		$resultats = $this->requeteTous($requete);

		if (!$resultats) {
			return false;
		} else {
			$infos = array();
			foreach ($resultats as $resultat) {
				$id = $resultat[$mappage['champ_id']];
				$prenom = AppControleur::formaterMotPremiereLettreChaqueMotEnMajuscule($resultat[$mappage['champ_prenom']]);
				$nom = AppControleur::formaterMotEnMajuscule($resultat[$mappage['champ_nom']]);

				$infos[$resultat[$mappage['champ_mail']]] = array('id' => $id, 'prenom' => $prenom, 'nom' => $nom);
			}
			return $infos;
		}

	}

	/**
	 * Renvoie toutes les infos disponibles associées à l'adresse email fournie, dans un annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param array $courriels un (et un seul pour l'instant) courriel
	 * @return array un tableau associatif contenant les infos
	 */
	public function obtenirMaximumInfosParCourriel($id_annuaire, $courriel) {
		$requete = 	'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$annuaire = $this->requeteUn($requete);
		if (!$annuaire) {
			return false;
		}

		$mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		// on ne garde que les champs de mappage correspondant au champ de l'annuaire principal
		$mappage = $mappage[0];

		$courriel = $this->proteger($courriel);
		$requete = 'SELECT *'
			. ' FROM ' . $annuaire['aa_bdd'] . '.' . $annuaire['aa_table']
			. ' WHERE ' . $mappage['champ_mail'] . " = $courriel";
		$resultat = $this->requeteUn($requete);

		if (!$resultat) {
			return false;
		}
		$infos = array();
		foreach (array_keys($mappage) as $cle) {
			// j'ai honte d'écrire un truc pareil
			$infos[substr($cle, 6)] = $resultat[$mappage[$cle]];
		}
		// pour certains champs (fonction par ex.) il n'y a pas de mappage
		// dans annu_triples : comment qu'on fait ? Ben on fait une dégueulasserie !
		$mappagesALArrache = array(
			"fonction" => "U_FONCTION",
			"titre" => "U_TITLE",
			"site_web" => "U_WEB",
			"region" => "U_STATE"
		);
		foreach ($mappagesALArrache as $k => $v) {
			$infos[$k] = (empty($resultat[$v]) ? '' : $resultat[$v]);
		}
		// les mappages ne correspondent pas à l'héritage de eFlore chatin, comment
		// qu'on fait ? Quelle est la norme ? Ben on fait des trucs cracra redondants
		// pour assurer la rétrocompatibilité !
		$infos['adresse01'] = $infos['adresse'];
		$infos['adresse02'] = $infos['adresse_comp'];
		$infos['courriel'] = $infos['mail'];
		$infos['mot_de_passe'] = $infos['pass'];
		
		return $infos;
	}

	/**
	 * Renvoie les infos pour un ou plusieurs utilisateurs, et un annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param mixed $ids_utilisateurs String un identifiant d'utilisateur ou Array un tableau d'identifiants
	 * @return array un tableau.
	 */
	public function obtenirInfosUtilisateurParId($id_annuaire, $ids_utilisateurs) {
		$plusieurs = false;
		if (is_array($ids_utilisateurs)) {
			$plusieurs = true;
			$ids_utilisateurs = implode(',', $ids_utilisateurs);
		}
		$requete = 	'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire;

		$annuaire = $this->requeteUn($requete);
		if (!$annuaire) {
			return false;
		}

		$mappageInfos = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		// on ne garde que les champs de mappage correspondant au champ de l'annuaire principal
		$mappage = $mappageInfos[0];
		$requete = 	'SELECT '.$mappage['champ_id'].', '.$mappage['champ_mail'].', '.$mappage['champ_prenom'].', '.$mappage['champ_nom'].' '.
			'FROM '.$annuaire['aa_bdd'].'.'.$annuaire['aa_table'].' '.
			'WHERE '.$mappage['champ_id'];
		if ($plusieurs) {
			$requete .= ' IN (' . $ids_utilisateurs . ')';
		} else {
			$requete .= ' = ' . $ids_utilisateurs;
		}
		$resultats = $this->requeteTous($requete);

		$infos = false;
		foreach ($resultats as $resultat) {
			$id = $resultat[$mappage['champ_id']];
			$prenom = AppControleur::formaterMotPremiereLettreChaqueMotEnMajuscule($resultat[$mappage['champ_prenom']]);
			$nom = AppControleur::formaterMotEnMajuscule($resultat[$mappage['champ_nom']]);
			$courriel = $resultat[$mappage['champ_mail']];

			$infos[$id] = array('id' => $id, 'prenom' => $prenom, 'nom' => $nom, 'courriel' => $courriel);
		}
		return $infos;
	}

	// TODO: commenter
	public function comparerIdentifiantMotDePasse($id_annuaire, $id_utilisateur, $mot_de_passe) {
		$requete_infos_annuaire = 'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}

		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		// on ne garde que les champs de mappage correspondant au champ de l'annuaire principal
		$champs_mappage = $champs_mappage[0];

		$mot_de_passe = $this->proteger($mot_de_passe);
		$id_utilisateur = $this->proteger($id_utilisateur);

		$requete_selection_utilisateur = 'SELECT COUNT(*) as match_login_mdp '.
			'FROM '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
			'WHERE '.$champs_mappage['champ_id'].' = '.$id_utilisateur.' '.
			'AND '.$champs_mappage['champ_pass'].' = '.$mot_de_passe;

		$resultat_selection_utilisateur = $this->requeteUn($requete_selection_utilisateur);

		// en cas d'erreur ou bien si le login ne matche pas le mot de passe
		// on renvoie false
		if (!$resultat_selection_utilisateur || $resultat_selection_utilisateur['match_login_mdp'] <= 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Récupère les champs demandé dans l'annuaire indiqué
	 * @param int $id_annuaire	l'identifiant de l'annuaire dans lequel on va travailler
	 * @param int $id_utilisateur l'identifiant de l'utilisateur dont on veut les informations
	 * @param Array $champs_mappage les noms des champs que l'on veut récupérer
	 * @return Array les informations demandées
	 */
	public function obtenirValeursUtilisateur($id_annuaire, $id_utilisateur, $champs_mappage) {
		$requete = 'SELECT * '.
				'FROM annu_annuaire '.
				'WHERE aa_id_annuaire = '.$id_annuaire.' ';
	
		$resultat = $this->requeteUn($requete);
	
		$retour = false;
		if ($resultat) {
			$champs_mappage_str = implode(',', $champs_mappage);
			$idUtilisateurP = $this->proteger($id_utilisateur);
	
			$requete = 'SELECT '.$champs_mappage_str.' '.
					'FROM '.$resultat['aa_bdd'].'.'.$resultat['aa_table'].' '.
					'WHERE '.$champs_mappage['champ_id'].' = '.$idUtilisateurP;
	
			$resultat = $this->requeteUn($requete);
			if ($resultat) {
				$retour = $resultat;
			}
		}
		return $retour;
	}

	/**
	 * Récupère les valeurs utilisateur dans l'annuaire indiqué en les ordonnant par le champ demandé
	 * @param int $id_annuaire	l'identifiant de l'annuaire dans lequel on va travailler
	 * @param Array $champs_mappage les noms des champs que l'on veut récupérer
	 * @param string order_by le champ par lequel on ordonne les résultats
	 * @param limit la limite au nombre de résultats
	 * @return Array les informations demandées
	 */
	public function obtenirTableauValeursUtilisateurs($id_annuaire, $champs_mappage, $order_by = 'champ_id', $dir= 'DESC', $limit = '20') {
		$requete_infos_annuaire = 'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}

		$champ_order_by = $champs_mappage[$order_by];

		$champs_mappage_str = implode(',',$champs_mappage);
		$id_utilisateur = $this->proteger($id_utilisateur);

		$requete_selection_utilisateur = 'SELECT '.$champs_mappage_str.' '.
			'FROM '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
			'ORDER BY '.$champ_order_by.' '.$dir.' LIMIT '.$limit;

		$resultat_selection_utilisateur = $this->requeteTous($requete_selection_utilisateur);
		if (!$resultat_selection_utilisateur) {
			return false;
		} else {
			return $resultat_selection_utilisateur;
		}
	}

/**
	 * Récupère les valeurs utilisateur dans l'annuaire indiqué en les ordonnant par le champ demandé
	 * @param int $id_annuaire	l'identifiant de l'annuaire dans lequel on va travailler
	 * @param Array $champs_mappage les noms des champs que l'on veut récupérer
	 * @param string order_by le champ par lequel on ordonne les résultats
	 * @param limit la limite au nombre de résultats
	 * @return Array les informations demandées
	 */
	public function obtenirTableauIdsUtilisateurs($id_annuaire, $champs_mappage, $order_by = 'champ_id', $dir= 'DESC', $limit = '20') {
		$requete_infos_annuaire = 'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}

		$champ_order_by = $champs_mappage[$order_by];

		$requete_selection_utilisateur = 'SELECT '.$champs_mappage['champ_id'].' '.
			'FROM '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
			'ORDER BY '.$champ_order_by.' '.$dir.' LIMIT '.$limit;

		$resultat_selection_utilisateur = $this->requeteTous($requete_selection_utilisateur);
		if (!$resultat_selection_utilisateur) {
			return false;
		} else {
			return $resultat_selection_utilisateur;
		}
	}

	public function obtenirNombreInscriptionsDansIntervalleDate($id_annuaire, $date_debut, $date_fin) {
		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$requete_infos_annuaire = 'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}

		//$supprimer_donnes = false;
		$valeurs_mappees = array();

		$champs_date = $this->obtenirChampDateEtValidite($id_annuaire);

		$date_inscription = $champs_date['champ_date_inscription'];

		$requete_nb_inscrits_intervalle = 'SELECT COUNT(*) as nb '.
			'FROM '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
			'WHERE '.$date_inscription.' >= "'.date('Y-m-d H:i:s', $date_debut).'" '.
			'AND '.$date_inscription.' < "'.date('Y-m-d H:i:s', $date_fin).'" ';

		$resultat_nb_inscrits_intervalle = $this->requeteUn($requete_nb_inscrits_intervalle);

		if (!$resultat_nb_inscrits_intervalle) {
			return 0;
		}
		return $resultat_nb_inscrits_intervalle['nb'];
	}

	/**
	 * Supprime une inscription dans une table annuaire
	 * @param int $id_annuaire l'identifiant de l'annuaire dans lequel on supprime les données
	 * @param int $id_utilisateur l'identifiant de l'utilisateur à supprimer
	 * @return boolean true si la suppression a réussi, false sinon
	 */
	public function supprimerInscriptionDansAnnuaireMappe($id_annuaire, $id_utilisateur) {
		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$requete_infos_annuaire = 'SELECT * '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_infos_annuaire = $this->requeteUn($requete_infos_annuaire);
		if (!$resultat_infos_annuaire) {
			return false;
		}

		$champs_date = $this->obtenirChampDateEtValidite($id_annuaire);

		$requete_suppression_utilisateur = 'DELETE FROM '.$resultat_infos_annuaire['aa_bdd'].'.'.$resultat_infos_annuaire['aa_table'].' '.
			'WHERE '.$champs_mappage[0]['champ_id'].' = '.$this->proteger($id_utilisateur);
		$resultat_suppression_utilisateur = $this->requeteUn($requete_suppression_utilisateur);
		if ($this->utilisateurExisteParId($id_annuaire, $id_utilisateur, $champs_mappage)) {
			return false;
		}
		return true;
	}

	/**
	 * Renvoie vrai si un utilisateur existe suivant un id donné
	 * @param int $id_annuaire l'identifiant de l'annuaire dans lequel on supprime les données
	 * @param int $id_utilisateur l'identifiant de l'utilisateur à supprimer
	 * @return boolean true si l'utilisateur existe, false sinon
	 */
	public function utilisateurExisteParId($id_annuaire, $id_utilisateur) {
		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$requete_informations_annuaire = 'SELECT aa_bdd, aa_table '.
			'FROM annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';
		$resultat_informations_annuaire = $this->requeteUn($requete_informations_annuaire);

		if (!$resultat_informations_annuaire) {
			trigger_error('impossible de récupérer les informations de la table '.$id_annuaire);
		}

		$requete_nombre_inscrits = 'SELECT COUNT(*) AS est_inscrit'.
			' FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'].
			' WHERE '.$champs_mappage[0]['champ_id'].' = '.$this->proteger($id_utilisateur);

		$resultat_nombre_inscrits = $this->requeteUn($requete_nombre_inscrits);
		if (!$resultat_nombre_inscrits) {
			trigger_error('impossible de vérifier l\'existence de cet utilisateur ');
		}

		return ($resultat_nombre_inscrits['est_inscrit'] > 0) ;
	}

	 /** Renvoie vrai si un utilisateur existe suivant un mail donné
	 * @param int $id_annuaire l'identifiant de l'annuaire dans lequel recherche
	 * @param int $id_utilisateur le mail de l'utilisateur à chercher
	 * @return boolean true si l'utilisateur existe, false sinon
	 */
	public function utilisateurExisteParMail($id_annuaire, $mail) {
		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$requete_informations_annuaire = 'SELECT aa_bdd, aa_table '.
			'FROM  annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_informations_annuaire = $this->requeteUn($requete_informations_annuaire);
		
		if (!$resultat_informations_annuaire) {
			trigger_error('impossible de récupérer les informations de la table '.$id_annuaire);
		}

		$requete_nombre_inscrits = 'SELECT COUNT(*) AS est_inscrit '.
			' FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'].
			' WHERE '.$champs_mappage[0]['champ_mail'].' = '.$this->proteger($mail);

		$resultat_nombre_inscrits = $this->requeteUn($requete_nombre_inscrits);

		if (!$resultat_nombre_inscrits) {
			trigger_error('impossible de vérifier l\'existence de cet utilisateur ');
		}

		return ($resultat_nombre_inscrits['est_inscrit'] > 0) ;

	}

	/**
	 * @param int $id_annuaire identifiant de l'annuaire dans lequel on recherche
	 * @param array $valeurs un tableau de valeurs à rechercher
	 * @param array $id_a_inclure un tableau d'identifiants à inclure (pour chainer des recherches)
	 * @param boolean $exclusive indique si l'on recherche effectue une recherche exclusive ou inclusive (AND, ou OR)
	 */
	public function rechercherInscritDansAnnuaireMappe($id_annuaire, $valeurs, $id_a_inclure = array(), $exclusive = true, $numero_page = 1, $taille_page = 50, $ordre = 'champ_nom') {
		// Si la fonction est appelée et que tous les critères sont vides
		if (count($valeurs) == 0 && count($id_a_inclure) == 0) {
			// on sort directement
			return array();
		}

		$requete_informations_annuaire = 'SELECT aa_bdd, aa_table '.
			'FROM  annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_informations_annuaire = $this->requeteUn($requete_informations_annuaire);

		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$requete_recherche_inscrits = 'SELECT '.$champs_mappage[0]['champ_id'].
			' FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'].
			' WHERE ';

		$requete_conditions_inscrits = '';

		if ($exclusive) {
			$separateur = ' AND ';
		} else {
			$separateur = ' OR ';
		}

		// on inclut les identifiants déjà trouvé par les critères de métadonnées
		if (is_array($id_a_inclure) && count($id_a_inclure) != 0) {
			$id_inclus = implode(',',$id_a_inclure);

			$requete_conditions_inscrits .= $champs_mappage[0]['champ_id'].' IN '.
			'('.$id_inclus.')'.$separateur ;
		}

		// si le champ contient un % alors on ne cherche pas une valeur exacte : on utilise LIKE
		foreach ($valeurs as $champ => $valeur) {
			if (trim($valeur) != '') {
				if ($champ == $champs_mappage[0]['champ_nom'] || $champ == $champs_mappage[0]['champ_prenom']) {
					if(strpos($valeur,"%") === false) {
						$valeur = '%' . $valeur.'%';
					}
				} elseif ($champ == $champs_mappage[0]['champ_code_postal']) {
					if (strpos($valeur,"%") === false) {
						$valeur .= '%';
					}
				} elseif ($champ == $champs_mappage[0]['champ_mail']) {
					if (strpos($valeur,"%") === false) {
						$valeur = '%'.$valeur.'%';
					}
				}

				$operateur =  (strpos($valeur,"%") === false) ? ' = ' : ' LIKE ';

				$requete_conditions_inscrits .= $champ.$operateur.$this->proteger($valeur).$separateur;
			}
		}

		$requete_conditions_inscrits = rtrim($requete_conditions_inscrits, $separateur);

		$requete_recherche_inscrits .= $requete_conditions_inscrits;

		$requete_nb_inscrits = 'SELECT COUNT( * ) as nb '.' FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'];

		if (trim($requete_conditions_inscrits) != '') {
			$requete_nb_inscrits .= ' WHERE '.$requete_conditions_inscrits;
		}

		$resultat_nb_inscrits = $this->requeteUn($requete_nb_inscrits . ' -- ' . __FILE__ . ':' . __LINE__);

		$nb_inscrits = 0;
		if ($resultat_nb_inscrits) {
			$nb_inscrits = $resultat_nb_inscrits['nb'];
		}

		$requete_recherche_inscrits .= ' ORDER BY '.$champs_mappage[0]['champ_nom'];

		if ($taille_page != 0) {
			$requete_recherche_inscrits .= ' LIMIT '.(($numero_page-1)*$taille_page).','.($taille_page);
		}

		$resultat_recherche_inscrits = $this->requeteTous($requete_recherche_inscrits . ' -- ' . __FILE__ . ':' . __LINE__);

		if (!$resultat_recherche_inscrits) {
			$resultat_recherche_inscrits = array();
		}

		return array('total' => $nb_inscrits, 'resultat' => $resultat_recherche_inscrits) ;
	}

	public function rechercherDoublonsDansAnnuaireMappe($id_annuaire, $numero_page = 1, $taille_page = 50) {
		$requete_informations_annuaire = 'SELECT aa_bdd, aa_table '.
			'FROM  annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';

		$resultat_informations_annuaire = $this->requeteUn($requete_informations_annuaire);

		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$champ_id = $champs_mappage[0]['champ_id'];
		$champ_mail = $champs_mappage[0]['champ_mail'];
		$champ_nom = $champs_mappage[0]['champ_nom'];
		$champ_prenom = $champs_mappage[0]['champ_prenom'];
		$champ_ville = $champs_mappage[0]['champ_ville'];

		$requete_recherche_doublon = 'SELECT DISTINCT t2.'.$champ_id.
			' FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'].' t1 '.
			'LEFT JOIN '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'].' t2 '.
			'USING ('.$champ_nom.','.$champ_prenom.','.$champ_ville.') '.
			'WHERE t1.'.$champ_id.' != t2.'.$champ_id.' '.
			'ORDER BY '.$champ_nom.', '.$champ_prenom.' ';



		$requete_nb_doublons = 'SELECT COUNT(DISTINCT t2.'.$champs_mappage[0]['champ_id'].') as nb'.
			' FROM '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'].' t1 '.
			'LEFT JOIN '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'].' t2 '.
			'USING ('.$champ_nom.','.$champ_prenom.','.$champ_ville.') '.
			'WHERE t1.'.$champ_id.' != t2.'.$champ_id;

		$resultat_nb_doublons = $this->requeteUn($requete_nb_doublons);

		$nb_doublons = 0;
		if ($resultat_nb_doublons) {
			$nb_doublons = $resultat_nb_doublons['nb'];
		}

		$resultat_recherche_doublons = $this->requeteTous($requete_recherche_doublon);

		if (!$resultat_recherche_doublons) {
			$resultat_recherche_doublons = array();
		} else {
			if ($taille_page != 0) {
				$resultat_recherche_doublons = array_slice($resultat_recherche_doublons,($numero_page-1)*$taille_page,$taille_page);
			}
		}

		return array('total' => $nb_doublons, 'resultat' => $resultat_recherche_doublons) ;
	}

	/**
	 * Reinitialise un mot de passe associé à un mail donné et en renvoie un nouveau,
	 * généré aléatoirement
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param string $mail le mail dont on doit réinitialiser le mot de passe
	 * @return string le nouveau mot de passe ou false si l'utilisateur n'existe pas
	 */
	public function reinitialiserMotDePasse($id_annuaire, $mail, $pass) {
		$nouveau_mdp = $pass;

		$requete_informations_annuaire = 'SELECT aa_bdd, aa_table '.
			'FROM  annu_annuaire '.
			'WHERE aa_id_annuaire = '.$id_annuaire.' ';
		$resultat_informations_annuaire = $this->requeteUn($requete_informations_annuaire);

		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$requete_modification_mdp = 'UPDATE '.$resultat_informations_annuaire['aa_bdd'].'.'.$resultat_informations_annuaire['aa_table'].
			' SET '.$champs_mappage[0]['champ_pass'].' = '.$this->proteger($nouveau_mdp).
			' WHERE '.$champs_mappage[0]['champ_mail'].' = '.$this->proteger($mail);

		$resultat_modification_mdp = $this->requete($requete_modification_mdp);

		if ($resultat_modification_mdp) {
			return $nouveau_mdp;
		}
		return false;
	}

	/**
	 * Puisqu'il n'y a pas la moindre méthode métier un peu propre pour inscrire un utilisateur, on
	 * va devoir tout faire à la main comme un verrat grassouilet.
	 * @WARNING c'est TRES TRES MAL de faire ça, ça casse la généricité, le multi-annuaire et tous les
	 * trucs mégachiants qui... euh... servent à rien en pratique :-/ mais bon c'est mal
	 */
	public function inscrireUtilisateurCommeUnGrosPorc($donnees) {
		//echo "GRUIIIIK !!! ";
		$donneesDefaut = array('nom' => '', 'prenom' => '', 'fonction' => '', 'titre' => '',
			'password' => '', 'email' => '', 'url' => '', 'addr1' => '', 'addr2' => '', 'code_postal' => '',
			'ville' => '', 'etat' => '', 'pays' => '', 'departement' => ''
		);
		$donnees = array_merge($donneesDefaut, $donnees);
		// 1) table principale de l'annuaire
		$req = "INSERT INTO annuaire_tela (U_ID, U_NAME, U_SURNAME, U_FONCTION, U_TITLE, U_PASSWD, U_MAIL, U_WEB, U_ADDR1, U_ADDR2, U_ZIP_CODE, "
            . "U_CITY, U_STATE, U_COUNTRY, U_FRENCH_DPT, U_ABO, U_NIV, U_SPE, U_GEO, U_ACT, U_VALIDE, U_DATE, U_ASS, U_COT, U_LETTRE) VALUES(DEFAULT, '"
			. $donnees['nom'] . "',	'"
			. $donnees['prenom'] . "', '"
			. $donnees['fonction'] . "', '"
			. $donnees['titre'] . "', '"
			. $donnees['password'] . "', '"
			. $donnees['email'] . "', '"
			. $donnees['url'] . "', '"
			. $donnees['addr1'] . "', '"
			. $donnees['addr2'] . "', '"
			. $donnees['code_postal'] . "', '"
			. $donnees['ville'] . "', '"
			. $donnees['etat'] . "', '"
			. $donnees['pays'] . "', '"
			. $donnees['departement'] . "',	'', NULL, '', '', 0, 1, CURRENT_TIMESTAMP, NULL, 0, 0);";

		//echo $req; $res = true;
		$res = $this->requete($req);
		if ($res) {
			// 2) récupération  de l'ID à la wanagain
			$id = $this->obtenirIdParMail(1, $donnees['email']);
			//echo "ID: "; var_dump($id);
			if ($id) {
				// 3) métadonnées : pseudo et infos partenaire
				// @WARNING les ids des colonnes peuvent différer d'une base à l'autre, méga cracra !
				$req2 = "INSERT INTO annu_meta_valeurs VALUES"
					. "(DEFAULT, 136, $id, 1)," // pseudo utilisé
					. "(DEFAULT, 99, $id, '" . $donnees['pseudo'] . "')," // pseudo
					. "(DEFAULT, 144, $id, '" . $donnees['partenaire'] . "')," // partenaire
					. "(DEFAULT, 145, $id, '" . $donnees['id_partenaire'] . "')" // id_partenaire
				;
				//echo $req2;
				$res2 = $this->requete($req2);

				// 4) date de dernière modification
				$req3 = "INSERT INTO annu_triples VALUES (DEFAULT, 1, $id, 'modification', CURRENT_TIMESTAMP)";
				$res3 = $this->requete($req3);

				// pas grave si la ddm a raté
				return ($res2 != false);
			}
		}
		return false;
	}
}
