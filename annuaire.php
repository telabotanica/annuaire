<?php
// declare(encoding='UTF-8');
/**
 *
 * PHP version 5
 *
 * @category PHP
 * @package Framework
 * @author Aurelien PERONNET <aurelien@tela-botanica.org>
 * @copyright Tela-Botanica 2009
 * @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license   http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version   SVN: $$Id$$
 * @link /doc/framework/
 */

include_once('initialisation.php');

// on récupère les variables d'identification
$identification = Config::get('identification');
$annuaire_controleur = new AnnuaireControleur();
if($identification) {
	$id = $annuaire_controleur->obtenirIdParMail('1', $identification);
} else {
	$id = false;
}

Registre::getInstance()->set('est_admin',false);
Registre::getInstance()->set('identification_id',$id);
Registre::getInstance()->set('identification_mail',$identification);

// identification
// TODO : faire mieux (un wrapper pour gérer différents types d'objets)
if(isset($_GET['id_utilisateur'])) {
	$GLOBALS['id_utilisateur'] = $_GET['id_utilisateur'];
} else if (isset($_POST['id_utilisateur'])) {
	$GLOBALS['id_utilisateur'] = $_POST['id_utilisateur'];
} else {
	$GLOBALS['id_utilisateur'] = $id;
}


/**
 * Fonction d'affichage de Papyrus, pour le corps de page
 */
function afficherContenuCorps() {

	// TODO : rendre cette partie modulable.
	if(isset($_GET['id_annuaire'])) {
		$id_annuaire = $_GET['id_annuaire'];
	} else {
		$_GET['id_annuaire'] = Config::get('annuaire_defaut');
	}
	
	$methode = '';

	if (isset($_GET['m'])) {
		$methode = $_GET['m'];
		//unset($_GET['m']);
	} else {
		if (isset($_POST['m'])) {
			$methode = $_POST['m'];
			//unset($_POST['m']);
		} else {
				// Gestion des paramêtres définis dans Papyrus
			if (isset($GLOBALS['_GEN_commun']['info_application']->m)) {
				
				if($methode != 'annuaire_afficher_formulaire_oubli_mdp' && $methode != 'annuaire_oubli_mdp') {
					       $methode = $GLOBALS['_GEN_commun']['info_application']->m;
				}
			}

			// Gestion des paramêtres définis dans Papyrus
			if (isset($GLOBALS['_GEN_commun']['info_application']->id_annuaire)) {
					        $_GET['id_annuaire'] = $GLOBALS['_GEN_commun']['info_application']->id_annuaire;
			}
		}
	}

	$identification = Config::get('identification');

	if(!$identification) {

		switch ($methode) {
			case 'annuaire_formulaire_inscription':
				$controleur = new AnnuaireControleur();
				$id = $_GET['id_annuaire'];
				$retour = $controleur->afficherFormulaireInscription($id);
				break;

			case 'annuaire_ajout_inscription':
				$valeurs = $_POST;
				$controleur = new AnnuaireControleur();
				$retour = $controleur->ajouterInscriptionTemporaire($valeurs);
				break;

			case 'annuaire_inscription_confirmation':
				$identifiant = $_GET['id'];
				$controleur = new AnnuaireControleur();
				$retour = $controleur->ajouterNouvelleInscriptionEtIdentifier($identifiant);
				break;
				
			case 'f_oubli_mdp':
				$identifiant_annuaire = $_GET['id_annuaire'];
				$controleur = new AnnuaireControleur();
				$retour = $controleur->afficherFormulaireOubliMotDePasse($identifiant_annuaire);
			break;

			case 'annuaire_oubli_mdp':
				$identifiant_annuaire = $_GET['id_annuaire'];
				$mail = $_POST['mail'];
				$controleur = new AnnuaireControleur();
				$retour = $controleur->reinitialiserMotDePasse($identifiant_annuaire, $mail);
			break;
			
			case 'annuaire_afficher_carte':
				$identifiant_annuaire = $_GET['id_annuaire'];
	
				$continent = null;
				$pays = null;
				$departement = null;
	
				if(isset($_GET['continent'])) {
					$continent = $_GET['continent'];
				}
	
				if(isset($_GET['pays'])) {
					$pays = $_GET['pays'];
				}
	
				if(isset($_GET['departement'])) {
					$departement = $_GET['departement'];
				}
	
				$controleur = new CartoControleur();
				$retour = $controleur->cartographier($identifiant_annuaire, $continent, $pays, $departement);
			break;

			case 'annuaire_inscrits_carto':
				$identifiant_annuaire = $_GET['id_annuaire'];
				$criteres = $_GET;
				$controleur = new AnnuaireControleur();
				$retour = $controleur->rechercherInscritParlocalisation($identifiant_annuaire,$criteres);
			break;

			default :
				$id = $_GET['id_annuaire'];
				$controleur = new IdentificationControleur();
				$retour = $controleur->afficherFormulaireIdentification($id);
			break;
		}

		if (Config::get('sortie_encodage') != Config::get('appli_encodage')) {
			$retour = mb_convert_encoding($retour, Config::get('sortie_encodage'),Config::get('appli_encodage'));
		}

		return $retour;
	}


	switch ($methode) {

		case 'annuaire_inscrits':
			$controleur = new AnnuaireControleur();
			$id = $_GET['id_annuaire'];

			if(isset($_GET['taille_page'])) {
				$taille_page = $_GET['taille_page'];
			} else  {
				$taille_page = 50;
			}

			if(isset($_GET['numero_page'])) {
				$numero_page = $_GET['numero_page'];
			} else {
				$numero_page = 1;
			}
			$retour = $controleur->afficherFormulaireRecherche($id);
			$retour .= $controleur->chargerAnnuaireListeInscrits($id, $numero_page, $taille_page);
			break;
			
		case 'annuaire_afficher_page':
			$id_annuaire = $_GET['id_annuaire'];
			$page = $_GET['page'];
			
			$controleur = new AnnuaireControleur();
			$retour = $controleur->afficherPage($id_annuaire, $GLOBALS['id_utilisateur'], $page);
			break;

		case 'annuaire_fiche_utilisateur_consultation':
			$identifiant_annuaire = $_GET['id_annuaire'];
			$controleur = new AnnuaireControleur();
			$retour = $controleur->afficherFicheUtilisateur($identifiant_annuaire,$GLOBALS['id_utilisateur']);
			break;

		case 'annuaire_fiche_resume_consultation':
			$identifiant_annuaire = $_GET['id_annuaire'];
			$controleur = new AnnuaireControleur();
			$retour = $controleur->afficherFicheResumeUtilisateur($identifiant_annuaire,$GLOBALS['id_utilisateur']);
			break;

		case 'annuaire_fiche_gestion_consultation':
			$identifiant_annuaire = $_GET['id_annuaire'];
			$controleur = new AnnuaireControleur();
			$retour = $controleur->gererInscriptionExterne($identifiant_annuaire,Registre::getInstance()->get('identification_id'));
			break;


		case 'annuaire_formulaire_modification_inscription':
			$controleur = new AnnuaireControleur();
			$id_annuaire = $_GET['id_annuaire'];
			$retour = $controleur->afficherFormulaireModificationInscription($id_annuaire, Registre::getInstance()->get('identification_id'));
			break;

		case 'annuaire_modification_inscription':
			$controleur = new AnnuaireControleur();
			$valeurs = $_POST;
			$retour = $controleur->modifierInscription($_POST);

			break;

		case 'annuaire_afficher_formulaire_ajout_image':
			$identifiant_annuaire = $_GET['id_annuaire'];
			$controleur = new AnnuaireControleur();
			$retour = $controleur->afficherFormulaireUploadImage($identifiant_annuaire,Registre::getInstance()->get('identification_id'), $_GET);
			break;

		case 'annuaire_ajouter_image':
			$identifiant_annuaire = $_GET['id_annuaire'];
			$GLOBALS['id_utilisateur'] = $_GET['id_utilisateur'];
			$infos_images = $_FILES;
			$controleur = new AnnuaireControleur();
			$retour = $controleur->ajouterImageUtilisateur($identifiant_annuaire,Registre::getInstance()->get('identification_id'),$infos_images);
			break;


		case 'annuaire_suppression_inscription':
			$identifiant_annuaire = $_GET['id_annuaire'];
			$id_utilisateur = $_GET['id_utilisateur'];
			$controleur = new AnnuaireControleur();
			$retour = $controleur->supprimerInscription($identifiant_annuaire,Registre::getInstance()->get('identification_id'));
			break;

		case 'annuaire_formulaire_suppression_inscription':
			$identifiant_annuaire = $_GET['id_annuaire'];
			$id_utilisateur = $_GET['id_utilisateur'];
			$controleur = new AnnuaireControleur();
			$retour = $controleur->afficherFormulaireSuppressionInscription($identifiant_annuaire,Registre::getInstance()->get('identification_id'));
			break;

		case 'annuaire_afficher_formulaire_recherche':
			$identifiant_annuaire = $_GET['id_annuaire'];
			$controleur = new AnnuaireControleur();
			$retour = $controleur->afficherFormulaireRecherche($identifiant_annuaire);
			break;

		case 'annuaire_recherche_inscrit':
			$identifiant_annuaire = $_GET['id_annuaire'];
			unset($_GET['id_annuaire']);

			if(isset($_GET['inclusive'])) {
				$exclusive = false;
				unset($_GET['inclusive']);
			} else {
				$exclusive = true;
			}

			$criteres = $_GET;

			$controleur = new AnnuaireControleur();
			$retour = $controleur->rechercherInscrit($identifiant_annuaire,$criteres, $exclusive);
			break;

		case 'annuaire_afficher_carte':
			$identifiant_annuaire = $_GET['id_annuaire'];

			$continent = null;
			$pays = null;
			$departement = null;

			if(isset($_GET['continent'])) {
				$continent = $_GET['continent'];
			}

			if(isset($_GET['pays'])) {
				$pays = $_GET['pays'];
			}

			if(isset($_GET['departement'])) {
				$departement = $_GET['departement'];
			}

			$controleur = new CartoControleur();
			$retour = $controleur->cartographier($identifiant_annuaire, $continent, $pays, $departement);
		break;
			
		case 'annuaire_inscrits_carto':
			$identifiant_annuaire = $_GET['id_annuaire'];
			$criteres = $_GET;
			$controleur = new AnnuaireControleur();
			$retour = $controleur->rechercherInscritParlocalisation($identifiant_annuaire,$criteres);
		break;

		case 'annuaire_envoyer_message':
			
			$controleur = new MessageControleur();
			
			$id_annuaire = $_POST['id_annuaire'];
			$contenu_message = $_POST['contenu_message'];
			$sujet_message = $_POST['sujet_message'];
			$destinataires = array_keys($_POST['destinataires']);
			$criteres = unserialize(urldecode($_POST['criteres']));
			
			unset($_POST['id_annuaire']);						
			if(isset($_POST['envoyer_tous'])) {
				$retour = $controleur->envoyerMailParRequete($id_annuaire,Config::get('identification'), $criteres, $sujet_message, $contenu_message);
			} else {
				$retour = $controleur->envoyerMailDirectOuModere($id_annuaire ,Config::get('identification'), $destinataires, $sujet_message, $contenu_message);
			}		
		break;
		
		// Fonctions de modération des messages
		case 'message_moderation_confirmation':
			if(isset($_GET['id'])) {
				$id_message = $_GET['id'];
			}
			$controleur = new MessageControleur();
			$retour = $controleur->envoyerMailModere($id_message);
		break;

		case 'message_moderation_suppression':
			if(isset($_GET['id'])) {
				$id_message = $_GET['id'];
			}
			$controleur = new MessageControleur();
			$retour = $controleur->supprimerMailModere($id_message);
		break;

		case 'inscription_lettre_actualite':
			$controleur = new AnnuaireControleur();
			$id_annuaire = $_GET['id_annuaire'];
			$retour = $controleur->inscriptionLettreActualite($id_annuaire, $GLOBALS['id_utilisateur']);

			if(isset($_GET['retour'])) {
				$retour = $controleur->gererInscriptionExterne($id_annuaire,$GLOBALS['id_utilisateur']);
			}
		break;

		case 'desinscription_lettre_actualite':
			$controleur = new AnnuaireControleur();
			$id_annuaire = $_GET['id_annuaire'];
			$retour = $controleur->desinscriptionLettreActualite($id_annuaire, $GLOBALS['id_utilisateur']);

			if(isset($_GET['retour'])) {
				$retour = $controleur->gererInscriptionExterne($id_annuaire,$GLOBALS['id_utilisateur']);
			}
		break;

		default:
			$controleur = new AnnuaireControleur();
			$retour = $controleur->afficherFicheUtilisateur(Config::get('annuaire_defaut'),$GLOBALS['id_utilisateur']);
		break;
	}

	if (Config::get('sortie_encodage') != Config::get('appli_encodage')) {
		$retour = mb_convert_encoding($retour, Config::get('sortie_encodage'),Config::get('appli_encodage'));
	}

	return $retour;
}

function afficherContenuTete() {

	// c'est très moche, il ne faudrait pas faire comme ceci
	if(function_exists('GEN_stockerStyleExterne')) {
		//GEN_stockerStyleExterne('annuaire_papyrus',Config::get('base_url_styles').'squelettes/css/annuaire_complexe.css');
		GEN_stockerStyleExterne('annuaire_papyrus_simple',Config::get('base_url_styles').'squelettes/css/annuaire.css');
	}
	return "";
}

function afficherContenuPied() {
	return '';
}

function afficherContenuNavigation() {
	return '';
}

function afficherContenuMenu() {

	// TODO : rendre cette partie modulable.
	if(isset($_GET['id_annuaire'])) {
		$id_annuaire = $_GET['id_annuaire'];
	} else {
		$id_annuaire = Config::get('annuaire_defaut');
	}

	$identification = Config::get('identification');

	if($identification) {
		$controleur = new NavigationControleur();
		$menu = $controleur->afficherContenuMenu($id_annuaire,false);	
	} else {
		$menu = '';
	}
	
	return '';

	return $menu;
}

?>