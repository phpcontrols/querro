<?php
include_once(__DIR__ ."/../includes/phpGrid/conf.php");

use phpCtrl\C_Database as C_Database;

$queryName  = $_POST['queryName'] ?? null;
$id         = $_POST['queryId'] ?? null;
$userId     = $_SESSION['UserId'] or die('Missing user ID.');

if ($queryName) {

    $db = new C_Database( APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

    $rs     = $db->db_query(
                    "UPDATE `query` SET `name` = ? WHERE `id` = ? AND `user_id` = ? ", 
                    [$queryName, $id, $userId]
                );

    echo '{"id":"'. $id .'"}';

}