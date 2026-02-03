<?php
include_once(__DIR__ ."/../includes/phpGrid/conf.php");

use phpCtrl\C_Database as C_Database;

$json = file_get_contents('php://input');
$jsonObj = json_decode($json);

$id = $jsonObj->id;
$userId  = $_SESSION['UserId'] or die('Missing user ID.');
           
$db = new C_Database( APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);


try {
    $results     = $db->db_query("DELETE FROM `query` WHERE id = ? AND user_id = ? ", [$id, $userId]); 

    echo 1;

} catch(Exception $ex) {

    echo 0;

}
echo 'end';