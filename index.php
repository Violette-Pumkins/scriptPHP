<?php

use Domaine;
use autoload;
use domaineController;

require 'vendor/autoload.php';
require 'Controller/domaineController.php';
require 'Model/Domaine.php';




$controller = new domaineController($dbHost, $dbName, $dbUser, $dbPass, $fileUrl, $logFile);
$controller->processFileContent();