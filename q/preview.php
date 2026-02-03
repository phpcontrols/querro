<?php
include_once(__DIR__ ."/../includes/phpGrid/conf.php");

use phpCtrl\C_DataGrid as C_DataGrid;

// Accept both POST (new) and GET (legacy) for transition period
$dbStr = $_POST['db'] ?? $_GET['db'] ?? die('No database connection information.');
$theTable = $_POST['table'] ?? $_GET['table'];
$cquery = $_POST['cquery'] ?? $_GET['cquery'] ?? die('No custom query information.');
$code = [];
?>
<!DOCTYPE html> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0,user-scalable=0"/>

<title>Preview</title>

<style>
#code {
    border: 1px solid #ddd;
    white-space: nowrap;
    overflow: auto;
    padding: 2px;
    border-radius: 5px;
    font-size: 12px;
    background: beige;
}
</style>

</head>
<body>

<?php
if ($cquery != '') {
	$conn = new \mysqli(APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);
	if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
	
	$stmt = $conn->prepare("SELECT *  FROM dbs WHERE CONCAT(`dbs`.`name`, '@', `dbs`.`server`, ':', `dbs`.`port`) = ? AND dbs.account_id = ? LIMIT 1");
	$stmt->bind_param("si", $dbStr, $accountId);
	$stmt->execute();

	$row = $stmt->get_result()->fetch_array(MYSQLI_ASSOC);
	
	$stmt->close();
	$conn->close();

	$pks = getPrimaryKeyFromSettings($dbStr, $theTable);

	// construct our initial basic datagrid
	$dg = new C_DataGrid($cquery, [], '',
										["hostname"=>$row['server'],
										"username"=>$row['username'],
										"password"=>$row['password'],
										"dbname"=>$row['name'], 
										"dbtype"=>'mysql',
										"dbcharset"=>$row['encoding']] );

	$dg->set_caption('Query Results');
	$dg->enable_autowidth(true);
	$dg->display();
}
?>

</body>
</html>