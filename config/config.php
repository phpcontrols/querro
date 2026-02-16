<?php

//==================================================================================================
//  CONFIGURATIONS
//==================================================================================================

// APP DATABASE - Load from .env file
// All database configuration is now in .env for easier environment management
define('APP_DBHOST', getenv('DB_HOST') ?: 'localhost');
define('APP_DBUSER', getenv('DB_USER') ?: 'root');
define('APP_DBPASS', getenv('DB_PASS') ?: 'root');
define('APP_DBNAME', getenv('DB_NAME') ?: 'querro');
define('APP_URL', getenv('APP_URL') ?: 'https://app.querro.local');



//==================================================================================================
//         Do not change the following system settings unless you know what you are doing :)
//==================================================================================================

// PATHES & URLS
define('ABSPATH',                   dirname(dirname(__FILE__)) . '/');  // The absolute path of the application
define('CONFIG_DIR_NAME',           'config');                          // Configurations directory name
define('CONFIG_DIR',                ABSPATH.CONFIG_DIR_NAME.'/');       // Configurations path
define('CONFIG_FILE',               CONFIG_DIR.'config.php');           // Configurations file path
define('DATABASES_FILE',            CONFIG_DIR.'databases.php');        // Databases file path
define('ABSURL',                    '');                                // The absolute url of the application

// PHP INI CONFIGURATIONS
define("UPLOAD_MAX_FILESIZE",       ini_get('upload_max_filesize'));    // The maximum size of an uploaded file - 2M, 200M, ...
define("MAX_FILE_UPLOADS",          ini_get('max_file_uploads'));       // The maximum number of files allowed to be uploaded simultaneously - 20, 30, ...
define("POST_MAX_SIZE",             ini_get('post_max_size'));          // Max size of post data allowed - 8M, 200M, ...
define("MEMORY_LIMIT",              ini_get('memory_limit'));           // Maximum amount of memory in bytes that a script is allowed to allocate - 128M, 256M, ...
define("MAX_EXECUTION_TIME",        ini_get('max_execution_time'));     // Maximum time in seconds a script is allowed to run before it is terminated by the parser - 30, 60, 300, ...
define("MAX_INPUT_TIME",            ini_get('max_input_time'));         // Maximum time in seconds a script is allowed to parse input data, like POST and GET - 30, 60, 300, ... -1 means that max_execution_time is used instead
define("ALLOW_URL_FOPEN",           ini_get('allow_url_fopen'));        // If the server can access remote files through url - true or false
define("CURL_INIT",                 function_exists('curl_init'));      // If the curl function is installed or not - true or false