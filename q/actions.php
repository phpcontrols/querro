<?php
include_once(__DIR__ ."/../includes/phpGrid/conf.php");
include_once(__DIR__ ."/../includes/DatabaseSchemaInterface.php");
include_once(__DIR__ ."/../includes/MySQLSchemaAdapter.php");

use ParseCsv\Csv;
use OzdemirBurak\JsonCsv\File\Json;

use phpCtrl\C_Database as C_Database;

/**
 * Factory function to get the appropriate schema adapter based on database type
 *
 * @param Database $db Database connection object
 * @return DatabaseSchemaInterface Schema adapter instance
 */
function getSchemaAdapter(Database $db): DatabaseSchemaInterface
{
    return new MySQLSchemaAdapter($db);
}

// Execute the action function
if (isset($_POST['action']) and $_POST['action'] != '')
    $action = $_POST['action'];
elseif (isset($_GET['action']) and $_GET['action'] != '')
    $action = $_GET['action'];

if (function_exists($action))
    call_user_func($action);

exit;

// ===================================================================================
//      GET MAPPING TABLE
// ===================================================================================

function getColumns()
{
    global $_databases;

    $db_id = $_POST['db_id'];
    $table_id = $_POST['table_id'];

    $core = new Core($_databases);
    $res_columns = $core->getGoodColumns($db_id, $table_id);

    if (!$res_columns) {
        $res["status"] = 'error'; // warning, error, success
        $res["message"] = 'Could not get the columns.';
    } else {
        $res["status"] = 'success';
        $res["message"] = 'Data selected from database';
        $res["data"]["names"] = $res_columns['names'];
        $res["data"]["labels"] = $res_columns['labels'];
    }

    echo json_encode($res);
}

// ===================================================================================
//      SAVE CONFIGURATIONS
// ===================================================================================

function save_configurations()
{    
    $accountId  = $_SESSION['AccountId'] or die('Missing account ID.');

    // DATABASES
    $databases = json_decode($_POST['databases']);

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new \mysqli(APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
    $conn->set_charset('utf8mb4');

    try{

        $conn->autocommit(false);
        $conn->begin_transaction();
    
        // TODO - change to update 
        // remove all previously saved db connections first
        $stmt = $conn->prepare("DELETE FROM `dbs` WHERE account_id = ? ");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();

        // re-insert dbs based on what is currently set in front-end settings.php
        foreach ($databases as $dkey => $database) {

            $label = $database -> label;   
            $name = $database -> name;   
            $server = $database -> server;  
            $username = $database -> username;
            $password = $database -> password;
            $port = $database -> port;
            $encoding = $database -> encoding;
            $active = ($database -> active) ? 1 : 0;
            $type = $database -> type;
            $tables = json_encode($database -> tables);  

            $stmt = $conn->prepare("INSERT INTO dbs(`account_id`, `label`, `name`, `server`, `username`, `password`, `port`, `encoding`, `active`, `type`, `tables`)
                VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssss", $accountId, $label, $name, $server, $username, $password, $port, $encoding, $active, $type, $tables);
            $stmt->execute();

        }

        $conn->commit();

        // Invalidate database cache so changes take effect immediately
        unset($_SESSION['_databases_cache']);
        unset($_SESSION['_databases_cache_account']);

        $res["status"] = 'success';
        $res["message"] = 'Configurations saved';

    } catch (\mysqli_sql_exception $ex) {

        $conn->rollback();

        $res["status"] = 'error';
        $res["message"] = $ex->getMessage() . '<br><br>Trace: '. $ex->getTraceAsString();

    } finally {

        $conn->autocommit(true);

        isset($stmt) && $stmt->close();
        isset($conn) && $conn->close();

    }

    echo json_encode($res);
    
}


// Return database-specific charset based on database type
function settings_getDBCharset()
{
    $res["status"] = 'success';
    $res["data"]['db_charset'] = 'utf8mb4';
    echo json_encode($res);
}

// get table list from the actual database
function settings_getAllTables()
{
    $db_type = isset($_POST['db_type']) ? $_POST['db_type'] : 'mysql';
    $db_port = isset($_POST['db_port']) ? $_POST['db_port'] : 3306;
    $db_encoding = isset($_POST['db_encoding']) ? $_POST['db_encoding'] : 'utf8mb4';

    $core = new Core();
    $res = [];

    try {
        // Reduce connection timeout (in seconds) from default (60s)
        ini_set('mysql.connect_timeout', 5);
        ini_set('default_socket_timeout', 5);

        // Create connection with database type support
        $db = Database::initialize($db_type, [
            $_POST['db_username'],
            $_POST['db_password'],
            $_POST['db_name'],
            $_POST['db_server'],
            $db_port,
            $db_encoding,
            $db_type
        ]);

        $conn = $db->quick_connect(
            $_POST['db_username'],
            $_POST['db_password'],
            $_POST['db_name'],
            $_POST['db_server'],
            $db_port,
            $db_encoding,
            $db_type
        );

        if (!$conn) {
            throw new Exception('Database connection failed.');
        }

        // Use schema adapter instead of hardcoded MySQL query
        $schemaAdapter = getSchemaAdapter($db);
        $tables = $schemaAdapter->getTables($_POST['db_name']);

        $result = [];
        foreach ($tables as $table) {
            $result[$table] = $table;
        }

        $res["status"] = 'success';
        $res["data"] = $result;

    } catch (Exception $ex) {
        $res["status"] = 'error';
        $res["msg"] = $ex->getMessage();
    }

    echo json_encode($res);
}

// get table list from dbs
function query_getAllTables()
{
    $accountId  = $_SESSION['AccountId'] or die('Missing account ID.');

    // TODO - hard coded port
    $db_port = isset($_POST['db_port']) ? $_POST['db_port'] : 3306;
    $db = $_POST["db_name"] .'@'. $_POST["db_server"] .':'. $db_port;

    $conn = new \mysqli(APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

    $res = [];

    try {
        
        $stmt = $conn->prepare("SELECT *  FROM `dbs` WHERE CONCAT(`dbs`.`name`, '@', `dbs`.`server`, ':', `dbs`.`port`) = ? AND dbs.account_id = ? LIMIT 1");
        $stmt->bind_param("si", $db, $accountId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_array(MYSQLI_ASSOC);
    
        $tables = array_keys(json_decode($row['tables'], true));

        $res["status"] = 'success';
        $res["data"] = $tables;

        $stmt->close();

    } catch (Exception $ex) {
        $res["status"] = 'error';
        $res["msg"] = $ex->getMessage();    }

    $conn->close();

    echo json_encode($res);
}

function settings_getAllColumns()
{
    global $_databases;

    $database       = $_POST['database'];
    $table          = $_POST['table'];
    $table_name     = isset($_databases[$database]['tables'][$table]['properties'][0]['name']) ? $_databases[$database]['tables'][$table]['properties'][0]['name'] : $table;
    $db_type        = isset($_POST['db_type']) ? $_POST['db_type'] : 'mysql';
    $db_port        = isset($_POST['db_port']) ? $_POST['db_port'] : 3306;
    $db_encoding    = isset($_POST['db_encoding']) ? $_POST['db_encoding'] : 'utf8mb4';

    $core = new Core();
    $res = [];

    try {
        // Reduce connection timeout (in seconds) from default (60s)
        ini_set('mysql.connect_timeout', 5);
        ini_set('default_socket_timeout', 5);

        // Create connection with database type support
        $db = Database::initialize($db_type, [
            $_POST['db_username'],
            $_POST['db_password'],
            $_POST['db_name'],
            $_POST['db_server'],
            $db_port,
            $db_encoding,
            $db_type
        ]);

        $conn = $db->quick_connect(
            $_POST['db_username'],
            $_POST['db_password'],
            $_POST['db_name'],
            $_POST['db_server'],
            $db_port,
            $db_encoding,
            $db_type
        );

        if (!$conn) {
            throw new Exception('Database connection failed.');
        }

        // Use schema adapter instead of hardcoded MySQL query
        $schemaAdapter = getSchemaAdapter($db);
        $columns = $schemaAdapter->getColumns($_POST['db_name'], $table_name);

        $colMeta = settings_getAllColumnMeta($table_name, $columns);

        $res["status"] = 'success';
        $res["data"] = $columns;
        $res["colMeta"] = $colMeta;

    } catch (Exception $ex) {
        $res["status"] = 'error';
        $res["msg"] = $ex->getMessage();
    }

    echo json_encode($res);
}

function settings_getAllColumnMeta($table, $columns) {
    $db_encoding = isset($_POST['db_encoding']) ? $_POST['db_encoding'] : 'utf8mb4';

    $db = new C_Database($_POST['db_server'], $_POST['db_username'], $_POST['db_password'], $_POST['db_name'], 'mysql', $db_encoding);
    $result = $db->select_limit("SELECT * FROM ${table}", 1, 0);

    $colMeta = [];
    foreach($columns as $column) {
        $pks = $db->db->metaPrimaryKeys($table);
        $metaType = $db->field_metatype($result, $db->field_index($result, $column));

        // tables might not have PK (even though they really should do!)
        if ($pks && in_array($column, $pks)) {
            $colMeta[$column] = ['pk' => true, 'metaType' => $metaType];
        } else {
            $colMeta[$column] = ['metaType' => $metaType];
        }
    }

    return $colMeta;
}


function query_getAllColumns()
{
    $accountId  = $_SESSION['AccountId'] or die('Missing account ID.');
    $db         = $_POST['database'];
    $table      = $_POST['table'];

    $conn = new \mysqli(APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

    $res = [];

    try {
        
        $stmt = $conn->prepare("SELECT *  FROM `dbs` WHERE CONCAT(`dbs`.`name`, '@', `dbs`.`server`, ':', `dbs`.`port`) = ? AND dbs.account_id = ? LIMIT 1");
        $stmt->bind_param("si", $db, $accountId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_array(MYSQLI_ASSOC);
    
        $columnNames = array_keys(json_decode($row['tables'], true));
        $columnMetaAll = json_decode($row['tables'], true)[$table]['properties'][0]['columns'];

        $colMeta = [];

        // we need colMeta to be in format ['col' => type ]
        foreach($columnMetaAll as $col) {
            $colMeta[$col['name']] = $col['type'];
        }

        $res["status"] = 'success';
        $res["data"] = $columnNames;
        $res["colMeta"] = $colMeta;

        $stmt->close();

    } catch (Exception $ex) {
        $res["status"] = 'error';
        $res["msg"] = $ex->getMessage();
    }

    $conn->close();

    echo json_encode($res);
}

// ===================================================================================
//      AI NATURAL LANGUAGE TO SQL
// ===================================================================================

/**
 * Generate SQL from natural language query
 */
function ai_generateSQL()
{
    header('Content-Type: application/json');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $accountId = $_SESSION['AccountId'] ?? die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));
    $userId = $_SESSION['UserId'] ?? $accountId;

    global $_databases;

    $naturalLanguageQuery = $_POST['natural_language_query'] ?? '';
    $databaseId = $_POST['database_id'] ?? '';

    $res = [];

    try {
        if (empty($naturalLanguageQuery)) {
            throw new Exception('Natural language query is required');
        }

        if (empty($databaseId)) {
            throw new Exception('Database selection is required');
        }

        // Get AI settings for this account
        $host = APP_DBHOST === 'localhost' ? '127.0.0.1' : APP_DBHOST;
        $conn = new \mysqli($host, APP_DBUSER, APP_DBPASS, APP_DBNAME);
        $stmt = $conn->prepare("SELECT openai_api_key, openai_model FROM ai_settings WHERE account_id = ? AND active = 1");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $aiSettings = $result->fetch_assoc();
        $stmt->close();

        if (!$aiSettings || empty($aiSettings['openai_api_key'])) {
            throw new Exception('OpenAI API key not configured. Please add your API key in Settings > AI Assistant.');
        }

        // Decrypt API key
        $apiKey = decryptApiKey($aiSettings['openai_api_key']);
        $model = $aiSettings['openai_model'] ?? 'gpt-4o';

        // Get database schema context
        if (!isset($_databases[$databaseId])) {
            throw new Exception('Database not found');
        }

        $dbConfig = $_databases[$databaseId];
        $schemaContext = buildSchemaContext($dbConfig, $databaseId);

        // Call OpenAI API
        $startTime = microtime(true);
        $sqlResult = callOpenAIAPI($apiKey, $model, $naturalLanguageQuery, $schemaContext, $dbConfig['type']);
        $endTime = microtime(true);
        $executionTimeMs = round(($endTime - $startTime) * 1000);

        // Log usage
        logAIUsage($accountId, $userId, $databaseId, $naturalLanguageQuery,
                   $sqlResult['sql'], $model, $sqlResult['tokens'] ?? null,
                   $executionTimeMs, true, null);

        $conn->close();

        $res['status'] = 'success';
        $res['data'] = [
            'sql' => $sqlResult['sql'],
            'explanation' => $sqlResult['explanation'] ?? null
        ];

    } catch (Exception $ex) {
        // Log failed attempt
        if (isset($accountId) && isset($userId) && isset($databaseId)) {
            logAIUsage($accountId, $userId, $databaseId, $naturalLanguageQuery ?? '',
                       null, $model ?? 'unknown', null, null, false, $ex->getMessage());
        }

        $res['status'] = 'error';
        $res['message'] = $ex->getMessage();
    }

    echo json_encode($res);
}

/**
 * Save AI settings
 */
function ai_saveSettings()
{
    header('Content-Type: application/json');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $accountId = $_SESSION['AccountId'] ?? die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));

    $apiKey = $_POST['openai_api_key'] ?? '';
    $model = $_POST['openai_model'] ?? 'gpt-4o';

    $res = [];

    try {
        if (empty($apiKey)) {
            throw new Exception('API key is required');
        }

        // Validate API key format
        if (!preg_match('/^sk-[A-Za-z0-9_-]{20,}$/', $apiKey)) {
            throw new Exception('Invalid API key format');
        }

        // Encrypt API key
        $encryptedKey = encryptApiKey($apiKey);

        $host = APP_DBHOST === 'localhost' ? '127.0.0.1' : APP_DBHOST;
        $conn = new \mysqli($host, APP_DBUSER, APP_DBPASS, APP_DBNAME);

        // Upsert settings
        $stmt = $conn->prepare("
            INSERT INTO ai_settings (account_id, openai_api_key, openai_model, active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                openai_api_key = VALUES(openai_api_key),
                openai_model = VALUES(openai_model),
                active = 1
        ");
        $stmt->bind_param("iss", $accountId, $encryptedKey, $model);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        $res['status'] = 'success';
        $res['message'] = 'Settings saved successfully';

    } catch (Exception $ex) {
        $res['status'] = 'error';
        $res['message'] = $ex->getMessage();
    }

    echo json_encode($res);
}

/**
 * Get AI settings
 */
function ai_getSettings()
{
    header('Content-Type: application/json');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $accountId = $_SESSION['AccountId'] ?? die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));

    $res = [];

    try {
        $host = APP_DBHOST === 'localhost' ? '127.0.0.1' : APP_DBHOST;
        $conn = new \mysqli($host, APP_DBUSER, APP_DBPASS, APP_DBNAME);

        $stmt = $conn->prepare("SELECT openai_api_key, openai_model FROM ai_settings WHERE account_id = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($settings) {
            // Mask API key for display (show last 4 chars only)
            $decryptedKey = decryptApiKey($settings['openai_api_key']);
            $settings['openai_api_key'] = '***' . substr($decryptedKey, -4);
        }

        $res['status'] = 'success';
        $res['data'] = $settings;

    } catch (Exception $ex) {
        $res['status'] = 'error';
        $res['message'] = $ex->getMessage();
    }

    echo json_encode($res);
}

/**
 * Test OpenAI connection
 */
function ai_testConnection()
{
    header('Content-Type: application/json');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $accountId = $_SESSION['AccountId'] ?? die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));

    $apiKey = $_POST['openai_api_key'] ?? '';

    $res = [];

    try {
        if (empty($apiKey)) {
            throw new Exception('API key is required');
        }

        // Test with simple completion
        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            throw new Exception($errorData['error']['message'] ?? 'API key validation failed');
        }

        $res['status'] = 'success';
        $res['message'] = 'Connection successful';

    } catch (Exception $ex) {
        $res['status'] = 'error';
        $res['message'] = $ex->getMessage();
    }

    echo json_encode($res);
}

/**
 * Get usage log
 */
function ai_getUsageLog()
{
    header('Content-Type: application/json');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $accountId = $_SESSION['AccountId'] ?? die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));

    $res = [];

    try {
        $host = APP_DBHOST === 'localhost' ? '127.0.0.1' : APP_DBHOST;
        $conn = new \mysqli($host, APP_DBUSER, APP_DBPASS, APP_DBNAME);

        $stmt = $conn->prepare("
            SELECT natural_language_input, model_used, success, created_at
            FROM ai_query_log
            WHERE account_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();

        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }

        $stmt->close();
        $conn->close();

        $res['status'] = 'success';
        $res['data'] = $logs;

    } catch (Exception $ex) {
        $res['status'] = 'error';
        $res['message'] = $ex->getMessage();
    }

    echo json_encode($res);
}

// ===================================================================================
//      HELPER FUNCTIONS
// ===================================================================================

/**
 * Build schema context for OpenAI prompt
 */
function buildSchemaContext($dbConfig, $databaseId)
{
    $dbType = $dbConfig['type'] ?? 'mysql';
    $dbName = $dbConfig['name'];
    $tables = $dbConfig['tables'] ?? [];

    $schemaContext = [
        'database_type' => $dbType,
        'database_name' => $dbName,
        'tables' => []
    ];

    // Build table/column structure
    foreach ($tables as $tableName => $tableConfig) {
        if (!isset($tableConfig['active']) || !$tableConfig['active']) {
            continue;
        }

        $tableInfo = [
            'name' => $tableName,
            'columns' => []
        ];

        if (isset($tableConfig['properties'][0]['columns'])) {
            foreach ($tableConfig['properties'][0]['columns'] as $column) {
                if (!isset($column['active']) || !$column['active']) {
                    continue;
                }

                $tableInfo['columns'][] = [
                    'name' => $column['name'],
                    'type' => $column['type'] ?? 'unknown',
                    'is_primary_key' => isset($column['pk']) && $column['pk']
                ];
            }
        }

        $schemaContext['tables'][] = $tableInfo;
    }

    // Limit schema size for token optimization
    if (count($schemaContext['tables']) > 50) {
        foreach ($schemaContext['tables'] as &$table) {
            $table['columns'] = array_filter($table['columns'], function($col) {
                return $col['is_primary_key'];
            });
        }
    }

    return $schemaContext;
}

/**
 * Call OpenAI API
 */
function callOpenAIAPI($apiKey, $model, $naturalLanguageQuery, $schemaContext, $dbType)
{
    // Build system prompt
    $systemPrompt = buildSystemPrompt($schemaContext, $dbType);

    // Build user prompt
    $userPrompt = "Convert this natural language query to SQL: " . $naturalLanguageQuery;

    // API request
    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ],
        'temperature' => 0.1,
        'max_tokens' => 1000
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        throw new Exception($errorData['error']['message'] ?? 'OpenAI API request failed');
    }

    $responseData = json_decode($response, true);

    if (!isset($responseData['choices'][0]['message']['content'])) {
        throw new Exception('Invalid response from OpenAI');
    }

    $content = $responseData['choices'][0]['message']['content'];

    // Extract SQL from response
    $sql = extractSQLFromResponse($content);

    return [
        'sql' => $sql,
        'explanation' => $content,
        'tokens' => $responseData['usage']['total_tokens'] ?? null
    ];
}

/**
 * Build system prompt for OpenAI
 */
function buildSystemPrompt($schemaContext, $dbType)
{
    $dialectNotes = getDialectNotes($dbType);
    $schemaJson = json_encode($schemaContext, JSON_PRETTY_PRINT);

    return <<<PROMPT
You are an expert SQL query generator. Your task is to convert natural language queries into valid SQL queries.

Database Type: {$dbType}
{$dialectNotes}

Database Schema:
{$schemaJson}

CRITICAL RESTRICTIONS:
- NEVER include ORDER BY clauses - sorting is handled by the application
- NEVER include LIMIT, OFFSET, TOP, or ROWNUM clauses - pagination is handled by the application
- Even for queries about "top", "most", "highest", "lowest", "latest", "first", "last" - DO NOT add ORDER BY
- The application will handle all sorting and limiting automatically

Instructions:
1. Generate ONLY the SQL query, without any explanation or markdown formatting
2. Use proper {$dbType} syntax and functions
3. Include appropriate WHERE clauses, JOINs, and aggregations as needed
4. Use table and column names exactly as shown in the schema
5. Return ONLY executable SQL, no code blocks or backticks
6. For queries about "most", "highest", "top" - still return all matching rows WITHOUT ORDER BY

Examples:
- "Show all customers" → SELECT * FROM customers
- "Top 10 products by price" → SELECT * FROM products
- "Most recent orders" → SELECT * FROM orders
- "Highest selling products" → SELECT product_id, SUM(quantity) as total FROM order_items GROUP BY product_id
- "Countries with most rentals" → SELECT country, COUNT(*) as rental_count FROM rentals GROUP BY country
- "Latest 5 transactions" → SELECT * FROM transactions
PROMPT;
}

/**
 * Get database dialect-specific notes
 */
function getDialectNotes($dbType)
{
    return "MySQL Notes: Use backticks for identifiers, DATE_FORMAT() for dates";
}

/**
 * Extract SQL from OpenAI response
 */
function extractSQLFromResponse($content)
{
    // Remove markdown code blocks
    $content = preg_replace('/```sql\s*/i', '', $content);
    $content = preg_replace('/```\s*/', '', $content);

    // Trim whitespace
    $content = trim($content);

    // If multiple statements, take the first one
    if (strpos($content, ';') !== false) {
        $statements = explode(';', $content);
        $content = trim($statements[0]);
    }

    return $content;
}

/**
 * Log AI usage
 */
function logAIUsage($accountId, $userId, $databaseId, $nlInput, $sql, $model, $tokens, $execTimeMs, $success, $errorMsg)
{
    try {
        $conn = new \mysqli(APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

        $stmt = $conn->prepare("
            INSERT INTO ai_query_log
            (account_id, user_id, database_id, natural_language_input, generated_sql,
             model_used, tokens_used, execution_time_ms, success, error_message)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("iissssiiis", $accountId, $userId, $databaseId, $nlInput, $sql,
                          $model, $tokens, $execTimeMs, $success, $errorMsg);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $ex) {
        error_log("Failed to log AI usage: " . $ex->getMessage());
    }
}

/**
 * Encrypt API key
 */
function encryptApiKey($apiKey)
{
    $cipher = "AES-256-CBC";
    $encryptionKey = hash('sha256', APP_DBPASS, true); // Binary output for stronger key
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

    // Use raw output (1) to get binary encrypted data (not base64)
    $encrypted = openssl_encrypt($apiKey, $cipher, $encryptionKey, 1, $iv);

    // Base64 encode once: IV + encrypted data
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt API key
 */
function decryptApiKey($encryptedData)
{
    $cipher = "AES-256-CBC";
    $encryptionKey = hash('sha256', APP_DBPASS, true); // Binary output for stronger key

    $data = base64_decode($encryptedData);
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);

    // Use raw output (1) to match encryption
    return openssl_decrypt($encrypted, $cipher, $encryptionKey, 1, $iv);
}