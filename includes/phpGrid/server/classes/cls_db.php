<?php
namespace phpCtrl;

if(!session_id()){ session_start();} // this is necessary for PHP that running on Windows

class C_Database{
	public $hostName;
	public $userName;
	public $password;
	public $databaseName;
	public $tableName;
	public $link;
	public $dbType;
	public $charset;
    public $db; 
    public $result;
	
	public function __construct($host, $user, $pass, $dbName, $db_type = "mysql", $charset=""){
		$this -> hostName = $host;
		$this -> userName = $user;
		$this -> password = $pass;
		$this -> databaseName = $dbName;
		$this -> dbType  = $db_type;
        $this -> charset = $charset;
		
		$this -> _db_connect();	
	}

	// Desc: connect to database
	public function _db_connect(){
        $this->db = ADONewConnection('mysqli'); // PHP 5.5 deprecates mysql extension. Switching to mysqli
        $this->db->Connect($this->hostName, $this->userName, $this->password, $this->databaseName) or die("Error: Could not connect to the database");
        if(!empty($this->charset)) {
            $this->db->Execute("SET NAMES '$this->charset'");
        }			
	}

    // Desc: query database
    public function db_query($query_str, $input_arr = []){
        $this->db->SetFetchMode(ADODB_FETCH_BOTH);

        if(!empty($input_arr)){
            $result = $this->db->Execute($query_str, $input_arr) or die(
                (C_Utility::is_debug()) ?
                        'C_Database->db_query() '.   "\n". $this->db->ErrorMsg() . "\n" . 'SQL: ' . $query_str :
                        'PHPGRID_ERROR:'. "\n". $this->db->ErrorMsg() . "\n");
        }else {
            $result = $this->db->Execute($query_str) or die(
                (C_Utility::is_debug()) ?
                    'C_Database->db_query() - '. "\n". $this->db->ErrorMsg() . "\n" . 'SQL: ' . $query_str :
                    'PHPGRID_ERROR:'. "\n". $this->db->ErrorMsg() . "\n");
        }
        
        $this->result = $result;        
        return $result;
    }
	
	public function select_limit($query_str, $size, $starting_row){
		$this->db->SetFetchMode(ADODB_FETCH_BOTH);
		$result = $this->db->SelectLimit($query_str, $size, $starting_row) or die(
            (C_Utility::is_debug())?
                "\n". 'PHPGRID_DEBUG: C_Database->select_limit() - '. $this->db->ErrorMsg() ."\n":
                "\n". 'PHPGRID_ERROR: Could not execute query. Error 102' ."\n");

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
		
	// Desc: number of rows query returned
	public function num_rows($result){
        return $result->RecordCount();
	} 
	
	// Desc: helper function. query then, fetch the FIRST record from database as associative array
	public function query_then_fetch_array_first($query_str){
		$ADODB_FETCH_MODE = ADODB_FETCH_BOTH;
		$result = $this->db->Execute($query_str) or die('PHPGRID_ERROR: query_then_fetch_array_first() - '. $this->db->ErrorMsg());
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

        if (isset($obj_field->name)) {
            return $obj_field->name;  // Return alias name for display
        }

        return false;  // Only return false if name doesn't exist at all
    }

    /**
     * Desc: Check if a column can be used in WHERE clauses
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
    public function field_metatype($result, $index){
        $obj_field = new \ADOFieldObject();
        $obj_field = $result->FetchField($index);
        $type = $result->MetaType($obj_field->type, $obj_field->max_length);   // Since ADOdb 3.0, MetaType accepts $fieldobj as the first parameter, instead of $nativeDBType.    
                
        return $type;              
    }
    
    // obtain meta column info as specific field in a table.e.g. auto increment, not null
    // return false if col_name is not in table, else return metacolumn
    public function field_metacolumn($table, $col_name){
        $arr = array();   
        $arr =  $this->db->MetaColumns($table);

        $obj_field = new \ADOFieldObject();
        if(isset($arr[strtoupper($col_name)])){
            $obj_field = $arr[strtoupper($col_name)];
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
	function quote_field($sql, $fieldname, $fieldvalue){
		$rs         = $this->select_limit($sql, 1, 1);
        $fm_type    = $this->field_metatype($rs, $this->field_index($rs, $fieldname));
		switch ($fm_type) {
			case 'I':
			case 'N':
			case 'R':
			case 'L':
				$qstr = $fieldname ."=". $fieldvalue;  
				break;
			default:
				$qstr = $fieldname ."='". $fieldvalue ."'";    
				break;
		}
		
		return $qstr;
	}

    // check MULTIPLE fields for datatype and add quotes around non-numeric fields.
    // SQL WHERE syntax for query multiple records with composite PK:
    //      where (A, B) in (('T1', 2010), ('T2', 2009), ('AG', 1992))
    function quote_fields(&$rs, $sql_key=array(), $key_value=array()){
        $pk_val_new = array();

        $fm_types = array();
        for($t=0; $t<count($sql_key); $t++){
            $fm_type   = $this->field_metatype($rs, $this->field_index($rs, $sql_key[$t]));
            $fm_types[] = $fm_type;
        }

        for($i =0; $i < count($key_value); $i++){
            $pk_val_fields = explode(PK_DELIMITER, $key_value[$i]);

            for($j=0; $j < count($sql_key); $j++){
                $fm_type = $fm_types[$j];
                if($fm_type != 'I' && $fm_type != 'N' && $fm_type != 'R'){
                    $pk_val_fld = "'" . $pk_val_fields[$j] ."'";
                }else{
                    $pk_val_fld = $pk_val_fields[$j];
                }
                $pk_val_fields[$j] = $pk_val_fld;
            }

            $pk_val_new[] = '('. implode(',', $pk_val_fields) .')';
        }

        return $pk_val_new;
    }

	
	// Desc: get original database field names in an array
	public function get_col_dbnames($result){
		$col_dbnames = array();
		$num_fields = $result->FieldCount();
		for($i = 0; $i < $num_fields; $i++) {
			$col_dbname = $this->field_name($result, $i);             
			$col_dbnames[] = $col_dbname;        
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
}