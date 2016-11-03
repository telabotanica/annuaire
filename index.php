<?php

// composer
require_once 'vendor/autoload.php';

require 'AnnuaireService.php';

// Initialisation et exécution du service
$svc = new AnnuaireService();
$svc->run();
