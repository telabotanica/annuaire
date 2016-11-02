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

Class DonneeTemporaireModele extends Modele {
	
	// attention le script de traitement des mails utilise des variables
	// similaires (donc si on change l'une on change l'autre)
	// TODO: factoriser ça comme des grands
	const STATUT_A_TRAITER = 'a_traiter';
	const STATUT_EN_TRAITEMENT = 'en_traitement';

	private $config = array();

	public function stockerDonneeTemporaire($donnee, $id_aleatoire = false) {
		$this->maintenanceDonneesTemporaires();

		// on protège et on sérialise les données
		$identifiant = $this->calculerIdentifiant($id_aleatoire);
		$donnees = $this->encoderDonneeTemporaire($donnee);

		$requete_insertion = 'INSERT INTO annu_donnees_temp (adt_id, adt_donnees, adt_date) '.
			'VALUES ('.$this->proteger($identifiant).','.$this->proteger($donnees).', NOW())';

		$insertion = $this->requete($requete_insertion);
		$retour = (!$insertion) ? false : $identifiant;
		return $retour;
	}

	public function chargerListeDonneeTemporaire($longueur_id = '8') {
		$requete_chargement_donnee = 'SELECT * '.
			'FROM annu_donnees_temp '.
			"WHERE LENGTH(adt_id) = $longueur_id ".
			'ORDER BY adt_date DESC ';

		$donnees_temp = $this->requeteTous($requete_chargement_donnee);
		foreach ($donnees_temp as &$donnee) {
			$code_confirmation = $donnee['adt_id'];
			$date_donnee_temp = $donnee['adt_date'];
			$donnee = $this->decoderDonneeTemporaire($donnee);

			$donnee['code_confirmation'] = $code_confirmation;
			$donnee['date'] = $date_donnee_temp;
		}
		return $donnees_temp;
	}

	public function chargerDonneeTemporaire($code_donnee) {
		$codeDonneeP = $this->proteger($code_donnee);
		$requete = "SELECT * FROM annu_donnees_temp WHERE adt_id = $codeDonneeP ";

		$donnees_temp = $this->requeteUn($requete);
		$retour = ($donnees_temp) ? $this->decoderDonneeTemporaire($donnees_temp) : false;
		return $retour;
	}

	public function supprimerDonneeTemporaire($code_donnee) {
		$requete = 'DELETE FROM annu_donnees_temp WHERE adt_id = '.$this->proteger($code_donnee);
		$resultat = $this->requete($requete);
		$retour = $resultat ? true : false;
		return $retour;
	}

	private function decoderDonneeTemporaire($donnee_encodee) {
		return unserialize(base64_decode($donnee_encodee['adt_donnees']));
	}

	private function encoderDonneeTemporaire($donnee) {
		return base64_encode(serialize($donnee));
	}

	private function calculerIdentifiant($aleatoire = false) {
		if (!$aleatoire) {
			// Le code de confirmation est constitué des 8 premiers caractères de l'identifiant de session
			// lors du stockage des données d'inscription, afin d'éviter d'accumuler les demandes
			// d'inscription pour une même session
			$code_confirmation = substr(session_id(), 0, 8) ;
		} else {
			// Ce code est à l'intention de la modération pour valider un email en attente de modération
			// mais qui doit être unique pour éviter des duplicates keys.
			$code_confirmation = time();
		}
		return $code_confirmation;
	}

	private function maintenanceDonneesTemporaires() {
		$requete = 'DELETE FROM annu_donnees_temp WHERE adt_date < (DATE_SUB(now(), INTERVAL 14 DAY))';
		$resultat = $this->requeteUn($requete);
		$retour = $resultat ? true : false;
		return $retour;
	}
	
	public function mettreDonneeTemporaireATraiter($code_donnee) {
		// TODO: si d'autres traitement que les mails existent un jour, ajouter un code de traitement
		// pour les différencier
		$requete = "UPDATE annu_donnees_temp SET adt_statut = '".self::STATUT_A_TRAITER."' ".
					"WHERE adt_id = ".$this->proteger($code_donnee)." AND (adt_statut IS NULL OR adt_statut != '".self::STATUT_EN_TRAITEMENT."') ";

		$resultat = $this->requete($requete);
		$retour = $resultat ? true : false;
		return $retour;
	}
}
?>