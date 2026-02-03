<?php
include_once(__DIR__ ."/../includes/phpGrid/conf.php");

use phpCtrl\C_Database as C_Database;

$cquery     = $_POST['cquery'] ?? null;
$queryName  = $_POST['queryName'] ?? null;
$dbConn     = $_POST['db'] ?? null;
$userId     = $_SESSION['UserId'] or die('Missing user ID.');
$shareKey   = getGUID();

if ($cquery) {

    $db = new C_Database( APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

    $rs     = $db->db_query("INSERT INTO `query` (`user_id`, `name`, `db`, `query`, `share_key`) VALUE(?, ?, ?, ?, ?)", [$userId, $queryName, $dbConn, $cquery, $shareKey]);

    echo '{"id":"'. $db->Insert_ID() .'", "shareKey": "'. $shareKey .'"}';

}