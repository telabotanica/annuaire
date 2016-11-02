<?php

require "AuthPartner.php";

/**
 * Permet de se connecter à l'annuaire de Tela Botanica à l'aide d'un compte Pl@ntNet / identify
 */
class AuthPartnerPlantnet extends AuthPartner {

	public function verifierAcces($login, $password) {
		$login = urlencode($login); // pour les espaces dans le nom d'utilisateur
		$password = urlencode($password);
		$url = "http://identify.plantnet-project.org/api/security/token/create?_username=$login&_password=$password";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, array()); // nécessaire dans les versions modernes de libcurl sinon on se prend un 400 !
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($curl);
		curl_close($curl);

		// var_dump($res); exit;
		$res = json_decode($res, true);
		//var_dump($res);
		if (!empty($res['JWT'])) {
			$this->jetonPartenaire = $res['JWT'];
			$jetonDecode = $this->auth->decoderJetonManuellement($this->jetonPartenaire);
			// stockage pour traitement dans les autres méthodes
			$this->data = $jetonDecode['details'];
			//var_dump($jeton);
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
		return $this->data['username'];
	}

	protected function getValeursProfilPartenaire() {
		return array(
			'nom' => $this->data['lastname'],
			'prenom' => $this->data['firstname'],
			'email' => $this->data['email'],
			'pseudo' => $this->data['username']
		);
	}

	/*public function getTimestampMajPartenaire() {
		return 420000000000;
	}*/
}
