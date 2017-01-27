# annuaire
Nouvelle version de l'annuaire (SSO) Tela Botanica basée sur Wordpress

## installation
composer install --no-dev

## configuration
```
cd config
cp config.default.json config.json
cp service.default.json service.json
cp clef-auth.defaut.ini clef-auth.ini
```
### service.json
 - **domain_root** : racine du domaine, sans le protocole (ex: "beta.tela-botanica.org")
 - **base_uri** : URI de base du service (ex: "/service:annuaire"), dépend des redirections choisies
 - **first_resource_separator** : permier caractère attendu après base_uri (ex: "/" ou ":")
 - **auth** : voir la documentation dans le fichier

### config.json
 - **adapter** : implémentation du stockage, laisser "AnnuaireWPAPI" (seule implémentation fonctionnelle pour l'instant)
 - **adapters**
  - **AnnuaireWPAPI**
    - **chemin_wp** : chemin de l'installation de Wordpress (ex: "/home/user/www/test")
  - **auth**
    - **mdp_magique_hache** : si un mot de passe haché en MD5 est placé ici, ce mot de passe permettra d'acéder à tous les comptes

### clef-auth.ini
Saisir une suite d'au moins 16 caractères dans ce fichier.

Cette clef sert à signer les jetons du service d'authentification.

En cas de problème de sécurité (fuite de jeton admin par ex.), modifier ce fichier invalidera tous les jetons existants.

## gestion des rôles SSO
Les services **utilisateur** et **auth** renvoient la liste des rôles affectés à l'utilisateur.

Pour cumuler plusieurs rôles (nécessaire pour gérer les permissions SSO), nécessite le plugin Wordpress "Multiple Roles" : https://fr.wordpress.org/plugins/multiple-roles/

Charge à l'administrateur WP d'ajouter des rôles.
