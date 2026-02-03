<?php
include_once(__DIR__ ."/../includes/phpGrid/conf.php");

use phpCtrl\C_Database as C_Database;

$queryId    = $_POST['queryId'] ?? null;
$userId     = $_SESSION['UserId'] or die('Missing user ID.');

if ($queryId) {

    $db = new C_Database( APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

    $result     = $db->db_query("SELECT * FROM `query` WHERE id = ? AND user_id = ? ", [$queryId, $userId]);

    if(!$result->EOF){
        $rs = $result->fields;
        $result->MoveNext();

        // Use json_encode to properly escape special characters (newlines, quotes, etc.)
        echo json_encode([
            'APP_URL' => APP_URL,
            'query' => $rs['query'],
            'name' => $rs['name'],
            'shareKey' => $rs['share_key'],
            'db' => $rs['db'],
            'date_modified' => $rs['date_modified']
        ], JSON_UNESCAPED_SLASHES);
    }
    
}