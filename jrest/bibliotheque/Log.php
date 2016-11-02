<?php
//declare(encoding='UTF-8');
/**
 * Classe permettant de logger des messages dans les fichier situés dans le dossier de log
 *
 * PHP Version 5
 *
 * @category  PHP
 * @package   Framework
 * @author	aurelien <aurelien@tela-botanica.org>
 * @copyright 2009 Tela-Botanica
 * @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @version   SVN: <svn_id>
 * @link	  /doc/framework/
 */

class Log {

	/**
	 * Tableau associatif stockant les descripteurs de fichiers
	 */
	private static $fichiersLog = array();

	/**
	 * Chemin de base du dossier log de l'application
	 */
	private static $cheminLogs = '';

	/**
	 * Booleen indiquant si l'on peut correctement écrire dans les fichiers de logs
	 */
	 private static $droitLogger = true;

	/**
	 * Zone horaire (pour éviter des avertissements dans les dates)
	 */
	private static $timeZone = '';

	/**
	 * Taille maximum d'un fichier de log avant que celui ne soit archivé (en octets)
	 */
	private static $tailleMax = 10000;

	/**
	 * séparateur de chemin
	 */
	private static $sd = DIRECTORY_SEPARATOR;

	/**
	 * Extension des fichiers de log
	 */
	private static $ext = '.log';

	/**
	 * La classe registre se contient elle-même, (pour le pattern singleton)
	 */
	private static $log;

	/**
	 * Constructeur par défaut, privé, car on accède à la classe par le getInstance
	 */
	private function __construct() {

		self::$sd = $sd;
		// gestion de la timezone pour éviter des erreurs
		if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get")) {
			date_default_timezone_set(self::$timeZone);
		}

		if(!is_dir(self::$cheminLogs) || !is_writable(self::$cheminLogs)) {
			self::desactiverEcriture();
		}
	}

	public static function setCheminLog($nouveauCheminLogs) {
		self::$cheminLogs = $nouveauCheminLogs;
	}

	public static function getCheminLog() {
		return  self::$cheminLogs;
	}

	public static function setTimeZone($NouvelleTimeZone) {
		self::$timeZone = $NouvelleTimeZone;
	}

	public static function setTailleMax($nouvelleTailleMax) {
		self::$tailleMax = $nouvelleTailleMax;
	}

	/**
	 * Fonction qui renvoie l'instance de classe en assurant son unicité, c'est l'unique méthode qui doit être
	 * utilisée pour récupérer l'objet Registre
	 * @return Log	le gestionnaire de log en cours
	 */
	public static function getInstance() {
		if (self::$log instanceof Log) {
			return self::$log;
		}
		self::$log = new Log();
		return self::$log;
	}

	/**
	 * Ajoute une entrée au log spécifié par le paramètre $nomFichier
	 * @param string $nomFichier le nom du fichier dans lequel écrire
	 */
	public static function ajouterEntree($nomFichier,$entree,$mode='a+') {
		if(self::$droitLogger) {
			$date = "\n"."\n".date('d m Y H:i')."\n" ;

			// si le fichier est déjà dans le tableau et qu'on peut y écrire
			if(self::verifierOuvrirFichier($nomFichier,$mode)) {
					// on y écrit le message de log
				   fwrite(self::$fichiersLog[$nomFichier],$date.$entree);
				   // on vérifie si le fichier ne dépasse pas la taille maximale
				   self::verifierTailleFichierOuArchiver($nomFichier);
			} else {
				// sinon on interdit l'écriture
				self::desactiverEcriture($nomFichier);
			}
		}
	}

	/**
	 * Vide un fichier log indiqué
	 * @param string $nomFichier le nom du fichier à vider
	 */
	public static function viderLog($nomFichier) {
		ajouterEntree($nomFichier,'','w');
	}

	/**
	 * Vérifie la présence d'un fichier dans le tableau, ses droits d'écriture,
	 * l'ouvre si nécessaire
	 * @param string $nomFichier le nom du fichier dont on doit vérifier la présence
	 * @return boolean true si le fichier est ouvert ou maintenant accessible, false sinon
	 */
	public static function verifierOuvrirFichier($nomFichier,$mode) {
		// le fichier est il déjà ouvert ?
		if(in_array($nomFichier,self::$fichiersLog)) {
			// si oui peut on y écrire ?
			if(is_writable(self::$cheminLogs.$nomFichier.self::$ext)) {
				// si oui on renvoie le descripteur
				return true;
			}
			return false;
		} else {
			// sinon on l'ouvre
			$fp = @fopen(self::$cheminLogs.$nomFichier.self::$ext,$mode);
			// si l'ouverture a réussi et si le fichier a les droits d'écriture
			if($fp && is_writable(self::$cheminLogs.$nomFichier.self::$ext)) {
				// si oui on renvoie le descripteur qu'on ajoute au tableau
				self::$fichiersLog[$nomFichier] = $fp;
				return true;
			}
			return false;
		}
	}

	/**
	 * Vérifie la taille d'un fichier donné et si celle ci est trop importante
	 * archive le fichier de log
	 * @param string $nomFichier nom du fichier à vérifier
	 */
	private static function verifierTailleFichierOuArchiver($nomFichier) {
		if(filesize(self::$cheminLogs.$nomFichier.self::$ext) > self::$tailleMax) {
			rename(self::$cheminLogs.$nomFichier.self::$ext,self::$cheminLogs.$nomFichier.date('d_m_Y_H:i').self::$ext);
			self::ajouterEntree($nomFichier,'');
		}
	}

	/**
	 * Désactive l'écriture du log et envoie un message au gestionnaire d'erreurs
	 * @param string $nomFichier le nom du fichier qui a causé l'erreur
	 */
	private static function desactiverEcriture($nomFichier = '') {
		self::$droitLogger = false;
		if($nomFichier != '') {
			$fichierDossier = 'fichier '.$nomFichier ;
		} else {
			$fichierDossier = 'dossier des logs';
		}
	}

	/**
	 * destructeur de classe, ferme les descripteurs ouverts
	 */
	public function __destruct() {
		foreach(self::$fichiersLog as $nomFichier => $fp) {
			fclose($fp);
		}
	}
}
?>
