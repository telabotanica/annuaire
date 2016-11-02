<?php
// Encodage : UTF-8
// +-------------------------------------------------------------------------------------------------------------------+
/**
* Migration des utilisateurs vers wordpress
*
* Description : classe permettant de migrer les profils de l'annuaire vers les profils wordpress
* Utilisation : php cli.php migrationwp -a tous
* /usr/local/bin/php -d memory_limit=4000M cli.php migrationwp -a tous
* vérifier le nom de la base et le préfixe des tables définis dans $basewp
*
//Auteur original :
* @author       Aurélien PERONNET <jpm@tela-botanica.org>
* @copyright	Tela-Botanica 1999-2014
* @licence		GPL v3 & CeCILL v2
* @version		$Id$
*/

class Migrationwp extends Script {
	private $basewp = "wordpress.site_";
	private $table = "site_";
	
	public function executer() {	
		$this->bdd = new Bdd();
		//$this->bdd->setAttribute(MYSQL_ATTR_USE_BUFFERED_QUERY, true);
		// évite les erreurs 2006 "MySQL has gone away"
		$this->bdd->executer("SET wait_timeout=300");
		
		$cmd = $this->getParametre('a');
		$this->mode_verbeux = $this->getParametre('v');
		
		$retour = array();
		
		switch($cmd) {
			case "tous":
				$retour = $this->migrerUtilisateur();
				$retour = $this->migrerUtilisateurMeta();
				$retour = $this->migrerUtilisateurProfil();
				$retour = $this->migrerUtilisateurActivite();
				break;
			case "utilisateur": //liste wordpress
				$retour = $this->migrerUtilisateur();
				break;
			case "meta": //role
				$retour = $this->migrerUtilisateurMeta();
				break;
			case "profil":
				$retour = $this->migrerUtilisateurProfil();
				break;
			case "activite": // obligatoire pour affichage
				$retour = $this->migrerUtilisateurActivite();
				break;
				case "actualiteTout" :
					$retour = $this->migrerUtilisateurActualites();
					$retour .= $this->migrerUtilisateurActualitesRubrique();
					$retour .= $this->migrerUtilisateurActualitesCommentaire();
					break;
			case "actualite" :
				$retour = $this->migrerUtilisateurActualites();
				break;
			case "actualiteRubrique" :
				$retour = $this->migrerUtilisateurActualitesRubrique();
				break;
			case "actualiteComm" :
				$retour = $this->migrerUtilisateurActualitesCommentaire();
				break;
			default: break;
		}
		
		if($this->mode_verbeux) {
			// echo pour que bash capte la sortie et stocke dans le log
			//echo 'Identifiants des mails traites : '.implode(',', $retour)."--";
		}
	}
	
	private function migrerUtilisateur() {
		$requete = "INSERT INTO ".$this->basewp."users
				(`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_url`, `user_registered`, `user_status`, `display_name`) 
				SELECT `U_ID`, `U_MAIL`, `U_PASSWD`,concat(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(lower(concat(`U_SURNAME`,'-',`U_NAME`,'-')),' ',''),'\'',''),'é','e'),'è','e'),'ï','i'),'ü','u'),'ø',''),'œ','oe'),'ë','e'),'ç','c'),cast(`U_ID` as char)), 
				`U_MAIL` as mail,  '' as user_url, `U_DATE`, '0', concat(`U_SURNAME`,' ',`U_NAME`)  FROM tela_prod_v4.`annuaire_tela`";
		$retour = $this->bdd->executer($requete);
		echo 'Il y a '.count($retour).' utilisateurs migrés '."--";
		return $retour;
	}
	

	//TODO encoder nick/first/last name pour '
	private function migrerUtilisateurMeta() {
		$retour = array();
		$requete = "SELECT `U_ID`, `U_NAME`, `U_SURNAME` FROM `annuaire_tela`;";
		$utilisateurs = $this->bdd->recupererTous($requete);
		foreach ($utilisateurs as $utilisateur) {
			// _access est pour définir les catégories d'articles que l'utilisateur pourra écrire
			$requete_insert = "INSERT INTO ".$this->basewp."usermeta (`user_id`, `meta_key`, `meta_value`) VALUES
					({$utilisateur['U_ID']}, 'last_activity', '2016-05-18 15:38:18'),
					({$utilisateur['U_ID']}, 'first_name', {$this->bdd->proteger($utilisateur['U_SURNAME'])}),
					({$utilisateur['U_ID']}, 'last_name', {$this->bdd->proteger($utilisateur['U_NAME'])}),
					({$utilisateur['U_ID']}, 'description', ''),
					({$utilisateur['U_ID']}, 'rich_editing', 'true'),
					({$utilisateur['U_ID']}, 'comment_shortcuts', 'false'),
					({$utilisateur['U_ID']}, 'admin_color', 'fresh'),
					({$utilisateur['U_ID']}, 'use_ssl', '0'),
					({$utilisateur['U_ID']}, 'show_admin_bar_front', 'true'),
					({$utilisateur['U_ID']}, '".$this->basewp."capabilities', 'a:1:{s:11:\"contributor\";b:1;}'),
					({$utilisateur['U_ID']}, '".$this->basewp."user_level', '1'),
					({$utilisateur['U_ID']}, 'dismissed_wp_pointers', ''),
					({$utilisateur['U_ID']}, 'wp_dashboard_quick_press_last_post_id', '63'),
					({$utilisateur['U_ID']}, '_restrict_media', '1'),
					({$utilisateur['U_ID']}, '_access', 'a:4:{i:0;s:1:\"2\";i:1;s:1:\"5\";i:2;s:1:\"6\";i:3;s:1:\"7\";}'),
					({$utilisateur['U_ID']}, 'bp_xprofile_visibility_levels', 'a:12:{i:1;s:6:\"public\";i:60;s:6:\'public\';i:61;s:6:\'public\';i:49;s:6:\'public\';i:55;s:6:\'public\';i:48;s:6:\'public\';i:62;s:6:\'public\';i:63;s:6:\'public\';i:68;s:6:\'public\';i:76;s:6:\'public\';i:120;s:6:\'public\';i:81;s:6:\'public\';}');";
			$retour[] = $this->bdd->executer($requete_insert);
		}
		// echo pour que bash capte la sortie et stocke dans le log
		//echo 'Il y a '.count($utilisateurs).' utilisateurs '."--";
		//print_r($utilisateurs);
		return $retour;
	}
	
	private function migrerUtilisateurActivite() {
		$retour = array();
		$requete = "SELECT `U_ID`, `U_NAME`, `U_SURNAME` FROM `annuaire_tela`;";
		$utilisateurs = $this->bdd->recupererTous($requete);
		foreach ($utilisateurs as $utilisateur) {
			$requete_insert = "INSERT INTO ".$this->basewp."bp_activity
					(`id`, `user_id`, `component`, `type`, `action`, `content`, `primary_link`, `item_id`, `secondary_item_id`, `date_recorded`, `hide_sitewide`, `mptt_left`, `mptt_right`, `is_spam`) 
					VALUES (NULL, {$utilisateur['U_ID']}, 'members', 'last_activity', '', '', '', '0', NULL, '2016-05-19 15:06:16', '0', '0', '0', '0');";
			$retour[] = $this->bdd->executer($requete_insert);
		}
		// echo pour que bash capte la sortie et stocke dans le log
		//echo 'Il y a '.count($utilisateurs).' utilisateurs '."--";
		//print_r($utilisateurs);
		return $retour;
	}
	
	private function migrerUtilisateurProfil() {
		$retour = array();
		$requete = "SELECT `U_ID`, `U_NAME`, `U_SURNAME`, U_WEB, `U_CITY`, `U_COUNTRY`, pays, `U_NIV`,  `LABEL_NIV` FROM `annuaire_tela` 
				left join (select  `amo_nom` as pays,  `amo_abreviation` FROM `annu_meta_ontologie` WHERE  `amo_ce_parent` = 1074) liste_pays  on `amo_abreviation` = `U_COUNTRY` 
				LEFT JOIN `annuaire_LABEL_NIV` ON `ID_LABEL_NIV` = `U_NIV`;";
		$utilisateurs = $this->bdd->recupererTous($requete);
		$requete_supp = "SELECT *  FROM `annu_meta_valeurs` WHERE `amv_ce_colonne` in (2,137, 99, 125) and (amv_valeur != ''  and amv_valeur != 0)";
		$infos_supp = $this->bdd->recupererTous($requete_supp); 
		$codes_langues = array("30842"=>"Anglais",
				"30843"=>"Allemand",
				"30844"=>"Italien",
				"30845"=>"Espagnol",
				"30846"=>"Arabe",
				"30847"=>"Chinois",
				"30848"=>"Russe");
		foreach ($infos_supp as $infos) {
			if ($infos['amv_ce_colonne'] == 2) {
				//exemple a:3:{i:0;s:7:"Anglais";i:1;s:8:"Espagnol";i:2;s:7:"Italien";}
				$langues = explode(";;", $infos['amv_valeur']);
				$valeur = "a:".count($langues).':{';
				foreach ($langues as $n=>$langue) {
					$valeur .= 'i:'.$n.';s:'.strlen($codes_langues[$langue]).':"'.$codes_langues[$langue].'";';
				}
				$valeur .='}';
				$supp[$infos['amv_cle_ligne']][$infos['amv_ce_colonne']] = $valeur;
			} else {
				$supp[$infos['amv_cle_ligne']][$infos['amv_ce_colonne']] = $infos['amv_valeur'];
			}
		}
		
		$correspondance_categories = array("99"=>"1",
				"137"=>"2",
				"125"=>"11",
				"2"=>"13");
		foreach ($utilisateurs as $utilisateur) {
			$requete_insert = "INSERT INTO ".$this->basewp."bp_xprofile_data (`field_id`, `user_id`, `value`, `last_updated`) VALUES
				('3', {$utilisateur['U_ID']}, {$this->bdd->proteger($utilisateur['pays'])}, '2016-05-19 15:06:16'),
				('4', {$utilisateur['U_ID']}, {$this->bdd->proteger($utilisateur['U_CITY'])}, '2016-05-19 15:06:16'),
				('9', {$utilisateur['U_ID']}, {$this->bdd->proteger($utilisateur['U_NAME'])}, '2016-05-19 15:06:16'),
				('10', {$utilisateur['U_ID']}, {$this->bdd->proteger($utilisateur['U_SURNAME'])}, '2016-05-19 15:06:16'),
				('12', {$utilisateur['U_ID']}, {$this->bdd->proteger($utilisateur['LABEL_NIV'])}, '2016-05-19 15:06:16'),
				('21', {$utilisateur['U_ID']}, {$this->bdd->proteger($utilisateur['U_WEB'])}, '2016-05-19 15:06:16')";
			if (isset($supp[$utilisateur['U_ID']])) {
				foreach ($supp[$utilisateur['U_ID']] as $num=>$val){
					$requete_insert .= ",({$correspondance_categories[$num]}, {$utilisateur['U_ID']}, {$this->bdd->proteger($val)}, '2016-05-19 15:06:16')";
				}				
			}
			$requete_insert .= ";";
			$retour[] = $this->bdd->executer($requete_insert);
		}
		return $retour;
	}
	
	private function migrerActualites() {
		$retour = array();
		/*INSERT INTO `wp4_posts`
		 SELECT spip_articles.`id_article` as ID, `id_auteur` as post_author, `date` as post_date, `date` as post_date_gmt,
		 replace(replace(replace(replace(replace(replace(replace(replace(replace(convert( convert( texte USING latin1 ) USING utf8 ),'{{{{',''), '}}}}', '<!--more-->'), '{{{','<h2>'), '}}}', '</h2>'), '{{', '<strong>'), '}}', '</strong>'), '{', '<em>'), '}', '</em>'), '_ ', '') as post_content,
		 `titre` as post_title,  "" as post_excerpt, replace(replace(replace(replace(replace(`statut`,'poubelle', 'trash'),'publie', 'publish'), 'prepa', 'private'), 'prop', 'pending'), 'refuse', 'trash') as post_status,  "open" as comment_status, "open" as ping_status, "" as post_password, spip_articles.`id_article` as post_name, "" as to_ping, "" as pinged, `date_modif` as post_modified,`date_modif` as post_modified_gmt, "" as post_content_filtered, "" as post_parent,
		 concat("http://tela-botanica.net/wpsite/actu",spip_articles.`id_article`) as guid, "0" as menu_order, "post" as post_type, "" as post_mime_type, "" as comment_count FROM tela_prod_spip_actu.`spip_articles` left join tela_prod_spip_actu.spip_auteurs_articles on spip_auteurs_articles.`id_article` =  spip_articles.`id_article` WHERE id_rubrique in (22,54,70,30,19,51)
		*/
		$requete = "SELECT spip_articles.`id_article` as ID, `id_auteur` as post_author, `date` as post_date, `date` as post_date_gmt,
			replace(replace(replace(replace(replace(replace(replace(replace(replace(convert( convert( texte USING latin1 ) USING utf8 ),'{{{{',''), '}}}}', '<!--more-->'), '{{{','<h2>'), '}}}', '</h2>'), '{{', '<strong>'), '}}', '</strong>'), '{', '<em>'), '}', '</em>'), '_ ', '') as post_content,
			`titre` as post_title,  \"\" as post_excerpt, replace(replace(replace(replace(replace(`statut`,'poubelle', 'trash'),'publie', 'publish'), 'prepa', 'private'), 'prop', 'pending'), 'refuse', 'trash') as post_status,  \"open\" as comment_status, \"open\" as ping_status, \"\" as post_password, spip_articles.`id_article` as post_name, \"\" as to_ping, \"\" as pinged, `date_modif` as post_modified,`date_modif` as post_modified_gmt, \"\" as post_content_filtered, \"\" as post_parent,
			concat(\"http://tela-botanica.net/wpsite/actu\",spip_articles.`id_article`) as guid, \"0\" as menu_order, \"post\" as post_type, \"\" as post_mime_type, \"\" as comment_count FROM tela_prod_spip_actu.`spip_articles` left join tela_prod_spip_actu.spip_auteurs_articles on spip_auteurs_articles.`id_article` =  spip_articles.`id_article` WHERE id_rubrique in (22,54,70,30,19,51)";
		$articles = $this->bdd->recupererTous($requete);
		$requete_doc = "SELECT d.`id_document`, `fichier`, `id_article` FROM `spip_documents` d  left join spip_documents_articles da on da.`id_document` = d.`id_document`";
		$documents = $this->bdd->recupererTous($requete_doc);
		foreach ($documents as $doc) {
			$doc_loc[$doc['id_document']] = $doc['fichier'];
		}
		foreach ($articles as $article) {
			$article['post_content'] = preg_replace("\[(.*)->(.*)\]", '<a href="\2" target="_blank">\1</a>', $article['post_content']);
			//$images = preg_grep("\<img([0-9]*)\|[a-z]*\>", $article['post_content']);
			$article['post_content'] = preg_replace("\<img([0-9]*)\|[a-z]*\>", '<img src="'.$doc_loc['${1}'].'" />', $article['post_content']);
			$insert[] = "(".implode($article, ', ').")";
		}
		$requete_insert = "INSERT INTO `wp4_posts` VALUES ".implode($insert, ', ').";";
		$retour[] = $this->bdd->executer($requete_insert);
		echo 'Il y a '.count($retour).' actualités migrées '."--";
		return $retour;
	}
	
	private function migrerActualitesRubrique() {
		$requete = "INSERT INTO ".$this->basewp."term_relationships`(`object_id`, `term_taxonomy_id`)
			SELECT `id_article`, replace(replace(replace(replace(replace(replace(`id_rubrique`, 22, 20), 54, 21), 30, 23), 19, 24), 51, 25), 70, 22)
			FROM `spip_articles` WHERE id_rubrique in (22,54,70,30,19,51)";
	
		$retour = $this->bdd->executer($requete);
		echo 'Il y a '.count($retour).' actualités migrées '."--";
		return $retour;
	}
	
	private function migrerActualitesCommentaires() {
		$requete = "INSERT INTO ".$this->basewp.'comments`(`comment_ID`, `comment_post_ID`, `comment_author`, `comment_author_email`, `comment_author_url`, `comment_author_IP`, `comment_date`, `comment_date_gmt`, `comment_content`, `comment_karma`, `comment_approved`, `comment_agent`, `comment_type`, `comment_parent`, `user_id`)
			SELECT `id_forum` , `id_article` , `auteur` , `email_auteur` , "" AS url, `ip` , `date_heure` , `date_heure` AS gmt, `texte` , "0" AS karma, replace(`statut`, "publie", "1") , "" AS agent, "" AS
			TYPE , `id_parent` , `id_auteur`
			FROM tela_prod_spip_actu.`spip_forum`
			WHERE id_article in (SELECT `id_article` FROM tela_prod_spip_actu.`spip_articles` WHERE id_rubrique in (22,54,70,30,19,51))';
	
		$retour = $this->bdd->executer($requete);
		echo 'Il y a '.count($retour).' actualités migrées '."--";
		return $retour;
	}
	
	private function migrerActualitesLogo() {
		$requete = "INSERT INTO ".$this->basewp."term_relationships`(`object_id`, `term_taxonomy_id`)
			SELECT `id_article`, replace(replace(replace(replace(replace(replace(`id_rubrique`, 22, 20), 54, 21), 30, 23), 19, 24), 51, 25), 70, 22)
			FROM `spip_articles` WHERE id_rubrique in (22,54,70,30,19,51)";
	
		$retour = $this->bdd->executer($requete);
		echo 'Il y a '.count($retour).' actualités migrées '."--";
		return $retour;
	}
	
}
?>
