<?php
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', TRUE);

include_once(__DIR__ ."/../includes/phpGrid/conf.php");

use phpCtrl\C_Database;
use phpCtrl\C_DataGrid;


$shareKey   = $_GET['sk'] ?? null;
$grid       = 'About nothing...';
$queryName  = 'View';

if ($shareKey) {

    $db = new C_Database(APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

    $result = $db->db_query("SELECT q.*, q.name as query_name, `dbs`.`server`, `dbs`.`name`, `dbs`.`username`, `dbs`.`password`, `dbs`.`port`, `dbs`.`encoding` FROM `query` q 
                        INNER JOIN `dbs` ON q.`db` = CONCAT(`dbs`.`name`, '@', `dbs`.`server`, ':', `dbs`.`port`)
                        WHERE q.`share_key` = ? ", 
                        [$shareKey]);

    if(!$result->EOF){
        
        $row = $result->fields;
        $result->MoveNext();

        $query          = $row['query'];
        $queryName      = $row['query_name'];
        $dbStr          = $row['name'] .'@'. $row['server'] .':'. $row['port'];

        $dg = new C_DataGrid($query, [], '',
                                    array("hostname"=>$row['server'],
                                    "username"=>$row['username'],
                                    "password"=>$row['password'],
                                    "dbname"=>$row['name'], 
                                    "dbtype"=>'mysql', 
                                    "dbcharset"=>$row['encoding']));


        $theTable = $dg->get_sql_table();

        $rs = $db->db_query("SELECT * FROM column_prop WHERE db_table = ? AND db = ? ", [$theTable, $dbStr]);

        // ***************************** COLUMN PROPS *****************************
        while($row = $db->fetch_array_assoc($rs)) {

            $col_name 	= $row['col_name'];
            $hidden		= $row['hidden'];
            $readonly	= $row['readonly'];
            $required	= $row['required'];
            $wysiwyg	= $row['wysiwyg'];
            $metatype	= $row['metatype'];
            $title 		= $row['title'];
            $align 		= $row['align'];
            $width 		= $row['width'];
            $linkto 	= $row['linkto'];
            $edittype	= $row['edittype'];
            $cond_format= $row['conditional_format'];
            $customrule	= $row['customrule'];
            $default_value	= $row['default_value'];
            $format		= $row['format'];
            $hyperlink	= $row['hyperlink'];
            $metatype	= $row['metatype'];
    
            if ($hidden) $dg->set_col_hidden($col_name);
            if ($readonly) $dg->set_col_readonly($col_name);
            if ($required) $dg->set_col_required($col_name);
            if ($wysiwyg) $dg->set_col_wysiwyg($col_name);
            if ($title) $dg->set_col_title($col_name, $title);
            if ($align) $dg->set_col_align($col_name, $align);
            if ($width) $dg->set_col_width($col_name, $width);
    
            // get primary key of the linkto tables
            if ($linkto) {
                $linktoTable = explode(".", $linkto)[0];	
                
                // get linkto primary key
                $conn = new \mysqli(APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);
                $stmt = $conn->prepare("SELECT dbs.id, dbs.tables  FROM `dbs` 
                                WHERE CONCAT(`dbs`.`name`, '@', `dbs`.`server`, ':', `dbs`.`port`) = ? LIMIT 1");
                $stmt->bind_param("s", $dbStr);
                $stmt->execute();

                $row = $stmt->get_result()->fetch_array(MYSQLI_ASSOC);

                $stmt->close();
                $conn->close();

                $tables = json_decode($row['tables'], true);
                $columns = $tables[$linktoTable]['properties'][0]['columns'];

                foreach($columns as $col) {
                    if(isset($col['pk']))
                        $pks[] = $col['name'];
                }

                // Use a single PK for now.
                // TODO - what to do with multiple PKs?
                $linktoPk = $pks[0];
        

                $dg->set_col_edittype($col_name, 'select', "SELECT $linktoPk, CONCAT_WS(', ', $linkto) FROM $linktoTable");	
            }
            if ($default_value) $dg->set_col_default($col_name, $default_value);
            
            if ($edittype) {
    
                $params = json_decode($edittype);
    
                switch($params->ctrl_type) {
                    case 'checkbox':
                        $dg->set_col_edittype($col_name, 'checkbox', "1:0");
                        break;
                    case 'select':
                        $dg->set_col_edittype($col_name, 'select', $params['keyvalue_pair']);
                        break;
                }
            }
            
            if ($format) {
                
                $params = json_decode($format, true);
    
                if ($params) {
    
                    $type = $params['format'];
                    $formatoptions = $params['formatoptions'] ?? null;
    
                    switch($type) {
                        case 'currency':
                            $dg->set_col_currency($col_name, 
                                                prefix: $formatoptions['prefix'] ?? null, 
                                                suffix: $formatoptions['suffix'] ?? null,
                                                thousandsSeparator: $formatoptions['thousandsSeparator'] ?? null,
                                                decimalSeparator: $formatoptions['decimalSeparator'] ?? null,
                                                decimalPlaces: $formatoptions['decimalPlaces'] ?? null,
                                                defaultValue: $formatoptions['defaultValue'] ?? null);
                            break;
                        case 'email':
                            $dg->set_col_format($col_name, 'email');
                            break;
                        case 'hyperlink':
                            if ($formatoptions) {
                                $dg->set_col_dynalink($col_name, 
                                                baseLinkUrl: $formatoptions['baseLinkUrl'] ?? null,
                                                dynaParam: array($formatoptions['dynaParam']) ?? null,
                                                staticParam: $formatoptions['staticParam'] ?? null,
                                                target: $formatoptions['target'] ?? null,
                                                prefix: $formatoptions['prefix'] ?? null);
                            } else {
                                $dg->set_col_link($col_name, '_new');
                            }
                            break;
                    }
    
                }
            }
    
            if ($cond_format) {
    
                $params = json_decode($cond_format, true);
    
                $dg->set_conditional_format($col_name, $params['type'], $params['formatoptions']);
            }			
        }


        
        
        // ***************************** TABLE PROPS *****************************
        // loop table properties and add setters to the datagrid
        $conn = new \mysqli(APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

        // Check connection
        if ($conn->connect_error) {
            error_log("Connection failed: " . $conn->connect_error);
        } else {
            $stmt = $conn->prepare("SELECT * FROM table_prop WHERE db_table = ? AND db = ? ");

            if ($stmt === false) {
                error_log("Prepare failed: " . $conn->error);
            } else {
                $stmt->bind_param("ss", $theTable, $dbStr);
                $stmt->execute();

                $result = $stmt->get_result(); // get the mysqli result
                while($row = $result->fetch_array(MYSQLI_ASSOC)) {

                    $locale 		= $row['locale'];
                    $edit 			= json_decode($row['edit']);
                    $parentChild 	= json_decode($row['parent_child']);

                    $dg->set_query_filter($row['query_filter']);
                    $dg->set_caption($row['caption']);
                    $dg->set_pagesize($row['page_size']);

                    switch($row['search_type']) {
                        case 'inline':
                            $dg->enable_search(true);
                        break;
                        case 'advanced':
                            $dg->enable_advanced_search(true);
                        break;
                        case 'global':
                            $dg->enable_global_search(true);
                        break;
                    }

                    switch($row['export_type']) {
                        case 'PDF':
                            $dg->enable_export('PDF');
                        break;
                        case 'EXCEL':
                            $dg->enable_export('EXCEL');
                        break;
                        case 'CSV':
                            $dg->enable_export('CSV');
                        break;
                        case 'HTML':
                            $dg->enable_export('HTML');
                        break;
                    }

                    if ($locale) {
                        $dg->set_locale($locale);
                    }

                    if ($edit) {
                        switch($edit->type) {
                            case 'FORM':
                                $dg->enable_edit('FORM', $edit->permission);
                            break;
                            case 'INLINE':
                                $dg->enable_edit('INLINE', $edit->permission);
                            break;
                        }
                    }

                    if ($parentChild && $parentChild->type != 'None') {

                        $child = $parentChild->child;
                        $childTbl = explode('.', $child)[0];
                        $childKey = explode('.', $child)[1];
                        $squery = "SELECT * FROM $childTbl";


                        $sdg = new C_DataGrid($squery, [], '', ['hostname' => $dg->db->hostName,
                                                                'username' => $dg->db->userName,
                                                                'password' => $dg->db->password,
                                                                'dbname' => $dg->db->databaseName,
                                                                'dbtype' => $dg->db->dbType,
                                                                'dbcharset' => $dg->db->charset]);

                        switch($parentChild->type) {
                            case 'masterDetail':
                                $dg->set_masterdetail($sdg, $childKey);
                            break;
                            case 'subgrid':
                                $dg->set_subgrid($sdg, $childKey);
                            break;
                        }
                    }
                }

                $stmt->close();
            }
            $conn->close();
        }




        
        $dg->display(false);
        $grid = $dg->get_display(true);
    }
}
?>
<!doctype html>
<html>
    <head>
        <title>Querro - <?= $queryName?></title>
        <style>
            body {
                overflow-x:hidden;
            }
        </style>
    </head>
<body>
<?php
echo $grid;
?>
</body>        
</html>