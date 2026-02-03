<?php
define('PHPGRID_DB_HOSTNAME','{{dbhost}}'); 
define('PHPGRID_DB_USERNAME', '{{dbuser}}');    
define('PHPGRID_DB_PASSWORD', '{{dbpass}}'); 
define('PHPGRID_DB_NAME', '{{dbname}}');
define('PHPGRID_DB_TYPE', '{{dbtype}}'); 
define('PHPGRID_DB_CHARSET','utf8mb4');



// *** You should only define SERVER_ROOT manually when use Apache alias directive or IIS virtual directory ***
define('SERVER_ROOT', str_replace(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])),'', str_replace('\\', '/',dirname(__FILE__))));
define('THEME', 'bootstrap');
define('FRAMEWORK', '');	// indicating framework integrating - not used yet**
define('CDN', false);        // use Cloud CDN by default. False to use the local libraries
define('DEBUG', false); // *** MUST SET TO FALSE WHEN DEPLOYED IN PRODUCTION *
define('UPLOADEXT', 'gif,png,jpg,jpeg');
define('UPLOADDIR', '/Applications/MAMP/localhost/phpGridx/uploads/');



/******** DO NOT MODIFY ***********/
require_once('phpGrid.php');
/**********************************/