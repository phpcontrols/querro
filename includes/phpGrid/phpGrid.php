<?php
require_once(dirname(__FILE__) .'/vendor/autoload.php');
include_once('conf.php');

if (FRAMEWORK != 'JOOMLA') {
	if(!session_id()){ session_start();}
}

if (DEBUG) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

require_once(dirname(__FILE__) .'/conf.php');
require_once(dirname(__FILE__) .'/callbackstr.php');
    require_once(dirname(__FILE__) .'/server/classes/cls_db.php');  
	require_once(dirname(__FILE__) .'/vendor/adodb/adodb-php/adodb.inc.php');  
require_once(dirname(__FILE__) .'/server/classes/cls_dataarray.php');  
require_once(dirname(__FILE__) .'/server/classes/cls_datagrid.php');  
require_once(dirname(__FILE__) .'/server/classes/cls_util.php');  
require_once(dirname(__FILE__) .'/server/classes/cls_searchfilter.php');
require_once(dirname(__FILE__) .'/server/classes/cls_csrf.php');
require_once(dirname(__FILE__) .'/server/classes/cls_session.php');  

// fix missing DOCUMENT_ROOT in IIS
if(!isset($_SERVER['DOCUMENT_ROOT'])){ 
	if(isset($_SERVER['SCRIPT_FILENAME'])){
        $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])));
	}; 
};
if(!isset($_SERVER['DOCUMENT_ROOT'])){ 
	if(isset($_SERVER['PATH_TRANSLATED'])){
        $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])));
	}; 
};

define('GRID_SESSION_KEY', '_oPHPGRID');
define('JQGRID_ROWID_KEY', '_rowid');
define("CHECKBOX", "checkbox");
define("SELECT", "select");
define("MULTISELECT", "multiselect");  
define('PK_DELIMITER', '---');     // must be 3 characters