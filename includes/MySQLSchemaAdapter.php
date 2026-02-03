<?php

require_once(__DIR__ . '/DatabaseSchemaInterface.php');

/**
 * MySQL Schema Adapter
 * Implements schema discovery for MySQL databases using INFORMATION_SCHEMA
 */
class MySQLSchemaAdapter implements DatabaseSchemaInterface
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all tables in a MySQL database
     *
     * @param string $database Database name
     * @return array Array of table names
     */
    public function getTables(string $database): array
    {
        $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                  WHERE TABLE_SCHEMA = '" . $this->db->escape($database) . "'
                  AND TABLE_TYPE = 'BASE TABLE'
                  ORDER BY TABLE_NAME";

        $result = $this->db->get_col($query);
        return $result !== false ? $result : [];
    }

    /**
     * Get all columns for a specific table
     *
     * @param string $database Database name
     * @param string $table Table name
     * @return array Array of column names
     */
    public function getColumns(string $database, string $table): array
    {
        $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = '" . $this->db->escape($database) . "'
                  AND TABLE_NAME = '" . $this->db->escape($table) . "'
                  ORDER BY ORDINAL_POSITION";

        $result = $this->db->get_col($query);
        return $result !== false ? $result : [];
    }

    /**
     * Get primary keys for a table
     *
     * @param string $database Database name
     * @param string $table Table name
     * @return array Array of primary key column names
     */
    public function getPrimaryKeys(string $database, string $table): array
    {
        $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = '" . $this->db->escape($database) . "'
                  AND TABLE_NAME = '" . $this->db->escape($table) . "'
                  AND COLUMN_KEY = 'PRI'
                  ORDER BY ORDINAL_POSITION";

        $result = $this->db->get_col($query);
        return $result !== false ? $result : [];
    }

    /**
     * Get detailed column information
     *
     * @param string $database Database name
     * @param string $table Table name
     * @return array Array of column details
     */
    public function getColumnDetails(string $database, string $table): array
    {
        $query = "SELECT
                    COLUMN_NAME as name,
                    DATA_TYPE as type,
                    COLUMN_TYPE as full_type,
                    IS_NULLABLE as nullable,
                    COLUMN_DEFAULT as default_value,
                    COLUMN_KEY as key,
                    EXTRA as extra,
                    CHARACTER_MAXIMUM_LENGTH as max_length,
                    NUMERIC_PRECISION as numeric_precision,
                    NUMERIC_SCALE as numeric_scale
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = '" . $this->db->escape($database) . "'
                  AND TABLE_NAME = '" . $this->db->escape($table) . "'
                  ORDER BY ORDINAL_POSITION";

        $result = $this->db->get_results($query, ARRAY_A);
        return $result !== false ? $result : [];
    }
}
