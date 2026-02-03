<?php
include_once(__DIR__ ."/../includes/phpGrid/conf.php");

use phpCtrl\C_Database as C_Database;

$queryId    = $_POST['queryId'] ?? null;
$userId     = $_SESSION['UserId'] or die('Missing user ID.');
$shared     = $_POST['shared'] ?? false;
$shareKey   = '';

if ($queryId && $userId) {
    $db = new C_Database( APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

    if ($shared === 'false' || $shared === false) {
        $rs = $db->db_query(
            "UPDATE `query` SET `share_key` = '' WHERE `id` = ? AND `user_id` = ? ", 
            [$queryId, $userId]
        );
    } else {
        $shareKey = getGUID();
        $rs = $db->db_query(
            "UPDATE `query` SET `share_key` = ? WHERE `id` = ? AND `user_id` = ? ", 
            [$shareKey, $queryId, $userId]
        );
    }

    echo '{"id":"'. $queryId .'", "shareKey":"'. $shareKey .'"}';
}