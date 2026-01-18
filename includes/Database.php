<?php
/**
 * Database Connection Class
 * Handles all database operations
 */

class Database {
    private static $instance = null;
    private $connection;
    private $lastError = null;
    private $lastQuery = null;

    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                DB_PORT
            );

            if ($this->connection->connect_error) {
                throw new Exception('Database Connection Failed: ' . $this->connection->connect_error);
            }

            $this->connection->set_charset('utf8mb4');
            $this->connection->query("SET time_zone = '" . date('P') . "'");
        } catch (Exception $e) {
            error_log('Database Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Execute query with prepared statements
     */
    public function query($sql, $params = [], $types = '') {
        try {
            $this->lastQuery = $sql;

            if (empty($params)) {
                $result = $this->connection->query($sql);
                if (!$result) {
                    throw new Exception($this->connection->error);
                }
                return $result;
            }

            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new Exception($this->connection->error);
            }

            if (!empty($params)) {
                if (empty($types)) {
                    $types = str_repeat('s', count($params));
                }
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            return $stmt;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Query Error: ' . $e->getMessage() . ' | Query: ' . $sql);
            throw $e;
        }
    }

    /**
     * Fetch single row as associative array
     */
    public function fetchRow($sql, $params = [], $types = '') {
        $result = $this->query($sql, $params, $types);

        if ($result instanceof mysqli_stmt) {
            $result = $result->get_result();
        }

        return $result->fetch_assoc();
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = [], $types = '') {
        $result = $this->query($sql, $params, $types);

        if ($result instanceof mysqli_stmt) {
            $result = $result->get_result();
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Insert record
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $types = $this->getParamTypes(array_values($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->query($sql, array_values($data), $types);

        return $this->connection->insert_id;
    }

    /**
     * Update record
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "$column = ?";
        }
        $setString = implode(', ', $set);

        $types = $this->getParamTypes(array_merge(array_values($data), $whereParams));
        $sql = "UPDATE $table SET $setString WHERE $where";

        return $this->query($sql, array_merge(array_values($data), $whereParams), $types);
    }

    /**
     * Delete record
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $types = $this->getParamTypes($params);
        return $this->query($sql, $params, $types);
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * Get affected rows
     */
    public function affectedRows() {
        return $this->connection->affected_rows;
    }

    /**
     * Start transaction
     */
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * Get parameter types for binding
     */
    private function getParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }

    /**
     * Escape string
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    /**
     * Get last error
     */
    public function getLastError() {
        return $this->lastError ?? $this->connection->error;
    }

    /**
     * Get connection object
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Close connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserializing
     */
    private function __wakeup() {}
}
