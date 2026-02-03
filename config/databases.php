<?php

//==================================================================================================
//  DATABASES - WITH SESSION CACHING FOR PERFORMANCE
//==================================================================================================

// Helper function to recursively decode JSON strings in nested structures
function recursiveJsonDecode($data) {
    if (is_string($data)) {
        // Try to decode if it's a JSON string
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
            // Recursively decode in case of nested JSON
            return recursiveJsonDecode($decoded);
        }
        return $data;
    } elseif (is_array($data)) {
        // Recursively process arrays
        foreach ($data as $key => $value) {
            $data[$key] = recursiveJsonDecode($value);
        }
        return $data;
    }
    return $data;
}

$accountId = $_SESSION['AccountId'] ?? false;

$_databases = [];

if ($accountId) {

    // Check if databases are already cached in session
    if (isset($_SESSION['_databases_cache']) && isset($_SESSION['_databases_cache_account']) && $_SESSION['_databases_cache_account'] === $accountId) {
        // Use cached data - HUGE performance improvement!
        $_databases = $_SESSION['_databases_cache'];

        // Ensure all nested JSON is decoded (handles old cache format)
        $cacheNeedsUpdate = false;
        foreach ($_databases as $key => &$db) {
            if (isset($db['tables'])) {
                $originalTables = serialize($db['tables']);
                $db['tables'] = recursiveJsonDecode($db['tables']);
                if (serialize($db['tables']) !== $originalTables) {
                    $cacheNeedsUpdate = true;
                }
            }
        }
        unset($db);

        // Update cache if we decoded anything
        if ($cacheNeedsUpdate) {
            $_SESSION['_databases_cache'] = $_databases;
        }
    } else {
        // Cache miss - load from database
        $conn = new \mysqli(APP_DBHOST, APP_DBUSER, APP_DBPASS, APP_DBNAME);

        if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

        $stmt = $conn->prepare("SELECT * FROM dbs WHERE account_id = ? AND active = 1");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();

        $results = $stmt->get_result();

        while($row = $results->fetch_array()) {

            $myDb = $row['name'] .'@'. $row['server'] .':'. $row['port'];
            $_databases[$myDb] = [
                'id'       => $row['id'],
                'label'    => $row['label'],
                'name'     => $row['name'],
                'server'   => $row['server'],
                'username' => $row['username'],
                'password' => $row['password'],
                'port'     => $row['port'],
                'encoding' => $row['encoding'],
                'active'   => $row['active'],
                'type'     => $row['type'],
                'charset'  => $row['encoding'],
                // Recursively decode all nested JSON
                'tables'   => recursiveJsonDecode($row['tables'])
            ];

        }

        $stmt->close();
        $conn->close();

        // Cache the databases in session for subsequent requests
        $_SESSION['_databases_cache'] = $_databases;
        $_SESSION['_databases_cache_account'] = $accountId;
    }

}