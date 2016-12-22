<?php

/**
 * Permet au service Auth de connecter l'utilisateur auprès d'un partenaire,
 * et de synchroniser un compte local
 * @author mathias
 */
abstract class AuthPartner {

	/** Injection de dépendance du service Auth */
	protected $auth;

	/** Injection de dépendance de la config des services */
	protected $config;

	/** Injection de dépendance de la lib Utilisateur depuis le service Auth */
	protected $annuaire;

	/** Injection de dépendance de la lib SSO depuis le service Auth */
	protected $SSO;

	/** Jeton brut retourné par le service d'authentification du partenaire */
	protected $jetonPartenaire;

	/** Données décodées depuis le jeton du partenaire */
	protected $data;

	/** Identifiant de l'utilisateur dans l'annuaire local, ou false s'il n'existe pas */
	protected $idLocal;

	public function __construct($authLib, $config) {
		$this->auth = $authLib;
		$this->config = $config;
		$this->annuaire = $authLib->getAnnuaire();
		$this->SSO = $this->annuaire->getSSO();
		$this->idLocal = false;
	}

	/** Retourne true si l'utilisateur est authentifié par le partenaire */
	public abstract function verifierAcces($login, $password);

	/**
	 * Vérifie si l'annuaire contient déjà une entrée associée au
	 * courriel de l'utilisateur et l'ajoute ou la met à jour au besoin
	 */
	public function synchroniser() {
		$courriel = $this->getCourriel();
		// l'utilisateur existe-t-il déjà ?
		$this->idLocal = $this->annuaire->idParCourriel($courriel);
		if ($this->idLocal !== false) {
			if (! $this->profilEstAJour()) {
				$this->mettreAJourProfil();
			}
		} else {
			$this->inscrireUtilisateur();
		}
	}

	/**
	 * Retourne true si le profil local est à jour par rapport à la date
	 * de dernière modification fournie par le partenaire; si une telle
	 * date n'existe pas, retourne $retourSiPasDeDate (true par défaut - on
	 * ne met pas à jour)
	 */
	protected function profilEstAJour($retourSiPasDeDate=true) {
		$tsMajPartenaire = $this->getTimestampMajPartenaire();
		//echo "Timestamp partenaire : "; var_dump($tsMajPartenaire); echo "<br/>";
		if ($tsMajPartenaire != null) {
			$dateMajLocale = $this->annuaire->getDateDerniereModifProfil($this->idLocal);
			$tsMajLocale = strtotime($dateMajLocale); // attention à ne pas changer le format de date !
			//echo "Timestamp local : "; var_dump($tsMajLocale); echo "<br/>";
			return ($tsMajLocale >= $tsMajPartenaire);
		}
		// Si le partenaire ne fournit pas de date, on retourne la valeur par défaut
		return $retourSiPasDeDate;
	}

	/**
	 * Retourne le nom du partenaire en cours
	 */
	protected abstract function getNomPartenaire();

	/**
	 * Retourne le courriel de l'utilisateur fourni par le partenaire
	 */
	public abstract function getCourriel();

	/**
	 * Retourne l'identifiant de l'utilisateur fourni par le partenaire
	 */
	protected abstract function getId();

	/**
	 * Retourne le timestamp de dernière mise à jour du profil fournie par le
	 * partenaire; par défaut retourne null, ce qui laisse au mécanisme de
	 * synchronisation le soin de décider si on met à jour le profil ou non
	 */
	protected function getTimestampMajPartenaire() {
		return null;
	}

	/**
	 * Retourne le jeton fourni par le partenaire
	 */
	public function getJetonPartenaire() {
		return $this->jetonPartenaire;
	}

	/**
	 * Retourne un tableau de valeurs correpondant au profil de l'utilisateur,
	 * fourni par le partenaire, et contenant au minimum :
	 * - nom
	 * - prenom
	 * - pseudo
	 * - email
	 * Pour les autres champs possibles, voir AnnuaireModele::inscrireUtilisateur
	 */
	protected abstract function getValeursProfilPartenaire();

	protected function inscrireUtilisateur() {
		$valeursProfil = $this->getValeursProfilPartenaire();
		$valeursProfil['partenaire'] = $this->getNomPartenaire();
		$valeursProfil['id_partenaire'] = $this->getId();
		// création d'un compte partenaire dans l'annuaire
		$this->annuaire->inscrireUtilisateur($valeursProfil);
	}

	protected function mettreAJourProfil() {
		$valeursProfil = $this->getValeursProfilPartenaire();
		// mise à jour du compte partenaire dans l'annuaire
		if (empty($this->idLocal)) {
			throw new Exception("Tentative de mise à jour avec un id local vide");
		}
		$this->annuaire->inscrireUtilisateur($valeursProfil, $this->idLocal);
	}
}