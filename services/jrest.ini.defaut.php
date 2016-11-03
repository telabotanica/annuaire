;<?/*
[settings]
baseURL = "/reseau/annuaire/jrest/"
; URL de base relative alternative de JREST (pour les raccourcis par exemple)
baseAlternativeURL = "/service:annuaire:"

; Default
[appli]
phptype  = mysql
username =
password =
hostspec = localhost
database = tb_v4

; Infos pour Google Analytics
[google]
email_google = accueil@tela-botanica.org
password_google = ""
id_site_google = 16474

; Identification
[database_ident]
phptype  = mysql
username =
password =
hostspec = localhost
database = tb_v4
annuaire = annuaire_tela
ann_id = U_MAIL
ann_pwd = U_PASSWD
pass_crypt_funct = md5
nom_cookie_persistant="pap-annuaire_tela-memo"
nom_cookie_utilisateur="pap-annuaire_tela-utilisateur"

;MESSAGERIE
[messagerie]
utilisateurs_autorises = identiplante@tela-botanica.org,annuaire@tela-botanica.org

; LOGS
[log]
cheminlog = "/tmp"
timezone = "Europe/Paris"
taillemax = 100000

; ADMIN
[jrest_admin]
admin = mathias@tela-botanica.org,delphine@tela-botanica.org,killian@tela-botanica.org
; Liste des ips (nom de domaine) autorisés à accéder aux services
ip_autorisees = "127.0.0.1, 193.54.123.169, 193.54.123.216, 162.38.234.6"
; mot de passe universel, haché en MD5 (attention à ne pas laisser fuiter !!)  - laisser vide ("") pour désactiver
mdp_magique_hache = ""

; AUTH (SSO)
[auth]
; si true, refusera toute connexion non-HTTPS
forcer_ssl = true
nom_cookie = tb_auth
duree_cookie = 31536000
duree_jeton = 900
; utiliser "tela-botanica.org" ou ".tela-botanica.org" pour lever la restriction sur les sous-domaines
domaine_cookie = tela-botanica.org
; si "true", Curl ne vérifiera pas l'authenticité de l'hôte (SSL de Sequoia trop vieux)
curl_soft_ssl = false
; si forcer_ssl est false et qu'on accède au service en HTTP, mettre à false pour obtenir le cookie
cookie_securise = true

;*/?>
