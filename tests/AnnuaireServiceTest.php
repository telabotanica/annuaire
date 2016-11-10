<?php

/**
 * Teste le Webservice Annuaire
 * lancer dans le dossier annuaire avec :
 *  $ phpunit --bootstrap vendor/autoload.php tests/AnnuaireServiceTest.php
 */
class AnnuaireServiceTest extends PHPUnit_Framework_TestCase {

	const CHEMIN_CONFIG = __DIR__ . "/config.json";
	const CHEMIN_TESTS = __DIR__ . "/tests.json";

	/** test URLs list */
	protected $tests;

	/** hosts config */
	protected $config;

	/** client HTTP Guzzle */
	protected $client;

	public function setUp(){
		// config
		$this->config = json_decode(file_get_contents(self::CHEMIN_CONFIG), true);
		$tests = json_decode(file_get_contents(self::CHEMIN_TESTS), true);
		$this->tests = $tests['AnnuaireServiceTest'];
		// client Webservices
		$this->client = new GuzzleHttp\Client();
	}

	public function tearDown(){}

	/**
	 * Appelle un Webservice avec Guzzle et renvoie le résultat
	 * @param type $url
	 * @param type $methode
	 * @param type $verifierCodeHTTP
	 * @param type $parserJson
	 * @return type
	 */
	protected function appelService($url, $methode='GET', $verifierCodeHTTP=200, $parserJson=true) {
			$res = $this->client->request($methode, $url);
			if ($verifierCodeHTTP !== false) {
				// @WARNING 2-en-1 crado
				$this->assertEquals($verifierCodeHTTP, $res->getStatusCode());
			}
			$data = $res->getBody()->__toString(); // @WTF y a pas mieux que __toString() ?
			if ($parserJson) {
				$data = json_decode($data, true);
			}
			return $data;
	}

	/**
	 * Appelle l'ancien service et le novueau service avec la même URL et
	 * vérifie que les résultats sont identiques (égalité parfaite)
	 */
	protected function comparerRetrocompat($url) {
		$newRoot = $this->config['root_url'];
		$oldRoot = $this->config['retrocompat_url'];
		$newURL = $newRoot . $url;
		$oldURL = $oldRoot . $url;
		// compare
		$oldData = $this->appelService($oldURL);
		$newData = $this->appelService($newURL);
		// test
		$this->assertEquals($oldData, $newData);
		// @TODO remplacer par assertArraySubset(...)
	}

	/**
	 * Vérifier que toutes les valeurs de $keys sont des clefs de $array
	 * 
	 * @TODO déplacer dans une classe d'assertion propre
	 * 
	 * @param type $keys
	 * @param type $array
	 */
	protected function assertArrayHasKeys($keys, $array) {
		$this->assertNotEmpty($array);
		$ok = true;
		foreach ($keys as $k) {
			$ok = $ok && array_key_exists($k, $array);
		}
		$this->assertTrue($ok);
	}

	public function testTestLoginMdp() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['TestLoginMdp']['ok']);
		$this->assertEquals(true, $data);
	}

	public function testTestLoginMdpErreur() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['TestLoginMdp']['ko']);
		$this->assertEquals(false, $data);
	}

	public function testNbInscrits() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['NbInscrits']);
		$this->assertInternalType('int', $data);
	}

	public function testUtilisateurId() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['id']['ok']);
		$this->assertArrayHasKeys(array(
			'id', 'prenom', 'nom', 'courriel', 'pseudo', 'pseudoUtilise', 'intitule', 'nomWiki'
		), array_shift($data));
	}

	/*public function testUtilisateurIdErreur() {
		// cas d'erreur
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['id']['ko']);
		$this->assertEquals(false, $data);
	}*/

	public function testUtilisateurIdentiteParCourriel() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['identite-par-courriel']['ok']);
		$this->assertArrayHasKeys(array(
			'id', 'prenom', 'nom', 'courriel', 'pseudo', 'pseudoUtilise', 'intitule', 'nomWiki'
		), array_shift($data));
	}

	public function testUtilisateurIdentiteParCourrielMulti() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['identite-par-courriel']['multi']);
		$this->assertCount(3, $data);
		$this->assertArrayHasKeys(array(
			'id', 'prenom', 'nom', 'courriel', 'pseudo', 'pseudoUtilise', 'intitule', 'nomWiki'
		), array_shift($data));
	}

	public function testUtilisateurIdentiteCompleteParCourriel() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['identite-complete-par-courriel']['ok']);
		$this->assertArrayHasKeys(array(
			'id', 'prenom', 'nom', 'courriel', 'pseudo', 'pseudoUtilise', 'intitule', 'nomWiki'
		), array_shift($data));
	}

	public function testUtilisateurIdentiteCompleteParCourrielMulti() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['identite-complete-par-courriel']['multi']);
		$this->assertCount(3, $data);
		$this->assertArrayHasKeys(array(
			'id', 'prenom', 'nom', 'courriel', 'pseudo', 'pseudoUtilise', 'intitule', 'nomWiki'
		), array_shift($data));
	}

	public function testUtilisateurIdentiteCompleteParCourrielXml() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['identite-complete-par-courriel']['xml'], 'GET', 200, false);
		// @TODO faire un meilleur test en parsant le XML
		$this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?><personne><adresse>', $data);
	}

	public function testUtilisateurPrenomNomParCourriel() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['prenom-nom-par-courriel']['ok']);
		$this->assertArrayHasKeys(array(
			'id', 'prenom', 'nom'
		), array_shift($data));
	}

	public function testUtilisateurPrenomNomParCourrielMulti() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['prenom-nom-par-courriel']['multi']);
		$this->assertCount(2, $data);
		$this->assertArrayHasKeys(array(
			'id', 'prenom', 'nom'
		), array_shift($data));
	}

	public function testUtilisateurInfosParIds() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['InfosParIds']['ok']);
		$this->assertArrayHasKeys(array(
			'id', 'prenom', 'nom', 'courriel', 'pseudo', 'pseudoUtilise', 'intitule', 'nomWiki'
		), array_shift($data));
	}

	public function testUtilisateurInfosParIdsMulti() {
		$data = $this->appelService($this->config['root_url'] . $this->tests['urls']['utilisateur']['InfosParIds']['multi']);
		$this->assertCount(3, $data);
		$this->assertArrayHasKeys(array(
			'id', 'prenom', 'nom', 'courriel', 'pseudo', 'pseudoUtilise', 'intitule', 'nomWiki'
		), array_shift($data));
	}

	public function testAuth() {
		// @TODO implémenter
	}

	// ------------ rétrocompatibilité (nécessite les mêmes données) -----------

	/*public function testUtilisateurIdRetrocompat() {
		// rétrocompat
		$this->comparerRetrocompat($this->tests['urls']['utilisateur']['id']['ok']);
	}*/
}
