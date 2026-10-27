<?php
namespace phpCtrl;

// Desc: a request rejected by phpGrid's own input validation (unknown column, operator,
// key value...). Its message is written for the client and is safe to return as-is;
// see C_Utility::endpoint_error_response().
class C_InvalidRequest extends \InvalidArgumentException{
}

// Desc: utility/tool shared functions
class C_Utility{

    // Desc: ends an endpoint request with an error message and HTTP status: 400 bad request
    // (default), 403 not permitted, 500 server/database error, 502 remote service error.
    // Messages should start with PHPGRID_ERROR so the grid's client code displays them.
    public static function fail($message, $status = 400){
        if(!headers_sent()){
            http_response_code($status);
        }
        exit($message);
    }

    // Desc: called at the top of each AJAX endpoint (data.php, edit.php, ...). Turns
    // uncaught exceptions into a clean "PHPGRID_ERROR: ..." response with an error status
    // instead of PHP's fatal-error output (paths, stack trace, input values). Details go
    // to the server error log. Not registered by phpGrid.php itself, so host pages keep
    // their own exception handler.
    public static function handle_endpoint_errors(){
        set_exception_handler(function($e){
            error_log('phpGrid: '. $e);
            $response = C_Utility::endpoint_error_response($e, C_Utility::is_debug());
            if(!headers_sent()){
                http_response_code($response['status']);
                header('Content-Type: text/plain; charset=utf-8');
            }
            echo $response['body'];
        });
    }

    // Desc: status code and body for an uncaught endpoint exception. Only our own
    // validation messages (C_InvalidRequest) reach the client; DEBUG adds the details.
    public static function endpoint_error_response($e, $debug){
        if($e instanceof C_InvalidRequest){
            $response = array('status' => 400, 'body' => 'PHPGRID_ERROR: '. $e->getMessage());
        }else{
            $response = array('status' => 500, 'body' => 'PHPGRID_ERROR: The request could not be completed.');
        }
        if($debug){
            $response['body'] .= "\n". get_class($e) .': '. $e->getMessage() .' in '. $e->getFile() .':'. $e->getLine();
        }

        return $response;
    }

    // Desc: Utility function to add slashes - add slashes only the magic_quotes_gpc is set to off
    // It is strongly recommended that to turn off the magic quotes in the configuration setting
    public static function add_slashes($str){
        //if (get_magic_quotes_gpc() == 1) {
        //    return ($str);
        //}else{ 
            return (addslashes($str));
        //}
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
            #bug: it adds newline wronly when the value is comma(,). Removed for now
            //if ($char == ',' || $char == '{' || $char == '[') {
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

    // convert boolean to literal string used by jqgrid script;
    public static function literalBool($boolValue){
        return ($boolValue)?'true':'false';
    }

    // Generate grid rowid from single or composite PK or a simple random number if no PK defined (e.g. complex query)
    public static function gen_rowids($arr=array(), $keys = array()){
        $rowids = '';
        if(is_array($keys)) { 

            // set all fields to the same case (UPPER). SELECT * will return whatever case is used in db schema
            if (PHPGRID_DB_TYPE == 'db2') {
                $keys = array_map('strtoupper', $keys);
                $arr = array_change_key_case($arr, CASE_UPPER);
            }

            foreach($keys as $val){
                $val = self::row_field_name($arr, $val);
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

    // Desc: the row field a key refers to. Keys may be written as SQL identifiers -
    // `orderNumber`, "orderNumber", [orderNumber], o.orderNumber - but fetched rows are
    // keyed by the bare column name.
    private static function row_field_name($arr, $key){
        if(array_key_exists($key, $arr)){
            return $key;
        }
        $parts = explode('.', (string)$key);
        $bare  = trim(end($parts), '`"[] ');

        return array_key_exists($bare, $arr) ? $bare : $key;
    }

    // Desc: most rows per data.php request a grid's configuration allows: the largest of its
    // page sizes ($row_nums) and pager options ($row_lists, e.g. "10000:All"). 0 = no cap,
    // used when any page size is unlimited (0 or negative, e.g. loadonce grids).
    public static function max_page_size(array $row_nums, array $row_lists){
        $max = 0;
        foreach($row_nums as $n){
            if((int)$n <= 0){
                return 0;
            }
            $max = max($max, (int)$n);
        }
        foreach($row_lists as $list){
            foreach((array)$list as $option){
                $n = explode(':', (string)$option)[0];
                if(ctype_digit($n)){
                    $max = max($max, (int)$n);
                }
            }
        }

        return $max;
    }

    // Desc: rows per page for a request. Non-numeric input falls back to the default;
    // with a cap ($max > 0), larger and "no limit" (<= 0) requests are clamped to it.
    public static function page_limit($requested, $max, $default = 20){
        $limit = (is_string($requested) || is_int($requested)) && preg_match('/^-?\d+$/', (string)$requested) ? (int)$requested : $default;
        if($max > 0 && ($limit <= 0 || $limit > $max)){
            $limit = $max;
        }

        return $limit;
    }

    // Desc: 1-based page number from the request; anything invalid is page 1.
    public static function page_number($requested){
        $page = (is_string($requested) || is_int($requested)) && ctype_digit((string)$requested) ? (int)$requested : 1;

        return max(1, $page);
    }

    // Desc: total-row query for pagination. Wraps the grid query so the database counts
    // instead of PHP fetching every row. The query must not end in ORDER BY (SQL Server
    // rejects it in a derived table); no AS before the alias (Oracle rejects it).
    public static function count_sql($sql){
        return 'SELECT COUNT(*) FROM ('. $sql .') phpgrid_count';
    }

    // Desc: a file name not yet used in $dir: "photo.png", then "photo-1.png", "photo-2.png"...
    // so an upload never overwrites another record's file.
    public static function unique_filename($dir, $name){
        $dot  = strrpos($name, '.');
        $base = ($dot === false || $dot === 0) ? $name : substr($name, 0, $dot);
        $ext  = ($dot === false || $dot === 0) ? '' : substr($name, $dot);

        $candidate = $name;
        for($i = 1; file_exists($dir . $candidate); $i++){
            $candidate = $base .'-'. $i . $ext;
        }

        return $candidate;
    }

    public static function is_debug(){
        return defined('DEBUG')?DEBUG:false;
    }

    // simple encryption decription using AES-256-CBC method
    // credit: https://nazmulahsan.me/simple-two-way-function-encrypt-decrypt-string/
    public static function simple_crypt( $data, $secret_key, $secret_iv, $action = 'e' ) {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

        if( $action == 'e' ) {
            $output = base64_encode( openssl_encrypt( $data, $encrypt_method, $key, 0, $iv ) );
        }
        else if( $action == 'd' ){
            $output = openssl_decrypt( base64_decode( $data ), $encrypt_method, $key, 0, $iv );
        }

        return $output;
    }

    //  "white list" where we only list allowed values. Can be used for preventing SQL injection
    // ref: https://phpdelusions.net/pdo_examples/order_by
    public static function white_list(&$value, $allowed, $message) {

        // drag and drop grouping has $value like 'status asc, 1', only need the first word.
        // (string): null is rejected like any other unlisted value (explode(null) is deprecated)
        $value = explode(',', (string)$value)[0];
        $value = str_replace(' asc', '', $value);
        $value = str_replace(' desc', '', $value);

        $key = array_search($value, $allowed, true);

        // 1 is the default value for sort  name used after ORDERBY
        // but remember everything thru $_GET is a string where 
        if ($key === false && $value !== '1') {
            throw new C_InvalidRequest($message);
        } else {
            return $value;
        }
    }

}