<?php

/**
 * Database Schema Interface
 * Provides database-agnostic methods for retrieving schema metadata
 */
interface DatabaseSchemaInterface
{
    /**
     * Get all tables in a database
     *
     * @param string $database Database name
     * @return array Array of table names
     */
    public function getTables(string $database): array;

    /**
     * Get all columns for a specific table
     *
     * @param string $database Database name
     * @param string $table Table name
     * @return array Array of column names
     */
    public function getColumns(string $database, string $table): array;

    /**
     * Get primary keys for a table
     *
     * @param string $database Database name
     * @param string $table Table name
     * @return array Array of primary key column names
     */
    public function getPrimaryKeys(string $database, string $table): array;

    /**
     * Get detailed column information
     *
     * @param string $database Database name
     * @param string $table Table name
     * @return array Array of column details (name, type, nullable, default, etc.)
     */
    public function getColumnDetails(string $database, string $table): array;
}
