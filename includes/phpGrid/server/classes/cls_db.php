<?php
namespace phpCtrl;

// if(!session_id()){ session_start();} // this is nessecory for PHP that running on Windows
class C_Database{
    public $hostName;
    public $userName;
    public $password;
    public $databaseName;
    public $tableName;
    public $link;
    public $dbType;
    public $charset;
    public $dbOptions = array();
    public $db; 
    public $result;
    public $session;
    
    public function __construct($host, $user, $pass, $dbName, $db_type = "mysql", $charset="utf8mb4", $dbOptions = array(), $sql_table=""){
        $this -> hostName = $host;
        $this -> userName = $user;
        $this -> password = $pass;
        $this -> databaseName = $dbName;
        $this -> dbType  = $db_type;
        $this -> charset = $charset;
        $this -> dbOptions = $dbOptions; // DB2 only 
        $this -> tableName = $sql_table;       
        $this -> session = C_SessionMaker::getSession(FRAMEWORK);
        
        $this -> _db_connect();    
    }

    /*
    ************************* working with this function in database layer class ************************
    *                                                                                                   *
    *  Connect to the Database                                                                          *
    *  Go to http://phplens.com/lens/adodb/docs-adodb.htm#connect_ex for database connection reference  *
    *  to other types of databases and simply modify/add to the Switch statement.                       *
    *                                                                                                   *
    *  There are multiple ways to connect Oracle db                                                     *
    *  see "http://phplens.com/adodb/code.initialization.html#oci8"                                     *
    *  and change it properly to suit your needs                                                        *
    *                                                                                                   *
    *****************************************************************************************************
    */
    public function _db_connect(){
        switch($this->dbType){
            case "access":
                $this->db = ADONewConnection($this->dbType);
                $dsn = "Driver={Microsoft Access Driver (*.mdb)};Dbq=".$this->databaseName.";Uid=".$this->userName.";Pwd=".$this->password.";";
                $this->db->Connect($dsn);
                break;
            case "odbc_mssql_native":
                $this->db = ADONewConnection('odbc_mssql');
                // DSN connectivity through SQL Native Client 10.0 ODBC Driver
                $dsn = "Driver={SQL Server};Server=".$this->hostName.";Database=".$this->databaseName.";";
                $this->db->Connect($dsn, $this->userName, $this->password);
                break;
            // PHP 7.2+ with SQL Server PHP driver for Windows
            // php.ini to include:
            //      extension=pdo_odbc
            //      extension=php_sqlsrv_72_ts_x86.dll
            //      extension=php_pdo_sqlsrv_72_ts_x86.dll
            case "sqlsrv": 
                $this->db = ADONewConnection('mssqlnative');
                $this->db->Connect($this->hostName, $this->userName, $this->password, $this->databaseName);
                break;
            case "odbc_mssql":
                $this->db = ADONewConnection($this->dbType);
                // DSN connectivity through unixODBC
                $this->db->Connect($this->hostName, $this->userName, $this->password);
                break;
            case "postgres":
                $this->db = ADONewConnection($this->dbType);
                $this->db->Connect($this->hostName, $this->userName, $this->password, $this->databaseName) or C_Utility::fail("Error: Could not connect to the database", 500);
                if(!empty($this->charset)) {
                    $this->db->Execute("SET NAMES '$this->charset'");
                }
                break;
            case "db2-dsn":
                $this->db = ADONewConnection('db2');
                $dsn = "driver={IBM db2 odbc DRIVER};Database=".$this->databaseName.";hostname=".$this->hostName.";port=50000;protocol=TCPIP;uid=".$this->userName."; pwd=".$this->password;
                $this->db->Connect($dsn, "", "", "", $this->dbOptions);
                break;
            case 'db2':
                $this->db = ADONewConnection('db2');
                $this->db->Connect($this->hostName,$this->userName,$this->password, $this->databaseName, false, $this->dbOptions);
                break;
            case "ibase":
                $this->db = ADONewConnection($this->dbType); 
                $this->db->Connect($this->hostName . $this->databaseName, $this->userName, $this->password);
                break;
            case "oci805":
                // Host name and SID
                $this->db = ADONewConnection($this->dbType);        // Christopher: It�s missing the code to initialize the db object for oci8 db type:
                $ret = $this->db->Connect($this->hostName, $this->userName, $this->password, $this->databaseName);         
                if(!$ret){
                    // Host Address and Service Name
                    // <servicename> is passed in databaseName
                    $this->db->Connect($this->hostName, $this->userName, $this->password, $this->databaseName);                                                                     
                }             
                
                // TNS Name defined in tnsnames.ora (or ONAMES or HOSTNAMES), eg. 'myTNS'
                // $this->db->Connect(false, $this->userName, $this->password, 'myTNS');
                
                break;
            case "sqlite":
                $this->db = ADONewConnection('sqlite3');
                $this->db->Connect($this->hostName); // e.g. c:\sqllite.db - sqlite will create if does not exist
                break;
            case "informix":
                $this->db = ADONewConnection('informix');        
                $this->db->Connect($this->hostName, $this->userName, $this->password, $this->databaseName) or C_Utility::fail("Error: Could not connect to the database", 500);
                break;
            case "informix72":
                $this->db = ADONewConnection('informix72');        
                $this->db->Connect($this->hostName, $this->userName, $this->password, $this->databaseName) or C_Utility::fail("Error: Could not connect to the database", 500);
                break;
            // the generic odbc presumes a correctly configured odbc.ini in /etc/
            case "odbc":
                $this->db = ADONewConnection($this->dbType);
                $dsn = "DSN=".$this->hostName.";uid=".$this->userName."; pwd=".$this->password;
                $this->db->Connect($dsn);
                break;
            case 'mysql_dsn':
                $dsn = 'mysqli://'. $this->userName .':'. $this->password .'@'. $this->hostName .'/'. $this->databaseName;
                $this->db = ADONewConnection($dsn);
                if(!empty($this->charset)) {
                    $this->db->Execute("SET NAMES '$this->charset'");
                }
                break;
            // default should be mysql and all other databases using the following form of connection
            default:    
                $this->db = ADONewConnection('mysqli'); // PHP 5.5 deprecates mysql extension. Switching to mysqli
                // $this->ssl_key    = "example.cert.pem";
                // $this->ssl_cert   = "example.cert.pem"; 
                // $this->ssl_ca     = "cacert.pem";
                // $this->ssl_capath = null; 
                // $this->ssl_cipher = null;
                $this->db->Connect($this->hostName, $this->userName, $this->password, $this->databaseName) or C_Utility::fail("Error: Could not connect to the database", 500);
                if(!empty($this->charset)) {
                    $this->db->Execute("SET NAMES '$this->charset'");
                }
        }            
    }
           
    // Desc: query database
    public function db_query($query_str, $input_arr = []){
        $this->db->SetFetchMode(ADODB_FETCH_BOTH);

        if(!empty($input_arr)){

            $result = $this->db->Execute($query_str, $input_arr) or C_Utility::fail(
                (C_Utility::is_debug()) ?
                        'C_Database->db_query() '.   "\n". $this->db->ErrorMsg() . "\n" . 'SQL: ' . $query_str :
                        'PHPGRID_ERROR:'. "\n". $this->db->ErrorMsg() . "\n", 500);
        }else {
            
            $result = $this->db->Execute($query_str) or C_Utility::fail(
                (C_Utility::is_debug()) ?
                    'C_Database->db_query() - '. "\n". $this->db->ErrorMsg() . "\n" . 'SQL: ' . $query_str :
                    'PHPGRID_ERROR:'. "\n". $this->db->ErrorMsg() . "\n", 500);
        }
        
        $this->result = $result;        
        return $result;
    }
    
    public function select_limit($query_str, $size, $starting_row, $input_arr = false){
        $this->db->SetFetchMode(ADODB_FETCH_BOTH);
        $result = $this->db->SelectLimit($query_str, $size, $starting_row, $input_arr) or C_Utility::fail(
            (C_Utility::is_debug())?
                'C_Database->select_limit() '. "\n". $this->db->ErrorMsg() ."\n". ' SQL: '. $query_str ."\n":
                'PHPGRID_ERROR:'. "\n". $this->db->ErrorMsg() . "\n", 500);

        $this->result = $result;        
        return $result;
    }
    
    // Desc: helper function to get array from select_limit function
    public function select_limit_array($query_str, $size, $starting_row){
        $result = $this->select_limit($query_str, $size, $starting_row);
        $resultArray = $result->GetArray();

        $this->result = $resultArray;
        return $resultArray;
    }
        
    // Desc: fetch a SINGLE record from database as row
    // Note: the parameter is passed as reference
    public function fetch_row(&$result){
        $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
        if(!$result->EOF){
             $rs = $result->fields;
             $result->MoveNext();        
             return $rs;
        }
    }
    
    // Desc: fetch a SINGLE record from database as array
    // Note: the parameter is passed as reference
    public function fetch_array(&$result){
        $ADODB_FETCH_MODE = ADODB_FETCH_BOTH;
        if(!$result->EOF){
             $rs = $result->fields;
             $result->MoveNext();   
             return $rs;
        }  
    }
    
    // Desc: fetch a SINGLE record from database as associative array
    // Note: the parameter is passed as reference
    public function fetch_array_assoc(&$result){
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        if(!$result->EOF){
             $rs = $result->fields;
             $result->MoveNext();  
             return $rs;
        }
    }    
        
    // Desc: number of rows query returned. Used by pagination. 
    public function num_rows($result){
        return $result->RecordCount();
        
        // SELECT COUNT(*) has better performance as it takes in count column indexes
        /*
        $totalCnt = 0;
        $result = $this->db_query('SELECT COUNT(*) FROM salaries');
        while($row = $this->fetch_array_assoc($result)) {
           $totalCnt = $row[0];    
        }
        
        return $totalCnt;
        */
        
    } 
    
    // Desc: helper function. query then, fetch the FIRST record from database as associative array
    public function query_then_fetch_array_first($query_str){
        $ADODB_FETCH_MODE = ADODB_FETCH_BOTH;
        $result = $this->db->Execute($query_str) or C_Utility::fail(
            (C_Utility::is_debug()) ?
                'C_Database->query_then_fetch_array_first() '. "\n". $this->db->ErrorMsg() . "\n" . 'SQL: ' . $query_str :
                'PHPGRID_ERROR: '. "\n".  $this->db->ErrorMsg(), 500);
        // die("Error: Could not execute query $query_str");
        if(!$result->EOF){
            $rs = $result->fields;
            $result->MoveNext();     
            return $rs;
        }
    }
    
    // Desc: number of data fields in the recordset
    public function num_fields($result){
        return $result->FieldCount();
    }
    
    // Desc: a specific field name (column name) with that index in the recordset
    public function field_name($result, $index){
        $obj_field = new \ADOFieldObject();
        $obj_field = $result->FetchField($index);

        // FIXED: Always return the column name (alias or original)
        // Aliases have empty orgname but still have valid name
        if (isset($obj_field->name)) {
            return $obj_field->name;  // Return alias name for display
        }

        return false;  // Only return false if name doesn't exist at all
    }

    /**
     * Check if a column can be used in WHERE clauses
     * Returns false for aliases/calculated columns
     *
     * @param mixed $result Query result
     * @param int $index Column index
     * @return bool True if column can be filtered, false otherwise
     */
    public function field_is_filterable($result, $index){
        $obj_field = new \ADOFieldObject();
        $obj_field = $result->FetchField($index);

        // Aliases have empty orgname - they cannot be used in WHERE clauses
        if (isset($obj_field->orgname) && $obj_field->orgname === '') {
            return false;  // This is an alias, not filterable
        }

        return true;  // Regular column, can be filtered
    }

    // Desc: the type of a specific field name (column name) with that index in the recordset
    public function field_nativetype($result, $index){
        $obj_field = new \ADOFieldObject();
        $obj_field = $result->FetchField($index);
        return isset($obj_field->type) ? $obj_field->type : "";
    }
   
    // Desc: the generic Meta type of a specific field name by index.      
    // Returns: 
    // C: Character fields that should be shown in a <input type="text"> tag.
    // X: Clob (character large objects), or large text fields that should be shown in a <textarea>
    // D: Date field
    // T: Timestamp field
    // L: Logical field (boolean or bit-field)
    // N: Numeric field. Includes decimal, numeric, floating point, and real.
    // I:  Integer field.
    // R: Counter (Access), Serial(PostgreSQL) or Autoincrement int field. Must be numeric.
    // B: Blob, or binary large objects.
    public function field_metatype($result, $index){
        $obj_field = new \ADOFieldObject();
        $obj_field = $result->FetchField($index);
        $type = $result->MetaType($obj_field->type, $obj_field->max_length);   // Since ADOdb 3.0, MetaType accepts $fieldobj as the first parameter, instead of $nativeDBType.
        
        // MySql datetime (not Date or Time) only. MetaType valued is returned from adodb-mysqli.inc.php
        // ref: https://framework.zend.com/apidoc/1.8/Zend_Amf/Parse/Zend_Amf_Parse_Resource_MysqliResult.html
        //      7 => "MYSQLI_TYPE_TIMESTAMP",
        //      10 => "MYSQLI_TYPE_DATE",
        //      11 => "MYSQLI_TYPE_TIME",
        //      12 => "MYSQLI_TYPE_DATETIME",
        //      13 => "MYSQLI_TYPE_YEAR",
        //      14 => "MYSQLI_TYPE_DATE",
        if ($this->dbType == 'mysql' && $type == 'T' && $obj_field->type == 11) {
            $type = 'TX';   // Mysql Time (exclusive)
        } elseif ($this->dbType == 'mysql' && $type == 'T' && $obj_field->type == 12) {
            $type = 'DT';   // Mysql datetime
        } elseif ($this->dbType == 'mysql' && $type == 'X' && $obj_field->binary > 0) {
            $type = 'B';    // longblob has type 'X' but binary > 0
        }

        // print_r($obj_field);

        return $type;              
    }
    
    // obtain meta column info as specific field in a table.e.g. auto increment, not null
    // file: adodb.$dbtype.inc.php - ADODB_$dbtype::ADOConnection.MetaColumns()
    // return false if col_name is not in table, else return metacolumn
    public function field_metacolumn($table, $col_name){
        $arr = array();   
        $arr =  $this->db->MetaColumns($table);

        $obj_field = new \ADOFieldObject();
        if(isset($arr[strtoupper($col_name)])){
            $obj_field = $arr[strtoupper($col_name)];
    //        print('<pre>');
    //        print_r($obj_field);
    //        print('</pre>');
            return $obj_field;                                        
        }else{
            return false;
        }
        


    }
    
    // Desc: return corresponding field index by field name
    public function field_index($result, $field_name){
        $field_count = $this->num_fields($result);
        $i=0;
        for($i=0;$i<$field_count;$i++){
            if($field_name == $this->field_name($result, $i))
                return $i;        
        }    
        return -1;
    }
    
    // Desc: the length of a speciifc field name (column name) with that index in the recordset
    public function field_len($result, $index){
        $obj_field = new \ADOFieldObject();
        $obj_field = $result->FetchField($index);
        return isset($obj_field->max_length) ? $obj_field->max_length : "";
    }

    // check SINGLE field datatype and add quotes around if it is a non-numeric field.
    // Returns "fieldname=<literal>"; the value is validated/escaped by sql_literal().
    function quote_field($sql, $fieldname, $fieldvalue){
        $rs         = $this->select_limit($sql, 1, 1);
        $fm_type    = $this->field_metatype($rs, $this->field_index($rs, str_replace('`', '', $fieldname)));

        return $fieldname ."=". $this->sql_literal($fm_type, $fieldvalue, array('I', 'N', 'R', 'L'));
    }

    // check MULTIPLE fields for datatype and add quotes around non-numeric fields.
    // SQL WHERE syntax for query multiple records with composite PK:
    //      where (A, B) in (('T1', 2010), ('T2', 2009), ('AG', 1992))
    function quote_fields(&$rs, $sql_key=array(), $key_value=array()){
        $pk_val_new = array();

        $fm_types = array();
        for($t=0; $t<count($sql_key); $t++){
            $fm_type   = $this->field_metatype($rs, $this->field_index($rs, str_replace('`', '', $sql_key[$t])));
            $fm_types[] = $fm_type;
        }

        for($i =0; $i < count($key_value); $i++){
            $pk_val_fields = explode(PK_DELIMITER, $key_value[$i]);

            $literals = array();
            for($j=0; $j < count($sql_key); $j++){
                $pk_val_fld = isset($pk_val_fields[$j]) ? $pk_val_fields[$j] : '';
                $literals[] = $this->sql_literal($fm_types[$j], $pk_val_fld, array('I', 'N', 'R'));
            }

            $pk_val_new[] = '('. implode(',', $literals) .')';
        }

        return $pk_val_new;
    }

    // Desc: render a key value as an SQL literal for a column of ADOdb metatype $fm_type.
    // Numeric columns only accept numeric values (anything else would be raw SQL, and
    // coercing it would silently target a different row); everything else is escaped
    // and quoted by the driver.
    private function sql_literal($fm_type, $value, $numeric_types){
        if(!in_array($fm_type, $numeric_types, true)){
            return $this->db->qstr((string)$value);
        }

        $value = trim((string)$value);
        if($fm_type == 'L' && in_array(strtolower($value), array('true', 'false'), true)){
            return $value;
        }
        if(!is_numeric($value)){
            throw new C_InvalidRequest('Invalid key value');
        }

        return $value;
    }

    // Desc: get original database field names in an array
    public function get_col_dbnames($result){
        $col_dbnames = array();
        $num_fields = $result->FieldCount();
        for($i = 0; $i < $num_fields; $i++) {

            $col_dbname = $this->field_name($result, $i);  // Now returns alias names too

            // CHANGED: Use new method to check if column is filterable
            // Aliases cannot be used in WHERE clauses
            if ($col_dbname && $this->field_is_filterable($result, $i)) {
                $col_dbnames[] = $col_dbname;  // Only include filterable columns
            }
        }

        return $col_dbnames;
    }

    // Get last insert id 
    public function Insert_ID(){
        return $this->db->Insert_ID();
    }

    public function GetInsertSQL($rs, $arrFields, $table) {
        return $this->db->GetInsertSQL($rs, $arrFields, false, ADODB_FORCE_NULL);
    }   

    public function GetUpdateSQL($rs, $arrFields, $table) {
        return $this->db->GetUpdateSQL($rs, $arrFields, true, false, ADODB_FORCE_NULL);
    }

    // get a list of DB column names in array
    public function get_columns($results){
        $field_count = $this->num_fields($results);
        $col_names = array();
        for($i = 0; $i < $field_count; $i++) {
            $col_name = $this->field_name($results, $i);
            $col_names[] = $col_name;
        }

        return $col_names;
    }


    // execute stored procedure using CALL and parameter binding.
    // OUT parameter is not supported currently. See stored_proc_out_param.php for workaround
    public function sp($callSpName, $inParams = []) {

        // force inParams to array
        if (!is_array($inParams)) $inParams = [$inParams];

        $results = $this->db_query("$callSpName", $inParams);

        $this->result = $results;

        return $results;

    }

}