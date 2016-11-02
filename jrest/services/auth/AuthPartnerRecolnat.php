<?php

require "AuthPartner.php";

/**
 * Permet de se connecter à l'annuaire de Tela Botanica à l'aide d'un compte eRecolnat
 * à travers le CAS de Brice (https://cas.recolnat.org)
 * 
 * // POST https://cas.recolnat.org/v1/tickets -H 'Content-Type: application/x-www-form-urlencoded' --data 'username=monNom&password=monMdp'
 * // => 400 ou 201
 * => GET https://api.recolnat.org/erecolnat/v1/users/login/monNom
 */
class AuthPartnerRecolnat extends AuthPartner {

	public function verifierAcces($login, $password) {
		$login = urlencode($login); // pour les espaces dans le nom d'utilisateur
		$password = urlencode($password);
		$url = "https://cas.recolnat.org/v1/tickets";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, "username=$login&password=$password");
		if ($this->config['auth']['curl_soft_ssl']) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		}
		$res = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		//var_dump($httpCode); exit;
		if ($httpCode == 201) { // un ticket a été créé, l'utilisateur existe
			// récupération des infos utilisateur
			// (attention, répond même si l'authentification a échoué !)
			$url = "https://api.recolnat.org/erecolnat/v1/users/login/$login";
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			if ($this->config['auth']['curl_soft_ssl']) {
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			}
			$res = curl_exec($curl);
			curl_close($curl);

			$res = json_decode($res, true);
			//var_dump($res); exit;
			if ($res != null) {
				unset($res['avatar']['data']); // trop gros, rentre pas dans le header du jeton et pète le service
				$this->jetonPartenaire = $res; // pas vraiment un jeton...
				// stockage pour traitement dans les autres méthodes
				$this->data = $res;
				//var_dump($this->data); exit;
				if ( !empty($this->data['email'])) {
					//var_dump($this->data['email']);
					return true;
				}
			}
		}
		return false;
	}

	protected function getNomPartenaire() {
		return "recolnat";
	}

	public function getCourriel() {
		return $this->data['email'];
	}

	protected function getId() {
		// le "login" est le "username" dans eRecolnat, mais il y a aussi
		// un "user_id" et un "user_uuid"... @TODO valider cette stratégie
		return $this->data['login'];
	}

	protected function getValeursProfilPartenaire() {
		return array(
			// @WARNING "firstname" et "lastname" semblent inversés
			'nom' => $this->data['firstname'],
			'prenom' => $this->data['lastname'],
			'email' => $this->data['email'],
			'pseudo' => $this->data['login'] // @TODO valider cette stratégie
		);
	}

	/*public function getTimestampMajPartenaire() {
		return 420000000000;
	}*/
}
