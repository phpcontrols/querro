<?php
use phpCtrl\C_DataBase as C_DataBase;
use phpCtrl\C_DataGrid as C_DataGrid;
use phpCtrl\C_DataArray as C_DataArray;
use phpCtrl\C_Utility as C_Utility;
use phpCtrl\C_SearchFilter as C_SearchFilter;
use phpCtrl\C_SessionMaker as C_SessionMaker;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


require_once("phpGrid.php");
C_Utility::handle_endpoint_errors();   // clean PHPGRID_ERROR responses, no stack traces

$session = C_SessionMaker::getSession(FRAMEWORK);

$gridName = isset($_GET['gn']) ? $_GET['gn'] : C_Utility::fail('PHPGRID_ERROR: URL parameter "gn" is not defined.');

$grid_sql	= $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql');
$sql_key	= $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_key');
$sql_fkey	= $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_fkey');
$sql_table	= $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_table');  
$sql_filter	= $session->get(GRID_SESSION_KEY.'_'.$gridName.'_sql_filter');       
$db_connection = unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_db_connection'));
// Export must be enabled for the grid (enable_export); the request may then pick the
// format, e.g. a custom Excel/PDF/CSV picker (see examples/grid_export.php).
$enabled_export_type = $session->get(GRID_SESSION_KEY.'_'.$gridName.'_export_type');
$export_type = ($enabled_export_type !== null && isset($_GET['export_type'])) ? $_GET['export_type'] : $enabled_export_type;
$col_titles	= unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_col_titles'));
$col_hiddens= array_keys(unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_col_hiddens')));	// extract the keys only from multiple dimension       
$col_edittypes	= unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_col_edittypes'));
$pdf_logo   = unserialize($session->get(GRID_SESSION_KEY.'_'.$gridName.'_pdf_logo'));

if(!is_array($grid_sql)){
// establish db connection
    $cn = $db_connection;
    if(empty($cn)){
        $db = new C_DataBase(PHPGRID_DB_HOSTNAME, PHPGRID_DB_USERNAME, PHPGRID_DB_PASSWORD, PHPGRID_DB_NAME, PHPGRID_DB_TYPE, PHPGRID_DB_CHARSET);
    }
    else {
        $db = new C_DataBase($cn["hostname"],$cn["username"],$cn["password"],$cn["dbname"],$cn["dbtype"],$cn["dbcharset"]);
    }
}else{
    $db = new C_DataArray($grid_sql);
}


// die if export wasn't enabled
if($export_type==null){
    C_Utility::fail('PHPGRID_ERROR: Cannot export the grid. Please use enable_export() method to enable this feature.');
}



// --------------------- 4/20/2015 Richard ----------------
// if it is a masterdetail/subgrid, obtain the value of the foreign key to used later for filtering
$src = isset($_GET['src'])?$_GET['src']:'';
if($src=='md' || $src=='sg'){
    $fkey = $sql_fkey;//$_GET['fkey'];
    $fkey_value = $_GET['fkey_value'];

    if(C_Utility::is_debug()){
        echo 'fkey: ' ."\n";
        print_r($fkey);
        echo  "\n";
        echo 'fkey_value: ' ."\n";
        print_r($fkey_value);
    }
}
// create filter if exporting from a detail grid. Must check if the key is actual a database field
$sql_filter_md = '';
if($src=='md' || $src=='sg'){
    $sql_filter_md = $db->quote_field($grid_sql, $fkey, $fkey_value);     // 1. master foreign key filter
}
// ------------ end of master detail filter ----------------


// get original column names and add 1 as default sortname
$rs          = $db->select_limit($grid_sql, 1, 1);
$col_dbnames = [];
if (!is_array($grid_sql)) {
    $col_dbnames = $db->get_col_dbnames($rs);
}
array_push($col_dbnames, 1);


$sord = (isset($_GET['sord']))?$_GET['sord']:'asc'; 
$sidx = (isset($_GET['sidx']))?$_GET['sidx']:1; 
if(!$sidx) $sidx =1; 

// The default sortname is 1 unless user click column name to sort whereaas the actual field name is provided
$sidx = C_Utility::white_list($sidx, $col_dbnames, "Invalid field name");
// sorting order - at first time sortorder. The default is 'asc'
$sord = C_Utility::white_list($sord, ["asc","desc"], "Invalid ORDER BY direction");




// Column names, operators and groupOp are whitelisted and every search value is
// bound as a parameter (see C_SearchFilter). Array-sourced grids have no SQL to filter.
$searchOn = (isset($_REQUEST['_search']) && $_REQUEST['_search'] =='true')?true:false;
$search = array('sql' => '', 'params' => array());
if($searchOn && !is_array($grid_sql)) {
    $search = C_SearchFilter::build($_REQUEST, $col_dbnames, function($colName) use ($db, $rs){
        return $db->field_metatype($rs, $db->field_index($rs, $colName));
    });
}
$sqlWhere = $search['sql'];
$searchOn = ($sqlWhere != '');


// set ORDER BY. Don't use if user hasn't select a sort
$sqlOrderBy = (!$sidx) ? "" : " ORDER BY $sidx $sord";

      
// ********* prepare the final query ***********************
$SQL = '';
if(!is_array($grid_sql)){
    if($sql_filter != '' && $searchOn){
        $SQL = $grid_sql .' WHERE '
                            .(($sql_filter_md!='') ? $sql_filter_md.' AND ' : '')
                            .$sql_filter .' AND (' .$sqlWhere .')' 
                            .$sqlOrderBy;
    }elseif($sql_filter != '' && !$searchOn){
        $SQL = $grid_sql .' WHERE '
                            .(($sql_filter_md!='') ? $sql_filter_md.' AND ' : '') 
                            .$sql_filter 
                            .$sqlOrderBy;
    }elseif($sql_filter == '' && $searchOn){
        $SQL = $grid_sql .' WHERE '
                            .(($sql_filter_md!='') ? $sql_filter_md.' AND ' : '')
                            .$sqlWhere 
                            .$sqlOrderBy;
    }else{  // if($sql_filter == '' && !$searchOn){
        $SQL = $grid_sql .  (($sql_filter_md!='') ? ' WHERE '.$sql_filter_md : '')
                            .$sqlOrderBy;
    }
}
//echo 'sql_filter: '. $sql_filter;
//echo 'searchOn: '. $searchOn;
//echo $SQL; 

$result     = $db->db_query($SQL, $search['params']);
$row_count  = $db->num_rows($result);
$col_count  = $db->num_fields($result);

// $col_titles = array();
// $col_titles = $col_titles();
$j = 0;

switch(strtoupper($export_type)){
    case 'HTML': 
        header("Content-type: text/html");
        header("Content-disposition:  attachment; filename=Grid_". $gridName ."_".date("Y-m-d").".htm");
        header ('Expires: 0');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');

        echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'. "\n";
        
        echo '<table border="1" cellspacing="0" cellpadding="2">' ."\n";  
        echo '<thead>' ."\n";      
            echo '<tr style="background-color:black;color:white">';
            for($j = 0; $j < $db->num_fields($rs); $j++) {
                $col_name = $db->field_name($rs, $j);                             
                if(!in_array($col_name, $col_hiddens)){
                    if(isset($col_titles[$col_name])){
                        echo '<th>'. $col_titles[$col_name] .'</th>';                
                    }else{
                        echo '<th>'. $col_name .'</th>';                                
                    }
                    
                }        
            }
            echo '</tr>' ."\n";            
        echo '</thead>' ."\n";
            
        echo '<tbody>' ."\n";
        if(!is_array($grid_sql)){
            while($row = $db->fetch_array_assoc($result)) {
                echo '<tr>';
                for($j = 0; $j < $db->num_fields($result); $j++) {
                    $col_name = $db->field_name($result, $j);
                    if(!in_array($col_name, $col_hiddens)){                // if not hidden
                        echo '<td>'. text_by_edittype($col_edittypes, $col_name, $row[$col_name]) .'&nbsp;</td>';
                    }
                }
                echo '</tr>' ."\n";
            }
        }else{
            foreach($result as $row){
                echo '<tr>';
                for($j = 0; $j < $db->num_fields($result); $j++) {
                    $col_name = $db->field_name($row, $j);
                    // echo 'col_name: '. $col_name ."\n";
                    if(!in_array($col_name, $col_hiddens)){
                        echo '<td>'. text_by_edittype($col_edittypes, $col_name, $row[$col_name]) .'&nbsp;</td>';
                        // echo '<td>'. $row[$col_name] .'&nbsp;</td>';
                    }
                }
                echo '</tr>' ."\n";
            }
        }
        echo '</tbody>' ."\n";
        echo '</table>' ."\n";      
        
        echo '</body></html>';      
		
    break;

    case 'EXCEL':
        // Just convert the HTML output to Excel and save the day. 
        $html_table = '';
        $html_table .= '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'. "\n";
        
        $html_table .= '<table border="1" cellspacing="0" cellpadding="2">' ."\n";  
        $html_table .= '<thead>' ."\n";      
            $html_table .= '<tr style="background-color:black;color:white">';
            for($j = 0; $j < $db->num_fields($rs); $j++) {
                $col_name = $db->field_name($rs, $j);                             
                if(!in_array($col_name, $col_hiddens)){
                    if(isset($col_titles[$col_name])){
                        $html_table .= '<th>'. $col_titles[$col_name] .'</th>';                
                    }else{
                        $html_table .= '<th>'. $col_name .'</th>';                                
                    }
                    
                }        
            }
            $html_table .= '</tr>' ."\n";            
        $html_table .= '</thead>' ."\n";
            
        $html_table .= '<tbody>' ."\n";
        if(!is_array($grid_sql)){
            while($row = $db->fetch_array_assoc($result)) {
                $html_table .= '<tr>';
                for($j = 0; $j < $db->num_fields($result); $j++) {
                    $col_name = $db->field_name($result, $j);
                    if(!in_array($col_name, $col_hiddens)){                // if not hidden
                        $html_table .= '<td>'. text_by_edittype($col_edittypes, $col_name, $row[$col_name]) .'</td>';
                    }
                }
                $html_table .= '</tr>' ."\n";
            }
        }else{
            foreach($result as $row){
                $html_table .= '<tr>';
                for($j = 0; $j < $db->num_fields($result); $j++) {
                    $col_name = $db->field_name($row, $j);
                    // echo 'col_name: '. $col_name ."\n";
                    if(!in_array($col_name, $col_hiddens)){
                        $html_table .= '<td>'. text_by_edittype($col_edittypes, $col_name, $row[$col_name]) .'</td>';
                    }
                }
                $html_table .= '</tr>' ."\n";
            }
        }
        $html_table .= '</tbody>' ."\n";
        $html_table .= '</table>' ."\n";      
        
        $html_table .= '</body></html>'; 


        $filename = "Grid_". $gridName ."_".date("Y-m-d") .".xlsx";

        // save $table inside temporary file that will be deleted later
        $tmpfile = tempnam(sys_get_temp_dir(), 'html');
        file_put_contents($tmpfile, $html_table);

        $spreadsheet = new Spreadsheet();
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
        @$reader->loadIntoExisting($tmpfile, $spreadsheet); // @ to surpress any HTML warnings
        $spreadsheet->getActiveSheet()->setTitle($gridName);

        unlink($tmpfile); // delete temporary file

        header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=". $filename);  //File name extension was wrong
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);

        // Creates a writer to output the $spreadsheet content
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');

        exit;

    break;



    case 'EXCELXML':
        header("Content-type: text/xml");
        header("Content-disposition:  attachment; filename=Grid_". $gridName ."_".date("Y-m-d").".xml");
        header ('Expires: 0');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');
        // Excel XML
        // ExpandedColumnCount and ExpandedRowCount must be greater than the actual # of cols and rows.
        echo '<?xml version="1.0"?>
            <?mso-application progid="Excel.Sheet"?>
            <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
             xmlns:o="urn:schemas-microsoft-com:office:office"
             xmlns:x="urn:schemas-microsoft-com:office:excel"
             xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
             xmlns:html="http://www.w3.org/TR/REC-html40">
             <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
              <Author>phpGrid.com</Author>
              <Created></Created>
              <LastSaved></LastSaved>
              <Version></Version>
             </DocumentProperties>
             <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
                <WindowHeight>768</WindowHeight>
                <WindowWidth>1024</WindowWidth>
                <WindowTopX>0</WindowTopX>
                <WindowTopY>0</WindowTopY>
                <ProtectStructure>False</ProtectStructure>
                <ProtectWindows>False</ProtectWindows>
            </ExcelWorkbook>
            <Styles>
                <Style ss:ID="Default" ss:Name="Normal">
                    <Alignment ss:Vertical="Bottom" />
                    <Borders/>
                    <Font ss:FontName="Arial" ss:Size="8" />
                    <Interior/>
                    <NumberFormat />
                    <Protection />
                </Style>
                <Style ss:ID="sHyperlink" ss:Name="Hyperlink">
                    <Font ss:Color="#0000FF" ss:Underline="Single" />
                </Style>
                <Style ss:ID="sDate">
                    <NumberFormat ss:Format="Short Date"/>
                </Style>
                <Style ss:ID="sNumber">
                    <NumberFormat/>
                </Style>                
                <Style ss:ID="sHeader">
                    <Font ss:Family="Arial" ss:Bold="1" />
                </Style>
                <Style ss:ID="sDecimal">
                    <NumberFormat ss:Format="Fixed"/>
                </Style>
            </Styles>';
        echo '<Worksheet ss:Name="Sheet1">
            <Table ss:ExpandedColumnCount="'. $col_count .'" 
              ss:ExpandedRowCount="'. ($row_count+1) .'" x:FullColumns="1"
              x:FullRows="1">';

        // grid header
        echo '<Row>';
        for($j = 0; $j < $col_count; $j++) {
            $col_name = $db->field_name($rs, $j);
            if(!in_array($col_name, $col_hiddens)){
                if(isset($col_titles[$col_name])){
                    echo '<Cell ss:StyleID="sHeader"><Data ss:Type="String">'. xml_text($col_titles[$col_name]) .'</Data></Cell>';
                }else{
                    echo '<Cell ss:StyleID="sHeader"><Data ss:Type="String">'. xml_text($col_name) .'</Data></Cell>';
                }

            }
        }
        echo '</Row>' ."\n";

        // grid body
        $fm_type = 'C'; // field meta type
        if(!is_array($grid_sql)){
            while($row = $db->fetch_array_assoc($result)) {
                echo '<Row>';
                for($j = 0; $j < $col_count; $j++) {
                    $col_name = $db->field_name($result, $j);
                    if(!in_array($col_name, $col_hiddens)){
                        // $fm_type   = $db->field_metatype($result, $db->field_index($result, $col_name));
                        $display_text = text_by_edittype($col_edittypes, $col_name, $row[$col_name]);
                        echo '<Cell ss:StyleID="sDate"><Data ss:Type="String">'. xml_text($display_text) .'</Data></Cell>';
                        /*
                        switch($fm_type){
                            case 'D':
                                echo '<Cell ss:StyleID="sDate"><Data ss:Type="String">'. xml_text($display_text) .'</Data></Cell>';
                                break;
                            case 'I':
                            case 'R':
                                echo '<Cell ss:StyleID="sNumber"><Data ss:Type="Number">'. $row[$col_name] .'</Data></Cell>';
                                break;
                            case 'N':
                                echo '<Cell ss:StyleID="sDecimal"><Data ss:Type="Number">'. $row[$col_name] .'</Data></Cell>';
                                break;
                            default:
                                echo '<Cell><Data ss:Type="String">'. xml_text($display_text) .'</Data></Cell>';
                        }
                        */
                    }
                }
                echo '</Row>' ."\n";
            }
        }else{
            foreach($result as $row) {
                echo '<Row>';
                for($j = 0; $j < $col_count; $j++) {
                    $col_name = $db->field_name($row, $j);
                    if(!in_array($col_name, $col_hiddens)){
                        // $fm_type   = $db->field_metatype($result, $db->field_index($result, $col_name));
                        $display_text = text_by_edittype($col_edittypes, $col_name, $row[$col_name]);
                        echo '<Cell ss:StyleID="sDate"><Data ss:Type="String">'. xml_text($display_text) .'</Data></Cell>';
                        /*
                        switch($fm_type){
                            case 'D':
                                echo '<Cell ss:StyleID="sDate"><Data ss:Type="String">'. xml_text($row[$col_name]) .'</Data></Cell>';
                                break;
                            case 'I':
                            case 'R':
                                echo '<Cell ss:StyleID="sNumber"><Data ss:Type="Number">'. $row[$col_name] .'</Data></Cell>';
                                break;
                            case 'N':
                                echo '<Cell ss:StyleID="sDecimal"><Data ss:Type="Number">'. $row[$col_name] .'</Data></Cell>';
                                break;
                            default:
                                echo '<Cell><Data ss:Type="String">'. xml_text($row[$col_name]) .'</Data></Cell>';
                        }
                        */
                    }
                }
                echo '</Row>' ."\n";
            }
        }


        echo '</Table>';
        echo '<WorksheetOptions 
              xmlns="urn:schemas-microsoft-com:office:excel">
                <Print>
                    <ValidPrinterInfo />
                    <HorizontalResolution>800</HorizontalResolution>
                    <VerticalResolution>0</VerticalResolution>
                </Print>
                <Selected />
                <Panes>
                    <Pane>
                        <Number>3</Number>
                        <ActiveRow>1</ActiveRow>
                    </Pane>
                </Panes>
                <ProtectObjects>False</ProtectObjects>
                <ProtectScenarios>False</ProtectScenarios>
            </WorksheetOptions>
        </Worksheet>
        </Workbook>';    
    break;

    default:
        C_Utility::fail('PHPGRID_ERROR: Unsupported export type.');
}


// e.g. str_kvpair: 1:San Francisco;2:Boston;3:NYC;test:Paris;5:Tokyo;6:Sydney;7:London
// convert to array e.g. $arr_kvpair[1] = 'San ...'
function array_kvpair($str_kvpair){
    $tmp = explode(';', $str_kvpair);
    $arr_kvpair = array();
    foreach($tmp as $key => $value){
        $k = substr($value, 0, strpos($value, ':'));
        $v = substr($value, strpos($value, ':')+1);
        $arr_kvpair[$k] = $v;
    }

    return $arr_kvpair;
}

// Escape a value for a SpreadsheetML <Data> cell. NULL (an empty database cell) becomes ''.
function xml_text($value){
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

// For now, the only edit type we need to deal with here is "select" 
function text_by_edittype(&$col_edtypes, $col, $select_key){
    $display_text = '';

    if(isset($col_edtypes[$col])){
        if($col_edtypes[$col]['type']=='select'){    // if edit type is select
            $arr_kvpair = array_kvpair($col_edtypes[$col]['value']);
            // print_r($arr_kvpair);
            if($select_key !== null && isset($arr_kvpair[$select_key])){   // null offsets are deprecated in PHP 8.5
                $display_text = $arr_kvpair[$select_key];
            }else{
                $display_text = '';
            }
        }else{
            $display_text = $select_key;
        }
    }else{
        $display_text = $select_key;    // default
    }

    return $display_text;
}
          
// free resource       
$db = null;