<?php
namespace phpCtrl;

if(str_replace( '\\', '/',$_SERVER['DOCUMENT_ROOT']) == SERVER_ROOT) { define('ABS_PATH', '');}
else { define('ABS_PATH', SERVER_ROOT); }

// if(!session_id()){ session_start();}
class C_DataGrid{
    public $jq_colModel;        // 3/31/2012 Richard: it's now set to public. Users can now manipulate the colModel.
    public $before_script_end;     // holds custom javascript will be loaded BEFORE end of the script when all the DOM elementa are presented

    // grid columns
    private $sql;
    private $sql_table;
    private $sql_key;
    private $sql_fkey;          // foreign key (used by when grid is a subgrid);
    private $col_dbnames;       // original database field names
    private $col_hiddens;       // columns that are hidden
    private $col_titles;        // descriptive titles
    private $col_links;         // hyplinks (formatter:link)
    private $col_dynalinks;     // dynamic hyplinks (formmatter:showLink)
    private $col_formats;         // column format
    private $col_datatypes;     // data type used in editrule
    private $col_imgs;          // image columns
    private $col_frozen;        // column frozen
    private $col_widths;        // columns width
    private $col_aligns;        // columns alignment
    private $col_virtual;        // virtual columns
    private $col_customrule;    // custom validation/rule
    private $col_autocomplete;  // autocomplete with Chosen

    private $sql_filter;        //  set filter

    // jqgrid
    private $jq_gridName;
    private $jq_url;
    private $jq_datatype;
    private $jq_mtype;
    private $jq_colNames;
    private $jq_pagerName;
    private $jq_rowNum;
    private $jq_rowList;
    private $jq_sortname;
    private $jq_sortorder;
    private $jq_viewrecords;    // display recornds count in pager
    private $jq_multiselect;    // display checkbox for each row
    private $jq_multipage;      // keep selected rows during pagination
    private $jq_multiselectPosition;    // leflt or right
    private $jq_autowidth;      // when true the width is set to 100%
    private $jq_width;
    private $jq_height; /* START all the variables for the group*/
    private $jq_caption;
    private $jq_altRows;        // can have alternative row, or zebra, color
    private $jq_scrollOffset;   // horizontal scroll bar offset
    private $jq_rownumbers;     // row index
    private $jq_shrinkToFit;    // shrink to fit
    private $jq_loadtext;       // load promote text
    private $jq_scroll;         // use vertical scrollbar to load data. pager is disabled automately if true. height MUST NOT be 100% if true.

    private $jq_hiddengrid;     // hide grid initially
    private $jq_gridview;       // load all the data at once result in faster rendering. However, if set to true No Subgrid, treeGrid, afterInsertRow
    private $jq_autoresizeOnLoad; // Auto resize on load (requires autoresize flag set to true in colum property);

    // jquery ui
    private $jqu_resize;         // resize grid

    // others
    private $_num_rows;
    private $_num_fields;
//    private $_file_path;
    private $_ver_num;
    private $edit_mode;         // CELL, INLINE, FORM, or NONE
    private $has_tbarsearch;    // integrated toolbar
    private $auto_filters = array();      // Excel like auto filter in toolbar search
    private $advanced_search;
    private $alt_colors;        // row color class: ui-priority-secondary, ui-state-highlight, ui-state-hover
    private $theme_name;        // jQuery UI theme name
    private $locale;
    private $kb_nav;            // keyboard navigation (jqgrid 4.x)

    public $export_type;       // Export to EXCEL, HTML, PDF
    public $export_url;
    public $db;
    public $db_connection = array();
    public $data_local = array();	// used to hold values of local array data when jq_atatype is 'local'
    public $session;                      // session object
    public $autoencode;                   // preventing from XSS 

    // grid elements for display
    private $script_includeonce;     // jqgrid js include file
    private $script_body;            // jqgrid everything else
    // private $script_addEvtHandler;    // jquery add event handler script
    private $script_ude_handler;     // user defined event handler

    private $cust_col_properties;    // Array, custom user defined custom column property
    private $cust_grid_properties;   // Array, custom user defined grid property
    public  $cust_prop_jsonstr;     // JSON string, custom JSON properties. This supersets cust_grid_properties which is json_encoded eventually
    private $img_baseUrl;           // image base URL to image column. Only a SINGLE image base url is supported in a datagrid
    private $grid_methods;          // array. jqGrid methods
    private $callbackstring;        // callback string for custom event handler
    private $iconSet;
    private $guiStyle;

    // these values need to persist across different classes (eg. among master/detail, subgrids etc). Do not use SESSION here.
    static $has_autocomplete;
    static $load_ajaxComplete;

    // Desc: our constructor
    // Note: Key and table are not technically required for ready-only grid
    public function __construct($sql, $sql_key=array(), $sql_table='', $db_connection= array()){

        // convert $sql_key to array if it's already not an array
        if(!is_array($sql_key)) $sql_key = array($sql_key);

        // Use Sql Parser to get sql_key and sql_table when they are missing.
        if(PHPGRID_DB_TYPE == 'mysql' && !is_array($sql) && empty($sql_key) && $sql_table == ''){

            $parser = new \PHPSQLParser\PHPSQLParser($sql, true);

            $sql_table = isset($parser->parsed['FROM'][0]['table']) ? $parser->parsed['FROM'][0]['table'] : '';

            if($sql_table != ''){
                // Set the default database from conf if no new connection
                if(empty($db_connection)) {
                    $this->db = new C_Database(PHPGRID_DB_HOSTNAME, PHPGRID_DB_USERNAME, PHPGRID_DB_PASSWORD, PHPGRID_DB_NAME, PHPGRID_DB_TYPE,PHPGRID_DB_CHARSET, $sql_table);

                    // We now have the db object from table name. get the primary key
                    $sql_key = $this->db->db->MetaPrimaryKeys($sql_table); 
                }
                // else establish new connection and store the connection
                else {
                    $this->db = new C_Database(
                        $db_connection["hostname"],
                        $db_connection["username"],
                        $db_connection["password"],
                        $db_connection["dbname"],
                        $db_connection["dbtype"],
                        $db_connection["dbcharset"]);
                    $this->db_connection = $db_connection;

                    // get the primary key again when connecting to a different database schema 
                    $sql_key = $this->db->db->MetaPrimaryKeys($sql_table);   
                }
            }
        }

        $this->jq_gridName  = ($sql_table == '')?'list1':str_replace(".", "_", $sql_table);

        if(!is_array($sql)){
            //set the default database from conf if no new connection
            if(empty($db_connection)) {
                $this->db = new C_Database(PHPGRID_DB_HOSTNAME, PHPGRID_DB_USERNAME, PHPGRID_DB_PASSWORD, PHPGRID_DB_NAME, PHPGRID_DB_TYPE,PHPGRID_DB_CHARSET, $sql_table);
            }
            // else establish new connection and store the connection
            else {
                $this->db = new C_Database($db_connection["hostname"],
                    $db_connection["username"],
                    $db_connection["password"],
                    $db_connection["dbname"],
                    $db_connection["dbtype"],
                    $db_connection["dbcharset"],
                    $sql_table);

                $this->db_connection = $db_connection;

                // get the primary key again when connecting to a different database schema 
                $sql_key = $this->db->db->MetaPrimaryKeys($sql_table);   
            }
            $this->jq_datatype  = 'json';
            $this->jq_url       = '"'. ABS_PATH .'/data.php?dt='. $this->jq_datatype .'&gn='. $this->jq_gridName .'"';  // Notice double quote
            $this->jq_mtype     = 'GET';
        } else {
            $this->db = new C_DataArray($sql);
            $this->jq_datatype = 'local';
            $this->data_local = $sql;       
        }

        $this->sql          = $sql;
        $this->sql_key      = $sql_key;
        $this->sql_fkey     = null;
        $this->sql_table    = $sql_table;

        // grid columns properties
        $this->col_hiddens          = array();
        $this->col_titles           = array();
        $this->col_links            = array();
        $this->col_dynalinks        = array();
        $this->col_dbnames          = array();
        $this->col_formats          = array();
        $this->col_widths           = array();
        $this->col_aligns           = array();
        $this->col_frozen           = array();
        $this->col_virtual          = array();
        $this->col_customrule       = array();
        $this->col_autocomplete     = array();
        $this->col_imgs             = array();

        // jqgrid
        $this->jq_colNames  = array();
        $this->jq_colModel  = array();
        $this->jq_pagerName = '"#'. $this->jq_gridName .'_pager1"';  // Notice the double quote
        $this->jq_rowNum    = 25;
        $this->jq_rowList   = array(10, 25, 50, 100, 200, 500, "10000:All");
        $this->jq_sortname  = 1;    // sort by the 1st column
        $this->jq_sortorder = 'asc';
        $this->jq_viewrecords = true;

        $this->jq_multiselect = false;
        $this->jq_multipage = true;
        $this->jq_multiselectPosition = 'right';
        $this->jq_autowidth = false;
        $this->jq_width     = 'auto';
        $this->jq_height    = 'auto';
        $this->jq_caption   = $sql_table .'&nbsp;';
        $this->jq_altRows   = true;
        $this->jq_scrollOffset = 0;
        $this->jq_rownumbers = false;
        $this->jq_shrinkToFit  = true;
        $this->jq_scroll    = false;
        $this->jq_hiddengrid= false;
        $this->jq_loadtext  = 'Loading phpGrid...';
        $this->jq_gridview  = true;

        // jQuery UI
        $this->jqu_resize           = array('is_resizable'=>false,'min_width'=>300,'min_height'=>100);

        $this->_num_rows            = 0;            // values are updated in display()
        $this->_num_fields          = 0;            // values are updated in display()
        $this->_ver_num             = 'phpGrid(v7.6) - see yarn.lock for dependencies exact version';    // version number
        $this->alt_colors           = array('hover'=>'#F2FC9C', 'highlight'=>'', 'altrow'=>'#F5FAFF');
        $this->theme_name           = (defined('THEME'))?THEME:'bootstrap';
        $this->locale               = 'en';
        $this->kb_nav               = false;
        $this->export_type          = null;
        $this->export_url           = ABS_PATH .'/export.php?dt='. $this->jq_datatype .'&gn='.$this->jq_gridName;
        $this->edit_mode            = 'NONE';
        $this->has_tbarsearch       = false;
        $this->auto_filters         = array();
        $this->advanced_search      = false;
        $this->cust_prop_jsonstr    = '';
        $this->script_includeonce   = '';
        $this->script_body          = '';
        $this->script_ude_handler   = '';
        $this->cust_col_properties  = array();
        $this->cust_grid_properties = array();
        $this->grid_methods         = array();
        $this->iconSet              = 'fontAwesome';
        $this->guiStyle             = (strpos($this->theme_name, 'bootstrap') !== false ? $this->theme_name : 'jQueryUI'); 
        $this->autoencode           = true;
        $this->session              = C_SessionMaker::getSession(FRAMEWORK);
        $this->before_script_end    = '';
    }

    // Desc: Intializing all necessary properties
    // Must call this method before display
    public function prepare_grid(){
        $this_db            = $this->db;
        $this->_num_rows    = 0; // $this_db->num_rows($this_db->db_query($this->sql)); /* seem a safe hack by Gabriel A. Calderon - boost performance tremedously on large datasets */
        $results            = $this_db->select_limit($this->sql,1, 1);
        $this->_num_fields  = $this_db->num_fields($results);
        $this->set_colNames($results);
        $this->set_colModel($results);

        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql', $this->sql);
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql_key', serialize($this->sql_key));
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql_fkey', $this->sql_fkey);
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql_table', $this->sql_table);
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_sql_filter', $this->sql_filter);
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_db_connection', serialize($this->db_connection));
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_has_multiselect', $this->jq_multiselect);
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_export_type', $this->export_type);
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_col_titles', serialize($this->col_titles));
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_col_hiddens', serialize($this->col_hiddens)); // not used
        $this->session->set(GRID_SESSION_KEY.'_'.$this->jq_gridName.'_pagecount', $this->jq_viewrecords);
    }

    public function set_colNames($results){
        $this_db = $this->db;
        $col_names = array();
        for($i = 0; $i < $this->_num_fields; $i++) {
            $col_name = $this_db->field_name($results, $i);

            // in case $col_name is not returned
            $col_name = $col_name ?? '';

            $this->col_dbnames[] = $col_name;

            // check descriptive titles
            if(isset($this->col_titles[$col_name]))
                $col_names[] = $this->col_titles[$col_name];
            else
                $col_names[] = str_replace('_', ' ', $col_name);
            
        }

        // insert virtual columns
        if(!empty($this->col_virtual)){
            foreach($this->col_virtual as $key => $value){
                if($this->col_virtual[$key]['insert_pos']!=-1){
                    array_splice($col_names, $this->col_virtual[$key]['insert_pos'], 0, $this->col_virtual[$key]['title']);
                } else {
                    $col_names[] = $this->col_virtual[$key]['title'];
                }
            }
        }
        
        $this->jq_colNames = $col_names;

        return $col_names;
    }

    public function get_colNames(){
        return $this->jq_colNames;
    }

    public function set_colModel($results){
        $this_db = $this->db;
        $colModel = array();
        for($i=0;$i<$this->_num_fields;$i++){
            $col_name = $this_db->field_name($results, $i);
            $col_type = $this_db->field_metatype($results, $i);

            $cols = array();
            $cols['autoResizable'] = true;

            // 4/30/2021 git efac7e0a changes for web service. It breaks subgrid INLINE edit. Avoid. 
            if ($this->jq_datatype == 'local') {
                // rowid value - only work for a single PK (by design jQgrid)
                $cols['key'] = ((count($this->sql_key) == 1) && ($this->sql_key[0] == $col_name));
            }

            $cols['name'] = $col_name;
            $cols['index'] = $col_name;
            $cols['hidden'] = isset($this->col_hiddens[$col_name]);
            $cols['headerTitle'] = isset($this->col_headerTitles[$col_name]) ? $this->col_headerTitles[$col_name] : $col_name;


            // set width of coulmns
            if(isset($this->col_frozen[$col_name])){
                $cols['frozen'] = $this->col_frozen[$col_name];
            }
            // set width of coulmns
            if(isset($this->col_widths[$col_name])){
                $cols['width'] = $this->col_widths[$col_name]['width'];
            }

            // set column alignments
            if(isset($this->col_aligns[$col_name])) {
                $cols['align'] = $this->col_aligns[$col_name]['align'];
            }

            $cols['editable'] = false;

            // custom validation/rule
            if(isset($this->col_customrule[$col_name])){
                $editrules['custom'] = true;
                $editrules['custom_func'] = '###'. $this->col_customrule[$col_name]['custom_func'] .'###';
            }

            // formatter & formatoptions
            if(isset($this->col_formats[$col_name])){
                if(isset($this->col_formats[$col_name]['link'])){
                    $cols['formatter'] = 'link';
                    $formatoptions = array();
                    $formatoptions['target'] = $this->col_formats[$col_name]['link']['target'];
                    $cols['formatoptions'] = $formatoptions;
                }elseif(isset($this->col_formats[$col_name]['showlink'])){
                    $cols['formatter'] = 'showlink';
                    $formatoptions = array();
                    $formatoptions['baseLinkUrl']   = $this->col_formats[$col_name]['showlink']['baseLinkUrl'];
                    $formatoptions['showAction']    = $this->col_formats[$col_name]['showlink']['showAction'];
                    $formatoptions['idName']        = (isset($this->col_formats[$col_name]['showlink']['idName'])?$this->col_formats[$col_name]['showlink']['idName']:'id');
                    $formatoptions['addParam']      = (isset($this->col_formats[$col_name]['showlink']['addParam'])?$this->col_formats[$col_name]['showlink']['addParam']:'');
                    $formatoptions['target']        = (isset($this->col_formats[$col_name]['showlink']['target'])?$this->col_formats[$col_name]['showlink']['target']:'_new');
                    $cols['formatoptions'] = $formatoptions;
                }elseif(isset($this->col_formats[$col_name]['image'])){    // custom formmater for displaying images
                    $cols['formatter'] = '###imageFormatter_'. $this->jq_gridName .'###';
                    $cols['unformat']  = '###imageUnformatter_'. $this->jq_gridName .'###';
               }elseif(isset($this->col_formats[$col_name]['email'])){
                    $cols['formatter'] = 'email';
                }elseif(isset($this->col_formats[$col_name]['integer'])){
                    $cols['formatter'] = 'integer';
                    $formatoptions = array();
                    $formatoptions['thousandsSeparator'] = $this->col_formats[$col_name]['integer']['thousandsSeparator'];
                    $formatoptions['defaultValue']       = $this->col_formats[$col_name]['integer']['defaultValue'];
                    $cols['formatoptions'] = $formatoptions;
                }elseif(isset($this->col_formats[$col_name]['number'])){
                    $cols['formatter'] = 'number';
                    $formatoptions = array();
                    $formatoptions['thousandsSeparator'] =$this->col_formats[$col_name]['number']['thousandsSeparator'];
                    $formatoptions['decimalSeparator']  = $this->col_formats[$col_name]['number']['decimalSeparator'];
                    $formatoptions['decimalPlaces']     = $this->col_formats[$col_name]['number']['decimalPlaces'];
                    $formatoptions['defaultValue']      = $this->col_formats[$col_name]['number']['defaultValue'];
                    $cols['formatoptions'] = $formatoptions;
                }elseif(isset($this->col_formats[$col_name]['date'])){
                    $cols['formatter'] = 'date';
                    $formatoptions = array();
                    $formatoptions['srcformat']            = $this->col_formats[$col_name]['date']['srcformat'];
                    $formatoptions['newformat']            = $this->col_formats[$col_name]['date']['newformat'];
                    $cols['formatoptions'] = $formatoptions;
                }elseif(isset($this->col_formats[$col_name]['datetime'])){
                    $cols['formatter'] = 'date';
                    $formatoptions = array();
                    $formatoptions['srcformat']            = $this->col_formats[$col_name]['datetime']['srcformat'];
                    $formatoptions['newformat']            = $this->col_formats[$col_name]['datetime']['newformat'];
                    $cols['formatoptions'] = $formatoptions;    
                }elseif(isset($this->col_formats[$col_name]['checkbox'])){
                    $cols['formatter'] = 'checkbox';
                    $formatoptions = array();
                    $formatoptions['disabled']            = true;
                    $cols['formatoptions'] = $formatoptions;
                }elseif(isset($this->col_formats[$col_name]['currency'])){
                    $cols['formatter'] = 'currency';
                    $formatoptions = array();
                    $formatoptions['prefix']            = $this->col_formats[$col_name]['currency']['prefix'];
                    $formatoptions['suffix']            = $this->col_formats[$col_name]['currency']['suffix'];
                    $formatoptions['thousandsSeparator'] =$this->col_formats[$col_name]['currency']['thousandsSeparator'];
                    $formatoptions['decimalSeparator']  = $this->col_formats[$col_name]['currency']['decimalSeparator'];
                    $formatoptions['decimalPlaces']     = $this->col_formats[$col_name]['currency']['decimalPlaces'];
                    $formatoptions['defaultValue']      = $this->col_formats[$col_name]['currency']['defaultValue'];
                    $cols['formatoptions'] = $formatoptions;
                }elseif(isset($this->col_formats[$col_name]['boolean'])){
                    $formatoptions = array();
                    $cols['formatter'] = '###booleanFormatter###';
                    $cols['unformat']  = '###booleanUnformatter###';
                    $formatoptions['Yes']  = $this->col_formats[$col_name]['boolean']['Yes'];
                    $formatoptions['No']     = $this->col_formats[$col_name]['boolean']['No'];
                    //$cols['formatoptions'] = $this->col_formats[$col_name];
                    $cols['formatoptions'] = $formatoptions;
                }elseif(isset($this->col_formats[$col_name]['custom'])){    // custom formmater for css
                    $cols['formatter'] = '###'.$col_name. '_customFormatter###';
                    $cols['unformat']  = '###'.$col_name. '_customUnformatter###';
                }
            }

            $cols['editoptions'] = [];
            $cols['editrules'] = [];

            // v5.0 merge with user defined column properties if there's any
            if(isset($this->cust_col_properties[$col_name])){
                $cols = array_replace_recursive($cols, $this->cust_col_properties[$col_name]);
            }

            $colModel[]   = $cols;
        }

        // virtual columns
        if(!empty($this->col_virtual)){
            foreach($this->col_virtual as $key => $value){
                $col_virtual = array();
                $col_property = $this->col_virtual[$key]['property'];
                foreach($col_property as $prop_key=>$prop_value){
                    if(is_string($prop_value) || is_array($prop_value)){
                        $prop_value = $this->parse_to_script($prop_value);
                    }
                    $col_virtual[$prop_key] = $prop_value;
                    $col_virtual['search'] = false;
                }

                if($this->col_virtual[$key]['insert_pos']!=-1){
                    array_splice($colModel, $this->col_virtual[$key]['insert_pos'], 0, array($col_virtual));
                }else{
                    $colModel[]   = $col_virtual;
                }
                

               // $colModel[]   = $col_virtual;
            }
        }

        $this->jq_colModel = $colModel;
    }





    public function get_colModel(){
        return $this->jq_colModel;
    }

    // Used by local array data only when jq_datatype is 'local'
    // "_grid_" is added to avoid potential javascript name collision
    private function display_script_data(){
        echo '<script>var _grid_'. $this->jq_gridName .'='. json_encode($this->data_local) .'</script>' ."\n";
    }

    private function display_style(){
        echo '<style type="text/css">' ."\n";

        if(!empty($this->alt_colors)){
            if($this->alt_colors['altrow']!=null)
                echo '#'. $this->jq_gridName .' tr:nth-child(odd){background-image: none;background-color:'. $this->alt_colors['altrow'] .';}' ."\n";

            echo '#'. $this->jq_gridName .' tr:hover{background-image: none;background:'. $this->alt_colors['hover'] .' !important; color:black}' ."\n";
            
            if($this->alt_colors['highlight']!=null)
                echo '#'. $this->jq_gridName .' tr:nth-child(even){background-image: none;background:'. $this->alt_colors['highlight'] .';}' ."\n";
            
            echo 'table#'. $this->jq_gridName .' tr{ opacity: 1}' ."\n";
        }

        if(!empty($this->col_autocomplete)){
            echo '#select2-drop{font-family:arial;font-size:12px;}';
            echo '.select2-no-results{color:rgb(163, 163, 163);font-size:10px}';
        }
        
        // overwrite frozen column transparenncy
        if(!empty($this->col_frozen)){
            echo 'table#'. $this->jq_gridName .'_frozen{background-color:white};';
        }

        echo '.notifyjs-bootstrap-base.notifyjs-bootstrap-error{
                white-space: pre-wrap;
                font-size: 12px;
            }' ."\n";
        echo '</style>' ."\n";

        if ($this->jq_caption === false) {
            echo "<style>". $this->jq_gridName .".ui-jqgrid-titlebar.ui-jqgrid-caption { display: none } </style>";
        }
    }

    // Desc: only include the scripts once. foriegn key indicates a detail grid. Dont' include script again
    public function display_script_includeonce(){
        if ($this->sql_fkey==null) {

            // ------------------------------------------- load CSS -------------------------------------------
            $this->script_includeonce = '<div id="_phpgrid_script_includeonce" style="display:inline">' ."\n";

            // theme file
            if($this->theme_name != 'NONE'){
                if (strpos($this->theme_name, 'bootstrap') !== false || strpos($this->theme_name, 'cobalt-flat') !== false) {
                    $this->script_includeonce .= '<link rel="stylesheet" id="theme-custom-style" type="text/css" media="screen" href="'. ABS_PATH .'/css/'. $this->theme_name .'/jquery-ui.css" />' ."\n";                               
                } else {
                    $this->script_includeonce .= '<link rel="stylesheet" id="theme-custom-style" type="text/css" media="screen" href="'. ABS_PATH .'/node_modules/jquery-ui/dist/themes/'. $this->theme_name .'/jquery-ui.css" />' ."\n";               
                }
            }

            $this->script_includeonce .= '<link rel="stylesheet" href="'. ABS_PATH .'/node_modules/jquery-ui-multiselect-widget/css/jquery.multiselect.css">' ."\n";
            $this->script_includeonce .= '<link rel="stylesheet" type="text/css" media="screen" href="'. ABS_PATH .'/node_modules/free-jqgrid/css/ui.jqgrid.min.css" />' ."\n";
            $this->script_includeonce .= (self::$has_autocomplete)?'<link href="'. ABS_PATH .'/node_modules/select2/dist/css/select2.min.css" rel="stylesheet" />'. "\n":'';
            $this->script_includeonce .= '<link rel="stylesheet" type="text/css" href="'. ABS_PATH .'/css/datagrid.css">' ."\n";
            
            // ------------------------------------------- load javascript -------------------------------------------
            // jquery
            $this->script_includeonce .= '<script type="text/javascript">
                    if (typeof jQuery == "undefined"){document.write("<script src=\''. ABS_PATH .'/node_modules/jquery/dist/jquery.min.js\' type=\'text/javascript\'><\/script>");}
                    </script>' ."\n";

            // select2
            $this->script_includeonce .= (self::$has_autocomplete)?'<script src="'. ABS_PATH .'/node_modules/select2/dist/js/select2.min.js" type=\'text/javascript\'></script>' ."\n":'';

            // jquery ui
            $this->script_includeonce .= '<script src="'. ABS_PATH .'/node_modules/jquery-ui/dist/jquery-ui.min.js" type="text/javascript"></script>'. "\n";
            
            // mutliselect
            $this->script_includeonce .= '<script src="'. ABS_PATH .'/node_modules/jquery-ui-multiselect-widget/src/jquery.multiselect.js" type="text/javascript"></script>' ."\n";

            // grid locale
            $this->script_includeonce .= '<script src="'. ABS_PATH . sprintf('/node_modules/free-jqgrid/dist/i18n/min/grid.locale-%s.js',$this->locale).'" type="text/javascript"></script>' ."\n";

            // jqgrid
            // TODO - this will loose bootstrap5 support            
            $this->script_includeonce .= '<script src="'. ABS_PATH .'/node_modules/free-jqgrid/dist/jquery.jqgrid.min.js" type="text/javascript"></script>' ."\n";
            // $this->script_includeonce .= '<script src="'. ABS_PATH .'/js/jquery.jqgrid-pg.min.js" type="text/javascript"></script>' ."\n";

            // grid import fix
            $this->script_includeonce .= '<script src="'. ABS_PATH .'/js/grid.import.fix.js" type="text/javascript"></script>' ."\n";

            // jquery migrate
            $this->script_includeonce .= '<script src="'. ABS_PATH .'/node_modules/jquery-migrate/dist/jquery-migrate.min.js" type="text/javascript"></script>' ."\n";
            
            // jquery datetimepicker timepicker
            $this->script_includeonce .='<script src="'. ABS_PATH .'/node_modules/jquery-ui-timepicker-addon/dist/jquery-ui-timepicker-addon.js" type="text/javascript"></script>' ."\n";

            // datetime flatpickr
            $this->script_includeonce .= '<link rel="stylesheet" href="'. ABS_PATH .'/node_modules/flatpickr/dist/flatpickr.min.css"><script src="'. ABS_PATH .'/node_modules/flatpickr/dist/flatpickr.min.js"></script>';

            // jquery browser
            $this->script_includeonce .= '<script src="'. ABS_PATH .'/node_modules/jquery.browser/dist/jquery.browser.min.js" type="text/javascript"></script>' ."\n";

            // notify
            $this->script_includeonce .= '<script src="'. ABS_PATH .'/node_modules/notifyjs-browser/dist/notify.js" type="text/javascript"></script>' ."\n";

            // bootstrap, font-awesome            
            if($this->theme_name == 'bootstrap'){  // bootstrap 3 
                $this->script_includeonce .= 
                '<script type="text/javascript">
                    if (typeof jQuery().modal != "function"){document.write("<link rel=\'stylesheet\' href=\''. ABS_PATH .'/css/bootstrap/bootstrap.min.css\'>") }
                </script>' ."\n";
                $this->script_includeonce .= '<link rel="stylesheet" href="'. ABS_PATH .'/css/bootstrap/pg-jqgrid-bootstrap.css">' ."\n";
            }
            
            $this->script_includeonce .= 
                '<script type="text/javascript">
                    if (typeof jQuery().modal != "function"){document.write("<link rel=\'stylesheet\' href=\''. ABS_PATH .'/node_modules/fontawesome-4.7/css/font-awesome.min.css\'>") }
                </script>' ."\n";

            // jquery tipsy for form 
            if(!empty($this->form_tooltip)){
                $this->script_includeonce .= '<script src="'. ABS_PATH .'/node_modules/jquery.tipsy/src/jquery.tipsy.js" type="text/javascript"></script>' ."\n";
            }

            $this->script_includeonce .= "</div>"."\n";

            echo $this->script_includeonce;
        }
    }

    private function display_script_begin(){
        echo '<script type="text/javascript">' ."\n";
        echo '//<![CDATA[' ."\n";
        echo 'var lastSel;' ."\n";
        echo 'var phpGrid_'. $this->jq_gridName .';'. "\n";
        echo 'jQuery(document).ready(function($){ ' ."\n";

        if($this->has_tbarsearch && !empty($this->auto_filters)){
            echo 'var getUniqueNames = function (columnName) {
                var texts = this.jqGrid("getCol", columnName), uniqueTexts = [],
                    textsLength = texts.length, text, textsMap = {}, i;
                for (i = 0; i < textsLength; i++) {
                    text = texts[i];
                    if (text !== undefined && textsMap[text] === undefined) {
                        // to test whether the texts is unique we place it in the map.
                        textsMap[text] = true;
                        uniqueTexts.push(text);
                    }
                }

                return uniqueTexts;
            },
            buildSearchSelect = function (uniqueNames) {
                var values = ":All";
                $.each(uniqueNames, function () {
                    values += ";" + this + ":" + this;
                });

                return values;
            },
            setSearchSelect = function (columnName) {
                this.jqGrid("setColProp", columnName, {
                    stype: "select",
                    searchoptions: {
                        value: buildSearchSelect(getUniqueNames.call(this, columnName)),
                        sopt: ["eq"]
                    }
                });
            };'; 
        }
    }

    private function display_properties_begin(){
        echo 'phpGrid_'. $this->jq_gridName .' = jQuery("#'. $this->jq_gridName .'").jqGrid({'."\n";
    }

    public function display_properties_main(){
        echo    ($this->jq_datatype != 'local') ?
            'url:'. $this->jq_url .",\n" :  // keep in mind that URL could be overwritten by setGridParam() later.
            'data: _grid_'. $this->jq_gridName .",\n";            // "_grid_" is added to avoid potential javascript name collision
        echo    'datatype:"'. $this->jq_datatype ."\",\n";
        echo    'mtype:"'. $this->jq_mtype ."\",\n";
        echo    'prmNames: {id:"'. JQGRID_ROWID_KEY .'"},'."\n";
        echo    'colNames:'. json_encode($this->jq_colNames) .",\n";
        echo    'colModel:'. (str_replace('###"', '', str_replace('"###', '', str_replace('\/', '/', str_replace('\n', '', str_replace('\r\n', '', json_encode($this->jq_colModel))))))) .",\n";
        echo    'pager: '. $this->jq_pagerName .",\n";
        echo    'rowNum:'. $this->jq_rowNum .",\n";
        echo    'rowList:'. json_encode($this->jq_rowList) .",\n";
        echo    'sortname:"'. $this->jq_sortname ."\",\n";
        echo    'sortorder:"'. $this->jq_sortorder ."\",\n";
        echo    'viewrecords:'. C_Utility::literalBool($this->jq_viewrecords) .",\n";
        echo    'multiselect:'. C_Utility::literalBool($this->jq_multiselect) .",\n";
        echo    'multiPageSelection:'. C_Utility::literalBool($this->jq_multipage) .",\n";
        echo    'multiselectPosition:"'. $this->jq_multiselectPosition ."\",\n";
        echo    'caption:"'. $this->jq_caption ."\",\n";
        echo    'altRows:'. C_Utility::literalBool($this->jq_altRows) .",\n";
        echo    'scrollOffset:'. $this->jq_scrollOffset .",\n";
        echo    'rownumbers:'. C_Utility::literalBool($this->jq_rownumbers) .",\n";
        echo    'shrinkToFit:'. C_Utility::literalBool($this->jq_shrinkToFit) .",\n";
        echo    'autowidth:'. C_Utility::literalBool($this->jq_autowidth) .",\n";
        echo    'hiddengrid:'. C_Utility::literalBool($this->jq_hiddengrid) .",\n";
        echo    'scroll:'. C_Utility::literalBool($this->jq_scroll) .",\n";
        echo    'height:"'. $this->jq_height ."\",\n";   
        echo    'autoresizeOnLoad:'. C_Utility::literalBool($this->jq_autoresizeOnLoad). ",\n";
        echo    'iconSet:"'. $this->iconSet ."\",\n";
        echo    'guiStyle:"'. (strpos($this->theme_name, 'bootstrap') !== false ? $this->theme_name : 'jQueryUI') ."\",\n";
        echo    'autoencode:'. C_Utility::literalBool($this->autoencode) .",\n";
        echo    'checkOnUpdate:true'.",\n";
        echo    'singleSelectClickMode:"selectonly",'. "\n";
        echo    str_replace('###"', '', str_replace('"###', '', 'widthOrg:"'. $this->jq_width). '"') .",\n";
        if(!$this->jq_autoresizeOnLoad){
            echo    str_replace('###"', '', str_replace('"###', '', 'width:"'. $this->jq_width). '"') .",\n";
        }
        echo    'sortable:'. C_Utility::literalBool(empty($this->col_frozen)) .",\n"; // sortable must be false for column froze to work
        echo    'loadError:
                    function(xhr,status, err) {
                        try{
                            jQuery.jgrid.info_dialog(
                                jQuery.jgrid.errors.errcap,
                                "<div style=\"font-size:10px;text-align:left;width:300px;;height:150px;overflow:auto;color:red;\">"+ xhr.responseText +"</div>",
                                jQuery.jgrid.edit.bClose,{buttonalign:"center"});
                        }
                        catch(e) { alert(xhr.responseText)};
                    },'."\n";

        echo    'gridview:'. C_Utility::literalBool($this->jq_gridview) .",\n";

        echo $this->cust_prop_jsonstr ."\n";
        if(!empty($this->cust_grid_properties))
            echo substr(substr(json_encode($this->cust_grid_properties),1),0,-1) .",\n";
    }

    private function display_properties_end(){
        echo    'loadtext:"'. $this->jq_loadtext ."\"\n";  // last properties - no ending comma.
        echo    '});' ."\n";
    }

    // Display additional properites. It's called before toolbar
    private function display_extended_properties(){
        if($this->kb_nav){
            echo '$("'. $this->jq_gridName .'").keydown(function (e) {
                    var $td = $(e.target).closest("td"),
                        $tr = $td.closest("tr.jqgrow"),
                        ci, ri, rows = this.rows;
                    if ($td.length === 0 || $tr.length === 0) {
                        return;
                    }
                    ci = $.jgrid.getCellIndex($td[0]);
                    ri = $tr[0].rowIndex;
                    if (e.keyCode === $.ui.keyCode.UP) { // 38
                        if (ri > 0) {
                            $(rows[ri-1]).focus();
                        }
                    }
                    if (e.keyCode === $.ui.keyCode.DOWN) { // 40
                        if (ri + 1 < rows.length) {
                            $(rows[ri+1]).focus();

                        }
                    }
                });'. "\n";

        }
    }

    private function display_toolbar(){
        echo 'jQuery("#'. $this->jq_gridName .'").jqGrid("navGrid", '. $this->jq_pagerName .",\n";
        echo '{edit:false,add:false,del:false,view:false,search:false,excel:'. (($this->export_type!=null)?'true':'false').'}, {})' ."\n";

        // resizable grid (beta - jQuery UI)
        if($this->jqu_resize['is_resizable']){
            echo 'jQuery("#'. $this->jq_gridName .'").jqGrid("gridResize",{minWidth:'. $this->jqu_resize['min_width'] .',minHeight:'. $this->jqu_resize['min_height'] .'});' ."\n";
        }

        // inline search
        if($this->has_tbarsearch){
            echo 'jQuery("#'. $this->jq_gridName .'").jqGrid("navButtonAdd",'. $this->jq_pagerName .',{caption:"",title:"Toggle inline search", buttonicon :"'. (($this->iconSet === 'fontAwesome') ? 'fa-search' : 'ui-icon-search') .'",
                        onClickButton:function(){
                            phpGrid_'. $this->jq_gridName .'[0].toggleToolbar();
                        }
                    });'."\n";


            $beforeSearch = 'function () {
                    postData = $(this).jqGrid("getGridParam", "postData"),
                    filters = $.parseJSON(postData.filters);';

            $beforeSearch .= '}';

            echo 'jQuery("#'. $this->jq_gridName .'").jqGrid("filterToolbar", {
                        searchOnEnter: false, 
                        stringResult: true, 
                        defaultSearch: "cn",
                        beforeSearch: '. $beforeSearch .'

                }); // foo'."\n";
            echo 'phpGrid_'. $this->jq_gridName .'[0].toggleToolbar();'."\n";   // hide inline search by default
        }

        //advanced search
        if($this->advanced_search){
            echo 'jQuery("#'. $this->jq_gridName.'")
                .navGrid('.$this->jq_pagerName.',{edit:false,add:false,del:false,search:false,refresh:false})
                .navButtonAdd('.$this->jq_pagerName.',{
                    caption:"",
                    buttonicon:"'. (($this->iconSet === 'fontAwesome') ? 'fa-search-plus' : 'ui-icon-search') .'",
                    onClickButton: function(){
                        jQuery("#'.$this->jq_gridName.'").jqGrid("searchGrid", {multipleSearch:true});
                },
                position:"first"
            });'."\n";
        }

        // Excel Export is not documented well. See JS source:
        // http://www.trirand.com/blog/phpjqgrid/examples/functionality/excel/default.php
        if($this->export_type!=null){
            echo 'jQuery("#'. $this->jq_gridName .'").jqGrid("navButtonAdd",'. $this->jq_pagerName .',{caption:"",title:"'. $this->export_type .'",
                        onClickButton:function(e){
                            var src, fkey_value;
                            try{
                                // add additional querystring parameter if exporting from master detail grid
                                mdUrl = jQuery("#'. $this->jq_gridName .'").jqGrid("getGridParam", "url");
                                if(mdUrl.indexOf("masterdetail.php") > -1){
                                    src="md";
                                    fkey_value = mdUrl.match(/fkey_value=([^&]*)/i)[1];
                                }
                                jQuery("#'. $this->jq_gridName .'").jqGrid("excelExport",{url:"'. $this->export_url .(($this->export_type!='')?'&src="+src+"&fkey_value="+fkey_value+"&export_type='. $this->export_type:'') .'"});
                            } catch (e) {
                                window.location= "'. $this->export_url .(($this->export_type!='')?'&export_type='. $this->export_type:'') .'";
                            }

                        }
                    });'."\n";
        }

        // render jqGrid methodS
        // replace \r\n (Windows), \n(UNIX)
        if(!empty($this->grid_methods)){
            foreach($this->grid_methods as $method){
                echo (str_replace('###"', '', str_replace('"###', '', str_replace('\"', '"', str_replace('\n', ' ', str_replace('\r\n', ' ', $method)))))) ."\n";
            }
        }
        unset($method);
    }

    // reorder rows by mouse
    public function set_sortablerow($sortable=false){
        if($sortable){
            $this->grid_methods[] = 'phpGrid_'. $this->jq_gridName .'.jqGrid("sortableRows", {});';
        }

        return $this;
    }

    // set jqgrid methods by method name and method options.
    // Note the method takes a variable number of arguments. Much more flexible as jqGrid methods have different number of params
    // Important Developer Note:s
    // 1.    The methods is injected AFTER navGrid BEFORE end of $(document).ready(...)
    // 2.    To modify grid property, call set_grid_properties instead
    public function set_grid_method(){
        $options = '';
        $method_name = func_get_arg(0);

        for ($i = 1; $i < func_num_args(); $i++) {
            if(is_array(func_get_arg($i))){
                $options .= json_encode(func_get_arg($i)). ',';
            } else {
                $options .= '"'. func_get_arg($i) .'",';
            }
        }
        $options = substr($options, 0, -1); // remove last comma

        $this->grid_methods[] = 'phpGrid_'. $this->jq_gridName .'.jqGrid("'. $method_name .'", '. $options .');';

        return $this;
    }

    // Display ending brackets. Here's where to put functions
    private function display_script_end(){

        // function call required for toolbar search dynamic filter dropdown based on unique column values
        if($this->has_tbarsearch && !empty($this->auto_filters)){
            foreach($this->auto_filters as $col_name){
                echo 'setSearchSelect.call(phpGrid_'. $this->jq_gridName .', '. $col_name .');'."\n";
            }

            echo 'phpGrid_'. $this->jq_gridName .'.jqGrid("setColProp", "Name", {
                searchoptions: {
                    sopt: ["cn"],
                    dataInit: function (elem) {
                        $(elem).autocomplete({
                            source: getUniqueNames.call($(this), "Name"),
                            delay: 0,
                            minLength: 0,
                            select: function (event, ui) {
                                var $myGrid, grid;
                                $(elem).val(ui.item.value);
                                if (typeof elem.id === "string" && elem.id.substr(0, 3) === "gs_") {
                                    $myGrid = $(elem).closest("div.ui-jqgrid-hdiv").next("div.ui-jqgrid-bdiv").find("table.ui-jqgrid-btable").first();
                                    if ($myGrid.length > 0) {
                                        grid = $myGrid[0];
                                        if ($.isFunction(grid.triggerToolbar)) {
                                            grid.triggerToolbar();
                                        }
                                    }
                                } else {
                                    // to refresh the filter
                                    $(elem).trigger("change");
                                }
                            }
                        });
                    }
                }
            });' ."\n";

            echo 'phpGrid_'. $this->jq_gridName .'.jqGrid("destroyFilterToolbar");';
            echo 'phpGrid_'. $this->jq_gridName .'.jqGrid("filterToolbar",{stringResult: true, searchOnEnter: true, defaultSearch: "cn"});' ."\n";
            echo 'phpGrid_'. $this->jq_gridName .'[0].triggerToolbar();' ."\n";
        }


        echo "\n". '});' ."\n";
        echo 'function getSelRows()
             {
                var rows = jQuery("#'.$this->jq_gridName.'").jqGrid("getGridParam","selarrrow");
                return rows;
             }' ."\n";
        echo '// cellValue - the original value of the cell
              // options - as set of options, e.g
              // options.rowId - the primary key of the row
              // options.colModel - colModel of the column
              // rowObject - array of cell data for the row, so you can access other cells in the row if needed ' ."\n";
        echo 'function imageFormatter_'. $this->jq_gridName .'(cellValue, options, rowObject)
             {
                return (cellValue == "" || cellValue === null)? "":"<img src=\"'. $this->img_baseUrl .'"+ cellValue + "\" originalValue=\""+ cellValue +"\" title=\""+ cellValue +"\">";
             }' ."\n";
        echo '// cellValue - the original value of the cell
              // options - as set of options, e.g
              // options.rowId - the primary key of the row
              // options.colModel - colModel of the column
              // cellObject - the HMTL of the cell (td) holding the actual value ' ."\n";
        echo 'function imageUnformatter_'. $this->jq_gridName .'(cellValue, options, cellObject)
             {
                return $(cellObject.html()).attr("originalValue");
             }' ."\n";
        echo 'function booleanFormatter(cellValue, options, rowObject)
             {
                var op;
                op = $.extend({},options.colModel.formatoptions);
                myCars=new Array();
                //alert(op.No);
                //mycars[cellValue]=  op.boolean.No;
                //mycars[cellValue]=  op.boolean.Yes;
                myCars[op.No]="No";
                myCars[op.Yes]="Yes";
                //alert(options[boolean]);
                return myCars[cellValue];
             }' ."\n";

        echo 'function booleanUnformatter(cellValue, options, cellObject)
             {    var op;
                  op = $.extend({},options.colModel.formatoptions);
                  //alert(op.No);
                  if(cellValue=="No")
                  return (op.No);
                  else
                  return (op.Yes);
            //alert(op.boolean.Yes)
            //return (op.boolean.cellValue);
              //  myCars=new Array();
            //    myCars["No"]=\'0\';
            //    myCars["Yes"]=1;
                //alert(myCars[cellValue]);
                //alert(options.colModel.formatoptions[1]);
                //return myCars[cellValue];
             }' ."\n";

        echo '//]]>' ."\n";
        echo '</script>' ."\n";
    }

    private function display_events(){
        echo '<script type="text/javascript">' ."\n";
        echo 'jQuery(document).ready(function($){ '. "\n";
        echo $this->script_ude_handler;

        if(!empty($this->col_frozen)){
            echo '$("#'. $this->jq_gridName .'").jqGrid("setFrozenColumns");'. "\n";
        }
        
        echo '});'. "\n";
        echo '</script>'. "\n";
    }

    // Desc: html element as grid placehoder 
    // Must strip out # sign. use str_replace() on pagerName because it also include (")
    private function display_container(){
        echo '<table id="'. $this->jq_gridName .'"></table>' ."\n";
        echo '<div id='. str_replace("#", "", $this->jq_pagerName) .'></div>' ."\n";
        echo '<br />'. "\n";

        echo "<Script Language='Javascript'>document.write(unescape('%3c%64%69%76%20%63%6c%61%73%73%3d%22%70%67%5f%6e%6f%74%69%66%79%22%20%73%74%79%6c%65%3d%22%66%6f%6e%74%2d%73%69%7a%65%3a%37%70%74%3b%63%6f%6c%6f%72%3a%67%72%61%79%3b%66%6f%6e%74%2d%66%61%6d%69%6c%79%3a%61%72%69%61%6c%3b%63%75%72%73%6f%72%3a%70%6f%69%6e%74%65%72%3b%22%3e%0d%0a%09%20%20%20%20%59%6f%75%20%61%72%65%20%75%73%69%6e%67%20%3c%61%20%68%72%65%66%3d%22%68%74%74%70%3a%2f%2f%70%68%70%67%72%69%64%2e%63%6f%6d%2f%22%20%74%61%72%67%65%74%3d%22%5f%6e%65%77%22%3e%70%68%70%47%72%69%64%20%4c%69%74%65%3c%2f%61%3e%2e%20%50%6c%65%61%73%65%20%63%6f%6e%73%69%64%65%72%20%3c%61%20%68%72%65%66%3d%22%68%74%74%70%3a%2f%2f%70%68%70%67%72%69%64%2e%63%6f%6d%2f%64%6f%77%6e%6c%6f%61%64%73%2f%3f%72%65%66%3d%6c%69%74%65%5f%6e%61%67%23%63%6f%6d%70%61%72%69%73%6f%6e%22%20%74%61%72%67%65%74%3d%22%5f%6e%65%77%22%3e%75%70%67%72%61%64%69%6e%67%20%70%68%70%47%72%69%64%3c%2f%61%3e%20%74%6f%20%74%68%65%20%66%75%6c%6c%20%76%65%72%73%69%6f%6e%20%74%6f%20%68%61%76%65%20%67%72%65%61%74%20%66%65%61%74%75%72%65%73%20%69%6e%63%6c%75%64%69%6e%67%20%65%64%69%74%2c%20%6d%61%73%74%65%72%20%64%65%74%61%69%6c%2c%20%61%6e%64%20%67%72%6f%75%70%69%6e%67%2c%20%63%6f%6d%70%6f%73%69%74%65%20%6b%65%79%2c%20%66%69%6c%65%20%75%70%6c%6f%61%64%2c%20%61%6e%64%20%70%72%65%6d%69%75%6d%20%74%68%65%6d%65%73%21%0d%0a%09%3c%2f%64%69%76%3e'));</Script>";
    }

    // Desc: debug function. dump the grid objec to screen
    private function display_debug(){
        echo '<script>jQuery(document).ready(function($){
                $(\'#_'. $this->jq_gridName .'_debug_ajaxresponse\').toggle();
                $(\'#_'. $this->jq_gridName .'_debug_ctrl\').toggle();
                $(\'#_'. $this->jq_gridName .'_debug_gridobj\').toggle();
                $(\'#_'. $this->jq_gridName .'_debug_sessobj\').toggle();
            });</script>';
        print('<u style="cursor:pointer" onclick="$(\'#_'. $this->jq_gridName .'_debug_ctrl\').toggle(\'fast\');">CONTROL VALIDATION</u><br />');
        print("<pre id='_". $this->jq_gridName ."_debug_ctrl' style='border:1pt dotted black;padding:5pt;background:red;color:white;display:block'>");
        if($this->jq_multiselect && $this->edit_mode=='NONE'){
            print("\n".'- Grid has multiselect enabled. However, the grid has not been set to be editable.');
        }
        if($this->jq_scroll){
            print("\n".'- Scrolling (set_sroll)is enabled. As a result, pagination is disabled.');
        }
        print("</pre>");

        print('<u style="cursor:pointer" onclick="$(\'#_'. $this->jq_gridName .'_debug_gridobj\').toggle(\'fast\');">DATAGRID OBJECT</u><br />');
        print("<pre id='_". $this->jq_gridName ."_debug_gridobj' style='border:1pt dotted black;padding:5pt;background:#E4EAF5;display:block'>");
        print_r($this);
        print("</pre>");

        print('<u style="cursor:pointer" onclick="$(\'#_'. $this->jq_gridName .'_debug_sessobj\').toggle(\'fast\');">SESSION OBJECT</u><br />');
        print("<pre id='_". $this->jq_gridName ."_debug_sessobj' style='border:1pt dotted black;padding:5pt;background:#FFDAFA;display:block'>");
        print("<br />SESSION NAME: ". session_name());
        print("<br />SESSION ID: ". session_id() ."<br />");
        print("SESSION KEY: ". GRID_SESSION_KEY.'_'.$this->jq_gridName ."<br />");
        print_r(C_Utility::indent_json(str_replace("\u0000", " ", json_encode($this->session)))); // \u0000 NULL
        print("</pre>");
    }

    // Desc: display ajax server response message in debug
    private function display_ajaxresponse(){
        print('<u style="cursor:pointer" onclick="$(\'#_'. $this->jq_gridName .'_debug_ajaxresponse\').toggle(\'fast\');">AJAX RESPONSE</u><br />');
        print("<pre id='_". $this->jq_gridName ."_debug_ajaxresponse' style='border:1pt dotted black;padding:5pt;background:yellow;color:black;display:block'>");
        print("</pre>");
    }

    // Desc: display finally
    public function display($render_content=true){
        if(C_Utility::is_debug()) { print("<h2>". $this->_ver_num ."</h2>");}

        $this->prepare_grid();
        
        if($this->jq_datatype == 'local') $this->display_script_data();

        // display include header
        ob_start();
        $this->display_script_includeonce();
        $this->script_includeonce = ob_get_contents();
        ob_end_clean();

        if($render_content){
            $this->display_script_includeonce();
        }

        // display script body
        ob_start();
        $this->display_style();
        $this->display_script_begin();
        $this->display_properties_begin();
        $this->display_properties_main();
        $this->display_properties_end();
        $this->display_extended_properties();
        $this->display_toolbar();
        $this->display_before_script_end();
        $this->display_script_end();
        $this->display_container();
        
        $this->display_events();

        if(C_Utility::is_debug()){
            $this->display_ajaxresponse();
            $this->display_debug();
        }

        $this->script_body = ob_get_contents();        // capture output into variable used by get_display
        $this->script_body = preg_replace('/,\s*}/', '}', $this->script_body);    // remove trailing comma in JSON just in case
        ob_end_clean();

        if($render_content){
            echo $this->script_body;
        }
    }

    // Desc: set sql string
    protected function set_sql($sqlstr){
        $this->sql = $sqlstr;

        return $this;
    }

    // Desc:For query filter
    public function set_query_filter($where){
        if($where!=''){
            $this->sql_filter = $where;
            //$this->sql.= ' WHERE '.$where;
        }

        return $this;
    }

    protected function get_filter(){
        return $this->sql_filter;

    }

    // Desc: set table name in sql string. Must call this function on client.
    protected function set_sql_table($sqltable){
        $this->sql_table = $sqltable;

        return $this;
    }

    public function get_sql_table(){
        return $this->sql_table;
    }

    // Desc: set data url
    protected function set_jq_url($url, $add_quote=true){
        $this->jq_url = ($add_quote)?('"'.$url.'"'):$url;

        return $this;
    }

    protected function get_jq_url(){
        return $this->jq_url;
    }

    public function set_jq_datatype($datatype){
        $this->jq_datatype = $datatype;
        $this->jq_url       = '"'. ABS_PATH .'/data.php?dt='. $datatype .'&gn='. $this->jq_gridName .'"';

        return $this;
    }

    public function get_jq_datatype(){
        return $this->jq_datatype;
    }


    // Desc: set a hidden column OR array of string separated by comma
    // the 2nd parameter indicates whether it's also hidden during add/edit, applicalbe ONLY to form
    public function set_col_hidden($col_name, $edithidden=true){
        if(is_array($col_name)){
            foreach($col_name as $col){
                $this->col_hiddens[$col]['edithidden'] = $edithidden;
            }
        } else {
            $col_names = preg_split("/[\s]*[,][\s]*/", $col_name);
            foreach($col_names as $col){
                $this->col_hiddens[$col]['edithidden'] = $edithidden;
            }
        }

        return $this;

    }

    public function get_col_hiddens(){
        return $this->col_hiddens;
    }

    // Desc: get sql string
    public function get_sql(){
        return $this->sql;
    }

    //Desc: get the currently set database
    public function get_db_connection(){
        return $this->db_connection;
    }

    // Desc: set sql PK
    public function set_sql_key($sqlkey){
        if(!is_array($sqlkey)) $sqlkey = array($sqlkey); // convert $sql_key to array if it's not an array
        $this->sql_key = $sqlkey;

        return $this;
    }

    // Desc: get sql PK
    public function get_sql_key(){
        return $this->sql_key;
    }

    // Desc: set sql Foreign PK
    public function set_sql_fkey($sqlfkey){
        $this->sql_fkey = $sqlfkey;

        return $this;
    }
    // Desc: get sql Master key
    public function get_sql_fkey(){
        return $this->sql_fkey;
    }

    // Desc: get number of rows
    public function get_num_rows(){
        return $this->_num_rows;
    }

    // Desc: vertical scroll to load data. pager is automatically disabled as a result
    // The height MUST NOT be 100%. The default height is 400 when scroll is true.
    public function set_scroll($scroll, $h='400'){
        $this->jq_scroll = $scroll;
        $this->jq_height = $h;

        return $this;
    }

    /**
     * Enable integrated toolbar search
     * @param  boolean $can_search      Enable integrated toolbar search
     * @param  Array $auto_filter     Excel-like auto filter
     * @return grid object              
     */
    public function enable_search($can_search, $auto_filters=array()){
        $this->has_tbarsearch   = $can_search;
        $this->auto_filters     = $auto_filters;

        return $this;
    }

    public function enable_advanced_search($has_adsearch){
        $this->advanced_search = $has_adsearch;

        return $this;
    }

    // Desc: sel multiselect
    // Note when positioned to right, it could pose a problem in conditional formatting.
    public function set_multiselect($multiselect, $multipage = true, $position='left'){
        $this->jq_multiselect = $multiselect;
        $this->jq_multipage = $multipage;
        $this->jq_multiselectPosition = $position;


        return $this;
    }

    public function has_multiselect(){
        return $this->jq_multiselect;
    }

    // Desc: set column title
    public function set_col_title($col_name, $new_title){
        $this->col_titles[$col_name] = $new_title;

        return $this;
    }

    // Desc: get column titles
    public function get_col_titles(){
        return $this->col_titles;
    }

    // Desc: set column value as hyper link
    public function set_col_link($col_name, $target="_new"){
        $this->col_formats[$col_name]['link'] = array("target"=>$target);
        // $this->col_links[$col_name] = array("target"=>$target);

        return $this;
    }

    // Desc: set column value as date;
    public function set_col_date($col_name, $srcformat="Y-m-d", $newformat="Y-m-d", $datePickerFormat="Y-m-d"){
        $this->col_formats[$col_name]['date'] = 
            [
                "srcformat"=>$srcformat,
                "newformat"=>$newformat,
                "datePickerFormat"=>$datePickerFormat
            ];

        return $this;
    }
    
    /**
     * Set column value as date time 
     * @param [type] $col_name         Column name
     * @param string $srcformat        Date source display format
     * @param string $newformat        Date new display format
     * @param string $datePickerFormat Datepicker displya format
     */
    public function set_col_datetime($col_name, $srcformat="Y-m-d H:i", $newformat="Y-m-d H:i", $datePickerFormat="Y-m-d H:i"){
        $this->col_formats[$col_name]['datetime'] = array("srcformat"=>$srcformat,
            "newformat"=>$newformat,
            "datePickerFormat"=>$datePickerFormat);

        return $this;
    }

    public function set_col_time($col_name){
        $this->col_formats[$col_name]['time'] = true;

        return $this;
    }

    // Desc: set column as currency when displayed
    public function set_col_currency($col_name, $prefix='$', $suffix='', $thousandsSeparator=',', $decimalSeparator='.',
                                    $decimalPlaces='2', $defaultValue='0.00'){
        $this->col_formats[$col_name]['currency'] = array("prefix" => $prefix,
            "suffix" => $suffix,
            "thousandsSeparator" => $thousandsSeparator,
            "decimalSeparator" => $decimalSeparator,
            "decimalPlaces" => $decimalPlaces,
            "defaultValue" => $defaultValue);
        return $this;
    }

    // Desc: set image column. Also set baseUrl for image.
    // Only a single image base Url is supported per datagrid
    public function set_col_img($col_name, $baseUrl=''){
        $this->col_formats[$col_name]['image'] = array('baseUrl' => $baseUrl);
        $this->img_baseUrl = $baseUrl;

        return $this;
    }
    /* ***************** end of formatter helper functions ********************************/

    // Desc: jqGrid formatter: integer, number, currency, date, link, showlink, email, select (special case)
    public function set_col_format($col_name, $format, $formatoptions=array()){
        $this->col_formats[$col_name][$format] = $formatoptions;

        return $this;
    }

    // Desc: set column value as dynamic hyper link
    public function set_col_dynalink($col_name, $baseLinkUrl="", $dynaParam="id",$staticParam="",$target="_new", $prefix=""){
        
        $sFormatter = "function ".$col_name."_customFormatter(cellValue, options, rowObject){ %s }";
        $sUnformatter = "function ".$col_name."_customUnformatter(cellValue, options, rowObject){ %s }";
        
        $results = $this->db->select_limit($this->sql,1, 1);

        $dynaParamQs= '';

        if($this->jq_datatype != 'local'){
            if(is_array($dynaParam) && !empty($dynaParam)){
                foreach($dynaParam as $key => $value){
                    $dynaParamQs .= $value .'=" + encodeURIComponent(rowObject['.$this->db->field_index($results,$value).']) + "&';
                }

                $dynaParamQs = rtrim($dynaParamQs, '&');
            } elseif ($dynaParam !== '') {
                $dynaParamQs .= $dynaParam .'=" + encodeURIComponent(rowObject['.$this->db->field_index($results,$dynaParam).']) + "';
            }
        } else {

            if(is_array($dynaParam) && !empty($dynaParam)){

                foreach($dynaParam as $key => $value){

                    $dynaParamQs .= $value .'=" + encodeURIComponent(rowObject.'. $value .') + "&';
                
                }
                $dynaParamQs = rtrim($dynaParamQs, '&');

            } elseif ($dynaParam !== '') {

                $dynaParamQs .= $dynaParam .'=" + encodeURIComponent(rowObject.'. $dynaParam .') + "';
            
            }
        }

        // if baseLinkUrl does not begin with http, then treat it as column name
        if (substr($baseLinkUrl, 0, 4) !== 'http') {

            $baseLinkUrl = '\''.$prefix.'\'+rowObject['.$this->db->field_index($results,$baseLinkUrl).']';
        
        } else {
            
            $baseLinkUrl = '\''.$prefix.$baseLinkUrl.'\'';
        
        }

        // only add ? when there isn't one already
        $questionMark = (strpos($baseLinkUrl, '?') === false) ? '?' : '';

        // note we remove the last character from url if it is question mark
        $sVal = '
        var params = "' .$questionMark .$dynaParamQs .$staticParam.'";
        var url = '.$baseLinkUrl.' + params;

        if (cellValue == null || cellValue == "") {return "";}

        return \'<a href="\'+url.replace(/\?$/, \'\')+\'" target="'.$target.'" value="\' + cellValue + \'">\'+cellValue+\'</a>\';
        ';

        $sFormatter = sprintf($sFormatter,$sVal);
        $sUnformatter = sprintf($sUnformatter,'var obj = jQuery(rowObject).html(); return jQuery(obj).attr("value");');
        
        $this->col_formats[$col_name]['custom'] = $staticParam;

        return $this;
    }

    // Desc: set grid height and width, the default height is 100%
    public function set_dimension($w, $h='100%', $shrinkToFit = true){
        $this->jq_width=$w;
        $this->jq_height=$h;
        $this->jq_shrinkToFit = $shrinkToFit;

        return $this;
    }

    // Desc: enable resizable grid(through jquery UI. Experimental feature)
    public function enable_resize($is_resizable, $min_w=350, $min_h=80){
        $this->jqu_resize["is_resizable"]   = $is_resizable;
        $this->jqu_resize["min_width"]      = $min_w;
        $this->jqu_resize["min_height"]     = $min_h;

        return $this;
    }

    // Desc: set pager name.
    // *** Note ***
    // The 2nd parameter adds quote around the pager name
    public function set_jq_pagerName($pagerName, $add_quote=true){
        $this->jq_pagerName = ($add_quote)?('"'.$pagerName.'"'):$pagerName;

        return $this;
    }

    // Desc: set grid name
    public function set_jq_gridName($gridName){
        $this->jq_gridName = $gridName;
        $this->jq_pagerName = '"#'. $gridName .'_pager1"';  // Notice the double quote;
        $this->jq_url = '"'. ABS_PATH .'/data.php?dt='. $this->jq_datatype .'&gn='.$gridName .'"';
        $this->export_url = ABS_PATH .'/export.php?dt='. $this->jq_datatype .'&gn='. $this->jq_gridName .(($this->export_type!='')?'&export_type='. $this->export_type:'');

        return $this;
    }

    // Desc: get grid name
    public function get_jq_gridName(){
        return $this->jq_gridName;
    }

    // Desc: set sort name
    public function set_sortname($sortname,$sortorder = 'ASC'){
        $this->jq_sortname = $sortname;
        $this->jq_sortorder = $sortorder;

        return $this;
    }

    public function enable_export($type='EXCEL'){
        $this->export_type = $type;

        return $this;
    }

    public function set_row_color($hover_color, $highlight_color=null, $altrow_color=null){
        $this->alt_colors['hover'] = $hover_color;
        $this->alt_colors['highlight'] = $highlight_color;
        $this->alt_colors['altrow'] = $altrow_color;

        return $this;
    }

    public function set_theme($theme){
        $this->theme_name = $theme;

        return $this;
    }

    // Desc: set locale
    public function set_locale($locale){
        $this->locale = $locale;

        return $this;
    }

    // Desc: set caption text
    public function set_caption($caption){
        if($caption==='') $caption = '&nbsp;';
        $this->jq_caption = $caption;

        return $this;
    }

    // Desc: set page size
    // Note: pagination is disabled when set_scroll is set to true.
    public function set_pagesize($pagesize){
        $this->jq_rowNum = $pagesize;

        return $this;
    }

    // Desc: boolean whether display sequence number to each row
    public function enable_rownumbers($has_rownumbers){
        $this->jq_rownumbers = $has_rownumbers;

        return $this;
    }

    // set coulmn width
    public function set_col_width($col_name, $width){
        $this->col_widths[$col_name]['width'] = $width;
        $this->set_col_property($col_name, array('autoResizable'=>false));                

        return $this;
    }
    // get coulmn width
    public function get_col_width(){
        return $this->col_widths;
    }

    // set coulmn width
    public function set_col_align($col_name, $align="left"){
        $this->col_aligns[$col_name]['align'] = $align;

        return $this;
    }
    // get coulmn width
    public function get_col_align(){
        return $this->col_aligns;
    }

    // Work with a single datagrid in read only mode
    public function enable_kb_nav($is_enabled = false){
        $this->kb_nav = $is_enabled;

        return $this;
    }

    public function setCallbackString ($string) {
        $this->callbackstring = '&__cbstr='.strtr(rtrim(base64_encode($string), '='), '+/', '-_');
        $this->jq_url = substr($this->jq_url,0,-1).$this->callbackstring.'"';
        $this->export_url .= $this->callbackstring;

        return $this;
    }

    // jq_autowidth is set to false by default, use this method to enable, the default width is 800
    public function enable_autowidth($autowidth=false){
        $this->jq_autowidth = $autowidth;
        $this->jq_autoresizeOnLoad = false;

        // auto resize script
        if($autowidth){
            $this->script_ude_handler .=
                '$(window).bind("resize", function() { 
                    phpGrid_'. $this->jq_gridName .'.jqGrid("setGridWidth", phpGrid_'. $this->jq_gridName .'.parent().width());
                }).trigger("resize");' ."\n";
        }

        return $this;
    }

    // jq_autoheight is set to false by default
    // Note: Do not use this method when multiple grids on a single page
    public function enable_autoheight($autoheight=false){
        // auto resize script
        if($autoheight){
            $this->script_ude_handler .=
                'var grid_height = $(window).height() -
                    $(".ui-jqgrid .ui-jqgrid-titlebar").height() -
                    $(".ui-jqgrid .ui-jqgrid-hbox").height() -
                    $(phpGrid_'. $this->jq_gridName .'.getGridParam("pager")).height() - 18;
                $(window).bind("resize", function() {
                    phpGrid_'. $this->jq_gridName .'.jqGrid("setGridHeight", grid_height );
                }).trigger("resize");' ."\n";
        }

        return $this;
    }

    // return the grid script include and body. It can be useful for MVC framework integration such as Drupal.
    public function get_display($add_script_includeonce=true){
        if($add_script_includeonce){
            return $this->script_includeonce . $this->script_body;
        } else {
            return $this->script_body;
        }
    }

    // set column frozen
    public function set_col_frozen($col_name, $value=true){
        $this->col_frozen[$col_name] = $value;        // doesn't really need a value

        return $this;
    }

    // advanced function
    // set event. new event model in jqgrid 4.3.2 will not overwrite previous handler of the same event
    public function add_event($event_name, $js_event_handler){
        $this->script_ude_handler .= 'phpGrid_'. $this->jq_gridName .'.bind("'. $event_name .'", '. $js_event_handler .');' ."\n";

        return $this;
    }

    // First It removes 'non-visible' ASCII characters and add "###" as special symbol signaling a javascript function
    private function parse_to_script($obj){
        if(is_array($obj)){
            $arr = array();
            foreach($obj as $key => $value){
                if(is_string($value)){
                    $script = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $value);
                    if(preg_match('/function\([^)].*\)/i', $script)){
                        $script = '###'. $script .'###';
                    }
                    $arr[$key] = $script;
                } else {
                    $arr[$key] = $value;
                }
            }

            return $arr;

        } elseif (is_string($obj)){
            $script = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $obj);
            if(preg_match('/function\([^)].*\)/i', $script)){
                $script = '###'. $script .'###';
            }
            return $script;

        }

    }

    // advanced function
    // set colModel property manually. Use this method when there's no exposed methods for some column properties.e.g. size
    // this method can now be called multiple time with property merge
    public function set_col_property($col_name, $property = array()){
        $cust_property = array();
        foreach($property as $prop_key=>$prop_value){
            if(is_string($prop_value) || is_array($prop_value)){
                $prop_value = $this->parse_to_script($prop_value);
            }
            $cust_property[$prop_key] = $prop_value;
        }

        // property merge, so set_col_property multiple times
        if(isset($this->cust_col_properties[$col_name])){
            $this->cust_col_properties[$col_name] = array_replace_recursive($cust_property, $this->cust_col_properties[$col_name]);
        } else {
            $this->cust_col_properties[$col_name] = $cust_property;
        }

        return $this;
    }

    // advanced function
    // set custom grid property
    public function set_grid_property($property = array()){
        $this->cust_grid_properties = array_replace_recursive($property, $this->cust_grid_properties);

        return $this;
    }

    // create virtual column
    // Note position rather than the last column could pose problem in conditional format
    public function add_column($col_name, $property = array(), $title='', $insert_pos = -1){
        $this->col_virtual[$col_name]['property'] = $property;
        $this->col_virtual[$col_name]['title'] = ($title == '') ? $col_name : $title;
        $this->col_virtual[$col_name]['insert_pos'] = $insert_pos;

        return $this;
    }

    // custom validation
    public function set_col_customrule($col_name, $customrule_func){
        $this->col_customrule[$col_name]['custom'] = true;
        $this->col_customrule[$col_name]['custom_func'] = $customrule_func;

        return $this;
    }

    // inject custom javascript before the end of closing script so all DOM elements are presented.
    private function display_before_script_end(){
        echo $this->before_script_end;
    }
}