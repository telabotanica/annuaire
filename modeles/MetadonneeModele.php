<?php
// declare(encoding='UTF-8');
/**
 * Modèle d'accès à la base de données des listes
 * d'ontologies
 *
 * PHP Version 5
 *
 * @package   Framework
 * @category  Class
 * @author	aurelien <aurelien@tela-botanica.org>
 * @copyright 2009 Tela-Botanica
 * @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license   http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version   SVN: $$Id: ListeAdmin.php 128 2009-09-02 12:20:55Z aurelien $$
 * @link	  /doc/framework/
 *
 */
class MetadonneeModele extends Modele {

	private $config = array();
	// TODO: externaliser l'identifiant de la liste des types depuis le fichier de config
	private $id_liste_liste = 0;
	private $id_liste_type = 1002;
	private $id_liste_champs = 30768;
	private $id_liste_pays = 1074;

	/**
	 * Charge la liste complète des champs de métadonnées associées à un annuaire en particulier
	 * return array un tableau contenant des objets d'informations sur les listes
	 * @return array un tableau d'objets contenant la liste des listes
	 */
   	public function chargerListeMetadonneeAnnuaire($id_annuaire) {

		$requete = 	'SELECT * '.
					'FROM annu_meta_colonne '.
					'WHERE amc_ce_annuaire = '.$id_annuaire ;

		$resultat = $this->requeteTous($requete);
		$annuaire = array();
		foreach ($resultat as $ligne) {
			// On remplace l'identifiant du type d'affichage par son nom
			$type_affichage = $this->renvoyerCorrespondanceNomId($ligne['amc_ce_type_affichage'], $this->id_liste_champs);
			$ligne['amc_ce_nom_type_affichage']  = $type_affichage['amo_nom'];
			$ligne['amc_ce_template_affichage'] = $type_affichage['amo_abreviation'];
			$annuaire[$ligne['amc_id_champ']] = $ligne;
		}

		return $annuaire;
	}

	/**
	 * Charge les elements d'une liste d'ontologie donnée
	 * @param int $id_liste	l'identifiant de la liste dont on veut les élements
	 * @param Array un tableau contenant les élements de la liste
	 */
	public function chargerInfosListe($id_liste) {
		$requete = 'SELECT amo_nom, amo_id_ontologie '.
					'FROM annu_meta_ontologie '.
					'WHERE amo_ce_parent = '.$id_liste.' '.
					'ORDER BY amo_nom';

		$resultat = $this->requeteTous($requete);
		$liste_types = array();
		foreach ($resultat as $ligne) {
			$liste_types[] = $ligne;
		}

		return $liste_types;
	}

	/**
	 * Charge la liste complète des types de champ
	 * return array un tableau contenant des objets d'informations sur les types de champ
	 * @return array un tableau d'objets contenant la liste des types de champs
	 */
	public function chargerListeDesTypesDeChamps() {
		return $this->chargerInfosListe($this->id_liste_champs);
	}

	/**
	 * Charge la liste complète des types SQL
	 * return array un tableau contenant des objets d'informations sur les types SQL
	 * @return array un tableau d'objets contenant la liste types de métadonnées
	 */
	public function chargerListeDesTypesSQL() {
		return $this->chargerInfosListe($this->id_liste_type);
	}

	/**
	 * Charge la liste complète des listes de métadonnées que l'on peut associer à un annuaire
	 * return array un tableau contenant des objets d'informations sur les types de métadonnées
	 * @return array un tableau d'objets contenant la liste types de métadonnées
	 */
	public function chargerListeDesListes() {
		return $this->chargerInfosListe($this->id_liste_liste);
	}

	/**
	 * Charge les informations d'une metadonnee
	 * @param int l'identifiant de cette metadonnee
	 * @return Array un tableau contenant les informations sur cette metadonnee
	 */
	 public function chargerInformationsMetaDonnee($id) {
	 	$requete = 'SELECT * '.
	 				'FROM annu_meta_colonne '.
	 				'WHERE amc_id_champ = '.$id;

	 	return $this->requeteUn($requete);
	 }

	/**
	 * Ajoute une nouvelle méta colonne à un annuaire donné
	 * @param Array $valeurs les valeurs à ajouter dans la base
	 * @return boolean true si la requete a réussi, false sinon
	 */
	public function ajouterNouvelleMetadonnee($valeurs) {

		$ontologie_liee = $this->proteger($valeurs['amc_ce_ontologie']);
		$annuaire_lie = $this->proteger($valeurs['amc_ce_annuaire']);
		$type_sql = $this->renvoyerTypeSQLPourChamp($valeurs['amc_ce_type_affichage']);
		$longueur = $this->renvoyerLongueurPourChamp($valeurs['amc_ce_type_affichage']);
		$nom = $this->proteger($valeurs['amc_nom']);
		$abreviation = $this->proteger($valeurs['amc_abreviation']);
		$description = $this->proteger($valeurs['amc_description']);
		$type_affichage = $this->proteger($valeurs['amc_ce_type_affichage']);

		$requete = 'INSERT INTO annu_meta_colonne '.
					'(amc_ce_ontologie, amc_ce_annuaire, amc_ce_type, amc_longueur, amc_nom, amc_abreviation, amc_description, amc_ce_type_affichage) '.
					'VALUES ('.$ontologie_liee.', '.
							$annuaire_lie.', '.
							$type_sql.', '.
							$longueur.', '.
							$nom.','.
							$abreviation.','.
							$description.', '.
							$type_affichage.')';

		return $this->requete($requete);
	}

	/**
	 * Modifie une meta colonne liée à un annuaire, grâce aux valeurs passées en paramètre
	 * @param Array $valeurs les valeurs à modifier
	 * @return boolean true si la requete a réussi, false sinon
	 */
	public function modifierMetadonnee($valeurs) {

		$ontologie_liee = $this->proteger($valeurs['amc_ce_ontologie']);
		$type_sql = $this->renvoyerTypeSQLPourChamp($valeurs['amc_ce_type_affichage']);
		$longueur = $this->renvoyerLongueurPourChamp($valeurs['amc_ce_type_affichage']);
		$nom = $this->proteger($valeurs['amc_nom']);
		$abreviation = $this->proteger($valeurs['amc_abreviation']);
		$description = $this->proteger($valeurs['amc_description']);
		$type_affichage = $this->proteger($valeurs['amc_ce_type_affichage']);

		$requete = 'UPDATE annu_meta_colonne '.
					'SET '.
					'amc_ce_ontologie = '.$ontologie_liee.', '.
					'amc_ce_type_affichage = '.$type_sql.', '.
					'amc_longueur = '.$longueur.', '.
					'amc_nom = '.$nom.', '.
					'amc_abreviation = '.$abreviation.', '.
					'amc_description = '.$description.', '.
					'amc_ce_type_affichage = '.$type_affichage.' '.
					'WHERE amc_id_champ = '.$valeurs['amc_id_champ'];

		return $this->requete($requete);
	}

	/**
	 * Supprime une metadonnée
	 * @param int $id_metadonnee l'identifiant de l'enregistrement à supprimer
	 */
	public function supprimerMetadonneeParId($id_metadonnee) {
		$requete_suppression_metadonnee = 'DELETE FROM annu_meta_colonne '.
					'WHERE amc_id_champ = '.$id_metadonnee;

		return $this->requete($requete_suppression_metadonnee);
	}

	/**
	 * Renvoie l'identifiant du type sql associé à un identifiant de type de champ
	 * exemple champ texte => VARCHAR, champ texte long => TEXT
	 * @param int $id_type_champ l'identifiant du type de champ
	 * @return int l'identifiant du type sql correspondant
	 */
	private function renvoyerTypeSQLPourChamp($id_type_champ) {
		// TODO: faire une vraie fonction
		return 1002 ;
	}

	/**
	 * Renvoie la longueur associée à un identifiant de type de champ
	 * exemple champ texte => 50, champ texte long => 1000
	 * @param int $id_type_champ l'identifiant du type de champ
	 * @return int la longueur du champ correspondante
	 * @return int la longueur associée au champ
	 */
	private function renvoyerLongueurPourChamp($id_type_champ) {
		// TODO: faire une vraie fonction
		return 255 ;
	}

	/**
	 * Renvoie le nom d'une valeur de liste d'ontologie grâce à son identifiant
	 * @param int $id_ontologie l'identifiant de la valeur dont on veut le nom
	 * @param int $id_parent l'identifiant de la liste parente
	 * @return string le nom du champ, ou false sinon
	 */
	public function renvoyerCorrespondanceNomId($id_ontologie,$id_parent) {
		
		if(trim($id_ontologie) == '') {
   			return false;
   		}
		
		$requete = 'SELECT amo_nom, amo_abreviation '.
					'FROM annu_meta_ontologie '.
					'WHERE amo_ce_parent = '.$this->proteger($id_parent).' '.
					'AND amo_id_ontologie = '.$this->proteger($id_ontologie);

		return $this->requeteUn($requete);
	}

	/**
	 * Renvoie le nom d'une valeur de liste d'ontologie grâce à son identifiant
	 * @param int $id_ontologie l'identifiant de la valeur dont on veut l'abreviation
	 * @return string l'abreviation, ou false sinon
	 */
	public function renvoyerCorrespondanceAbreviationId($id_ontologie) {
		
		if(trim($id_ontologie) == '') {
   			return false;
   		}
		
		$requete = 'SELECT amo_abreviation '.
					'FROM annu_meta_ontologie '.
					'WHERE amo_id_ontologie = '.$this->proteger($id_ontologie);

		$resultat = $this->requeteUn($requete);

		if($resultat) {
			return $resultat['amo_abreviation'];
		} else {
			return false;
		}
	}
	
	public function renvoyerCorrespondanceIdParAbreviation($abreviation, $id_parent) {
				
		$requete = 'SELECT amo_id_ontologie '.
					'FROM annu_meta_ontologie '.
					'WHERE amo_ce_parent = '.$this->proteger($id_parent).' '.
					'AND amo_abreviation = '.$this->proteger($abreviation);
		
		$resultat = $this->requeteUn($requete);

		if($resultat) {
			return $resultat['amo_id_ontologie'];
		} else {
			return false;
		}
	}
	
	public function renvoyerCorrespondanceNomParAbreviation($abreviation, $id_parent) {
	
		$requete = 'SELECT amo_nom '.
						'FROM annu_meta_ontologie '.
						'WHERE amo_ce_parent = '.$this->proteger($id_parent).' '.
						'AND amo_abreviation = '.$this->proteger($abreviation);
	
		$resultat = $this->requeteUn($requete);
	
		if($resultat) {
			return $resultat['amo_nom'];
		} else {
			return false;
		}
	}


	/**
	 * Renvoie le nom du template associé à un champ grâce à son identifiant
	 * @param int $id_ontologie l'identifiant du champ dont on veut le template
	 * @return string le nom du template (qui est l'abreviation du champ), ou false sinon
	 */
	public function renvoyerTypeAffichageParId($id_champ) {
		
		if(trim($id_champ) == '') {
   			return false;
   		}

		$requete = 'SELECT amo_abreviation '.
			'FROM annu_meta_ontologie '.
			'WHERE amo_ce_parent = '.$this->id_liste_champs.' '.
			'AND amo_id_ontologie = '.$id_champ;

		$resultat = $this->requeteUn($requete);

		return $resultat['amo_abreviation'];
	}

	/**
	 * Renvoie le nom du template associé à un champ grâce à son identifiant
	 * @param int $id_ontologie l'identifiant du champ dont on veut le template
	 * @return string le nom du template (qui est l'abreviation du champ), ou false sinon
	 */
	public function renvoyerTypeAffichagePourColonne($id_colonne) {

		$requete = 'SELECT amo_abreviation '.
			'FROM annu_meta_ontologie '.
			'WHERE amo_ce_parent = '.$this->id_liste_champs.' '.
			'AND amo_id_ontologie =
			(SELECT amc_ce_type_affichage '.
				'FROM annu_meta_colonne '.
				'WHERE amc_id_champ = '.$id_colonne.')';

		$resultat = $this->requeteUn($requete);

		if($resultat) {
			return $resultat['amo_abreviation'];
		} else {
			return false;
		}
	}

	/**
	 * Renvoie vrai si un utilisateur possède une valeur de metadonnées pour une colonne donnée
	 */
	public function valeurExiste($id_champ, $id_enregistrement_lie) {
		$requete_existence_valeur = 'SELECT COUNT(*) as valeur_existe '.
					'FROM annu_meta_valeurs '.
					'WHERE amv_ce_colonne = '.$id_champ.' '.
					'AND amv_cle_ligne = '.$id_enregistrement_lie;

		$resultat = $this->requeteUn($requete_existence_valeur);

		return ($resultat['valeur_existe'] >= 1);
	}
	
		
	public function renvoyerIdChampMetadonneeParAbreviation($id_annuaire, $abreviation) {
		
		$requete_id = 'SELECT amc_id_champ '.
					'FROM annu_meta_colonne '.
					'WHERE amc_abreviation = '.$this->proteger($abreviation).' '.
					'AND amc_ce_annuaire ='.$id_annuaire;
		
		$resultat = $this->requeteUn($requete_id);

		return ($resultat['amc_id_champ']) ;
	}

	/**
	 * Ajoute une nouvelle valeur à un champ de metadonnées pour une ligne dans un annuaire donné
	 * @param int $id_champ l'identifiant du champ auquel on ajoute cette valeur
	 * @param int $id_enregistrement_lie l'identifiant de l'enregistrement lié dans l'annuairé mappé
	 * @param mixed $valeur la valeur à associer au champ (peut-être une valeur brute ou bien un identifiant de liste d'ontologie)
	 * @return true ou false suivant le succès de la requête
	 */
	public function ajouterNouvelleValeurMetadonnee($id_champ, $id_enregistrement_lie, $valeur) {

		$valeur = $this->proteger($valeur);

		$requete = 'INSERT INTO annu_meta_valeurs '.
		'(amv_ce_colonne, amv_cle_ligne, amv_valeur) '.
		'VALUES ('.$id_champ.','.$id_enregistrement_lie.','.$valeur.')';

		return $this->requete($requete);
	}

	/**
	 * Modifie une valeur d'un champ de metadonnées pour une ligne dans un annuaire donné
	 * @param int $id_champ l'identifiant du champ dont on modifie la valeur
	 * @param mixed $valeur la nouvelle valeur à associer au champ (peut-être une valeur brute ou bien un identifiant de liste d'ontologie)
	 * @return boolean true ou false suivant le succès de la requête
	 */
	public function modifierValeurMetadonnee($id_champ, $id_enregistrement_lie, $valeur) {

		$requete = 'UPDATE annu_meta_valeurs '.
		'SET amv_valeur = '.$this->proteger($valeur).' '.
		'WHERE amv_cle_ligne = '.$id_enregistrement_lie.' '.
		'AND amv_ce_colonne = '.$id_champ;

		return $this->requete($requete);
	}

	/**
	 * Supprime une valeur de metadonnée par son identifiant
	 * @param int $id_valeur_metadonnee l'identifiant de valeur à supprimer
	 * @return true ou false suivant le succès de la requete
	 */
	public function supprimerValeurMetadonnee($id_valeur_metadonnee) {

		$requete = 'DELETE FROM annu_meta_valeurs '.
		'WHERE amv_id_valeur = '.$id_valeur_metadonnee;

		return $this->requete($requete);
	}

	/**
	 * Supprime les valeurs de metadonnées associés à un identifiant de ligne d'annuaire
	 * @param int $id_enregistrement_lie l'identifiant de la ligne à laquelle sont associées les valeurs à supprimer
	 */
	public function supprimerValeursMetadonneesParIdEnregistrementLie($id_enregistrement_lie) {

		$requete = 'DELETE FROM annu_meta_valeurs '.
		'WHERE amv_cle_ligne = '.$id_enregistrement_lie;

		return $this->requete($requete);
	}

	/** Supprime les valeurs de metadonnées associés à un identifiant de colonne
	 * @param int $id_colonne_liee l'identifiant de la colonne à laquelle sont associées les valeurs à supprimer
	 */
	public function supprimerValeursMetadonneesParIdColonneLiee($id_colonne_liee) {
		$requete = 'DELETE FROM annu_meta_valeurs '.
		'WHERE amv_ce_colonne = '.$id_colonne_liee;

		return $this->requete($requete);
	}

	/**
	 * Charge les valeurs de metadonnées pour un identifiant de ligne donné
	 * @param int $id_annuaire l'identifiant de l'annuaire sur lequel on travaille
	 * @param int $id_utilisateur l'identifiant de la ligne dans l'annuaire pour laquelle on veut récupérer les valeur de metadonnées
	 */
	 public function chargerListeValeursMetadonneesUtilisateur($id_annuaire, $id_enregistrement_lie) {

		// première requete pour obtenir les valeurs des champs de metadonnées liées à la ligne
	 	$requete_valeurs_metadonnees =  'SELECT amv_ce_colonne, amv_valeur, amc_ce_ontologie, amc_abreviation, amc_ce_type_affichage FROM annu_meta_valeurs '.
	 		 						  	'LEFT JOIN annu_meta_colonne '.
	 									'ON annu_meta_colonne.amc_id_champ = annu_meta_valeurs.amv_ce_colonne '.
	 									'WHERE amv_cle_ligne = '.$id_enregistrement_lie.' ';

		$resultat_valeurs_metadonnees = $this->requeteTous($requete_valeurs_metadonnees);

		if(!$resultat_valeurs_metadonnees) {

	 		$liste_metadonnee = array();

		} else {
	 		foreach ($resultat_valeurs_metadonnees as $ligne) {

				// pour toutes les valeurs qui sont des élements d'une liste d'ontologie
				if($ligne['amc_ce_ontologie'] != 0) {

					// Si c'est un champ qui contient de multiples valeurs, alors il contient potientiellement le séparateur de métadonnées
					if(strpos($ligne['amv_valeur'],Config::get('separateur_metadonnee'))) {

						$id_valeurs_metadonnees = explode(Config::get('separateur_metadonnee'), $ligne['amv_valeur']);
						$ligne['amv_valeur'] = $id_valeurs_metadonnees;

						foreach ($id_valeurs_metadonnees as $id_valeur) {
							$resultat_nom_valeur = $this->renvoyerCorrespondanceNomId($id_valeur,$ligne['amc_ce_ontologie']);
							$ligne['amo_nom'][] = $resultat_nom_valeur['amo_nom'];
						}
					} else {
						$resultat_nom_valeur = $this->renvoyerCorrespondanceNomId($ligne['amv_valeur'],$ligne['amc_ce_ontologie']);
						$ligne['amo_nom'] = $resultat_nom_valeur['amo_nom'];
					}

					$nom_valeur = $resultat_nom_valeur['amo_nom'];
				} else {
					$ligne['amv_valeur'] = stripslashes($ligne['amv_valeur']);
				}

				$ligne['amc_ce_type_affichage'] = $this->renvoyerTypeAffichageParId($ligne['amc_ce_type_affichage']);
				$liste_metadonnee[$ligne['amc_abreviation']] = $ligne;
			}
		}

		$colonnes_totales = $this->chargerListeMetadonneeAnnuaire($id_annuaire);

		foreach ($colonnes_totales as $colonne) {
			if(!isset($liste_metadonnee[$colonne['amc_abreviation']])) {

				if($colonne['amc_ce_ontologie'] != 0) {
					$valeur = array();
				} else {
					$valeur = '';
				}

				$liste_metadonnee[$colonne['amc_abreviation']] = array('amv_ce_colonne' => $colonne['amc_id_champ'],
            		'amv_valeur' => $valeur,
            		'amo_nom' => '',
            		'amc_ce_ontologie' => $colonne['amc_ce_ontologie'],
            		'amc_abreviation' => $colonne['amc_abreviation'],
            		'amc_ce_type_affichage' => $this->renvoyerTypeAffichageParId($colonne['amc_ce_type_affichage']));
			}
		}

		return $liste_metadonnee;

	 }

	/**
	 * Recherche les enregistrements correspondants au criètres donnés et renvoie une liste d'identifiants, correspondants
	 * @param int $id_annuaire l'identifiant de l'annuaire dans lequel on recherche
	 * @valeurs array un talbeau de valeurs à rechercher
	 * $exclusive boolean indique si la recherche doit se faire avec un ET ou bien un OU sur les critèrex
	 */
	 public function rechercherDansValeurMetadonnees($id_annuaire, $valeurs, $exclusive = true) {
		// Définition du séparateur de requête suivant la paramètre
		if($exclusive) {
			$separateur = ' AND ';
		} else {
			$separateur = ' OR ';
		}

		$chaine_recherche = '' ;

	 	if(!$exclusive) {

			foreach($valeurs as $nom_champ => $valeur) {

				if(is_array($valeur)) {
					foreach($valeur as $cle => $valeur_multi_meta) {
						$chaine_recherche .= '(amv_ce_colonne = '.$this->proteger($nom_champ).' AND amv_valeur LIKE '.$this->proteger('%'.$cle.'%').')'.$separateur;
					}
				} else {
					if(trim($valeur) != '') {
						$chaine_recherche .= '(amv_ce_colonne = '.$this->proteger($nom_champ).' AND amv_valeur = '.$this->proteger($valeur).')'.$separateur;
					}
				}
			}
		} else {
			foreach($valeurs as $nom_champ => $valeur) {

				if(is_array($valeur)) {
					foreach($valeur as $cle => $valeur_multi_meta) {
						$chaine_recherche .= ' amv_cle_ligne IN (SELECT amv_cle_ligne FROM annu_meta_valeurs WHERE amv_ce_colonne = '.$this->proteger($nom_champ).' AND amv_valeur LIKE '.$this->proteger('%'.$cle.'%').')'.$separateur;
					}
				} else {
					if(trim($valeur) != '') {
						$chaine_recherche .= ' amv_cle_ligne IN (SELECT amv_cle_ligne FROM annu_meta_valeurs WHERE amv_ce_colonne = '.$this->proteger($nom_champ).' AND amv_valeur = '.$this->proteger($valeur).')'.$separateur;
					}
				}
 			}
		}

		if(trim($chaine_recherche) == '') {
			return array();
		}

		$chaine_recherche = rtrim($chaine_recherche,$separateur);

	 	$requete_recherche = 'SELECT DISTINCT amv_cle_ligne '.
							'FROM annu_meta_valeurs '.
							'WHERE '.$chaine_recherche ;
		$resultat_recherche = $this->requeteTous($requete_recherche);

		if($resultat_recherche) {

			$tableau_id = array();
			foreach($resultat_recherche as $resultat) {
				$tableau_id[] = $resultat['amv_cle_ligne'];
			}
			return $tableau_id;

		} else {
			return array();
		}
	 }

	/**
	 * Renvoie les valeur d'une méta colonne pour un identifiant d'enregistrement lié et de meta colonne donnés
	 * @param int $id_champ l'identifiant de champ
	 * @param int $id_utilisateur l'identifiant de ligne à laquelle est associée la metadonnée
	 * @return mixed la valeur du champ pour l'enregistrement lié.
	 */
	public function obtenirValeurMetadonnee($id_champ, $id_enregistrement_lie) {
		$requete = 'SELECT amv_valeur '.
					'FROM annu_meta_valeurs '.
					'WHERE amv_ce_colonne = '.$this->proteger($id_champ).' '.
					'	AND amv_cle_ligne = '.$this->proteger($id_enregistrement_lie);
		
		$resultat = $this->requeteUn($requete);
		return ($resultat) ? $resultat['amv_valeur'] : false;
	}

	/** Suivant un identifiant de champ, renvoie un tableau contenant le nombre d'enregistrement pour chaque valeur
	 * @param int $id_champ l'identifiant de champ
	 * @return array un tableau d'informations contenant les données
	 */
	public function obtenirNombreValeurMetadonnee($id_champ) {
		$requete = 'SELECT COUNT(*) as nb, amv_valeur FROM annu_meta_valeurs '.
					'WHERE amv_ce_colonne = '.$id_champ.' '.
					'GROUP BY amv_valeur '.
					'ORDER BY nb DESC ';

		$resultat = $this->requeteTous($requete);

		return ($resultat) ? $resultat : false;
	}
	  
	public function obtenirOntologieLieeAChampParId($id_champ) {
		$requete = 	'SELECT amc_ce_ontologie FROM annu_meta_colonne '.
					'WHERE amc_id_champ = '.$this->proteger($id_champ);
	  	
		$resultat = $this->requeteUn($requete);
		
		return ($resultat) ? $resultat['amc_ce_ontologie'] : 0 ;
	}

	public function obtenirValeurPaysParAbbreviation($abrevation_pays) {
		return $this->renvoyerCorrespondanceIdParAbreviation($abrevation_pays, $this->id_liste_pays);
	}
}
?>