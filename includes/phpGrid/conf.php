<?php
include_once(__DIR__ ."/../../includes/load.php");

global $_databases;

// Only need the FIRST db during initial page load
if (!empty($_databases)) {

    $dbSettings = $_databases[key($_databases)];

    if (!defined('PHPGRID_DB_HOSTNAME')) define('PHPGRID_DB_HOSTNAME',$dbSettings['server']); // database host name
    if (!defined('PHPGRID_DB_USERNAME')) define('PHPGRID_DB_USERNAME',$dbSettings['username']); // database user name
    if (!defined('PHPGRID_DB_PASSWORD')) define('PHPGRID_DB_PASSWORD',$dbSettings['password']); // database password
    if (!defined('PHPGRID_DB_NAME'))     define('PHPGRID_DB_NAME',    $dbSettings['name']); // database name
    if (!defined('PHPGRID_DB_TYPE'))     define('PHPGRID_DB_TYPE', 'mysql');  // database type
    if (!defined('PHPGRID_DB_CHARSET'))  define('PHPGRID_DB_CHARSET','utf8');

}else {

    if (!defined('PHPGRID_DB_HOSTNAME')) define('PHPGRID_DB_HOSTNAME',''); // database host name
    if (!defined('PHPGRID_DB_USERNAME')) define('PHPGRID_DB_USERNAME',''); // database user name
    if (!defined('PHPGRID_DB_PASSWORD')) define('PHPGRID_DB_PASSWORD',''); // database password
    if (!defined('PHPGRID_DB_NAME'))     define('PHPGRID_DB_NAME',    ''); // database name
    if (!defined('PHPGRID_DB_TYPE'))     define('PHPGRID_DB_TYPE', 'mysql');  // database type
    if (!defined('PHPGRID_DB_CHARSET'))  define('PHPGRID_DB_CHARSET','utf8');
}

if (!defined('SERVER_ROOT'))    define('SERVER_ROOT', str_replace(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])),'', str_replace('\\', '/',dirname(__FILE__))));
if (!defined('THEME'))          define('THEME', 'bootstrap');
if (!defined('FRAMEWORK'))      define('FRAMEWORK', '');	// indicating framework integrating - not used yet
if (!defined('DEBUG'))          define('DEBUG', false); // *** MUST SET TO FALSE WHEN DEPLOYED IN PRODUCTION ***
if (!defined('CDN'))            define('CDN', true);        // use Cloud CDN by default. False to use the local libraries



/******** DO NOT MODIFY ***********/
require_once('phpGrid.php');
/**********************************/