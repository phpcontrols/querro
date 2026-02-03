<?php
namespace phpCtrl;

// if(!session_id()){ session_start();} // this is required for PHP that running on Windows
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
	}
	
	public function select_limit($query_str, $size, $starting_row){
	}
	
	// Desc: helper function to get array from select_limit function
	public function select_limit_array($query_str, $size, $starting_row){
	}
	
	// Desc: number of rows query returned
	public function num_rows($result){
	
	} 

	// Desc: number of data fields in the recordset
	public function num_fields($result){
	}
	
	// Desc: a specific field name (column name) with that index in the recordset
	public function field_name($result, $index){
	}
	
	// Desc: the generic Meta type of a specific field name by index.      
	public function field_metatype($result, $index){
		return 'C';
	}

	// Desc: return corresponding field index by field name
	public function field_index($result, $field_name){
		return -1;
	}	
}