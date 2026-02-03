<?php
namespace phpCtrl;

// Desc: utility/tool shared functions
class C_Utility{
    
    public static function add_slashes($str){
        return addslashes($str);
    }
    
     // Indents JSON string to be more readable
     public static function indent_json($json) {     
        $result    = '';
        $pos       = 0;
        $strLen    = strlen($json);
        $indentStr = '  ';
        $newLine   = "\n";
     
        for($i = 0; $i <= $strLen; $i++) {
            
            // Grab the next character in the string
            $char = substr($json, $i, 1);
            
            // If this character is the end of an element, 
            // output a new line and indent the next line
            if($char == '}' || $char == ']') {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }
            
            // Add the character to the result string
            $result .= $char;
     
            // If the last character was the beginning of an element, 
            // output a new line and indent the next line
            if ($char == '{' || $char == '[') {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
        }
     
        return $result;
    }
            
    // Convert boolean to literal string used by jqgrid script;
    public static function literalBool($boolValue){
        return ($boolValue)?'true':'false';
    }

    // Generate grid rowid from single or composite PK or a simple random number if no PK defined (e.g. complex query)
    public static function gen_rowids($arr=array(), $keys = array()){
        $rowids = '';

        if(is_array($keys)) { 
            foreach($keys as $val){
                $val = str_replace('`', '', $val);

                if (isset($arr[$val]) && $arr[$val] != '') {
                    $rowids .= $arr[$val] .PK_DELIMITER;
                } else {
                    $rowids .= mt_rand(1, 10000) . PK_DELIMITER;
                }                
            }
        }
        $rowids = substr($rowids, 0, -3);   // remove the last PK_DELIMITER

        return $rowids;
    }

    public static function is_debug(){
        return defined('DEBUG')?DEBUG:false;
    }
}