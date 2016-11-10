<?php

class AnnuaireServiceTest extends PHPUnit_Framework_TestCase {

	const CHEMIN_CONFIG = __DIR__ . "/config.json";
	const CHEMIN_TESTS = __DIR__ . "/tests.json";

	/** test URLs list */
	protected $tests;

	/** hosts config */
	protected $config;

	/** cURL handle */
	protected $ch;

	public function setUp(){
		// config
		$this->config = json_decode(file_get_contents(self::CHEMIN_CONFIG), true);
		$this->tests = json_decode(file_get_contents(self::CHEMIN_TESTS), true);
		// libcurl
	}

	public function tearDown(){}

	public function testRetrocompat() {
		var_dump($this->config);
		var_dump($this->tests);
	}

	public function testAuth() {
		// @TODO implémenter
	}
}
