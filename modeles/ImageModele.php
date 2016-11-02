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

Class ImageModele extends Modele {

	private $extensions = '';

	public function verifierFormat($nom_fichier) {

		$extensions = Config::get('extensions_acceptees');
		$extensions = explode('|', $extensions);
		$extension_fichier = strrchr($nom_fichier, '.');

		return in_array($extension_fichier, $extensions);
	}

	// traite l'upload d'une fichier et le deplace en le renommant selon un identifiant donne
	public function stockerFichier($id_annuaire, $id_utilisateur, $fichier)
	{
		$droits = 0777;
		umask(0);

		$chemin_sur_serveur = Config::get('base_chemin_images') ;

		if(!file_exists($chemin_sur_serveur.$id_annuaire.'/')) {
			if(mkdir($chemin_sur_serveur.'/'.$id_annuaire, $droits, true)) {
				//chmod($chemin_sur_serveur.'/'.$id_annuaire,$droits);
			}
			else
			{
				trigger_error('ERROR : probleme durant l\'écriture du dossier des images pour l\'annuaire '.$id_annuaire.' \n'.$chemin_sur_serveur) ;
				return false;
			}
		}

		$chemin_sur_serveur = $chemin_sur_serveur.$id_annuaire;

		$taille_max = Config::get('taille_max_images');

		$id = sprintf('%09s', $id_utilisateur) ;
		$id = wordwrap($id, 3 , '_', true) ;

		$id_fichier = $id.".jpg" ;

		$niveauDossier = split("_", $id) ;

		$dossierNiveau1 = $niveauDossier[0] ;
		$dossierNiveau2 = $niveauDossier[1] ;

		if(!file_exists($chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S'))
		{
			if(mkdir($chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S',$droits, true)) {
				chmod($chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S',$droits);
			}
			else
			{
				trigger_error('ERROR : probleme durant l\'écriture du dossier s \n') ;
				return false;
			}
		}

		if(!file_exists($chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/M'))
		{
			if(mkdir($chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/M',$droits, true)) {
				chmod($chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/M',$droits);
			}
			else
			{
				trigger_error('ERROR : probleme durant l\'écriture du dossier m \n') ;
				return false;
			}
		}

		if(!file_exists($chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/L'))
		{
			if(mkdir($chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/L',$droits, true)) {
				chmod($chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/L',$droits);
			}
			else
			{
				trigger_error('ERROR : probleme durant l\'écriture du dossier l \n') ;
				return false;
			}
		}

		$chemin_sur_serveur_final = $chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2 ;

		$chemin_fichier = $chemin_sur_serveur_final.'/L/'.$id.".jpg" ;

		if(move_uploaded_file($fichier['tmp_name'],$chemin_fichier))
		{
			// on redimensionne
			list($width, $height) = getimagesize($chemin_fichier);

			$small_height = 100;
			if($height > $small_height) {
				$small_height = 100;
				$ratio = $height/$small_height;
				$small_width = $width/$ratio;
			} else {
				$small_height = $height;
				$small_width = $width;
			}

			$medium_height = 300;
			if($height > $medium_height) {
				$ratio = $height/$medium_height;
				$medium_width = $width/$ratio;
			} else {
				$medium_height = $height;
				$medium_width = $width;
			}

			// on reechantillonne
			$image_p = imagecreatetruecolor($small_width, $small_height);
			$image_m = imagecreatetruecolor($medium_width, $medium_height);
			$image_l = imagecreatetruecolor($width, $height);

			$image = imagecreatefromjpeg($chemin_fichier);

	        $ratio_compression = 100 ;

	        if(filesize($chemin_fichier) >= $taille_max) {
	            $ratio_compression = 85 ;
	        }

			if($image == null)
			{
				trigger_error('Probleme durant la création des images resamplées \n') ;
				return false ;
			}

			// et on copie les nouvelles images (pour la galerie et la liste)
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $small_width, $small_height, $width, $height);
			imagecopyresampled($image_m, $image, 0, 0, 0, 0, $medium_width, $medium_height, $width, $height);
			imagecopyresampled($image_l, $image, 0, 0, 0, 0, $width, $height, $width, $height);

			imagejpeg($image_p, $chemin_sur_serveur_final.'/S/'.$id.'_S.jpg', 85);
			chmod($chemin_sur_serveur_final.'/S/'.$id.'_S.jpg',$droits);

			imagejpeg($image_m,$chemin_sur_serveur_final.'/M/'.$id.'_M.jpg', 85);
			chmod($chemin_sur_serveur_final.'/M/'.$id.'_M.jpg',$droits);

			imagejpeg($image_l,$chemin_sur_serveur_final.'/L/'.$id.'_L.jpg', $ratio_compression);
			chmod($chemin_sur_serveur_final.'/L/'.$id.'_L.jpg',$droits);

			unlink($chemin_fichier) ;
			
			chmod($chemin_sur_serveur, $droits);

	  		return $id_utilisateur;
	  	}
	  	else
	  	{
			trigger_error('Probleme durant le déplacement du fichier temporaire \n') ;
			return false ;
	  	}
	}

	public static function obtenirEmplacementFichierParId($id_utilisateur, $id_annuaire, $taille = 'M') {

		$id = sprintf('%09s', $id_utilisateur) ;
		$id = wordwrap($id, 3 , '_', true) ;

		$niveauDossier = split("_", $id) ;

		$dossierNiveau1 = $niveauDossier[0] ;
		$dossierNiveau2 = $niveauDossier[1] ;

		$base_url = Config::get('base_url_images').$id_annuaire;

		if($taille == 'A') {
			$url = array('S' => $base_url.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S/'.$id.'_S.jpg',
							'M' => $base_url.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S/'.$id.'_S.jpg',
								'L' => $base_url.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S/'.$id.'_S.jpg');
		} else {
			$url = $base_url.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/'.$taille.'/'.$id.'_'.$taille.'.jpg';
		}

		return $url;
	}
	
	public static function obtenirUrlFichierParId($id_utilisateur, $id_annuaire, $taille = 'M') {
		$id = sprintf('%09s', $id_utilisateur) ;
		$id = wordwrap($id, 3 , '_', true) ;

		$niveauDossier = split("_", $id) ;

		$dossierNiveau1 = $niveauDossier[0] ;
		$dossierNiveau2 = $niveauDossier[1] ;

		$base_url = 'http://'.$_SERVER['SERVER_NAME'].Config::get('base_url_images').$id_annuaire;

		if($taille == 'A') {
			$url = array('S' => $base_url.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S/'.$id.'_S.jpg',
							'M' => $base_url.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S/'.$id.'_S.jpg',
								'L' => $base_url.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S/'.$id.'_S.jpg');
		} else {
			$url = $base_url.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/'.$taille.'/'.$id.'_'.$taille.'.jpg';
		}

		return $url;
	}

	public function supprimerFichier($id)
	{
		$chemin_sur_serveur = Config::get('base_chemin_images') ;

		$id = sprintf('%09s', $id) ;
		$id = wordwrap($id, 3 , '_', true) ;

		$id_fichier = $id.".jpg" ;

		$niveauDossier = split("_", $id) ;

		$dossierNiveau1 = $niveauDossier[0] ;
		$dossierNiveau2 = $niveauDossier[1] ;

		$fichier_s = $chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/S/'.$id.'_S.jpg' ;
		$fichier_m = $chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/M/'.$id.'_M.jpg' ;
		$fichier_l = $chemin_sur_serveur.'/'.$dossierNiveau1.'/'.$dossierNiveau2.'/L/'.$id.'_L.jpg' ;

		if(file_exists($fichier_s))
		{
			unlink($fichier_s) ;
		} // Si le fichier existe

		if(file_exists($fichier_m))
		{
			unlink($fichier_m) ;
		} // Si le fichier existe

		if(file_exists($fichier_l))
		{
			unlink($fichier_l) ;
		} // Si le fichier existe

	}
}
?>