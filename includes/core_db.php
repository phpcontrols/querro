<?php

//==================================================================================================
//  Handles the insert, update, delete actions for a normal mysql DB
//==================================================================================================

require_once __DIR__ . '/Database.php';

class Core
{
    public $_databases;     // untouched database structure defined in config
    public $dbs_structure;  // tested database structure
    public $_dbs;
    public $db;             // DB connection
    public $db_id;
    public $db_index;
    public $db_name;
    public $table;
    public $table_index;
    public $table_name;
    public $table_keys;
    public $mapping;
    private $insert_query_columns;
    public $debug = false;


    //==================================================================================================
    //  Constructor
    //==================================================================================================
    function __construct($databases = '')
    {
        if ($databases)
            $this->setDatabases($databases);
        // return false;
    }


    //==================================================================================================
    //  Set a Database structure
    //==================================================================================================
    function setDatabases($databases)
    {
        $this->_databases = $databases;
    }


    //==================================================================================================
    //  Adjust the database keys if they don't exist
    //==================================================================================================
    function restructureDBsKeys()
    {
        $arr_new_keys = array();
        foreach ($this->_databases as $key => $database) {
            if (!is_string($key)) {
                $new_key = $database['name'] . '@' . $database['server'] . ':' . $database['port'];
                $i = 1;
                while (in_array($new_key,$arr_new_keys)) {
                    $new_key = $database['name'] . '@' . $database['server'] . ':' . $database['port'] . '-' . $i;
                    $i++;
                }
            } else {
                $new_key = $key;
            }
            $arr_new_keys[] = $new_key;
            $this->dbs_structure[$new_key] = $database;
        }
    }


    //==================================================================================================
    //  Get a tested database structure
    //==================================================================================================
    function getDBsStructure($db_id = '', $table_id = '', $options = array('databases', 'tables', 'columns'))
    {
        $results = array();

        if (is_array($this->_databases)) {

            foreach ($this->_databases as $dkey => $database) {

                $db = Database::initialize('mysqli', [$this->_databases[$dkey]['username'], $this->_databases[$dkey]['password'], $this->_databases[$dkey]['name'], $this->_databases[$dkey]['server']], $dkey);

                if ($db_id == '' or $db_id != '' and $db_id == $dkey) {

                    $db_port = isset($database['port']) ? $database['port'] : 3306;
                    $db_encoding = isset($database['encoding']) ? $database['encoding'] : 'utf8mb4';

                    // Just check if the Database connecition is good
                    if ($db->quick_connect($database['username'], $database['password'], $database['name'], $database['server'], $db_port, $db_encoding)) {

                        $results[$dkey] = array(
                            'label'     => (isset($database['label']) and $database['label'] != '') ? $database['label'] : $database['name'],
                            'name'      => $database['name'],
                            'server'    => $database['server'],
                            'username'  => $database['username'],
                            'password'  => $database['password'],
                            'port'      => $db_port,
                            'encoding'  => $db_encoding,
                            'active'    => (isset($database['active']) and $database['active'] != '') ? $database['active'] : true
                        );

                        if (in_array('tables', $options)) {

                            // Tables on the selected Database
                            $query = "SHOW TABLES";
                            $db_tables = $db->get_col($query);

                            // Decode tables JSON if it's a string (lazy loading optimization)
                            $database_tables = null;
                            if (isset($database['tables'])) {
                                if (is_string($database['tables'])) {
                                    $database_tables = json_decode($database['tables'], true);
                                } else {
                                    $database_tables = $database['tables'];
                                }
                            }

                            // If there is a table defined on the structure
                            if ($database_tables !== null and is_array($database_tables)) {

                                foreach ($database_tables as $tkey => $table) {

                                    if (($table_id == '' or $table_id != '' and $table_id == $tkey) and $table['active']) {

                                        // If table found on Database
                                        if (in_array($database_tables[$tkey]['properties'][0]['name'], $db_tables)) {

                                            // Table
                                            $results[$dkey]['tables'][$tkey] = array(
                                                'label' => (isset($table['label']) and trim($table['label']) != '') ? $table['label'] : $table['properties'][0]['name'],
                                                'active' => isset($table['active']) ? $table['active'] : true
                                            );
                                            $results[$dkey]['tables'][$tkey]['properties'][0]['name'] = $table['properties'][0]['name'];

                                            // Primary Keys
                                            $query = "SHOW KEYS FROM `" . $table['properties'][0]['name'] . "` WHERE Key_name = 'PRIMARY';";
                                            $table_keys = $db->get_col($query,4);
                                            $results[$dkey]['tables'][$tkey]['properties'][0]['keys'] = $table_keys;

                                            if (in_array('columns', $options)) {

                                                // Columns
                                                $query = "SHOW COLUMNS FROM `" . $table['properties'][0]['name'] . "`";
                                                $columns = $db->get_results($query,ARRAY_N);

                                                $arr_hide_columns = array();
                                                if (isset($table['properties'][0]['hide_columns']))
                                                    $arr_hide_columns = is_array($table['properties'][0]['hide_columns']) ? $table['properties'][0]['hide_columns'] : explode(',', $table['properties'][0]['hide_columns']);

                                                if (isset($table['properties'][0]['columns']) and is_array($table['properties'][0]['columns'])) {

                                                    foreach ($table['properties'][0]['columns'] as $column_value) {

                                                        if ((!$arr_hide_columns or !in_array($column_value['name'], $arr_hide_columns)) and $column_value['active']) {

                                                            $results[$dkey]['tables'][$tkey]['properties'][0]['columns'][] = array(
                                                                'label' => (isset($column_value['label']) and trim($column_value['label']) != '') ? $column_value['label'] : $column_value['name'],
                                                                'name'  => $column_value['name'],
                                                                'active' => true
                                                            );

                                                        }

                                                    }

                                                } else {

                                                    foreach ($columns as $column) {

                                                        if (!$arr_hide_columns or !in_array($column[0],$arr_hide_columns)) {

                                                            $results[$dkey]['tables'][$tkey]['properties'][0]['columns'][] = array(
                                                                'label' => $column[0],
                                                                'name'  => $column[0],
                                                                'active' => true
                                                            );

                                                        }

                                                    }

                                                }

                                            }

                                        }

                                    }

                                }

                            } else {

                                // Get all Tables and All Columns
                                foreach ($db_tables as $table) {

                                    if ($table_id == '' or $table_id != '' and $table_id == $table) {

                                        // Table
                                        $results[$dkey]['tables'][$table] = array(
                                            'label'  => $table,
                                            'active' => true
                                        );
                                        $results[$dkey]['tables'][$table]['properties'][0]['name'] = $table;

                                        // Primary Keys
                                        $query = "SHOW KEYS FROM `" . $table . "` WHERE Key_name = 'PRIMARY';";
                                        $table_keys = $db->get_col($query,4);
                                        $results[$dkey]['tables'][$table]['properties'][0]['keys'] = $table_keys;

                                        if (in_array('columns', $options)) {

                                            // Columns
                                            $query = "SHOW COLUMNS FROM `" . $table . "`";
                                            $columns = $db->get_results($query,ARRAY_N);

                                            foreach ($columns as $column) {
                                                $results[$dkey]['tables'][$table]['properties'][0]['columns'][] = array(
                                                    'label' => $column[0],
                                                    'name'  => $column[0],
                                                    'active' => true
                                                );
                                            }

                                        }

                                    }

                                }

                            }

                        }
                        $db->disconnect();
                    } else {
                        echo $db->getLast_Error() . '<br>';
                    }
                }
            }
            return $results;
        }
    }


    //==================================================================================================
    //  get table structure already stored in database table 'dbs'
    //  db_id = name@server:port
    //==================================================================================================
    function getDbTableStructureCached($db_id, $account_Id) {

        try {
            $pdo = new PDO(
                "mysql:host=" . APP_DBHOST . ";dbname=" . APP_DBNAME . ";charset=utf8mb4",
                APP_DBUSER,
                APP_DBPASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            $stmt = $pdo->prepare("SELECT * FROM dbs WHERE CONCAT(name, '@', server, ':', port) = ? AND account_id = ? AND active = 1");
            $stmt->execute([$db_id, $account_Id]);

            $structure = [];
            while ($row = $stmt->fetch()) {
                $structure = [
                    'id'       => $row['id'],
                    'label'    => $row['label'],
                    'name'     => $row['name'],
                    'server'   => $row['server'],
                    'username' => $row['username'],
                    'password' => $row['password'],
                    'port'     => $row['port'],
                    'encoding' => $row['encoding'],
                    'active'   => $row['active'],
                    // 'type'     => $row['type'],
                    // 'charset'  => $row['encoding'],     // TODO - charset and encoding are the same thing! merge and keep just one
                    'tables'   => json_decode($row['tables'], true)
                ];
            }

            return $structure;

        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }


    //==================================================================================================
    //  Set a tested database structure
    //==================================================================================================
    function setDBsStructure($db_id = '', $table_id = '', $options = array('databases', 'tables', 'columns'))
    {
        $structure = $this->getDBsStructure($db_id, $table_id, $options);
        $this->dbs_structure[$db_id] = $structure[$db_id];
    }


    //==================================================================================================
    //  Return all the good and tested databases structure
    //==================================================================================================
    function getGoodDBs()
    {
        $structure = $this->getDBsStructure('', '', array('databases'));
        return $structure;
    }


    //==================================================================================================
    //  Return all the good and tested tables structure
    //==================================================================================================
    function getGoodTables($db_id)
    {
        $structure = $this->getDBsStructure($db_id, '', array('databases', 'tables'));
        if (isset($structure[$db_id]['tables']))
            return $structure[$db_id]['tables'];
        else
            return array();
    }


    //==================================================================================================
    //  Return all the good and tested columns structure
    //==================================================================================================
    function getGoodColumns($db_id,$table_id)
    {
        $res = [];

        if ($table_id) {
            
            $structure = $this->getDBsStructure($db_id, $table_id, array('databases', 'tables', 'columns'));
            $columns = $structure[$db_id]['tables'][$table_id]['properties'][0]['columns'];

            foreach ($columns as $column) {
                $res["names"][] = $column['name'];
                $res["labels"][] = $column['label'];
            }
        }

        return $res;
    }


    //==================================================================================================
    //  Open a db connection and store it
    //==================================================================================================
    function setDB($db_name, $db_username = '', $db_password = '', $db_server = '', $db_port = '', $db_encoding = '')
    {
        $db_username = $db_username ? $db_username : '';
        $db_password = $db_password ? $db_password : '';
        $db_server = $db_server ? $db_server : '';
        $db_port = $db_port ? $db_port : '';
        $db_encoding = $db_encoding ? $db_encoding : '';

        $db = Database::initialize('mysqli', [$db_username, $db_password, $db_name, $db_server]);

        $res = @$db->quick_connect($db_username, $db_password, $db_name, $db_server, $db_port, $db_encoding);
        if ($res) {
            $this->db_id = $db_name;
            $this->db_name = $db_name;
            $this->db = $db;
            return true;
        } else {
            return false;
        }
    }


    //==================================================================================================
    //  Open a db connection by db id
    //==================================================================================================
    function setDBbyId($db_id)
    {
        if (isset($this->dbs_structure[$db_id])) {
            $db_port = isset($this->dbs_structure[$db_id]['port']) ? $this->dbs_structure[$db_id]['port'] : 3306;
            $db_encoding = isset($this->dbs_structure[$db_id]['encoding']) ? $this->dbs_structure[$db_id]['encoding'] : 'utf8mb4';
            return $this->setDB($this->dbs_structure[$db_id]['name'], $this->dbs_structure[$db_id]['username'], $this->dbs_structure[$db_id]['password'], $this->dbs_structure[$db_id]['server'], $db_port, $db_encoding);
        }
    }


    //==================================================================================================
    //  Sets a table
    //==================================================================================================
    function setTable($table_id, $db_id = '')
    {
        $this->setDBsStructure($db_id, $table_id);

        if ($db_id and $db_id != $this->db_id) {
            $this->setDBbyId($db_id);
        }

        $this->table = $this->dbs_structure[$db_id]['tables'][$table_id];
        $this->table_name = $this->dbs_structure[$db_id]['tables'][$table_id]['properties'][0]['name'];
        $this->table_keys = isset($this->dbs_structure[$db_id]['tables'][$table_id]['properties'][0]['keys']) ? $this->dbs_structure[$db_id]['tables'][$table_id]['properties'][0]['keys'] : array();
    }


    //==================================================================================================
    //  Sets a mapping
    //==================================================================================================
    function setMapping($mapping)
    {
        $this->mapping = $mapping;
    }


    //==================================================================================================
    //  Get the duplicates
    //==================================================================================================
    function getDuplicates($duplicates, $arr_values)
    {
        $arr_keys = array_keys($arr_values);

        $query = "SELECT `" . implode('`,`', $this->table_keys) . "` FROM `" . $this->table['properties'][0]['name'] . "` WHERE `" . $this->table['properties'][0]['columns'][$duplicates]['name'] . "`='" . $arr_values[$arr_keys[$this->mapping[$duplicates]]] . "'";

        $arr_duplicates = $this->db->get_results($query, ARRAY_N);

        return $arr_duplicates;
    }


    //==================================================================================================
    //  Insert values on the selected table
    //==================================================================================================
    function insert($arr_values)
    {
        $query = $this->getInsertQuery($arr_values);

        @$this->db->query($query);

        if (!$this->db->getLast_Error()) {
            $res['status']  = 'inserted';
            $res['message'] = 'ID: ' . $this->db->getInsertId() . ($this->debug ? '<br>' . $query : '');
            $res['data'] = $this->db->getInsertId();
        } else {
            $res['status'] = 'error';
            $res['message'] = $this->db->getLast_Error().($this->debug ? '<br>' . $query : '');
        }

        return $res;
    }


    //==================================================================================================
    //  Get the insert query
    //==================================================================================================
    function getInsertQuery($arr_values)
    {

        // $arr_values is an associative array. to access by index use array_keys
        // e.g. $arr_values[$arr_keys[0]]
        $arr_keys= array_keys($arr_values);

        $insert_query_columns = '';
        $insert_query_values = '';

        if (!$this->insert_query_columns) {

            for ($i = 0; $i<count($this->table['properties'][0]['columns']); $i++) {
                if ($this->mapping[$i] != '') {
                    $insert_query_columns .= ($insert_query_columns ? ',' : '') . '`' . $this->table['properties'][0]['columns'][$i]['name'] . '`';
                    $insert_query_values .= ($insert_query_values ? ',' : '') . "'" . $this->db->escape($arr_values[$arr_keys[$this->mapping[$i]]]) . "'";
                }
            }

            $this->insert_query_columns = $insert_query_columns;

        } else {

            $arr_mapped_values = $this->getMappedValues($arr_values, true);

            $insert_query_values = implode(',', $arr_mapped_values);

        }

        // Query
        $insert_query = 'INSERT INTO `' . $this->table_name . '` (' . $this->insert_query_columns . ') VALUES (' . str_ireplace("'NULL'", "NULL", $insert_query_values) . ')';

        return $insert_query;
    }


    //==================================================================================================
    //  Updates the selected table
    //==================================================================================================
    function update($arr_values,$arr_ids)
    {
        $query_columns_values = '';

        $arr_keys = array_keys($arr_values);

        if ($arr_ids) {

            for ($i = 0; $i < count($this->mapping); $i++) {
                if ($this->mapping[$i] != '')
                    $query_columns_values .= ($query_columns_values ? ', ' : '') . "`" . $this->table['properties'][0]['columns'][$i]['name'] . "`='" . $this->db->escape($arr_values[$arr_keys[$this->mapping[$i]]]) . "'";
            }

            $duplicates_values = $this->getInValues($arr_ids);

            $query = "UPDATE `" . $this->table_name . "` SET " . $query_columns_values . " WHERE (`" . implode('`,`', $this->table_keys) . "`) IN (" . $duplicates_values . ")";

            @$this->db->query($query);

            if (!$this->db->getLast_Error()) {
                $res['status'] = 'updated';
                $res['message'] = 'ID' . (count($arr_ids)==1 ? '' : 'S') . ': ' . $duplicates_values . ($this->debug ? '<br>' . $query : '');
            } else {
                $res['status'] = 'error';
                $res['message'] = $this->db->getLast_Error() . ($this->debug ? '<br>' . $query : '');
            }

        } else {
            $res['status'] = 'error';
            $res['message'] = 'No IDs selected';
        }

        return $res;
    }


    //==================================================================================================
    //  Deletes the selected table
    //==================================================================================================
    function delete($arr_ids)
    {
        if ($arr_ids) {

            $duplicates_values = $this->getInValues($arr_ids);

            $query = "DELETE FROM `" . $this->table_name . "` WHERE (`" . implode('`,`', $this->table_keys) . "`) IN (" . $duplicates_values . ")";

            @$this->db->query($query);

            if (!$this->db->getLast_Error()) {
                $res['status'] = 'deleted';
                $res['message'] = $this->db->rows_affected . ' record' . ($this->db->rows_affected==1 ? '' : 's') . ' deleted (ID' . (count($arr_ids)==1 ? '' : 'S') . ': ' . $duplicates_values . ($this->debug ? '<br>' . $query : '');
            } else {
                $res['status'] = 'error';
                $res['message'] = $this->db->getLast_Error() . ($this->debug ? '<br>' . $query : '');
            }

        } else {
            $res['status'] = 'error';
            $res['message'] = 'No IDs selected';
        }

        return $res;
    }


    //==================================================================================================
    //  Deletes from the table where ids are not on the array
    //==================================================================================================
    function deleteNotIn($arr_ids)
    {
        if ($arr_ids) {

            $duplicates_values = $this->getInValues($arr_ids);

            $query = "DELETE FROM `" . $this->table_name . "` WHERE (`" . implode('`,`', $this->table_keys) . "`) NOT IN (" . $duplicates_values . ")";

            @$this->db->query($query);

            if (!$this->db->getLast_Error()) {
                $res['status'] = 'deleted';
                $res['message'] = $this->db->rows_affected . ' record' . ($this->db->rows_affected==1 ? '' : 's') . ' deleted (NOT IN' . (count($arr_ids)==1 ? '' : 'S') . ': ' . $duplicates_values . ')' . ($this->debug ? '<br>' . $query : '');
            } else {
                $res['status'] = 'error';
                $res['message'] = $this->db->getLast_Error() . ($this->debug ? '<br>' . $query : '');
            }

        } else {

            $query = "DELETE FROM `" . $this->table_name . "`";

            if (!$this->db->getLast_Error()) {
                $res['status'] = 'deleted';
                $res['message'] = $this->db->rows_affected . ' records deleted' . ($this->debug ? '<br>' . $query : '');
            } else {
                $res['status'] = 'error';
                $res['message'] = $this->db->getLast_Error() . ($this->debug ? '<br>' . $query : '');
            }

        }
        return $res;
    }


    //==================================================================================================
    //  Get values in the array
    //==================================================================================================
    function getInValues($arr_ids)
    {
        // If it has multiple primary keys
        if (is_array($arr_ids[0])) {
            $in_ids = '';
            foreach ($arr_ids as $key => $arr_ids_value) {
                $in_ids_line = '';
                    foreach ($arr_ids_value as $value) {
                        $in_ids_line .= ($in_ids_line ? ',' : '') . "'" . $this->db->escape($value) . "'";
                    }
                $in_ids .= ($in_ids ? ',' : '') . (count($arr_ids_value)>1 ? '(' : '') . $in_ids_line . (count($arr_ids_value) > 1 ? ')' : '');
            }
        } else {
            $in_ids = "'" . implode("','", $this->table_keys) . "'";
        }
        return $in_ids;
    }


    //==================================================================================================
    //  Get columns from the selected table
    //==================================================================================================
    function getColumns()
    {
        foreach ($this->table['properties'][0]['columns'] as $columns) {
            $res['name'][] = $columns['name'];
            $res['label'][] = $columns['label'];
        }
        return $res;
    }


    //==================================================================================================
    //  Get mapped columns information
    //==================================================================================================
    function getMappedColumns()
    {
        $res=array();
        for ($i = 0; $i < count($this->table['properties'][0]['columns']); $i++) {
            if ($this->mapping[$i] != '') {
                $res['name'][] = $this->table['properties'][0]['columns'][$i]['name'];
                $res['label'][] = $this->table['properties'][0]['columns'][$i]['label'];
            }
        }
        return $res;
    }


    //==================================================================================================
    //  Get mapped values
    //==================================================================================================
    function getMappedValues($arr_values, $db_escape = false)
    {
        $arr_keys= array_keys($arr_values);
        
        $res=array();

        for ($i = 0; $i < count($this->mapping); $i++) {
            if ($this->mapping[$i] != '')
                $res[$i] = $db_escape ? "'" . $this->db->escape($arr_values[$arr_keys[$this->mapping[$i]]]) . "'" : $arr_values[$arr_keys[$this->mapping[$i]]];
        }
        return $res;
    }

}
