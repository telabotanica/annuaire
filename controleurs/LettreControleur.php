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

/**
 * Controleur permettant l'abonnement à une liste de diffusion
 * à travers un mécanisme de mail ou de web services
 */
class LettreControleur extends AppControleur {

	/** Adresse de base des web services
	 * ex : http://mail.domaine.com/
	 */
	private $adresse_service_lettre = null;

	/**
	 * Nom du service permettant de s'inscrire
	 * ex : inscription.php
	 */
	private $service_inscription_lettre = null;

	/**
	 * Nom du service permettant de s'inscrire
	 * ex : desinscription.php
	 */
	private $service_desinscription_lettre = null;

	/**
	 * domaine de la liste
	 * ex : domaine.org
	 */
	private $domaine_lettre = null;

	/**
	 * nom de la liste de diffusion
	 * ex : actualite
	 */
	private $nom_lettre = null;

	/**
	 * adresse mail d'inscription (si on utilise les mails)
	 */
	private $adresse_inscription_lettre = null;

	/**
	 * adresse mail de desinscription (si on utilise les mails)
	 */
	private $adresse_desinscription_lettre = null;

	/**
	 * indique si on utilise les mails ou non
	 * (si les infos des web services sont entrées, on met cette variable à true)
	 */
	private $utilise_mail = false;

	/**
	 * Constructeur sans paramètres
	 */
	public function LettreControleur() {

		$this->__construct();

		// on charge les variables de classes à partir du fichier de configuration
		if(Config::get('adresse_service_lettre') != null) {
			$this->adresse_service_lettre = Config::get('adresse_service_lettre');
		} else {
			$this->utilise_mail = true;
		}

		if(Config::get('service_inscription_lettre') != null) {
			$this->service_inscription_lettre = Config::get('service_inscription_lettre');
		} else {
			$this->utilise_mail = true;
		}

		if(Config::get('service_desinscription_lettre') != null) {
			$this->service_desinscription_lettre = Config::get('service_desinscription_lettre');
		} else{
			$this->utilise_mail = true;
		}

		if(Config::get('domaine_lettre') != null) {
			$this->domaine_lettre = Config::get('domaine_lettre');
		} else {
			$this->utilise_mail = true;
		}

		if(Config::get('nom_lettre') != null) {
			$this->nom_lettre = Config::get('nom_lettre');
		} else {
			$this->utilise_mail = true;
		}
		// si l'une des variables pour les web services n'est pas valide

		// alors on utilise les mails
		if(Config::get('adresse_inscription_lettre') != null) {
			$this->adresse_inscription_lettre = Config::get('adresse_inscription_lettre');
		}

		if(Config::get('adresse_desinscription_lettre') != null) {
			$this->adresse_desinscription_lettre = Config::get('adresse_desinscription_lettre');
		}
	}

/** --------------------------------- Fonction liées à l'inscription ou la desinscription à la lettre d'actualité gérée par une liste externe -------------------------------------------*/	
		
	
	/**
	 * Envoie un mail avec l'adresse de l'utilisateur donné en paramètre,
	 * à l'adresse donnée en paramètre
	 * @param string $adresse l'adresse de la liste à laquelle on veut abonner
	 * @param string $inscrit l'adresse de l'inscrit qui doit être abonné
	 * @param string $sujet sujet du mail
	 * @return boolean true ou false suivant le succès ou non de l'envoi
	 */
	private function envoyerMail($adresse, $inscrit, $sujet) {

		 // Pour envoyer un mail HTML, l'en-tête Content-type doit être défini
	     $entetes  = 'MIME-Version: 1.0' . "\r\n";
	     $entetes .= 'Content-type: text/html; charset='.Config::get('appli_encodage'). "\r\n";
	     // En-têtes additionnels
	     $entetes .= 'To: '.$adresse."\r\n";
	     $entetes .= 'From: '.$inscrit."\r\n";

	     $contenu_mail = '';

		return mail($adresse, $sujet, $contenu_mail, $entetes);
	}

	/**
	 * Inscrit une adresse à la lettre d'actu
	 * @param string $mail le mail à inscrire à la lettre
	 * @return boolean true ou false suivant le succès de la requete
	 */
	public function inscriptionLettreActualite($mail) {

		if($this->utilise_mail) {
			return $this->envoyerMail($this->adresse_inscription_lettre, $mail, 'inscription à la lettre d\'actualité');
		} else {
			$params = '?domaine='.$this->domaine_lettre.'&liste='.$this->nom_lettre.'&mail='.$mail;
			//Log::ajouterEntree('lettre','inscription params '.$this->adresse_service_lettre.$this->service_inscription_lettre.$params);
			return file_get_contents($this->adresse_service_lettre.$this->service_inscription_lettre.$params);
		}

	}

	/**
	 * Desinscrit une adresse à une liste donnée
	 * @param string $mail le mail à desinscrire à la lettre
	 * @return boolean true ou false suivant le succès de la requete
	 */
	public function desinscriptionLettreActualite($mail) {

		if($this->utilise_mail) {
			return $this->envoyerMail($this->adresse_inscription_lettre, $mail, 'desinscription à la lettre d\'actualité');
		} else {
			$params = '?domaine='.$this->domaine_lettre.'&liste='.$this->nom_lettre.'&mail='.$mail;
			//Log::ajouterEntree('lettre','desinscription params '.$this->adresse_service_lettre.$this->service_desinscription_lettre.$params);
			return file_get_contents($this->adresse_service_lettre.$this->service_desinscription_lettre.$params);
		}

	}

	/**
	 * Desinscrit l'ancien mail d'un utilisateur et réinscrit le nouveau
	 * @param string $ancien_mail l'ancien mail à desinscrire à la lettre
	 * @param string $nouveau_mail l'ancien mail à inscrire à la lettre
	 * @return boolean true ou false suivant le succès de la requete
	 */
	public function modificationInscriptionLettreActualite($ancien_mail, $nouveau_mail) {

		if($this->utilise_mail) {

			$adresse_deinscription_lettre = Config::get('adresse_desinscription_lettre');
			$suppression_ancien_mail = $this->envoyerMail($adresse_deinscription_lettre, $ancien_mail, 'desinscription à la lettre d\'actualité');

			$adresse_inscription_lettre = Config::get('adresse_inscription_lettre');
			$ajout_nouveau_mail = $this->envoyerMail($adresse_inscription_lettre, $nouveau_mail, 'inscription à la lettre d\'actualité');

			return $suppression_ancien_mail && $ajout_nouveau_mail;
		} else {
			$desinscription = $this->desinscriptionLettreActualite($ancien_mail);
			$inscription = $this->inscriptionLettreActualite($nouveau_mail);

			return ($desinscription && $inscription);
		}

	}

	
/** ---------------------------------    Fonction de gestion du champ de données associé à la lettre d'actu (appelés par les web services) -------------------------------------------*/	
		
	
	public function estAbonneLettreActualite($id_annuaire, $id_utilisateur) {

		$annuaire_modele = $this->getModele('AnnuaireModele');
		$champs_description = $annuaire_modele->obtenirChampsDescriptionAnnuaire($id_annuaire);

		$valeur = $annuaire_modele->obtenirValeurChampAnnuaireMappe($id_annuaire, $id_utilisateur, 'champ_lettre');

		return $valeur;
	}

	public function abonnerDesabonnerLettreActualite($id_annuaire, $id_utilisateur, $abonner = true) {

		$annuaire_modele = $this->getModele('AnnuaireModele');
		$champs_description = $annuaire_modele->obtenirChampsDescriptionAnnuaire($id_annuaire);

		$mail_utilisateur = $annuaire_modele->obtenirMailParId($id_annuaire, $id_utilisateur);

		$champ_lettre = $champs_description[0]['champ_lettre'];

		if($abonner) {
			$valeur = 'on';
		} else {
			$valeur = '0';
		}

		$verificateur = new VerificationControleur();
		$valeur_modif = $verificateur->remplacerValeurChampPourModification($id_annuaire, $id_utilisateur, 'lettre', $valeur, $mail_utilisateur);


		$annuaire_modele = $this->getModele('AnnuaireModele');
		$valeur_modif = $annuaire_modele->modifierValeurChampAnnuaireMappe($id_annuaire, $id_utilisateur, $champ_lettre, $valeur_modif);
		$this->chargerModele('MetadonneeModele');
		$this->MetadonneeModele->modifierValeurMetadonnee($champ_lettre,$id_utilisateur,$valeur_modif);

		return $valeur_modif;
	}
}
?>