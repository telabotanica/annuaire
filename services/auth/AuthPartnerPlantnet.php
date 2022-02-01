<?php

require "AuthPartner.php";

/**
 * Permet de se connecter à l'annuaire de Tela Botanica à l'aide d'un compte Pl@ntNet / identify
 */
class AuthPartnerPlantnet extends AuthPartner {

	public function verifierAcces($login, $password) {
		$login = urlencode($login); // pour les espaces dans le nom d'utilisateur
		$password = urlencode($password);
		$url = "https://api.plantnet.org/v1/users/login";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
			'login' => urldecode($login),
			'password' => urldecode($password)
		]));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		$res = curl_exec($curl);
		curl_close($curl);

		// var_dump($res); exit;
		$res = json_decode($res, true);
		//var_dump($res); exit;
		if (!empty($res['token'])) {
			$this->jetonPartenaire = $res['token'];
			$this->data = $this->SSO->decoderJetonManuellement($this->jetonPartenaire);
			// stockage pour traitement dans les autres méthodes
			//var_dump($this->data); exit;
			if ( !empty($this->data['email'])) {
				//var_dump($this->data['email']);
				return true;
			}
		}
		return false;
	}

	protected function getNomPartenaire() {
		return "plantnet";
	}

	public function getCourriel() {
		return $this->data['email'];
	}

	protected function getId() {
		// la clef primaire est le "username" dans Pl@ntNet, apparemment
		return $this->data['userName'];
	}

	protected function getValeursProfilPartenaire() {
		return array(
			'nom' => $this->data['lastName'],
			'prenom' => $this->data['firstName'],
			'email' => $this->data['email'],
			'pseudo' => $this->data['userName']
		);
	}

	/*public function getTimestampMajPartenaire() {
		return 420000000000;
	}*/
}
