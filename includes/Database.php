<?php

/**
 * PDO Database Wrapper
 * Provides ezsql-compatible methods using PDO
 */
class Database
{
    private ?PDO $pdo = null;
    private ?PDOStatement $lastStatement = null;
    private ?string $lastError = null;
    public int $rows_affected = 0;

    private string $host = '';
    private string $database = '';
    private string $username = '';
    private string $password = '';
    private int $port = 3306;
    private string $charset = 'utf8mb4';
    private string $dbType = 'mysql';

    /**
     * Quick connect to database
     *
     * @param string $username Database username
     * @param string $password Database password
     * @param string $database Database name
     * @param string $host Database host
     * @param int $port Database port (default: MySQL=3306)
     * @param string $charset Character encoding
     * @param string $dbType Database type (mysql only)
     * @return bool Success status
     */
    public function quick_connect(
        string $username = '',
        string $password = '',
        string $database = '',
        string $host = 'localhost',
        int $port = 3306,
        string $charset = 'utf8mb4',
        string $dbType = 'mysql'
    ): bool {
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->host = $host;
        $this->port = $port;
        $this->charset = $charset;
        $this->dbType = $dbType;

        try {
            $dsn = $this->buildDSN($dbType, $host, $port, $database, $charset);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, $username, $password, $options);
            $this->lastError = null;
            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Build DSN string based on database type
     *
     * @param string $dbType Database type
     * @param string $host Database host
     * @param int $port Database port
     * @param string $database Database name
     * @param string $charset Character encoding
     * @return string DSN string
     */
    private function buildDSN(string $dbType, string $host, int $port, string $database, string $charset): string
    {
        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    /**
     * Execute a query
     *
     * @param string $query SQL query
     * @return bool Success status
     */
    public function query(string $query): bool
    {
        try {
            $this->lastStatement = $this->pdo->query($query);
            $this->rows_affected = $this->lastStatement->rowCount();
            $this->lastError = null;
            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Get a single column from query results
     *
     * @param string $query SQL query
     * @param int $columnOffset Column index (0-based)
     * @return array|false Array of column values or false on error
     */
    public function get_col(string $query, int $columnOffset = 0): array|false
    {
        try {
            $stmt = $this->pdo->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN, $columnOffset);
            $this->lastError = null;
            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Get query results
     *
     * @param string $query SQL query
     * @param int $output Output type constant (OBJECT, ARRAY_A, ARRAY_N)
     * @return array|false Results array or false on error
     */
    public function get_results(string $query, int $output = OBJECT): array|false
    {
        try {
            $stmt = $this->pdo->query($query);

            $results = match($output) {
                ARRAY_N => $stmt->fetchAll(PDO::FETCH_NUM),
                ARRAY_A => $stmt->fetchAll(PDO::FETCH_ASSOC),
                default => $stmt->fetchAll(PDO::FETCH_OBJ),
            };

            $this->lastError = null;
            return $results;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Escape string for SQL query (for legacy compatibility)
     * Note: Use prepared statements instead when possible
     *
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escape(string $string): string
    {
        // Remove quotes added by PDO::quote() to match ezsql behavior
        $quoted = $this->pdo->quote($string);
        return substr($quoted, 1, -1);
    }

    /**
     * Get last insert ID
     *
     * @return string Last insert ID
     */
    public function getInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Get last error message
     *
     * @return string|null Error message or null
     */
    public function getLast_Error(): ?string
    {
        return $this->lastError;
    }

    /**
     * Disconnect from database
     */
    public function disconnect(): void
    {
        $this->pdo = null;
        $this->lastStatement = null;
    }

    /**
     * Get PDO instance for advanced usage
     *
     * @return PDO|null PDO instance
     */
    public function getPDO(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Static factory method to maintain ezsql-like initialization
     *
     * @param string $driver Database driver (mysql only)
     * @param array $credentials [username, password, database, host, port, charset, dbType]
     * @param string|null $tag Instance tag (not used in this implementation)
     * @return self Database instance
     */
    public static function initialize(string $driver, array $credentials, ?string $tag = null): self
    {
        $instance = new self();

        $username = $credentials[0] ?? '';
        $password = $credentials[1] ?? '';
        $database = $credentials[2] ?? '';
        $host = $credentials[3] ?? 'localhost';
        $port = $credentials[4] ?? 3306;
        $charset = $credentials[5] ?? 'utf8mb4';
        $dbType = $credentials[6] ?? $driver ?? 'mysql';

        $instance->quick_connect($username, $password, $database, $host, $port, $charset, $dbType);

        return $instance;
    }

    /**
     * Get database type
     *
     * @return string Database type
     */
    public function getDbType(): string
    {
        return $this->dbType;
    }
}

// Define constants for backward compatibility with ezsql
if (!defined('OBJECT')) {
    define('OBJECT', 1);
    define('ARRAY_A', 2);
    define('ARRAY_N', 3);
}
