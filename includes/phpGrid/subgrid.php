<?php
use phpCtrl\C_DataBase as C_DataBase;
use phpCtrl\C_DataGrid as C_DataGrid;
use phpCtrl\C_Utility as C_Utility;
use phpCtrl\C_SessionMaker as C_SessionMaker;

require_once("phpGrid.php");
C_Utility::handle_endpoint_errors();   // clean PHPGRID_ERROR responses, no stack traces

// the request url should looks sth like this: 
// subgrid.php?id=2&_search=false&nd=1277597709752&rows=20&page=1&sidx=lineid&sord=asc
$session = C_SessionMaker::getSession(FRAMEWORK);

// s_* indicates subgrid variables
$gridName    = isset($_GET['gn'])  ? $_GET['gn'] :  C_Utility::fail('PHPGRID_ERROR: URL parameter "gn" is not defined.');
$s_gridName = isset($_GET['sgn']) ? $_GET['sgn'] : C_Utility::fail('PHPGRID_ERROR: URL parameter "sgn" is not defined.');
$data_type  = isset($_GET['dt']) ? $_GET['dt']:'json';

$grid_sql    = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql');
$sql_key    = unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_key'));
$sql_fkey    = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_fkey');
$sql_table    = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_table');  
$sql_filter    = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_filter');       
$db_connection = unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_db_connection'));
// $is_debug        = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_is_debug');

$s_grid_sql        = $session->get(GRID_SESSION_KEY.'_'.$s_gridName.'_sql');
$s_sql_key        = unserialize($session->get(GRID_SESSION_KEY.'_'.$s_gridName.'_sql_key'));
$s_sql_fkey        = $session->get(GRID_SESSION_KEY.'_'.$s_gridName.'_sql_fkey');
$s_sql_table    = $session->get(GRID_SESSION_KEY.'_'.$s_gridName.'_sql_table');  
$s_sql_filter    = $session->get(GRID_SESSION_KEY.'_'.$s_gridName.'_sql_filter');       
$s_db_connection= unserialize($session->get(GRID_SESSION_KEY.'_'.$s_gridName.'_db_connection'));

//establish db connection
$cn = $db_connection;
if(empty($cn)){
    $db = new C_DataBase(PHPGRID_DB_HOSTNAME, PHPGRID_DB_USERNAME, PHPGRID_DB_PASSWORD, PHPGRID_DB_NAME, PHPGRID_DB_TYPE, PHPGRID_DB_CHARSET);
}
else {       
    $db = new C_DataBase($cn["hostname"],$cn["username"],$cn["password"],$cn["dbname"],$cn["dbtype"],$cn["dbcharset"]);        
}


$fk     = $s_sql_fkey;
$pk     = $sql_key; // Array: primary key
$pk_val = (isset($_GET[JQGRID_ROWID_KEY])) ? explode(',', urldecode($_GET[JQGRID_ROWID_KEY])) : null; // e.g. "10104---141,10103---14111", convert to Array
$m_fkey = (isset($_GET['m_fkey'])) ? urldecode($_GET['m_fkey']) : -1;


// ************************************************************************************************************
// ***********  query to obtain the foreign key value from master grid. No composite PK support ***************
// ************************************************************************************************************
$rs         = $db->select_limit($grid_sql, 1, 1);
$pk_val_new = $db->quote_fields($rs, $sql_key, $pk_val);
$sqlWhere   = ' WHERE ('. implode(',', $sql_key) .') IN ('. implode(',', $pk_val_new) .') ';
$all_columns = $db->get_columns($rs);
// run the m_key through the whitelist or die to avoid SQL injection
if (in_array($m_fkey, $all_columns)) {
    $sqlFkey    = 'SELECT '. $m_fkey .' FROM '. $sql_table .$sqlWhere;
} else {
    C_Utility::fail('PHPGRID_ERROR: Could not execute query. Error 103.');
}
$result     = $db->query_then_fetch_array_first($sqlFkey);
$fk_val     = (!empty($result)) ? $result[str_replace('`', '', $m_fkey)] : null;


// ************************************************************************************************************
// ****************  query to obtain the detail grid data using $fk_val obtained previously *******************
// ************************************************************************************************************
// get original column names and add 1 as default sortname
array_push($all_columns, 1);

$page   = (isset($_GET['page'])) ? $_GET['page'] : 1;
$limit  = (isset($_GET['rows'])) ? $_GET['rows'] : 20;
$sord   = (isset($_GET['sord'])) ? $_GET['sord'] : 'asc';
$sidx   = (isset($_GET['sidx'])) ? $_GET['sidx'] : 1;
if(!$sidx) $sidx = 1;

// Get all subgrid columns used for whitelisting
$rs     = $db->select_limit($s_grid_sql, 1, 1);
$all_subgrid_columns = $db->get_columns($rs);
$all_columns = array_merge($all_columns, $all_subgrid_columns);

// The default sortname is 1 unless user click column name to sort whereaas the actual field name is provided
$sidx = C_Utility::white_list($sidx, $all_columns, "Invalid field name");
// sorting order - at first time sortorder. The default is 'asc'
$sord = C_Utility::white_list($sord, ["asc","desc"], "Invalid ORDER BY direction");


$sqlWhere   = ' HAVING '. $db->quote_field($s_grid_sql, $fk, $fk_val);
// $sqlWhere   = ' AND '. $db->quote_field($s_grid_sql, $fk, $fk_val);
// set ORDER BY. Don't use if user hasn't select a sort
$sqlOrderBy = (!$sidx) ? "" : " ORDER BY $sidx $sord";
// the actual query for the grid data
if($s_sql_filter != ''){
    $SQL = $s_grid_sql. $sqlWhere .' AND '. $s_sql_filter . $sqlOrderBy;
    // $SQL = $s_grid_sql. $s_sql_filter .' AND '. $sqlWhere . $sqlOrderBy;
}else{
    $SQL = $s_grid_sql. $sqlWhere . $sqlOrderBy;
}

// replace HAVING with WHERE when aggregate func exists to be compatible with non-msyql
if (!strrpos($SQL, ' GROUP BY ')) {
    $SQL = str_replace(' HAVING ', ' WHERE ', $SQL);
}

// ************************ pagination ************************
$rs    = $db->db_query($SQL);            
$count = $db->num_rows($rs);

// calculate the total pages for the query 
if( $count > 0 && $limit > 0) { 
    $total_pages = ceil($count/$limit); 
}else{ 
    $total_pages = 0; 
} 
 
// if for some reasons the requested page is greater than the total. set the requested page to total page 
if ($page > $total_pages) $page=$total_pages;
 
// calculate the starting position of the rows 
$start = $limit*$page - $limit;
 
// if for some reasons start position is negative set it to 0. typical case is that the user type 0 for the requested page 
if($start <0) $start = 0; 


// ******************* execute query finally *****************
$db->db->SetFetchMode(ADODB_FETCH_BOTH);
$result = $db->select_limit($SQL, $limit, $start);

// *************** return results in XML or JSON ************
// $data_type = $sdg->get_jq_datatype();
switch($data_type)
{
    // render xml. Must set appropriate header information. 
    case "xml":
        $data = "<?xml version='1.0' encoding='utf-8'?>";
        $data .=  "<rows>";
        $data .= "<page>".$page."</page>";
        $data .= "<total>".$total_pages."</total>";
        $data .= "<records>".$count."</records>"; 
        $i = 0;
        while($row = $db->fetch_array_assoc($result)) {
            $data .= "<row id='". C_Utility::gen_rowids($row, $s_sql_key) ."'>";
            for($i = 0; $i < $db->num_fields($rs); $i++) {
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
        $response = new stdClass();   // define anonymous object
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $i=0;
        $data = array();              
        while($row = $db->fetch_array_assoc($result)) {
            unset($data);
            $response->rows[$i]['id']=C_Utility::gen_rowids($row, $s_sql_key);
            for($j = 0; $j < $db->num_fields($result); $j++) {
                $col_name = $db->field_name($result, $j);                             
                    $data[] = $row[$col_name];    
            }            
            $response->rows[$i]['cell'] = $data;
            $i++;
        }        
        echo json_encode($response);  
        break;
} 
          
// free resource
$db = null;