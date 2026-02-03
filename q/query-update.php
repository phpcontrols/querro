<?php
include_once(__DIR__ ."/../includes/phpGrid/conf.php");

use phpCtrl\C_Database as C_Database;

$cquery     = $_POST['cquery'] ?? null;
$id         = $_POST['queryId'] ?? null;
$userId     = $_SESSION['UserId'] or die('Missing user ID.');

if ($cquery && $id) {

    $db = new C_Database( APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

    $rs     = $db->db_query(
                    "UPDATE `query` SET `query` = ? WHERE `id` = ? AND `user_id` = ? ", 
                    [$cquery, $id, $userId]
                );

    echo '{"id":"'. $id .'"}';

}