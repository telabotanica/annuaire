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

class AnnuaireControleur extends AppControleur {

	/**
	 * Fonction affichant la vue par défaut, ici le menu principal
	 * @return String la vue contenant le menu
	 */
	public function index() {
		if (Registre::getInstance()->get('est_admin')) {
			$data = array();
			$index_annuaire = $this->getVue('index_annuaire', $data);
			return $index_annuaire;
		} else {
			return $this->afficherFicheUtilisateur(Registre::getInstance()->get('identification_id'));
			}
	}

	/**
	 * Fonction d'affichage par défaut
	 */
	public function executerActionParDefaut() {
		if (Registre::getInstance()->get('est_admin')) {
			$data = array();
			$index_annuaire = $this->getVue('index_annuaire', $data);
			return $index_annuaire;
		} else {
			return $this->afficherFicheUtilisateur(Registre::getInstance()->get('identification_id'));
		}
	}

/**-------- Fonctions de gestion des annuaires --------------------------------*/

	/**
	 * Charge la vue contenant la liste des annuaires gérés par l'application
	 * @return string la vue contenant la liste des annuaires
	 */
	public function chargerListeAnnuaire() {
		$this->chargerModele('AnnuaireModele');
		$data['erreurs'] = null;
		$data['annuaires'] = $this->AnnuaireModele->chargerListeAnnuaire();
		$liste_annu = $this->getVue(Config::get('dossier_squelettes_gestion_annuaires').'liste_annu', $data);

		return $liste_annu;
	}

	/**
	 * Charge la vue contenant les informations d'un annuaire donné en paramètre
	 * @param int $id l'identifiant de l'annuaire
	 * @return string la vue contenant les informations sur l'annuaire
	 */
	public function chargerAnnuaire($id) {
		$this->chargerModele('AnnuaireModele');
		$this->chargerModele('MetadonneeModele');
		$data['erreurs'] = array();
		$data['champs_mappage'] = $this->obtenirChampsMappageAnnuaire($id);
		$data['annuaire'] = $this->AnnuaireModele->chargerAnnuaire($id, true);
		$data['metadonnees'] = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id);
		$annuaire = $this->getVue(Config::get('dossier_squelettes_gestion_annuaires').'annuaire', $data);

		return $annuaire;
	}

	/**
	 * Charge et affiche la liste des inscrits à un annuaire donné en paramètre
	 * @param $id int l'identifiant de l'annuaire
	 * @return string la vue contenant les inscrits à l'annuaire
	 */
	public function chargerAnnuaireListeInscrits($id_annuaire, $numero_page = 1, $taille_page = 50) {
		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire);
		$data['erreurs'] = array();
		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		$champ_id_annuaire = $tableau_mappage[0]['champ_id'];

		$resultat_recherche = $this->AnnuaireModele->chargerAnnuaireListeInscrits($id_annuaire, $numero_page, $taille_page);

		$nb_resultats = $resultat_recherche['total'];
		$resultat_recherche = $resultat_recherche['resultat'];

		$resultats = array();
		foreach($resultat_recherche as $resultat) {
			$id_utilisateur = $resultat[$champ_id_annuaire];
			$resultats[$id_utilisateur] = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);
		}

		// on renvoie une liste identique à celle de la liste des inscrits
		$donnees['resultats_recherche'] = $resultats;
		$donnees['tableau_mappage'] = $tableau_mappage[1];
		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['nb_resultats'] = $nb_resultats;
		$url_pagination = new URL(Registre::getInstance()->get('base_url_application'));
		$url_pagination->setVariableRequete('m','annuaire_inscrits');
		$url_pagination->setVariableRequete('id_annuaire',$id_annuaire);

		$donnees['criteres'] = urlencode(serialize(array('tous' => '1')));

		$donnees['pagination'] = $this->paginer($numero_page,$taille_page,$nb_resultats,$url_pagination, array());

		// S'il existe une page de résultats spécifique à l'annuaire pour la recherche

		if($this->templateExiste($annuaire['informations']['aa_code'].'_resultat_recherche', Config::get('dossier_squelettes_annuaires'))) {
			// on l'affiche
			$annuaires_inscrits = $this->getVue(Config::get('dossier_squelettes_annuaires').$annuaire['informations']['aa_code'].'_resultat_recherche', $donnees);
		} else {
			// sinon on prend celle par défaut
			$tableau_nom_mappage = $this->obtenirNomsChampsMappageAnnuaire($id_annuaire);
			$donnees['mappage_nom_champs'] = $tableau_nom_mappage;
			$annuaires_inscrits = $this->getVue(Config::get('dossier_squelettes_annuaires').'resultat_recherche', $donnees);
		}

		return $annuaires_inscrits;
	}

/**-------- Fonctions d'affichage du formulaire de saisie d'un champ de metadonnée suivant le type de champ---------*/

	/**
	 * Affiche le formulaire d'inscription pour un annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire pour lequel on veut afficher le formulaire
	 * @param Array $donnees le tableau de données pour préremplir le formulaire si besoin (en cas de retour erreur)
	 */
	public function afficherFormulaireInscription($id_annuaire, $donnees = array()) {
		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire, false);

		$this->chargerModele('MetadonneeModele');
		$donnees['aa_id_annuaire'] = $id_annuaire;

		$metadonnees = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id_annuaire);

		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		// TODO: ceci charge toutes les métadonnées, voir si l'on ne peut pas parser le formulaire
		// pour ne charger que ce qui est nécéssaire
		foreach ($metadonnees as $metadonnee) {
			$id_champ = $metadonnee['amc_id_champ'];
			$type_champ = $metadonnee['amc_ce_template_affichage'];
			$nom_champ = $metadonnee['amc_abreviation'];

			$metadonnee['aa_id_annuaire'] = $id_annuaire;
			if(isset($donnees['erreurs'])) {
				$metadonnee['erreurs'] = $donnees['erreurs'];
			}

			if(isset($donnees[$type_champ.'_'.$id_champ])) {
				$metadonnee['valeur_defaut']['amv_valeur'] = $donnees[$type_champ.'_'.$id_champ];
			}

			// on charge le formulaire d'affichage de chacune des métadonnées
			$donnees['champs'][$nom_champ] = $this->afficherFormulaireChampMetadonnees($id_champ,$metadonnee);
		}

		$donnees['tableau_mappage'] = $tableau_mappage[1];

		if ($this->annuaireAvoirFormulaireInscription($annuaire['informations']['aa_code'])) {
			$formulaire_inscription = $this->GetVue(Config::get('dossier_squelettes_formulaires').$annuaire['informations']['aa_code'].'_inscription',$donnees);
		} else {

			$tableau_nom_mappage = $this->obtenirNomsChampsMappageAnnuaire($id_annuaire);
			$donnees['mappage_nom_champs'] = $tableau_nom_mappage;

			$formulaire_inscription = $this->genererFormulaireInscription($donnees);
		}

		return $formulaire_inscription;
	}

/**-------- Fonctions d'inscription -------------------------------------------------------------------------------*/

	/**
	 * Lors d'une tentative d'inscription, ajoute les infos dans la table d'inscription
	 * temporaire et envoie le mail contenant le lien de confirmation si tout s'est bien passé
	 * @param Array $valeurs les valeurs à ajouter
	 * @return string la vue contenant la confirmation de l'inscription
	 */
	public function ajouterInscriptionTemporaire($valeurs) {
		$this->chargerModele('MetadonneeModele');
		$id_annuaire = $valeurs['aa_id_annuaire'];
		unset($valeurs['aa_id_annuaire']);

		$valeurs_mappees = array();
		$valeurs_a_inserer = array();

		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$verificateur = new VerificationControleur();

		$valeurs_collectees = $verificateur->collecterValeurInscription($valeurs, $tableau_mappage);

		$valeurs_mappees = $valeurs_collectees['valeurs_mappees'];
		$valeurs_a_inserer = $valeurs_collectees['valeurs_a_inserer'];

		// vérification des champs minimaux : nom, prénom, mail, mot de passe
		if($erreurs = $verificateur->verifierErreursChampsSelonType($id_annuaire,$valeurs_mappees, $tableau_mappage)) {
			$valeurs['erreurs'] = $erreurs;
			return $this->afficherFormulaireInscription($id_annuaire, $valeurs);
		}

		$valeurs_a_inserer['aa_id_annuaire'] = $id_annuaire ;

		$this->chargerModele('DonneeTemporaireModele');

		$code_confirmation = $this->DonneeTemporaireModele->stockerDonneeTemporaire($valeurs_a_inserer);

		$mail = $valeurs_mappees[$tableau_mappage[1]['champ_mail']]['valeur'];
		$nom = $valeurs_mappees[$tableau_mappage[1]['champ_nom']]['valeur'];

		if (isset($tableau_mappage[1]['champ_prenom']) && isset($valeurs_mappees[$tableau_mappage[1]['champ_prenom']]['valeur'])) {
			$prenom = $valeurs_mappees[$tableau_mappage[1]['champ_prenom']]['valeur'];
		} else {
			$prenom = '';
		}

		$messagerie = new MessageControleur();

		$messagerie->envoyerMailConfirmationInscription($mail, $nom, $prenom, $code_confirmation);

		$tableau_vide = array();
		// Si tout s'est bien passé, on affiche la page de confirmation
		return $this->getVue(Config::get('dossier_squelettes_annuaires').'annuaire_inscription_reussie',$tableau_vide);
	}

	/**
	 * Ajoute une nouvelle inscription à un annuaire à partir de données d'une table temporaire.
	 * Typiquement, on déclenche cette fonction en cliquant sur le lien contenu dans le mail de confirmation
	 * @param int $indentifant L'identifant de session d'une tentative d'inscription
	 */
	public function ajouterNouvelleInscription($identifiant) {
		$this->chargerModele('DonneeTemporaireModele');
		$valeurs = $this->DonneeTemporaireModele->chargerDonneeTemporaire($identifiant);

		if (!$valeurs || count($valeurs) == 0) {
			return false;
		}

		$this->chargerModele('AnnuaireModele');

		$id_annuaire = $valeurs['aa_id_annuaire'];
		unset($valeurs['aa_id_annuaire']);

		$this->chargerModele('MetadonneeModele');

		$verificateur = new VerificationControleur();

		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$valeurs_mappees = array();
		$valeurs_metadonnees = array();

		$mail_nouvel_inscrit = $valeurs['mail_'.$tableau_mappage[1]['champ_mail']];
		$pass_nouvel_inscrit = $valeurs['password_'.$tableau_mappage[1]['champ_pass']];
			
		// Dernière vérification du mail au cas où la personne aurait fait plusieurs demandes 
		// d'inscription et aurait déjà validée l'une d'entre elles
		// TODO: supprimer la demande d'inscription si c'est le cas ?
		// (elle sera supprimée de toute façon par la gestion des données obsolètes)
		if($this->AnnuaireModele->utilisateurExisteParMail($id_annuaire, $mail_nouvel_inscrit)) {
			return false;
		}

		$valeurs['text_'.$tableau_mappage[1]['champ_nom']] = AppControleur::formaterMotEnMajuscule($valeurs['text_'.$tableau_mappage[1]['champ_nom']]);
		$nom = $valeurs['text_'.$tableau_mappage[1]['champ_nom']];


		$mail = $mail_nouvel_inscrit;
		$pass = $valeurs['password_'.$tableau_mappage[1]['champ_pass']];

		if (isset($tableau_mappage[0]['champ_prenom']) && isset($valeurs_mappees[$tableau_mappage[0]['champ_prenom']])) {
			$valeurs['text_'.$tableau_mappage[1]['champ_prenom']] = AppControleur::formaterMotPremiereLettreChaqueMotEnMajuscule($valeurs['text_'.$tableau_mappage[1]['champ_prenom']]);
			$prenom = $valeurs['text_'.$tableau_mappage[1]['champ_prenom']];
		} else {
			$prenom = '';
		}

		if (isset($tableau_mappage[0]['champ_pays']) && isset($valeurs_mappees[$tableau_mappage[0]['champ_pays']])) {
			$pays = $valeurs['select_'.$tableau_mappage[1]['champ_pays']];
		} else {
			$pays = '';
		}

		// on itère sur le tableau de valeur pour récupérer les métadonnées;
		foreach ($valeurs as $nom_champ => $valeur) {
			// pour chaque valeur
			// on extrait l'id du champ
			$ids_champ = mb_split("_",$nom_champ, 2);

			$type = $ids_champ[0];
			$condition = false;
			$id_champ = $ids_champ[1];

			// on fait des vérifications et des remplacements sur certaines valeurs
			$valeur = $verificateur->remplacerValeurChampPourInsertion($type,$valeur,$mail_nouvel_inscrit);

			// Si le champ fait partie des champs mappés
			$cle_champ = array_search($id_champ, $tableau_mappage[1]);
			if ($cle_champ) {
				// on ajoute sa clé correspondante dans l'annuaire mappé et sa valeur dans le tableau des champs mappés
				$valeurs_mappees[$tableau_mappage[0][$cle_champ]] = $valeur;
				// et on supprime sa valeur du tableau de valeurs pour ne pas la retrouver lors
				// de l'insertion des métadonnées
				unset($valeurs[$nom_champ]);
			} else {
				$valeurs_metadonnees[$nom_champ] = $valeur;
			}
		}

		// cas spécial du champ pays ou l'on fait un double stockage des données
		if (isset($tableau_mappage[0]['champ_pays']) && isset($valeurs_mappees[$tableau_mappage[0]['champ_pays']])) {
			$pays = $valeurs_mappees[$tableau_mappage[0]['champ_pays']];
			$valeurs_metadonnees[$tableau_mappage[1]['champ_pays']] = $pays;
			$pays = $this->MetadonneeModele->renvoyerCorrespondanceAbreviationId($pays);
			$valeurs_mappees[$tableau_mappage[0]['champ_pays']] = $pays;
		}

		// obtenir l'id du nouvel arrivant en faisant un select sur le mail qui doit être unique
		$id_nouvel_inscrit = $this->AnnuaireModele->ajouterInscriptionDansAnnuaireMappe($id_annuaire,$valeurs_mappees, $tableau_mappage[0]);

		// les champs de metadonnees arrivent avec un identifiant sous la forme type_condition_id
		foreach ($valeurs_metadonnees as $nom_champ => $valeur) {
			// pour chaque valeur
			// on extrait l'id du champ
			$ids_champ = mb_split("_",$nom_champ);
			$id_champ = $ids_champ[count($ids_champ) - 1];

			// Si l'insertion dans la base a réussi
			if ($this->MetadonneeModele->ajouterNouvelleValeurMetadonnee($id_champ,$id_nouvel_inscrit,$valeur)) {
				// on continue
			} else {

				// Si une des insertions échoue, on supprime les méta valeurs déjà entrées.
				// La transaction du pauvre en quelque sorte
				$this->MetadonneeModele->supprimerValeursMetadonneesParIdEnregistrementLie($id_nouvel_inscrit);
				return false;
			}
		}

		$appli_controleur = new ApplicationExterneControleur();

		$infos_nouvel_inscrit = array (
			'id_utilisateur' => $id_nouvel_inscrit,
			'prenom' => $prenom,
			'nom' => $nom,
			'mail' => trim($mail),
			'pass' => $pass,
			'pays' => $pays,
			'nouveau_pass' => '',
			'nouveau_mail' => ''
		);

		// on crée un controleur qui appelle les webservice pour chaque application externe
		$resumes_controleur = new ApplicationExterneControleur();
		$resumes_controleur->ajouterInscription($id_nouvel_inscrit, $infos_nouvel_inscrit);

		// Si tout a réussi on supprime les données d'inscription temporaire
		$this->DonneeTemporaireModele->supprimerDonneeTemporaire($identifiant);

		$infos_nouvel_inscrit['id_annuaire'] =  $id_annuaire;

		return $infos_nouvel_inscrit;
	}

	public function ajouterNouvelleInscriptionSansIdentifier($code_confirmation) {
		// TODO: ajouter un controle d'erreurs
		$inscription_ajout = $this->ajouterNouvelleInscription($code_confirmation);
		$id_annuaire = $inscription_ajout['id_annuaire'];

		return $this->afficherInscritsEnAttenteConfirmation($id_annuaire);
	}

	public function ajouterNouvelleInscriptionEtIdentifier($code_confirmation) {
		$inscription_ajout = $this->ajouterNouvelleInscription($code_confirmation);

		if (!$inscription_ajout) {
			$identificateur = new IdentificationControleur();

			$donnees['titre'] = 'Erreur d\'inscription';
			$donnees['message'] = 'Erreur : aucune demande d\'inscription ne correspond &agrave; ce lien <br />'.
			'La demande a peut &ecirc;tre d&eacute;j&agrave; &eacute;t&eacute; valid&eacute;e, essayez de vous connecter sur le site <br />'.
			'Si votre demande d\'inscription date de moins de deux semaines, essayez de vous connecter avec les informations fournies lors de l\'inscription<br />'.
			'Si votre demande d\'inscription date de plus de deux semaines, alors celle ci doit &ecirc;tre renouvel&eacute;e';

			$vue_resultat_inscription = $this->getVue(Config::get('dossier_squelettes_annuaires').'information_simple',$donnees).$identificateur->afficherFormulaireIdentification(Config::get('annuaire_defaut'), array());
		} else {
			$mail = $inscription_ajout['mail'];
			$pass = $inscription_ajout['pass'];
			$id_nouvel_inscrit = $inscription_ajout['id_utilisateur'];
			$prenom = $inscription_ajout['prenom'];
			$nom = $inscription_ajout['nom'];
			$id_annuaire = $inscription_ajout['id_annuaire'];

			$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire, false);

			// Identifier l'utilisateur !
			$identificateur = new IdentificationControleur();

			if(config::get('identification')) {
				$identificateur->deloggerUtilisateur();
			}

			$identificateur->loggerUtilisateur($mail, $pass);

			if ($this->annuaireAvoirPageAccueilPostInscription($annuaire['informations']['aa_code'])) {
				// on l'affiche
				$donnees = array('id_utilisateur' => $id_nouvel_inscrit, 'id_annuaire' => $id_annuaire);
				$vue_resultat_inscription = $this->getVue(Config::get('dossier_squelettes_annuaires').$annuaire['informations']['aa_code'].'_inscription_confirmation', $donnees);

			} else {
				// sinon on le redirige
				$vue_resultat_inscription = $this->afficherFicheUtilisateur($id_annuaire, $id_nouvel_inscrit);
			}
		}

		return $vue_resultat_inscription;
	}

	public function afficherInscritsEnAttenteConfirmation($id_annuaire) {
		$donnees['id_annuaire'] = $id_annuaire;

		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire);

		$this->chargerModele('DonneeTemporaireModele');

		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$inscrits_en_attente = $this->DonneeTemporaireModele->chargerListeDonneeTemporaire('8');

		$inscrits_en_attente_formates = array();

		foreach ($inscrits_en_attente as $inscrit_attente) {
			if ($id_annuaire == $inscrit_attente['aa_id_annuaire']) {
				$lien_confirmation_inscription = AppControleur::getUrlConfirmationInscriptionAdmin($inscrit_attente['code_confirmation']);
				$lien_suppression_inscription = AppControleur::getUrlSuppressionInscriptionTemporaire($id_annuaire, $inscrit_attente['code_confirmation']);

				$date_inscription_formatee = AppControleur::formaterDateMysqlVersDateAnnuaire($inscrit_attente['date']);

				$inscrits_en_attente_formates[] = array(
					'lien_confirmation' => $lien_confirmation_inscription,
					'lien_suppression' => $lien_suppression_inscription,
					'date_inscription' => $date_inscription_formatee,
					'mail' => $inscrit_attente['mail_'.$tableau_mappage[1]['champ_mail']],
					'nom' => $inscrit_attente['text_'.$tableau_mappage[1]['champ_nom']],
					'prenom' => $inscrit_attente['text_'.$tableau_mappage[1]['champ_prenom']]);
			}
		}

		$donnees['inscrits_en_attente'] = $inscrits_en_attente_formates;

		return $this->getVue(Config::get('dossier_squelettes_annuaires').'annuaire_inscrits_en_attente', $donnees);
	}

	public function supprimerInscriptionEnAttente($id_annuaire, $id_inscrit_en_attente) {
		$this->chargerModele('DonneeTemporaireModele');
		$inscrits_en_attente = $this->DonneeTemporaireModele->supprimerDonneeTemporaire($id_inscrit_en_attente);

		return $this->afficherInscritsEnAttenteConfirmation($id_annuaire);
	}

	public function afficherPage($id_annuaire, $id_utilisateur, $page) {
		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['id_utilisateur'] = $id_utilisateur;

		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire);

		$donnees['aa_id_annuaire'] = $id_annuaire;

		$this->chargerModele('MetadonneeModele');
		$champ_metadonnees = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id_annuaire);
		$valeurs_metadonnees = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);

		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		foreach ($champ_metadonnees as $champ_metadonnee) {
			$id_champ = $champ_metadonnee['amc_id_champ'];
			$nom_champ = $champ_metadonnee['amc_abreviation'];

			if(isset($valeurs_metadonnees[$nom_champ])) {
				$champ_metadonnee['valeur_defaut'] = $valeurs_metadonnees[$nom_champ];
			}

			$champ_metadonnee['aa_id_annuaire'] = $id_annuaire;
			// on charge le formulaire d'affichage de chacune des métadonnées
			$donnees['champs'][$nom_champ] = $this->afficherFormulaireChampMetadonnees($id_champ,$champ_metadonnee);
			$donnees['valeurs'] = $valeurs_metadonnees;
		}

		$navigateur = new NavigationControleur();
		$donnees['navigation'] = $navigateur->afficherBandeauNavigationUtilisateur($id_annuaire ,$id_utilisateur, $page);

		if ($this->templateExiste($page, '/pages/')) {
			return $this->getVue(Config::get('dossier_squelettes_pages').$page, $donnees);
		}
	}

	/**
	 * Affiche la fiche principale d'un utilisateur
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param int $id_utilisateur l'identifiant de l'utilisateur
	 * @return string la vue contenant la fiche utilisateur
	 */
	public function afficherFicheUtilisateur($id_annuaire, $id_utilisateur) {
		// Chargement des informations de l'utilisateur dans la table annuaire principale
		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire);

		$tableau_mappage = $this->AnnuaireModele->obtenirChampsMappageAnnuaire($id_annuaire);

		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['id_utilisateur'] = $id_utilisateur;

		$verificateur = new VerificationControleur();

		$champs = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);

		$donnees['tableau_mappage'] = $tableau_mappage[1];

		$donnees['champs'] = $champs;

		$url_modification_profil = self::getUrlModificationProfil($id_annuaire, $id_utilisateur);
		$donnees['url_modification_profil'] = $url_modification_profil;

		$navigateur = new NavigationControleur();
		$donnees['navigation'] = $navigateur->afficherBandeauNavigationUtilisateur($id_annuaire ,$id_utilisateur, 'fiche');

		// S'il existe une fiche spécifique pour l'annuaire
		if ($this->annuaireAvoirFicheUtilisateur($annuaire['informations']['aa_code'])) {
			// on l'affiche
			$fiche_inscrit = $this->getVue(Config::get('dossier_squelettes_fiches').$annuaire['informations']['aa_code'].'_fiche',$donnees);
		} else {
			// sinon on en génère une minimale par défaut
			$tableau_nom_mappage = $this->obtenirNomsChampsMappageAnnuaire($id_annuaire);
			$donnees['mappage_nom_champs'] = $tableau_nom_mappage;
			$fiche_inscrit = $this->genererFicheInscrit($donnees);

		}

		return $fiche_inscrit;
	}

	/** Affiche le resumé des contributions d'un utilisateur
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param int $id_utilisateur l'identifiant de l'utilisateur
	 * @return string la vue contenant les contributions utilisateur
	 */
	public function afficherFicheResumeUtilisateur($id_annuaire, $id_utilisateur) {
		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire);

		$champs = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);
		$mail_utilisateur = $this->AnnuaireModele->obtenirMailParId($id_annuaire,$id_utilisateur);

		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['id_utilisateur'] = $id_utilisateur;
		$donnees['mail_utilisateur'] = $mail_utilisateur;

		$url_modification_profil = self::getUrlModificationProfil($id_annuaire, $id_utilisateur);

		$url_oubli_mdp = self::getUrlOubliMotDePasse($id_annuaire, $id_utilisateur);

		$donnees['url_oubli_mdp'] = $url_oubli_mdp;
		$donnees['url_modification_profil'] = $url_modification_profil;

		$donnees['champs'] = $champs;

		$navigateur = new NavigationControleur();
		$donnees['navigation'] = $navigateur->afficherBandeauNavigationUtilisateur($id_annuaire ,$id_utilisateur, 'resume');

		// on crée un controleur appelle les hooks de résumé pour chaque application externe
		$resumes_controleur = new ApplicationExterneControleur();

		$donnees['resumes'] = $resumes_controleur->obtenirResume($id_utilisateur,$mail_utilisateur);

		$donnees['carte_id'] = $this->getVue(Config::get('dossier_squelettes_fiches').$annuaire['informations']['aa_code'].'_carte_id',$donnees);

		$fiche_contrib = $this->getVue(Config::get('dossier_squelettes_fiches').$annuaire['informations']['aa_code'].'_resume',$donnees);

		return $fiche_contrib;
	}

	public function gererInscriptionExterne($id_annuaire, $id_utilisateur) {
		$this->chargerModele('AnnuaireModele');
		$mail_utilisateur = $this->AnnuaireModele->obtenirMailParId($id_annuaire,$id_utilisateur);

		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['id_utilisateur'] = $id_utilisateur;

		$url_modification_profil = self::getUrlModificationProfil($id_annuaire,$id_utilisateur);

		$url_oubli_mdp = self::getUrlOubliMotDePasse($id_annuaire,$id_utilisateur);

		$donnees['url_oubli_mdp'] = $url_oubli_mdp;
		$donnees['url_modification_profil'] = $url_modification_profil;

		// on crée un controleur appelle les hooks de résumé pour chaque application externe
		$resumes_controleur = new ApplicationExterneControleur();

		$donnees['champs'] = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);

		$navigateur = new NavigationControleur();
		$donnees['navigation'] = $navigateur->afficherBandeauNavigationUtilisateur($id_annuaire ,$id_utilisateur, 'gestion');

		//Debug::printr($champs);
		$donnees['resumes'] = $resumes_controleur->gererInscription($id_utilisateur,$mail_utilisateur);
		$donnees['carte_id'] = $this->getVue(Config::get('dossier_squelettes_fiches').'annuaire_tela_inscrits_carte_id',$donnees);

		$fiche_contrib = $this->getVue(Config::get('dossier_squelettes_fiches').'annuaire_tela_inscrits_gestion_inscription',$donnees);

		return $fiche_contrib;
	}

	public function afficherFormulaireModificationInscription($id_annuaire, $id_utilisateur, $erreurs = array()) {
		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire);

		$donnees['aa_id_annuaire'] = $id_annuaire;

		$this->chargerModele('MetadonneeModele');
		$champ_metadonnees = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id_annuaire);
		$valeurs_metadonnees = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);

		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		// TODO: ceci charge toutes les métadonnées, voir si l'on ne peut pas parser le formulaire
		// pour ne charger que ce qui est nécéssaire
		foreach ($champ_metadonnees as $champ_metadonnee) {

			$id_champ = $champ_metadonnee['amc_id_champ'];
			$nom_champ = $champ_metadonnee['amc_abreviation'];

			if (isset($valeurs_metadonnees[$nom_champ])) {
				$champ_metadonnee['valeur_defaut'] = $valeurs_metadonnees[$nom_champ];
			}

			$champ_metadonnee['aa_id_annuaire'] = $id_annuaire;
			// on charge le formulaire d'affichage de chacune des métadonnées
			$donnees['champs'][$nom_champ] = $this->afficherFormulaireChampMetadonnees($id_champ,$champ_metadonnee);

		}

		$donnees['tableau_mappage'] = $tableau_mappage[1];

		$donnees['id_utilisateur'] = $id_utilisateur;
		$donnees['erreurs'] = $erreurs;


		// Si le formulaire spécifique à l'annuaire existe, on l'affiche
		if ($this->annuaireAvoirFormulaireModificationInscription($annuaire['informations']['aa_code'])) {
			// Sinon on prend celui par defaut
			$formulaire_modification = $this->GetVue(Config::get('dossier_squelettes_formulaires').$annuaire['informations']['aa_code'].'_modification',$donnees);
		} else {
			$tableau_nom_mappage = $this->obtenirNomsChampsMappageAnnuaire($id_annuaire);
			$donnees['mappage_nom_champs'] = $tableau_nom_mappage;

			$formulaire_modification = $this->genererFormulaireModificationInscription($donnees);
		}

		return $formulaire_modification;

	}

	public function modifierInscription($valeurs) {
		$this->chargerModele('MetadonneeModele');

		$id_utilisateur = $valeurs['id_utilisateur'];
		unset($valeurs['id_utilisateur']);

		$id_annuaire = $valeurs['aa_id_annuaire'];
		unset($valeurs['aa_id_annuaire']);

		$this->chargerModele('AnnuaireModele');
		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$mail_utilisateur = $this->AnnuaireModele->obtenirMailParId($id_annuaire, $id_utilisateur);
		$ancien_mail = $mail_utilisateur;

		$verificateur = new VerificationControleur();

		$valeurs_mappees = array();
		$valeurs_metadonnees = array();

		$erreurs = array();

		// on itère sur le tableau de valeur pour récupérer les métadonnées
		// et les valeurs
		foreach($valeurs as $nom_champ => $valeur) {

			// pour chaque valeur
			// on extrait l'id du champ
			$ids_champ = mb_split("_",$nom_champ);

			$confirmation = false;
			$valeur_a_ignorer = false;

			// l'identifiant du champ est la dernière valeur du tableau
			if(count($ids_champ) == 3) {

				$type = $ids_champ[0];
				$id_champ = $ids_champ[2];
				$condition = $ids_champ[1];

			} else {
				$type = $ids_champ[0];
				$condition = false;
				$id_champ = $ids_champ[1];
			}

			if($type == 'checkbox' && $condition != 'hidden') {
				// on récupère la valeur
					$nom_champ = $type.'_'.$id_champ;
					$valeur = $valeurs[$type.'_'.$id_champ];
			}

			// cas de la checkbox qui devrait être là mais pas cochée
			if ($condition == 'hidden') {
				if (!isset($valeurs[$type.'_'.$id_champ])) {
					// dans ce cas là on fabrique une valeur qui vaut 0
					$nom_champ = $type.'_'.$id_champ;
					$valeur = '0';
				} else {
					// sinon la valeur a déjà été traitée et doit être ignorée
					$valeur_a_ignorer = true;
				}
			}

			if ($type ==  'mail') {
				$mail_utilisateur = $valeur;
			}

			// cas du changement de mot de passe
			if ($type == 'password') {
				if ($condition == 'conf') {
					$valeur_a_ignorer = true;
				}

				$tentative_changemement_mdp = false;

				if (isset($valeurs[$type.'_conf_'.$id_champ]) && trim($valeurs[$type.'_conf_'.$id_champ]) != '') {
					$tentative_changemement_mdp = true;
				} else {
					$valeur_a_ignorer = true;
				}

				if ($tentative_changemement_mdp) {
					$confirmation = $valeurs[$type.'_conf_'.$id_champ];
				}
			}

			// Si la valeur n'est présente dans le formulaire que pour des raisons de vérification
			// on passe à l'iteration suivante
			if ($valeur_a_ignorer) {
				continue;
			}

			$verification = $verificateur->verifierErreurChampModification($id_annuaire, $id_utilisateur, $type , $valeur, $confirmation);

			if ($verification[0] == false) {
				$erreurs[$type.'_'.$id_champ] = $verification[1];

			}

			// on fait des vérifications et des remplacements sur certaines valeurs
			// et quelques fois des actions externes
			$valeur = $verificateur->remplacerValeurChampPourModification($id_annuaire, $id_utilisateur, $type, $valeur, $mail_utilisateur);

			// Si le champ fait partie des champs mappés
			$cle_champ = array_search($id_champ, $tableau_mappage[1]);
			if ($cle_champ) {
				// on ajoute sa clé correspondante dans l'annuaire mappé et sa valeur dans le tableau des champs mappés
				$valeurs_mappees[$tableau_mappage[0][$cle_champ]] = $valeur;
			} else {
				// sinon, il est stocké dans les valeurs de metadonnées
				$valeurs_metadonnees[$id_champ] = $valeur;
			}
		}

		if (count($erreurs) > 0) {
			return $this->afficherFormulaireModificationInscription($id_annuaire,$id_utilisateur,$erreurs);
		}


		if (isset($tableau_mappage[0]['champ_pays']) && isset($valeurs_mappees[$tableau_mappage[0]['champ_pays']])) {
			$pays = $valeurs_mappees[$tableau_mappage[0]['champ_pays']];
			$valeurs_metadonnees[$tableau_mappage[1]['champ_pays']] = $pays;
			$pays = $this->MetadonneeModele->renvoyerCorrespondanceAbreviationId($pays);
			$valeurs_mappees[$tableau_mappage[0]['champ_pays']] = $pays;
		} else {
			$pays = '';
		}

		$changement_mail = false;
		if ($ancien_mail != $mail_utilisateur) {
			$changement_mail = true;
		}

		if (isset($tableau_mappage[0]['champ_prenom']) && isset($valeurs_mappees[$tableau_mappage[0]['champ_prenom']])) {
			$valeurs['text_'.$tableau_mappage[1]['champ_prenom']] = AppControleur::formaterMotPremiereLettreChaqueMotEnMajuscule($valeurs['text_'.$tableau_mappage[1]['champ_prenom']]);
			$prenom = $valeurs['text_'.$tableau_mappage[1]['champ_prenom']];
		} else {
			$prenom = '';
		}

		$valeurs['text_'.$tableau_mappage[1]['champ_nom']] =  AppControleur::formaterMotEnMajuscule($valeurs['text_'.$tableau_mappage[1]['champ_nom']]);
		$nom = $valeurs['text_'.$tableau_mappage[1]['champ_nom']];

		$mail = $mail_utilisateur;
		$pass = $valeurs['password_'.$tableau_mappage[1]['champ_pass']];

		$this->chargerModele('AnnuaireModele');
		$modification_annuaire = $this->AnnuaireModele->modifierInscriptionDansAnnuaireMappe($id_annuaire, $id_utilisateur ,$valeurs_mappees, $tableau_mappage[0]);

		$nouveau_mail = $this->AnnuaireModele->obtenirMailParId($id_annuaire, $id_utilisateur);

		// Si le mail a changé alors il faut appeler les applications externes pour modification
		if ($ancien_mail != $mail_utilisateur || $tentative_changemement_mdp) {

			$appli_controleur = new ApplicationExterneControleur();

			$params = array (
				'id_utilisateur' => $id_utilisateur,
				'prenom' => $prenom,
				'nom' => $nom,
				'mail' => trim($ancien_mail),
				'pass' => $pass,
				'pays' => $pays,
				'nouveau_pass' => $pass,
				'nouveau_mail' => trim($nouveau_mail)
			);

			$appli_controleur->modifierInscription($id_utilisateur, $params);
		}

		// les champs arrivent avec un identifiant sous la forme type_xxx_id
		foreach ($valeurs_metadonnees as $id_champ => $valeur) {

			// S'il existe déjà une valeur de metadonnée pour cette colonne et cet utilisateur
			// car on a pu ajouter de nouveaux champs entre temps
			if ($this->MetadonneeModele->valeurExiste($id_champ,$id_utilisateur)) {
				// On se contente de la modifier
				$this->MetadonneeModele->modifierValeurMetadonnee($id_champ,$id_utilisateur,$valeur);
			} else {
				// S'il n'existe pas de valeur, on ajoute une nouvelle ligne à la table de valeurs de meta données
				if ($this->MetadonneeModele->ajouterNouvelleValeurMetadonnee($id_champ,$id_utilisateur,$valeur)) {
					// Si l'insertion a réussi, on continue
				} else {
					return false;
				}
			}
		}

		if ($changement_mail) {
			$identificateur = new IdentificationControleur();
			$identificateur->setUtilisateur($nouveau_mail);
		}

		$statistique = new StatistiqueControleur();
		$statistique->ajouterEvenementStatistique($id_annuaire, $id_utilisateur, 'modification');

		return $this->afficherFicheUtilisateur($id_annuaire, $id_utilisateur);
	}

	public function bloquerDebloquerUtilisateur($id_annuaire, $id_utilisateur, $bloquer = true) {
		$annuaire_modele = $this->getModele('AnnuaireModele');
		$champs_description = $annuaire_modele->obtenirChampsDescriptionAnnuaire($id_annuaire);

		$valeur = '0';

		if ($bloquer) {
			$valeur = '1';
		}

		$metadonne_modele = $this->getModele('MetadonneeModele');
		$metadonne_modele->modifierValeurMetadonnee($champs_description[1]['champ_statut'],$id_utilisateur,$valeur);

		return $this->afficherFicheUtilisateur($id_annuaire, $id_utilisateur);
	}

	/**
	 * Affiche le formulaire permettant d'entrer un mail et de recevoir le mot de passe
	 * associé sur cette adresse
	 * @param int $id_annuaire l'identifiant de l'annuaire associé
	 */
	public function afficherFormulaireOubliMotDePasse($id_annuaire) {
		$donnees['aa_id_annuaire'] = $id_annuaire;
		return $this->getVue(Config::get('dossier_squelettes_formulaires').'oubli_mdp',$donnees);
	}

	/**
	 * Supprime l'ancien mot de passe d'un utilisateur et crée un nouveau mot de passe
	 * aléatoire qui sera envoyé par mail
	 * @param int $id_annuaire l'identifiant de l'annuaire associé
	 * @param int $mail le mail auquel on envoie le mot de passe
	 *
	 */
	public function reinitialiserMotDePasse($id_annuaire, $mail) {
		$this->chargerModele('AnnuaireModele');
		$verificateur = new VerificationControleur();
		$messagerie = new MessageControleur();

		$donnees = array();

		if (!$verificateur->mailValide($mail) || !$this->AnnuaireModele->utilisateurExisteParMail($id_annuaire,$mail)) {
			$donnees['erreurs']['mail'] = 'Cet utilisateur n\'existe pas';
			$donnees['aa_id_annuaire'] = $id_annuaire;
			return $this->getVue(Config::get('dossier_squelettes_formulaires').'oubli_mdp',$donnees);
		}

		$nouveau_mdp = $verificateur->genererMotDePasse();
		$nouveau_mdp_encrypte = $verificateur->encrypterMotDePasse($nouveau_mdp);

		$modif_mdp = $this->AnnuaireModele->reinitialiserMotDePasse($id_annuaire, $mail, $nouveau_mdp_encrypte);

		if (!$modif_mdp) {
			$donnees['erreurs']['mdp'] = 'Impossible de générer un nouveau mot de passe';
			$donnees['aa_id_annuaire'] = $id_annuaire;
			return $this->getVue(Config::get('dossier_squelettes_formulaires').'oubli_mdp',$donnees);
		}

		if ($messagerie->envoyerMailOubliMdp($id_annuaire, $mail, $nouveau_mdp)) {
			$donnees['titre'] = 'Mot de passe envoyé';
			$donnees['message'] = 'Votre nouveau mot de passe a été envoyé à l\'adresse '.$mail;
		} else {
			$donnees['titre'] = 'Impossible de renvoyer le nouveau mot de passe';
			$donnees['message'] = 'Votre nouveau mot de passe n\'a pas pu être envoyé à l\'adresse indiquée ';
		}

		return $this->getVue(Config::get('dossier_squelettes_annuaires').'information_simple',$donnees);
	}

	public function afficherFormulaireSuppressionInscription($id_annuaire, $id_utilisateur) {
		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['id_utilisateur'] = $id_utilisateur;
		return $this->getVue(Config::get('dossier_squelettes_formulaires').'suppression_inscription',$donnees);

	}

	/**
	 * Supprime l'inscription d'un utilisateur dans un annuaire donné
	 * @param int $id_annuaire l'identifiant de l'annuaire associé
	 * @param int $id_utilisateur l'identifiant de l'utilisateur à supprimer
	 */
	public function supprimerInscription($id_annuaire, $id_utilisateur) {
		if (!$id_utilisateur || $id_utilisateur == '') {
			return $this->index();
		}

		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire);
		$champs_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$mail_utilisateur = $this->AnnuaireModele->obtenirMailParId($id_annuaire, $id_utilisateur);

		$suppression_inscription = $this->AnnuaireModele->supprimerInscriptionDansAnnuaireMappe($id_annuaire, $id_utilisateur);

		if (!$mail_utilisateur || $mail_utilisateur == '') {
			return $this->index();
		}

		$donnees = array('erreurs' => array());

		$this->chargerModele('MetadonneeModele');
 		$suppression_metadonnees = $this->MetadonneeModele->supprimerValeursMetadonneesParIdEnregistrementLie($id_utilisateur);

		if (!$suppression_inscription || !$suppression_metadonnees) {
			$donnees['erreurs']['inscription'] = $suppression_inscription;
			$donnees['erreurs']['metadonnees'] = $suppression_metadonnees;
			$donnees['erreurs']['titre'] = 'Erreur lors de la suppression de l\'inscription ';

			return $this->getVue(Config::get('dossier_squelettes_elements').'erreurs',$donnees);
		}

		$params = array (
			'id_utilisateur' => $id_utilisateur,
			'prenom' => '',
			'nom' => '',
			'mail' => $mail_utilisateur,
			'pass' => '',
			'pays' => '',
			'nouveau_pass' => '',
			'nouveau_mail' => ''
		);

		// on appelle les controleur de lettre actu et d'applications externes
		$appli_controleur = new ApplicationExterneControleur();
		$appli_controleur->supprimerInscription($id_utilisateur, $params);

		// pour qu'ils lancent les procédures de désinscription associées
		$lettre_controleur = new LettreControleur();
		$lettre_controleur->desinscriptionLettreActualite($mail_utilisateur);

		if ($id_utilisateur == Registre::getInstance()->get('identification_id')) {
			$identificateur = new IdentificationControleur();
			$identificateur->deloggerUtilisateur();
		}

		$donnees = array();

		// Si le formulaire spécifique à l'annuaire existe, on l'affiche
		if ($this->annuaireAvoirPagePostDesinscription($annuaire['informations']['aa_code'])) {
			$informations_desinscription = $this->GetVue(Config::get('dossier_squelettes_annuaires').$annuaire['informations']['aa_code'].'_desinscription_confirmation',$donnees);
		} else {
			// Sinon on prend celui par defaut
			$donnees['titre'] = 'Vous êtes maintenant désinscrit de l\'annuaire';
			$donnees['message'] = 'Votre désinscription a bien été prise en compte <br />';

			$informations_desinscription = $this->getVue(Config::get('dossier_squelettes_annuaires').'information_simple',$donnees);
		}

		$statistique = new StatistiqueControleur();
		$statistique->ajouterEvenementStatistique($id_annuaire, $id_utilisateur, 'suppression');

		return $informations_desinscription;
	}

	/**
	 * Affiche le formulaire de recherche pour un annuaire donné ou en génère un à la volée
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param array $donnees des données utilisées pour pré remplir le formulaire
	 * @return string le html contenant le formulaire de recherche
	 */
	public function afficherFormulaireRecherche($id_annuaire, $donnees = array()) {
		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire, false);

		$this->chargerModele('MetadonneeModele');
		$metadonnees = $this->MetadonneeModele->chargerListeMetadonneeAnnuaire($id_annuaire);

		$donnees['aa_id_annuaire'] = $id_annuaire;

		// TODO: ceci charge toutes les métadonnées, voir si l'on ne peut pas parser le formulaire
		// pour ne charger que ce qui est nécéssaire

		foreach ($metadonnees as $nom_champ => $metadonnee) {
			$id_champ = $metadonnee['amc_id_champ'];
			$type_champ = $metadonnee['amc_ce_template_affichage'];
			$nom_champ = $metadonnee['amc_abreviation'];

			if(isset($donnees[$type_champ.'_'.$id_champ])) {

				$metadonnee['valeur_defaut']['amv_valeur'] = $donnees[$type_champ.'_'.$id_champ];
			}

			$metadonnee['aa_id_annuaire'] = $id_annuaire;
			// on charge le formulaire d'affichage de chacune des métadonnées
			$donnees['champs'][$nom_champ] = $this->afficherFormulaireChampMetadonnees($id_champ,$metadonnee);
		}

		// Si le formulaire spécifique à l'annuaire existe, on l'affiche
		if ($this->annuaireAvoirFormulaireRecherche($annuaire['informations']['aa_code'])) {
			// Sinon on prend celui par defaut
			$formulaire_recherche = $this->GetVue(Config::get('dossier_squelettes_formulaires').$annuaire['informations']['aa_code'].'_recherche',$donnees);
		} else {
			$tableau_nom_mappage = $this->obtenirNomsChampsMappageAnnuaire($id_annuaire);
			$donnees['mappage_nom_champs'] = $tableau_nom_mappage;
			$formulaire_recherche = $this->genererFormulaireRecherche($donnees);
		}

		return $formulaire_recherche;
	}

	/**
	 * Recherche un ou plusieurs inscrits selon les valeurs passées en paramètres, qui peuvent êtres des valeurs
	 * dans l'annuaire mappé ou bien des valeurs de metadonnées
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param array $valeurs_recherchees les valeurs à rechercher
	 * @param boolean $exclusive indique si la recherche si fait sur tous les critères ou bien sur au moins un
	 */
	public function rechercherInscrit($id_annuaire, $valeurs_recherchees, $exclusive = true) {
		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire, true);

		if (isset($_GET['numero_page'])) {
			$numero_page = $_GET['numero_page'];
			unset($_GET['numero_page']);
			unset($valeurs_recherchees['numero_page']);
		} else {
			$numero_page = 1;
		}

		if (isset($_GET['taille_page'])) {
			$taille_page = $_GET['taille_page'];
			unset($_GET['taille_page']);
			unset($valeurs_recherchees['taille_page']);
		} else {
			$taille_page = 50;
		}

		$tableau_mappage = $this->AnnuaireModele->obtenirChampsMappageAnnuaire($id_annuaire);

		$valeurs_mappees = array();
		$valeurs = array();

		$collecteur = new VerificationControleur();
		$tableau_valeur_collectees = $collecteur->collecterValeursRechercheMoteur($valeurs_recherchees, $tableau_mappage);

		$valeurs_recherchees = $tableau_valeur_collectees['valeurs_recherchees'];
		$valeurs_mappees = $tableau_valeur_collectees['valeurs_mappees'];
		$valeurs_get = $tableau_valeur_collectees['valeurs_get'];

		$admin = Registre::getInstance()->get('est_admin');

		// on recherche dans les métadonnées
		$this->chargerModele('MetadonneeModele');
		// le résultat est un ensemble d'identifiants
		$resultat_metadonnees = $this->MetadonneeModele->rechercherDansValeurMetadonnees($id_annuaire,$valeurs_recherchees, $exclusive);

		// on recherche les infos dans la table annuaire mappée
		// en incluant ou excluant les id déjà trouvées dans les metadonnées
		// suivant le critères d'exclusivité ou non
		$resultat_annuaire_mappe = $this->AnnuaireModele->rechercherInscritDansAnnuaireMappe($id_annuaire,$valeurs_mappees, $resultat_metadonnees, $exclusive, $numero_page, $taille_page);

		$resultat_recherche = $resultat_annuaire_mappe['resultat'];

		$nb_resultats = $resultat_annuaire_mappe['total'];

		$champ_id_annuaire = $tableau_mappage[0]['champ_id'];

		$resultats = array();
		foreach ($resultat_recherche as $resultat) {
			$id_utilisateur = $resultat[$champ_id_annuaire];
			$resultats[$id_utilisateur] = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);
		}

		// on renvoie une liste identique à celle de la liste des inscrits
		$donnees['resultats_recherche'] = $resultats;
		$donnees['tableau_mappage'] = $tableau_mappage[1];
		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['nb_resultats'] = $nb_resultats;

		$url_base = new URL(Registre::getInstance()->get('base_url_application'));
		$url_pagination = clone($url_base);

		$valeurs_get['m'] = $_GET['m'];

		$valeurs_get['id_annuaire'] = $id_annuaire;
		$donnees['pagination'] = $this->paginer($numero_page,$taille_page,$nb_resultats,$url_pagination, $valeurs_get);

		$valeurs_get['exclusive'] = $exclusive;
		$donnees['criteres'] = urlencode(serialize($valeurs_get));

		$valeurs_get['id_annuaire'] = $id_annuaire;

		// S'il existe une page de résultats spécifique à l'annuaire pour la recherche
		if ($this->annuaireAvoirPageResultatRecherche($annuaire['informations']['aa_code'])) {
			// on l'affiche
			$vue_resultat_recherche = $this->getVue(Config::get('dossier_squelettes_annuaires').$annuaire['informations']['aa_code'].'_resultat_recherche', $donnees);
		} else {
			// sinon on prend celle par défaut
			$tableau_nom_mappage = $this->obtenirNomsChampsMappageAnnuaire($id_annuaire);
			$donnees['mappage_nom_champs'] = $tableau_nom_mappage;

			$vue_resultat_recherche = $this->getVue(Config::get('dossier_squelettes_annuaires').'resultat_recherche', $donnees);
		}

		return $this->afficherFormulaireRecherche($id_annuaire, $valeurs_get).$vue_resultat_recherche;
	}

	/** Recherche un ou plusieurs inscrits selon des indications géographiques, qui peuvent êtres des valeurs
	 * dans l'annuaire mappé ou bien des valeurs de metadonnées
	 * @param int $id_annuaire l'identifiant de l'annuaire
	 * @param array $valeurs_recherchees les valeurs à rechercher
	 * @param boolean $exclusive indique si la recherche si fait sur tous les critères ou bien sur au moins un
	 * @param int $numero_page le numero de page demandé
	 * @param int $taille_page la taille de page
	 */
	public function rechercherInscritParlocalisation($id_annuaire,$valeurs_recherchees) {
		if (isset($_GET['taille_page'])) {
			$taille_page = $_GET['taille_page'];
		} else  {
			$taille_page = 50;
		}

		if (isset($_GET['numero_page'])) {
			$numero_page = $_GET['numero_page'];
		} else {
			$numero_page = 1;
		}

		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire, true);

		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);

		$valeurs_get = $valeurs_recherchees;

		$valeurs_mappees = array();
		$valeurs = array();

		$continent = $valeurs_recherchees['continent'];

		$champ_id_annuaire = $tableau_mappage[0]['champ_id'];

		$valeur = $valeurs_recherchees['pays'];
		$champ_critere = $tableau_mappage[0]['champ_pays'];

		$criteres = array($tableau_mappage[0]['champ_pays'] => $valeurs_recherchees['pays']);

		if (isset($valeurs_recherchees['departement'])) {
			$valeur = $valeurs_recherchees['departement'];
			$champ_critere = $tableau_mappage[0]['champ_code_postal'];

			$criteres = array(
				$tableau_mappage[0]['champ_pays'] => $valeurs_recherchees['pays'],
				$tableau_mappage[0]['champ_code_postal'] => $valeurs_recherchees['departement']
			);
		}

		$resultat_annuaire_mappe = $this->AnnuaireModele->rechercherInscritDansAnnuaireMappeParTableauChamps($id_annuaire, $criteres, true, $numero_page, $taille_page);

		$resultat_recherche = $resultat_annuaire_mappe;

		$nb_resultats = $resultat_recherche['total'];
		$resultat_recherche = $resultat_recherche['resultat'];

		$resultats = array();
		foreach ($resultat_recherche as $resultat) {
			$id_utilisateur = $resultat[$champ_id_annuaire];
			$resultats[$id_utilisateur] = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);
		}

		// on renvoie une liste identique à celle de la liste des inscrits
		$donnees['resultats_recherche'] = $resultats;
		$donnees['tableau_mappage'] = $tableau_mappage[1];
		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['nb_resultats'] = $nb_resultats;

		$donnees['criteres'] = urlencode(serialize(array(
			'select_'.$tableau_mappage[1]['champ_pays'] => $valeurs_recherchees['pays'],
			'text_'.$tableau_mappage[1]['champ_code_postal'] => $valeurs_recherchees['departement'],
			'exclusive' => true
		)));

		$url_base = new URL(Registre::getInstance()->get('base_url_application'));
		$url_pagination = clone($url_base);

		$valeurs_get['id_annuaire'] = $id_annuaire;
		$valeurs_get['m'] = $_GET['m'];

		$donnees['pagination'] = $this->paginer($numero_page,$taille_page,$nb_resultats,$url_pagination, $valeurs_get);

		if ($this->annuaireAvoirPageResultatRecherche($annuaire['informations']['aa_code'])) {
			// on l'affiche
			$navigation_carto = new NavigationControleur();
			$cartographe = new CartoControleur();
			$donnees_navigation = $cartographe->obtenirUrlsNavigation($id_annuaire,$valeurs_recherchees['continent'],$valeurs_recherchees['pays'],$valeurs_recherchees['departement']);
			$donnees['navigation'] = $navigation_carto->afficherBandeauNavigationCartographie($donnees_navigation);
			$vue_resultat_recherche = $this->getVue(Config::get('dossier_squelettes_annuaires').$annuaire['informations']['aa_code'].'_resultat_recherche', $donnees);
		} else {
		// sinon on prend celle par défaut
			$tableau_nom_mappage = $this->obtenirNomsChampsMappageAnnuaire($id_annuaire);
			$donnees['mappage_nom_champs'] = $tableau_nom_mappage;

			$vue_resultat_recherche = $this->getVue(Config::get('dossier_squelettes_annuaires').'resultat_recherche', $donnees);
		}

		return $vue_resultat_recherche;
	}

	public function rechercherDoublons($id_annuaire) {
		if (isset($_GET['taille_page'])) {
			$taille_page = $_GET['taille_page'];
		} else  {
			$taille_page = 50;
		}

		if (isset($_GET['numero_page'])) {
			$numero_page = $_GET['numero_page'];
		} else {
			$numero_page = 1;
		}

		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire, true);
		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		$champ_id_annuaire = $tableau_mappage[0]['champ_id'];

		$resultat_recherche_doublons= $this->AnnuaireModele->rechercherDoublonsDansAnnuaireMappe($id_annuaire, $numero_page, $taille_page);

		$nb_resultats = $resultat_recherche_doublons['total'];
		$resultat_recherche = $resultat_recherche_doublons['resultat'];

		$resultats = array();
		foreach ($resultat_recherche as $resultat) {
			$id_utilisateur = $resultat[$champ_id_annuaire];
			$resultats[$id_utilisateur] = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);
		}

		// on renvoie une liste identique à celle de la liste des inscrits
		$donnees['resultats_recherche'] = $resultats;
		$donnees['tableau_mappage'] = $tableau_mappage[1];
		$donnees['id_annuaire'] = $id_annuaire;
		$donnees['nb_resultats'] = $nb_resultats;

		$url_base = new URL(Registre::getInstance()->get('base_url_application'));
		$url_pagination = clone($url_base);

		$valeurs_get = array('m' => 'annuaire_recherche_doublons', 'id_annuaire' => $id_annuaire);
		$donnees['pagination'] = $this->paginer($numero_page,$taille_page,$nb_resultats,$url_pagination, $valeurs_get);

		if ($this->annuaireAvoirPageResultatRecherche($annuaire['informations']['aa_code'])) {
			// on l'affiche
			$vue_resultat_recherche = $this->getVue(Config::get('dossier_squelettes_annuaires').$annuaire['informations']['aa_code'].'_resultat_recherche', $donnees);
		} else {
		// sinon on prend celle par défaut
			$tableau_nom_mappage = $this->obtenirNomsChampsMappageAnnuaire($id_annuaire);
			$donnees['mappage_nom_champs'] = $tableau_nom_mappage;

			$vue_resultat_recherche = $this->getVue(Config::get('dossier_squelettes_annuaires').'resultat_recherche', $donnees);
		}

		return $vue_resultat_recherche;
	}

/** --- Fonction pour les images ------------------------------------------------------------------------*/

	public function afficherFormulaireUploadImage($id_annuaire,$id_utilisateur, $donnees = array()) {
		$donnees['aa_id_annuaire'] = $id_annuaire;
		$donnees['id_utilisateur'] = $id_utilisateur;

		$donnees['amc_nom'] = 'Image de profil';

		$this->chargerModele('AnnuaireModele');
		$id_champ_image = $this->AnnuaireModele->obtenirChampAvatar($id_annuaire);

		if (!$id_champ_image) {
			$donnees['erreurs'] = 'Aucun champ n\'est défini pour l\'image de profil';
			return $this->getVue(Config::get('dossier_squelettes_elements').'erreurs',$donnees);
		}

		$formats = str_replace('|',', ',Config::get('extensions_acceptees'));
		$donnees['formats'] = $formats;

		$taille_max = Config::get('taille_max_images');
		$taille_max_formatee = VerificationControleur::convertirTailleFichier(Config::get('taille_max_images'));

		$donnees['taille_max_formatee'] = $taille_max_formatee;
		$donnees['taille_max'] = $taille_max;

		$donnees['amc_id_champ'] = $id_champ_image;

		return $this->getVue(Config::get('dossier_squelettes_champs').'image',$donnees);
	}

	/**
	 * Ajoute une image uploadée à travers le formulaire
	 *
	 */
	public function ajouterImageUtilisateur($id_annuaire, $id_utilisateur, $fichier_a_stocker, $retourner_booleen = false) {
		$donnees = array('erreurs' => array(), 'aa_id_annuaire' => $id_annuaire);

		foreach ($fichier_a_stocker as $nom_champ => $fichier) {
			$ids_champ = mb_split("_",$nom_champ, 3);
			if (count($ids_champ) == 2) {
				$type = $ids_champ[0];
				$id_champ = $ids_champ[1];
			} else {
				trigger_error('Ce champ n\'est pas relié à un annuaire');
				return false;
			}

			$this->chargerModele('ImageModele');

			$format_accepte = $this->ImageModele->verifierFormat($fichier['name']);

			if (!$format_accepte) {
				$formats = str_replace('|',', ',Config::get('extensions_acceptees'));
				$donnees['erreurs'][$id_champ] = 'Cette extension de fichier n\'est pas prise en charge. Veuillez utiliser un fichier portant l\'une des extensions suivantes :'.$formats ;

				return $this->afficherFormulaireUploadImage($id_annuaire, $id_utilisateur,$donnees);
			}

			$stockage_image = $this->ImageModele->stockerFichier($id_annuaire, $id_utilisateur, $fichier);

			$this->chargerModele('MetadonneeModele');

			if ($this->MetadonneeModele->valeurExiste($id_champ,$id_utilisateur)) {
				// On se contente de la modifier
				if ($stockage_image && $this->MetadonneeModele->modifierValeurMetadonnee($id_champ,$id_utilisateur,$id_utilisateur)) {
				} else {
					$donnees['erreurs'][$id_champ] = 'Problème durant le stockage de l\'image';
					return $this->afficherFormulaireUploadImage($id_annuaire, $id_utilisateur,$donnees);
				}
			} else {
				// S'il n'existe pas de valeur, on ajoute une nouvelle ligne à la table de valeurs de meta données
				if ($stockage_image && $this->MetadonneeModele->ajouterNouvelleValeurMetadonnee($id_champ,$id_utilisateur,$id_utilisateur)) {
					// Si l'insertion a réussi, on continue
				} else {
					$donnees['erreurs'][$id_champ] = 'Problème durant le stockage de l\'image';
					return $this->afficherFormulaireUploadImage($id_annuaire, $id_utilisateur,$id_champ,$donnees);
				}
			}
		}

		if ($retourner_booleen) {
			return true;
		} else {
			return $this->afficherFicheUtilisateur($id_annuaire, $id_utilisateur) ;
		}
	}

	public function obtenirTableauDerniersInscrits($id_annuaire, $limite = '20') {
		// Chargement des informations de l'utilisateur dans la table annuaire principale
		$this->chargerModele('AnnuaireModele');
		$annuaire = $this->AnnuaireModele->chargerAnnuaire($id_annuaire);
		$tableau_mappage = $this->obtenirChampsMappageAnnuaire($id_annuaire);
		$this->chargerModele('AnnuaireModele');
		$tableau_ids = $this->AnnuaireModele->obtenirTableauIdsUtilisateurs($id_annuaire, $tableau_mappage[0]);

		$derniers_inscrits = array();

		foreach ($tableau_ids as $id) {

			$id_utilisateur = $id[$tableau_mappage[0][champ_id]];
			$derniers_inscrits[$id_utilisateur] = $this->obtenirValeursUtilisateur($id_annuaire, $id_utilisateur);
		}

		return $derniers_inscrits;
	}

	public function chargerNombreAnnuaireListeInscrits($id_annuaire) {
		$annuaire_modele = $this->getModele('AnnuaireModele');
		return $annuaire_modele->chargerNombreAnnuaireListeInscrits($id_annuaire);
	}

	public function chargerNombreAnnuaireListeInscritsParPays($id_annuaire, $id_zones) {
		$annuaire_modele = $this->getModele('AnnuaireModele');
		return $annuaire_modele->chargerNombreAnnuaireListeInscritsParPays($id_annuaire, $id_zones);
	}

	public function chargerNombreAnnuaireListeInscritsParDepartement($id_annuaire) {
		$this->chargerModele('AnnuaireModele');
		return $this->AnnuaireModele->chargerNombreAnnuaireListeInscritsParDepartement($id_annuaire);
	}
}
