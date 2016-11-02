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
*
*/

/**
 * Controleur chargé de la vérification des formulaires
 * Remplace aussi les valeurs lors des actions spéciales comme la modification du mail
 * (l'inscription à la lettre d'actu se fait aussi ici même si ça n'est pas totalement sa place)
 */
class VerificationControleur extends AppControleur {

	/**
	 * Vérifie que les valeurs des champs de mappage et les valeurs obligatoires d'un annuaire donné
	 * sont correctes
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param Array $valeurs les valeurs à vérifier
	 */
	public function verifierErreursChampsSelonType($id_annuaire, $valeurs, $tableau_mappage) {

		$erreurs = array();

		$this->chargerModele('AnnuaireModele');
		$tableau_champs_obligatoire = $this->AnnuaireModele->obtenirChampsObligatoires($id_annuaire);
		
		if(!$tableau_champs_obligatoire) {
			$tableau_champs_obligatoire = array();
		}

		foreach($valeurs as $id => $valeur_champ) {

			$type = $valeur_champ['type'];
			$valeur = $valeur_champ['valeur'];
			$condition = $valeur_champ['condition'];

			switch($type) {

				case 'text':

					if($this->estUnchampObligatoire($id, $tableau_champs_obligatoire) && trim($valeur) == '') {
						$erreurs[$id] = 'Ce champ est obligatoire';
					}
				break;

				case 'mail':
					if($this->estUnchampObligatoire($id, $tableau_champs_obligatoire) && trim($valeur) == '') {
						$erreurs[$id] = 'Le mail est obligatoire ';
					}

					if($this->estUnchampObligatoire($id, $tableau_champs_obligatoire) && !$this->mailValide($valeur)) {
						$erreurs[$id] = 'Le mail est invalide ';
					}

					if($this->AnnuaireModele->utilisateurExisteParMail($id_annuaire, $valeur)) {
						$erreurs[$id] = 'Cet email est déjà utilisé par quelqu\'un d\'autre ';
					}
				break;
				
				case 'select':
					if($this->estUnchampObligatoire($id, $tableau_champs_obligatoire) && trim($valeur) == '') {
						$erreurs[$id] = 'Ce champ est obligatoire';
					}
				break;

				case 'password':
					if($this->estUnchampObligatoire($id, $tableau_champs_obligatoire) && trim($valeur) == ''
					|| $valeur != $condition) {
						$erreurs[$id] = 'Le mot de passe est invalide';
					}
				break;

				// cas du champ checkbox
				case 'checkbox':
					if($this->estUnchampObligatoire($id, $tableau_champs_obligatoire) && trim($condition) != 'on') {
						$erreurs[$id] = 'N\'oubliez pas de cocher cette case';
					}
				break;

				default:

				break;
			}
		}

		if(count($erreurs) == 0) {
			$erreurs = false;
		}

		return $erreurs;
	}

	/**
	 * Vérifie les valeurs des champs pour la modification d'un formulaire
	 */
	public function verifierErreurChampModification($id_annuaire, $id_utilisateur, $type ,$valeur, $confirmation = false) {

		$retour = array(true,false);

		switch($type) {
			case 'mail':
				if(!$this->mailValide($valeur)) {
					$retour[0] = false;
					$retour[1] = 'mail invalide';
					break;
				}

				$this->chargerModele('AnnuaireModele');
				$ancien_mail = $this->AnnuaireModele->obtenirMailParId($id_annuaire,$id_utilisateur);

				if($ancien_mail != $valeur && $this->AnnuaireModele->utilisateurExisteParMail($id_annuaire, $valeur)) {
					$retour[0] = false;
					$retour[1] = 'cet email est déjà utilisé par quelqu\'un d\'autre';
				}

			break;

			case 'password':

				if(trim($valeur) != trim($confirmation)) {
					$retour[0] = false;
					$retour[1] = 'mot de passe invalide';
				}
		}

		return $retour;
	}

	public function remplacerValeurChampPourInsertion($type, $valeur, $mail_utilisateur) {

		$valeur_modifiee = $valeur;

		switch($type) {

			// cas du champ texte, à priori, rien de particulier
			case 'text':
				$valeur_modifiee = $valeur;
			break;

			// cas du champ password : on le crypte
			case 'password':
				$valeur_modifiee = $this->encrypterMotDePasse($valeur);
			break;

			// cas du champ checkbox
			case 'checkbox':
				// Si c'est un groupe checkbox, alors c'est une liste de checkbox liée à une ontologie
				if(is_array($valeur)) {
					// on stocke les valeurs d'ontologies liées au cases cochées
					$valeur_modifiee = implode(Config::get('separateur_metadonnee'), array_keys($valeur));

				} else {
					if($valeur == 'on') {
						// sinon on stocke 1 pour indique que la case est cochée (cas de la checkbox oui/non)
						$valeur_modifiee = 1;
					} else {
						$valeur_modifiee = 0;
					}
				}

			break;

			case 'lettre':
					if($valeur == 'on') {
						// sinon on stocke 1 pour indique que la case est cochée (cas de la checkbox oui/non)
						$valeur_modifiee = 1;
						// Si c'est une inscription à la lettre d'actualité, on appelle la fonction d'inscription
						$lettre_controleur = new LettreControleur();
						$lettre_controleur->inscriptionLettreActualite($mail_utilisateur);
					} else {
						$valeur_modifiee = 0;
					}
			break;

			default:
				$valeur_modifiee = $valeur;
			break;
		}

		return $valeur_modifiee;
	}

	public function remplacerValeurChampPourModification($id_annuaire, $id_utilisateur, $type, $valeur, $mail_utilisateur) {

		$valeur_modifiee = $valeur;

		switch($type) {

			// cas du champ texte, à priori, rien de particulier
			case 'text':
				$valeur_modifiee = $valeur;
			break;

			// cas du champ password : on le crypte
			case 'password':
				$valeur_modifiee = $this->encrypterMotDePasse($valeur);
			break;

			// cas du champ checkbox
			case 'checkbox':
				
				// Si c'est un groupe checkbox, alors c'est une liste de checkbox liée à une ontologie
				if(is_array($valeur)) {

					// on stocke les valeurs d'ontologies liées au cases cochées
					$valeur_modifiee = implode(Config::get('separateur_metadonnee'), array_keys($valeur));

				} else {
					
					if($valeur == 'on' || $valeur == '1') {
						// sinon on stocke 1 pour indique que la case est cochée (cas de la checkbox oui/non)
						$valeur_modifiee = 1;
					} else {
						$valeur_modifiee = 0;
					}
				}

			break;

			case 'lettre':

				// Si c'est une inscription à la lettre d'actualité, on appelle la fonction d'inscription
				$lettre_controleur = new LettreControleur();

				$this->chargerModele('AnnuaireModele');
				$ancien_mail = $this->AnnuaireModele->obtenirMailParId($id_annuaire, $id_utilisateur);

				$changement_mail = false;

				if($ancien_mail != $mail_utilisateur) {
					$changement_mail = true;
				}

				if($valeur == 'on' || $valeur == '1') {
					// on stocke 1 pour indique que la case est cochée (comme la checkbox oui/non)
					$valeur_modifiee = 1;

					// si le mail a changé on désinscrit l'ancien mail et on inscrit le nouveau
					if($changement_mail) {
						$lettre_controleur->ModificationInscriptionLettreActualite($ancien_mail, $mail_utilisateur);
					} else {

						$lettre_controleur->inscriptionLettreActualite($mail_utilisateur);
					}

				} else {
					// sinon, si la case est vide
					$valeur_modifiee = 0;
					$mail_a_desinscrire = $mail_utilisateur;
					if($changement_mail) {
						$mail_a_desinscrire = $ancien_mail;
					}
					// on desinscrit l'utilisateur
					$lettre_controleur->desinscriptionLettreActualite($mail_a_desinscrire);
				}
			break;

			default:
				$valeur_modifiee = $valeur;
			break;
		}

		return $valeur_modifiee;
	}

	public function verifierEtRemplacerValeurChampPourAffichage($type, $valeur, $id_annuaire) {

		if(!$type) {
			$valeur_modifiee = array();

			$valeur_modifiee['amv_type'] = 'champ_annuaire';
			$valeur_modifiee['amv_valeur_affichage'] = $valeur;

		} else {
			
			if(!isset($valeur['amv_valeur'])) {
				$valeur['amv_valeur'] = '';
			}

			$valeur_modifiee = $valeur;

			switch($type) {

				// cas du champ texte, à priori, rien de particulier
				case 'text':
					$valeur_modifiee['amv_valeur_affichage'] = $this->remplacerLienHtml($valeur['amv_valeur']);
				break;
				
				// cas du champ texte long, à priori, rien de particulier
				case 'textarea':
					$valeur_modifiee['amv_valeur_affichage'] = $this->remplacerLienHtml($valeur['amv_valeur']);
				break;

				// cas du champ checkbox
				case 'checkbox':
					// si c'est un groupe checkbox, alors c'est une liste de checkbox liée à une ontologie
					if(isset($valeur['amo_nom'])) {
						if(is_array($valeur['amo_nom']) && count($valeur['amo_nom']) > 0) {
						// on stocke les valeurs d'ontologies liées au cases cochées
							$valeur_modifiee['amv_valeur_affichage'] = implode(', ', $valeur['amo_nom']);
						} else {
							$valeur_modifiee['amv_valeur_affichage'] = $valeur['amo_nom'];
						}
					} else {
						// sinon on stocke 1 pour indique que la case est cochée (cas de la checkbox oui/non)
						if($valeur['amv_valeur'] == 1) {
							$valeur_modifiee['amv_valeur_affichage'] = 'oui';
						} else {
							$valeur_modifiee['amv_valeur_affichage'] = 'non';
						}
					}
				break;

				case 'select':
					// TODO: si ça n'existe pas on va le chercher ?
					if(isset($valeur['amo_nom'])) {
						$valeur_modifiee['amv_valeur_affichage'] = $valeur['amo_nom'];
					} else {
						if(isset($valeur['amv_valeur'])) {
							$ontologie_modele = new OntologieModele();
							$infos_onto = $ontologie_modele->chargerInformationsOntologie($valeur['amv_valeur']);
							if(is_array($infos_onto) && !empty($infos_onto)) {
								$valeur_modifiee['amv_valeur_affichage'] = $infos_onto['amo_nom'];
							} else  {
								$valeur_modifiee['amv_valeur_affichage'] = '';
							}
						} else  {
							$valeur_modifiee['amv_valeur_affichage'] = '';
						}
					}
					
				break;

				case 'radio':
					$valeur_modifiee['amv_valeur_affichage'] = $valeur['amo_nom'];
				break;

				case 'image':
					// si c'est une image, on recherche son url véritable à partir de l'id donnée en paramètre
					if(isset($valeur['amv_valeur']) && $valeur['amv_valeur'] != '') {
						$this->chargerModele('ImageModele');
						$valeur_modifiee['amv_valeur_affichage'] = $this->ImageModele->obtenirEmplacementFichierParId($valeur['amv_valeur'],$id_annuaire, 'S');
					}
				break;

				// cas du champ lettre
				case 'lettre':

					// on affiche oui ou non
					if($valeur_modifiee['amv_valeur'] == 1) {
						$valeur_modifiee['amv_valeur_affichage'] = 'oui';
					} else {
						$valeur_modifiee['amv_valeur_affichage'] = 'non';
					}
				break;
				
				// cas de la date, on la formate
				case 'date':
					
					//echo '|'.$valeur['amv_valeur'].'|';
					
					$format = Config::get('date_format_simple');
					
					if(!isset($format)) {
						$format = 'd/m/Y';
					}

					$time = strtotime($valeur['amv_valeur']);
					
					if(!$time || $time == '') {
						$valeur_modifiee['amv_valeur_affichage'] = $valeur['amv_valeur'];
					} else {
						$valeur_modifiee['amv_valeur_affichage'] = date($format, $time);
					}
								
				break;

				default:
					$valeur_modifiee['amv_valeur_affichage'] = $valeur['amv_valeur'];
				break;
			}
		}

		return $valeur_modifiee;
	}
	
	public function collecterValeurInscription($valeurs, $tableau_mappage) {
		
		$valeurs_mappees = array();
		$valeurs_a_inserer = array();
		
		// on itère sur le tableau de valeur pour récupérer les champs de mappage;
		foreach($valeurs as $nom_champ => $valeur) {

			// pour chaque valeur
			// on extrait l'id du champ
			$ids_champ = mb_split("_",$nom_champ, 3);

			if(count($ids_champ) == 3) {

				$type = $ids_champ[0];
				$id_champ = $ids_champ[2];
				$condition = $ids_champ[1];

				// cas de la checkbox qui devrait être là mais pas cochée
				if($condition == 'hidden' && !isset($valeurs[$type.'_'.$id_champ])) {
					// dans ce cas là on fabrique une valeur vide
					$valeurs[$type.'_'.$id_champ] = 0;
				}

			} else {
				$type = $ids_champ[0];
				$condition = false;
				$id_champ = $ids_champ[1];
			}

			// Si le champ fait partie des champs mappés
			$cle_champ = array_search($id_champ, $tableau_mappage[1]);

			// on ajoute sa clé correspondante dans le tableau des champs mappés
			// qui sont les champs à vérifier
			if($condition) {
				$condition = $valeurs[$type.'_'.$id_champ];
				$valeurs_mappees[$id_champ] = array('valeur' => $valeur, 'type' => $type, 'condition' => $condition);
			} else {
				//$valeurs_mappees[$cle_champ] = $valeur;
				$valeurs_mappees[$id_champ] = array('valeur' => $valeur, 'type' => $type, 'condition' => false);
			}

			if(!$condition) {
				$valeurs_a_inserer[$nom_champ] = $valeur;
			}
		}
		
		return array('valeurs_mappees' => $valeurs_mappees, 'valeurs_a_inserer' => $valeurs_a_inserer);
	}
	
	public function collecterValeursRechercheMoteur($valeurs_recherchees, $tableau_mappage) {
		
		// on itère sur le tableau de valeur pour récupérer les métadonnées;
		foreach($valeurs_recherchees as $nom_champ => $valeur) {

			$ids_champ = mb_split("_",$nom_champ);

			if(count($ids_champ) == 2) {

				$type = $ids_champ[0];
				$id_champ = $ids_champ[1];

				$cle_champ = array_search($id_champ, $tableau_mappage[1]);
				if($cle_champ && $cle_champ != 'champ_pays') {

					$valeurs_mappees[$tableau_mappage[0][$cle_champ]] = $valeur;

				} else {
					if($cle_champ && $cle_champ == 'champ_pays' && !is_numeric($valeur)) {

						$this->chargerModele('MetadonneeModele');
						$valeur = $this->MetadonneeModele->obtenirValeurPaysParAbbreviation($valeur);
					}
					$valeurs_recherchees[$id_champ] = $valeur;
				}
				 
				$valeurs_get[$nom_champ] = $valeur;
			}

			unset($valeurs_recherchees[$nom_champ]);
		}	
		return array('valeurs_mappees' => $valeurs_mappees, 'valeurs_recherchees' => $valeurs_recherchees, 'valeurs_get' => $valeurs_get);
	}
	
	public function convertirTableauRechercheVersChaine($id_annuaire, $valeurs_recherchees) {
				
		$this->chargerModele('MetadonneeModele');
		$metadonnees = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id_annuaire);
		
		$champs = array();
		
		foreach($metadonnees as $id => $metadonnee) {
			$id_champ_formulaire = $metadonnee['amc_ce_template_affichage'].'_'.$id;
			if(isset($valeurs_recherchees[$id_champ_formulaire]) && $valeurs_recherchees[$id_champ_formulaire] != '') {
				$valeur = $valeurs_recherchees[$id_champ_formulaire];
				$champs[] = array('label' => $metadonnee['amc_nom'],
								  'valeur' => $this->convertirValeurChampRechercheVersTexte($metadonnee, $valeur)
							);
			}
		}
		return $champs;
	}
	
	private function convertirValeurChampRechercheVersTexte($metadonnee, $valeur) {
		if($metadonnee['amc_ce_ontologie'] != 0) {
			$valeurs_onto = array();
			if(is_array($valeur)) {
				foreach($valeur as $id => $element) {
					$valeur_element = $this->MetadonneeModele->renvoyerCorrespondanceNomId($id, $metadonnee['amc_ce_ontologie']);
					$valeurs_onto[] = $valeur_element['amo_nom'];
				}
				$valeur = implode(', ',$valeurs_onto);
				
			} else {
				if(is_numeric($valeur)) {
					$valeurs_onto = $this->MetadonneeModele->renvoyerCorrespondanceNomId($valeur, $metadonnee['amc_ce_ontologie']);
					$valeur = $valeurs_onto['amo_nom'];
				} else {
					$valeur_onto = $this->MetadonneeModele->renvoyerCorrespondanceNomParAbreviation($valeur, $metadonnee['amc_ce_ontologie']);
					$valeur = $valeur_onto;
				}
			}
		}
		return $valeur;
	}

	/**
	 * Renvoie vrai ou faux suivant qu'un mail donné en paramètre est syntaxiquement valide (ne vérifie pas l'existence
	 * de l'adresse)
	 * @param string $mail le mail à tester
	 * @return boolean vrai ou faux suivant que le mail est valide ou non
	 */
	public function mailValide($mail) {

		$regexp_mail = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
		return preg_match($regexp_mail, strtolower($mail));

	}

	/**
	 * Renvoie un mot de passe crypté selon la fonction d'encryptage définie dans le fichier de configuration
	 * (si celle-ci n'existe pas on utilise une fonction par défaut)
	 * @param string $pass le mot de passe à encrypter
	 * @return string le mot de passe encrypté
	 */
	public function encrypterMotDePasse($pass) {

		$fonction = Config::get('pass_crypt_fonct');

		if(function_exists($fonction)) {
			return $fonction($pass);
		} else {
			return md5($pass);
		}
	}

	//TODO: créer une class util
	static function encrypterMotDepasseStatic($pass) {

		$fonction = Config::get('pass_crypt_fonct');

		if(function_exists($fonction)) {
			return $fonction($pass);
		} else {
			return md5($pass);
		}
	}
	
	static function convertirTailleFichier($taille) {
		if(!$taille) {
			return "0 Mo";
		}
		
		return number_format($taille*(1/1024)*(1/1024), 0).' Mo';
	}
	
	public function genererMotDePasse() {
		
		$pass = "";
		$chaine = "abcdefghkmnpqrstuvwxyzABCDEFGHKLMNPQRSTUVWXYZ23456789";

		srand((double)microtime()*1000000);
		for($i = 0; $i < 10; $i++){
			$pass .= $chaine[rand()%strlen($chaine)];
		}
		
		return $pass;
		
	}
	
	public static function champEstRempli($champ) {
		return is_array($champ) && isset($champ['amv_valeur_affichage']) && trim($champ['amv_valeur_affichage']) != '';
	}
	
	public static function AfficherSiChampRempli($champ, $chaine_format = '%s') {
		
		$affichage = '';
			
		if (self::champEstRempli($champ)) {
			$valeur = $champ['amv_valeur_affichage'];
			$affichage = sprintf($chaine_format,$valeur);
		}
		
		return $affichage;
	}
	
	public static function AfficherChampSiAdmin($champ, $chaine_format = '%s') {
		
		$affichage = '';
			
		if (Registre::getInstance()->get('est_admin')) {
			$affichage = self::AfficherSiChampRempli($champ, $chaine_format);
		}
			
		return $affichage;
	}

	/**
	 * Suivant un identifiant de champ et un tableau, renvoie vrai ou faux suivant que le champs est obligatoire ou non
	 * @param int $id_champ l'identifiant de champ
	 * @param int $champ_obligatoire le tableau des champs obligatoires
	 */
	private function estUnchampObligatoire($id_champ, $champs_obligatoire) {

		return in_array($id_champ, $champs_obligatoire) || in_array($id_champ, array_keys($champs_obligatoire));
	}
	
	/**
	 * 
	 */
	private function remplacerLienHtml($texte) {
		
		$expr = "(http[\S\.\/:]*)";
		
		$matches = array();
		preg_match_all($expr, $texte, $matches);
				
		foreach($matches as $match) {
			
			foreach($match as $element) {
				$str_lien = '<div><a class="info_resume" href="'.$element.'" >'.$element.'</a></div>'; 
				$texte = str_replace($element, $str_lien, $texte);
			}
		}
		
		return $texte;
	}
}
?>