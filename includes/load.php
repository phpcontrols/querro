<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('__ROOT__', dirname(dirname(__FILE__)));

if(!session_id()){ session_start();}

// parsed URL path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// unauthenticated user to login except for a few paths;
if ($path != '/q/view.php' &&
    $path != '/includes/phpGrid/data.php' &&
    !isset($_SESSION['AccountId'])
    ) {
    header('Location: /login');
}

// Configurations
include_once(__ROOT__ .'/config/config.php');

// Databases
include_once(DATABASES_FILE);
if(!isset($_databases))
    $_databases = array();

/* Autoload for vendor */
include_once(__ROOT__ .'/vendor/autoload.php');

// Core
include_once(__ROOT__ .'/includes/core_db.php');

// Functions
include_once(__ROOT__ .'/includes/functions.php');

// If there is an action
if (isset($_GET['action']) or isset($_POST['action']))
    include_once(__ROOT__ .'/q/actions.php');


// Add server side error tracking below