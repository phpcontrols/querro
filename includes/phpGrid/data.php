<?php 
use phpCtrl\C_Database as C_Database;
use phpCtrl\C_DataGrid as C_DataGrid;
use phpCtrl\C_Utility as C_Utility;
use phpCtrl\C_SessionMaker as C_SessionMaker;

require_once('phpGrid.php');

$session = C_SessionMaker::getSession(FRAMEWORK);

$gridName   = isset($_GET['gn']) ? $_GET['gn'] : die('PHPGRID_ERROR: ULR parameter "gn" is not defined.');
$data_type  = isset($_GET['dt']) ? $_GET['dt']:'json';

$grid_sql   = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql');
$sql_key    = unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_key'));
$sql_fkey   = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_fkey');
$sql_table  = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_table');
$sql_filter = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_filter');
$db_connection = unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_db_connection'));
$has_pagecount  = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_pagecount');


// Establish db connection
$cn = $db_connection;
if(empty($cn)){  // PHPGRID_DB_OPTIONS is DB2 specific
    $db = new C_Database(PHPGRID_DB_HOSTNAME, PHPGRID_DB_USERNAME, PHPGRID_DB_PASSWORD, PHPGRID_DB_NAME, PHPGRID_DB_TYPE, PHPGRID_DB_CHARSET, $sql_table);
}
else { // Multiple database support
    $db = new C_Database($cn["hostname"],$cn["username"],$cn["password"],$cn["dbname"],$cn["dbtype"],$cn["dbcharset"], $sql_table);
}

// Get original column names and add 1 as default sortname
$rs          = $db->select_limit($grid_sql, 1, 0);
$col_dbnames = [];
$col_dbnames = $db->get_col_dbnames($rs);
array_push($col_dbnames, 1);

// Sql limit, range, sort name, sort order
$page   = (isset($_GET['page'])) ? $_GET['page'] : 1;
$limit  = (isset($_GET['rows'])) ? $_GET['rows'] : 20;
$sord   = (isset($_GET['sord'])) ? $_GET['sord'] : 'asc';
$sidx   = (isset($_GET['sidx'])) ? $_GET['sidx'] : 1;
if(!$sidx) $sidx = 1;


// Prepare sql where statement.
$sqlWhere = "";
$searchOn = (isset($_REQUEST['_search']) && $_REQUEST['_search'] =='true')?true:false;

if($searchOn) {
    // Check if the key is actual a database field. If true, add it to SQL Where (sqlWhere) statement
    foreach($_REQUEST as $key=>$value) {
         if(in_array($key, $col_dbnames)){
            // Make sure to only pass the column name excluding table namespace
            $colName = (strpos($key, '.') === false) ? $key : explode('.', $key)[1];
            $fm_type = $db->field_metatype($rs, $db->field_index($rs, $colName));
            switch ($fm_type) {
                case 'I':
                case 'N':
                case 'R': case 'SERIAL':
                case 'L':
                    $sqlWhere .= " AND ".$key." = ".$value;
                    break;
                default:
                    $sqlWhere .= " AND ".$key." LIKE '".$value."%'";
                    break;
            }
        }

    }

    // Integrated toolbar and advanced search    
    if(isset($_REQUEST['filters']) && $_REQUEST['filters'] !=''){
        $op = array("eq"=>" ='%s' ","ne"=>" !='%s' ","lt"=>" < %s ",
            "le"=>" <= %s ","gt"=>" > %s ","ge"=>" >= %s ",
            "bw"=>" like '%s%%' ","bn"=>" not like '%s%%' " ,
            "in"=> " in (%s) ","ni"=> " not in (%s) ",
            "ew"=> " like '%%%s' ","en"=> " not like '%%%s' ",
            "cn"=> " like '%%%s%%' ","nc"=> " not like '%%%s%%' ");
            
        $filters = json_decode(stripcslashes($_REQUEST['filters']));
        $groupOp = $filters->groupOp;	// AND/OR
        $rules = $filters->rules;

        for($i=0;$i<count($rules);$i++){                   
            $sqlWhere .=  $groupOp . " ". $rules[$i]->field .
                sprintf($op[$rules[$i]->op],$rules[$i]->data);              
        }
    }
}

// Remove leading sql AND/OR
$pos = strpos($sqlWhere,'AND ');
if ($pos !== false) {
	$sqlWhere = substr_replace($sqlWhere,'',$pos,strlen('AND '));
}
$pos = strpos($sqlWhere,'OR ');
if ($pos !== false) {
	$sqlWhere = substr_replace($sqlWhere,'',$pos,strlen('OR '));
}

// Set ORDER BY. Don't use if user hasn't select a sort
$sqlOrderBy = (!$sidx) ? "" : " ORDER BY $sidx $sord";


// ********* prepare the final query ***********************
$groupBy_Position = strpos(strtoupper($grid_sql), "GROUP BY");

if($sql_filter != '' && $searchOn){
    $SQL = $grid_sql .' WHERE '. $sql_filter .' AND ('. $sqlWhere .')'. $sqlOrderBy;
}elseif($sql_filter != '' && !$searchOn){
    $SQL = $grid_sql .' WHERE '. $sql_filter . $sqlOrderBy;
}elseif($sql_filter == '' && $searchOn){
    $SQL = $grid_sql .' WHERE '. $sqlWhere . $sqlOrderBy;
}else{ // if($sql_filter == '' && !$searchOn){
    $SQL = $grid_sql . $sqlOrderBy;
}


// ******************* execute query finally *****************
// Calculate the starting position of the rows 
$start = $limit*$page - $limit;
// When some reasons start position is negative set it to 0. typical case is that the user type 0 for the requested page 
if($start <0) $start = 0; 
$db->db->SetFetchMode(ADODB_FETCH_BOTH);
$result = $db->select_limit($SQL, $limit, $start);

// ************************ pagination ************************
$count = $db->num_rows($db->db_query($SQL)); // total record count used for pagination (unfortunate performance penality).                 
// Calculate the total pages for the query 
if( $count > 0 && $limit > 0) { 
	$total_pages = ceil($count/$limit); 
}else{ 
	$total_pages = 0; 
} 
// If for some reasons the requested page is greater than the total set the requested page to total page 
if ($page > $total_pages) $page=$total_pages;

// *************** return in JSON ************
switch($data_type)
{
    case "json":
        $response = new stdClass();   // define anonymous objects
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $i=0;
        $data = array();


        while($row = $db->fetch_array_assoc($result)) {
            unset($data);
            $response->rows[$i]['id']=C_Utility::gen_rowids($row, $sql_key); //$row[$sql_key];
            for($j = 0; $j < $db->num_fields($result); $j++) {
                $col_name = $db->field_name($result, $j);
                    if(PHPGRID_DB_TYPE == 'odbc_mssql_native' || PHPGRID_DB_TYPE == 'odbc_mssql'){
                        $data[] = utf8_encode($row[$col_name]);
                    } else {
                        $data[] = $row[$col_name];
                    }
            }
            $response->rows[$i]['cell'] = $data;
            $data = array();    // reset array

            $i++;
        }
        echo json_encode($response);
        break;
} 

$db = null;