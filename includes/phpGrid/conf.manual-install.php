<?php
//*
if (stripos($_SERVER['SCRIPT_NAME'], 'apps/phpgrid-custom-crm')) {
    define('PHPGRID_DB_HOSTNAME', '127.0.0.1'); // database host name
    define('PHPGRID_DB_USERNAME', 'root');     // database user name
    define('PHPGRID_DB_PASSWORD', ''); // database password
    define('PHPGRID_DB_NAME', 'phpgrid_custom_crm'); // database name
    define('PHPGRID_DB_TYPE', 'mysql');  // database type
    define('PHPGRID_DB_CHARSET','utf8'); // ex: utf8(for mysql),AL32UTF8 (for oracle), leave blank to use the default charset
} elseif (stripos($_SERVER['SCRIPT_NAME'], 'apps/phpgrid-project-management')) {
    define('PHPGRID_DB_HOSTNAME', '127.0.0.1'); // database host name
    define('PHPGRID_DB_USERNAME', 'root');     // database user name
    define('PHPGRID_DB_PASSWORD', ''); // database password
    define('PHPGRID_DB_NAME', 'phpgrid_simple_pm'); // database name
    define('PHPGRID_DB_TYPE', 'mysql');  // database type
    define('PHPGRID_DB_CHARSET','utf8'); // ex: utf8(for mysql),AL32UTF8 (for oracle), leave blank to use the default charset
} else {
	//* mysql example 
    define('PHPGRID_DB_HOSTNAME','localhost'); // database host name
    define('PHPGRID_DB_USERNAME', 'root');     // database user name
    define('PHPGRID_DB_PASSWORD', 'root'); // database password
    define('PHPGRID_DB_NAME', 'sampledb'); // database name
    define('PHPGRID_DB_TYPE', 'mysql');  // database type
    define('PHPGRID_DB_CHARSET','utf8mb4');
}
/*
define('PHPGRID_DB_HOSTNAME', '75.126.155.153:50001'); // database host name
define('PHPGRID_DB_USERNAME', 'user07599');     // database user name
define('PHPGRID_DB_PASSWORD', 'UviZULzvvvHM'); // database password
define('PHPGRID_DB_NAME', 'SQLDB'); // database name
define('PHPGRID_DB_TYPE', 'odbc');  // database type
define('PHPGRID_DB_CHARSET','utf8'); // ex: utf8(for mysql),AL32UTF8 (for oracle), leave blank to use the default charset
/**/
/*
// PDO DB2 - currently only worked for cataloged connection with DB2 on the same system
// Future setting will work with uncatlagoed TCP/IP remote DB2
define('PHPGRID_DB_HOSTNAME', 'localhost'); // database host name
define('PHPGRID_DB_PORT', '50000'); // database host name
define('PHPGRID_DB_USERNAME', 'db2inst1');     // database user name
define('PHPGRID_DB_PASSWORD', 'db2user99'); // database password
define('PHPGRID_DB_NAME', 'SAMPLE'); // database name or DSN name (cataloged) 
define('PHPGRID_DB_TYPE', 'pdo_odbc_db2');  // database type
define('PHPGRID_DB_CHARSET','utf8'); // ex: utf8(for mysql),AL32UTF8 (for oracle), leave blank to use the default charset

putenv('ODBCSYSINI=/etc');
putenv('ODBCINI=/etc/odbc.ini');
/**/
/*
define('PHPGRID_DB_HOSTNAME', '75.126.155.153:50001'); // database host name
define('PHPGRID_DB_USERNAME', 'user07599');     // database user name
define('PHPGRID_DB_PASSWORD', 'UviZULzvvvHM'); // database password
define('PHPGRID_DB_NAME', 'bluemix'); // database name or DSN name (cataloged) 
define('PHPGRID_DB_TYPE', 'pdo_odbc_db2');  // database type
define('PHPGRID_DB_CHARSET','utf8'); // ex: utf8(for mysql),AL32UTF8 (for oracle), leave blank to use the default charset

putenv('ODBCSYSINI=/etc');
putenv('ODBCINI=/etc/odbc.ini');
/**/

// microsoft access example (Windows only)
/*
define('PHPGRID_DB_HOSTNAME', ''); // database host name
define('PHPGRID_DB_USERNAME', '');     // database user name
define('PHPGRID_DB_PASSWORD', ''); // database password
define('PHPGRID_DB_NAME', 'c:\\xampp1\\htdocs\\phpGridx\\examples\\SampleDB\\QrySampl.mdb'); // database name
define('PHPGRID_DB_TYPE', 'access');  // database type
define('PHPGRID_DB_CHARSET',''); // ex: utf8(for mysql),AL32UTF8 (for oracle), leave blank to use the default charset
*/

// postgres example
/*
define('PHPGRID_DB_HOSTNAME','localhost'); // database host name
define('PHPGRID_DB_USERNAME', 'root');     // database user name
define('PHPGRID_DB_PASSWORD', ''); // database password
define('PHPGRID_DB_NAME', 'test'); // database name
define('PHPGRID_DB_TYPE', 'postgres');  // database type
define('PHPGRID_DB_CHARSET','');
/**/

/* mssql server example (Linx)
define('PHPGRID_DB_HOSTNAME','sampledb123.database.windows.net'); // database host name or DSN name
define('PHPGRID_DB_USERNAME', 'myroot');     // database user name
define('PHPGRID_DB_PASSWORD', ''); // database password
define('PHPGRID_DB_NAME', 'phpgridazure'); // database name
define('PHPGRID_DB_TYPE', 'sqlsrv');  // database type
define('PHPGRID_DB_CHARSET','');
/*
putenv("ODBCINSTINI=/usr/local/Cellar/unixodbc/2.3.1/etc/odbcinst.ini");
putenv("ODBCINI=/usr/local/Cellar/unixodbc/2.3.1/etc/odbc.ini"); //odbc.ini contains your DSNs.
/**/

// mssql server example (Windows)
/*
define('PHPGRID_DB_HOSTNAME','localhost'); // database host name or DSN name
define('PHPGRID_DB_USERNAME', 'sqluser');     // database user name
define('PHPGRID_DB_PASSWORD', 'pass'); // database password
define('PHPGRID_DB_NAME', 'sampledb'); // database name
define('PHPGRID_DB_TYPE', 'sqlsrv');  // database type
define('PHPGRID_DB_CHARSET','');
/**/

// oracle server exampl
/*
define('PHPGRID_DB_HOSTNAME','oracle-rds.cbdlprkhjrmd.us-west-1.rds.amazonaws.com');
define('PHPGRID_DB_USERNAME', 'oracleuser');     // database user name
define('PHPGRID_DB_PASSWORD', ''); // database password
define('PHPGRID_DB_NAME', 'sampledb'); // database name
define('PHPGRID_DB_TYPE', 'oci805');  // database type
define('PHPGRID_DB_CHARSET','AL32UTF8');
/**/

// sqlite server example
/*
define('PHPGRID_DB_HOSTNAME','c:\path\to\sqlite.db'); // database host name
define('PHPGRID_DB_USERNAME', '');     // database user name
define('PHPGRID_DB_PASSWORD', ''); // database password
define('PHPGRID_DB_NAME', ''); // database name
define('PHPGRID_DB_TYPE', 'sqlite');  // database type
define('PHPGRID_DB_CHARSET','');
/**/

// db2 example
/*
define('PHPGRID_DB_HOSTNAME','localhost'); // database host name
define('PHPGRID_DB_USERNAME', 'db2user');     // database user name
define('PHPGRID_DB_PASSWORD', 'db2user'); // database password
define('PHPGRID_DB_NAME', 'sample'); // database name
define('PHPGRID_DB_TYPE', 'db2');  // database type
define('PHPGRID_DB_CHARSET','');
/**/


// *** You should define SERVER_ROOT manually when use Apache alias directive or IIS virtual directory ***
/* if (stripos($_SERVER['SCRIPT_NAME'], 'apps/phpgrid-custom-crm')) {
    define('SERVER_ROOT', '/phpGridx/apps/phpgrid-custom-crm/phpGrid'); 
    define('THEME', 'start');
*/


    
define('SERVER_ROOT', str_replace(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])),'', str_replace('\\', '/',dirname(__FILE__))));
define('THEME', 'bootstrap');
define('FRAMEWORK', '');	// indicating framework integrating - not used yet
define('DEBUG', false); // *** MUST SET TO FALSE WHEN DEPLOYED IN PRODUCTION ***
define('CDN', false);        // use Cloud CDN by default. False to use the local libraries
define('UPLOADEXT', 'gif,png,jpg,jpeg');
define('UPLOADDIR', '/Applications/MAMP/localhost/phpGridx/uploads/');




/******** DO NOT MODIFY ***********/
require_once('phpGrid.php');
/**********************************/
?>
