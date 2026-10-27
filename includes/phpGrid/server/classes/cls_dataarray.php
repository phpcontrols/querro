<?php
namespace phpCtrl;

// if(!session_id()){ session_start();} // this is nessecory for PHP that running on Windows
class C_DataArray{
	public $data;
	public $dbType;
	public $session;
	
	public function __construct($data=array()){
		$this -> dbType  = 'local';
		$this -> data = $data;
		$this -> session = C_SessionMaker::getSession(FRAMEWORK);
	}
		
	// Desc: query database
	public function db_query($query_str){
		return $this->data;
	}
	
	public function select_limit($query_str, $size, $starting_row){
		if(empty($this->data)) die('No data found.');

		return $this->data[$size-1];
	}
	
	// Desc: helper function to get array from select_limit function
	public function select_limit_array($query_str, $size, $starting_row){
		return $this->select_limit($query_str, $size, $starting_row);
	}
	
	// Desc: number of rows query returned
	public function num_rows($result){
		return count($this->data);
		
	} 

	// Desc: number of data fields in the recordset
	public function num_fields($result){
		return (count($this->data,1)/count($this->data,0))-1;
	}
	
	// Desc: a specific field name (column name) with that index in the recordset
	public function field_name($result, $index){
		$keys = array_keys($result);
		return $keys[$index];
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
		return 'C';
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
}