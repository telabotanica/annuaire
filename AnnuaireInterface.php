<?php

interface AnnuaireInterface {

	//

	// -------------- rétrocompatibilité (11/2016) -------------------

	public function testLoginMdp($courriel, $mdpHache);

}
