<?php 
use phpCtrl\C_DataBase as C_DataBase;
use phpCtrl\C_DataGrid as C_DataGrid;
use phpCtrl\C_Utility as C_Utility;
use phpCtrl\C_SearchFilter as C_SearchFilter;
use phpCtrl\C_SessionMaker as C_SessionMaker;

require_once('phpGrid.php');
C_Utility::handle_endpoint_errors();   // clean PHPGRID_ERROR responses, no stack traces


// ********************* Get session variables *********************
$session = C_SessionMaker::getSession(FRAMEWORK);

$gridName   = isset($_GET['gn']) ? $_GET['gn'] : C_Utility::fail('PHPGRID_ERROR: URL parameter "gn" is not defined.');
$data_type  = isset($_GET['dt']) ? $_GET['dt']:'json';

$grid_sql   = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql');
$sql_key    = unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_key'));
$sql_fkey   = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_fkey');
$sql_table  = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_table');
$sql_filter = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_filter');
$db_connection = unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_db_connection'));
$has_pagecount  = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_pagecount');
$max_rows       = (int)$session->get(GRID_SESSION_KEY.'_'.$gridName.'_max_rows');   // 0 = no cap

$is_grouping    = (bool)$session->get(GRID_SESSION_KEY.'_'.$gridName.'_grouping');


// ********************* Establish db connection *********************
$cn = $db_connection;
if(empty($cn)){  // PHPGRID_DB_OPTIONS is DB2 specific
    $db = new C_DataBase(PHPGRID_DB_HOSTNAME, PHPGRID_DB_USERNAME, PHPGRID_DB_PASSWORD, PHPGRID_DB_NAME, PHPGRID_DB_TYPE, PHPGRID_DB_CHARSET, $sql_table);
}
else { // multiple database support
    $db = new C_DataBase($cn["hostname"],$cn["username"],$cn["password"],$cn["dbname"],$cn["dbtype"],$cn["dbcharset"], $sql_table);
}

// get original column names and add 1 as default sortname
$rs          = $db->select_limit($grid_sql, 1, 0);
$col_dbnames = [];
$col_dbnames = $db->get_col_dbnames($rs);
array_push($col_dbnames, 1);

// sql limit, range, sort name, sort order
// page size is capped at the most rows the grid's configuration offers (C_Utility::max_page_size)
$page   = C_Utility::page_number(isset($_GET['page']) ? $_GET['page'] : 1);
$limit  = C_Utility::page_limit(isset($_GET['rows']) ? $_GET['rows'] : null, $max_rows);
$sord   = (isset($_GET['sord'])) ? $_GET['sord'] : 'asc';
$sidx   = (isset($_GET['sidx'])) ? $_GET['sidx'] : 1;
if(!$sidx) $sidx = 1;


// ********************* Security check *********************
// grouping field would have a secondary sort 
$sidx2 = '';
if ($is_grouping) {
    $sidx2 = trim(explode(',', $sidx)[1]);
    $sidx2 = C_Utility::white_list($sidx2, $col_dbnames, "Invalid field name");
}

// The default sortname is 1 unless users click column name whereas actual field name is passed
$sidx = C_Utility::white_list($sidx, $col_dbnames, "Invalid field name");

// sorting order - at first time sortorder. The default is 'asc'
$sord = C_Utility::white_list($sord, ["asc","desc"], "Invalid ORDER BY direction");


// ********************* Prepare SQL statement *********************
// prepare sql where statement. Column names, operators and groupOp are whitelisted
// and every search value is bound as a parameter (see C_SearchFilter).
$searchOn = (isset($_REQUEST['_search']) && $_REQUEST['_search'] =='true')?true:false;
$search = array('sql' => '', 'params' => array());
if($searchOn) {
    $search = C_SearchFilter::build($_REQUEST, $col_dbnames, function($colName) use ($db, $rs){
        return $db->field_metatype($rs, $db->field_index($rs, $colName));
    });
}
$sqlWhere = $search['sql'];
$searchOn = ($sqlWhere != '');

/* (MySql only) Allow reserved keywords (hyphen) in column names for sorting */
if(PHPGRID_DB_TYPE == 'mysql' || PHPGRID_DB_TYPE == 'mysql_dsn'){
    if(strpos($sidx, '-') !== false){  // only escape column name contains '-'
        $sidx = '`'.strtoupper($sidx).'`';
    }
}
// set ORDER BY. Don't use if user hasn't select a sort
$secondary_sort = ($sidx2 == '') ? '' : ",$sidx2";
$sqlOrderBy = (!$sidx) ? "" : " ORDER BY $sidx $secondary_sort $sord";


// ********* prepare the final query ***********************
// Store GROUP BY Position (james matrek)
$groupBy_Position = strpos(strtoupper($grid_sql), "GROUP BY");

if($sql_filter != '' && $searchOn){
    $SQL_unsorted = $grid_sql .' WHERE '. $sql_filter .' AND ('. $sqlWhere .')';
}elseif($sql_filter != '' && !$searchOn){
    $SQL_unsorted = $grid_sql .' WHERE '. $sql_filter;
}elseif($sql_filter == '' && $searchOn){
    $SQL_unsorted = $grid_sql .' WHERE '. $sqlWhere;
}else{ // if($sql_filter == '' && !$searchOn){
    $SQL_unsorted = $grid_sql;
}
$SQL = $SQL_unsorted . $sqlOrderBy;


// ******************* execute query finally *****************
// calculate the starting position of the rows 
$start = $limit*$page - $limit;
// if for some reasons start position is negative set it to 0. typical case is that the user type 0 for the requested page 
if($start <0) $start = 0; 
if(PHPGRID_DB_TYPE != 'pdo_odbc_db2'){ $db->db->SetFetchMode(ADODB_FETCH_BOTH); }
$result = $db->select_limit($SQL, $limit, $start, $search['params']);

//print_r($result);
/*
echo 'sql_filter: '. $sql_filter;
echo 'searchOn: '. $searchOn;
echo $SQL . '; LIMIT: '. $limit .'; START: '. $start;
echo '<pre>';
print_r($result);
echo '<pre>';
*/


// ************************ pagination ************************
// total record count used for pagination - counted by the database, not by fetching every row
$count = 1000000000;
if($has_pagecount){
    $count_rs  = $db->db_query(C_Utility::count_sql($SQL_unsorted), $search['params']);
    $count_row = $db->fetch_array_assoc($count_rs);   // takes its argument by reference
    $count = is_array($count_row) ? (int)reset($count_row) : 0;   // first column: key case varies by database
}
// calculate the total pages for the query 
if( $count > 0 && $limit > 0) { 
    $total_pages = ceil($count/$limit); 
}else{ 
    $total_pages = 0; 
} 
// if for some reasons the requested page is greater than the total set the requested page to total page 
if ($page > $total_pages) $page=$total_pages;


// *************** return results in XML or JSON ************
// $data_type = $dg->get_jq_datatype();
switch($data_type)
{
    // render xml. Must set appropriate header information. 
    case "xml":
        $data = "<?xml version='1.0' encoding='utf-8'?>";
        $data .=  "<rows>";
        $data .= "<page>".$page."</page>";
        $data .= "<total>".$total_pages."</total>";
        $data .= "<records>".$count."</records>"; 
        while($row = $db->fetch_array_assoc($result)) {
            $data .= "<row id='". C_Utility::gen_rowids($row, $sql_key) ."'>";
            for($i = 0; $i < $db->num_fields($result); $i++) {
                $col_name = $db->field_name($result, $i);
                    $data .= "<cell>". $row[$col_name] ."</cell>";
            }
            $data .= "</row>";
        }
        $data .= "</rows>";

        header("Content-type: text/xml;charset=utf-8");
        echo $data;
        break;

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
                $metaType = $db->field_metatype($result, $j);
                if(PHPGRID_DB_TYPE == 'odbc_mssql_native' || PHPGRID_DB_TYPE == 'odbc_mssql'){
                    $data[] = mb_convert_encoding($row[$col_name], 'UTF-8', mb_detect_encoding($row[$col_name]));
                } 
                elseif ($metaType == 'B') {
                    $data[] = ($row[$col_name] != null) ? base64_encode($row[$col_name]): "";
                }
                else {
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

// free resource
$db = null;