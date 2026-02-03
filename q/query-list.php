<?php
include_once(__DIR__ ."/../includes/phpGrid/conf.php");

use phpCtrl\C_Database as C_Database;

$userId  = $_SESSION['UserId'] or die('Missing user ID.');

$db = new C_Database( APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

// Get optional database filter parameter
$selectedDb = isset($_GET['db']) ? $_GET['db'] : null;

// Build query with optional database filter
if ($selectedDb) {
    $results = $db->db_query("SELECT * FROM `query` WHERE user_id = ? AND db = ? ORDER BY date_modified DESC", [$userId, $selectedDb]);
} else {
    $results = $db->db_query("SELECT * FROM `query` WHERE user_id = ? ORDER BY date_modified DESC", [$userId]);
}

$queryList = array();
$count = 0;
while($row = $db->fetch_array_assoc($results)) {
 $data_row = array();
    for($i = 0; $i < $db->num_fields($results); $i++) {
        $col_name = $db->field_name($results, $i);
        $queryList[$count][$col_name] = $row[$col_name];
    }
    $count++;
}
echo json_encode($queryList);  