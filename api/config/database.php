<?php
/**
 * Database Configuration
 * Konfigurasi koneksi database untuk Agenda Management System
 */

class Database {
    private $host = 'localhost';
    private $dbname = 'agenda_management';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $pdo;
    
    public function __construct() {
        // Ambil konfigurasi dari environment variables jika tersedia
        $this->host = $_ENV['DB_HOST'] ?? $this->host;
        $this->dbname = $_ENV['DB_NAME'] ?? $this->dbname;
        $this->username = $_ENV['DB_USER'] ?? $this->username;
        $this->password = $_ENV['DB_PASS'] ?? $this->password;
    }
    
    /**
     * Get database connection
     * @return PDO
     * @throws Exception
     */
    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                    PDO::ATTR_TIMEOUT => 30
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
                
                // Set timezone
                $this->pdo->exec("SET time_zone = '+07:00'");
                
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     * @return bool
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    /**
     * Check if connection is alive
     * @return bool
     */
    public function isConnected() {
        try {
            $this->getConnection()->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get database info
     * @return array
     */
    public function getInfo() {
        try {
            $pdo = $this->getConnection();
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            
            return [
                'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                'version' => $version,
                'server_info' => $pdo->getAttribute(PDO::ATTR_SERVER_INFO),
                'connection_status' => $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS)
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Execute raw query (untuk maintenance)
     * @param string $query
     * @return mixed
     */
    public function rawQuery($query) {
        try {
            return $this->getConnection()->query($query);
        } catch (Exception $e) {
            error_log('Raw query error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get table info
     * @param string $tableName
     * @return array
     */
    public function getTableInfo($tableName) {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("DESCRIBE {$tableName}");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get table info error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if table exists
     * @param string $tableName
     * @return bool
     */
    public function tableExists($tableName) {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get database size
     * @return array
     */
    public function getDatabaseSize() {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("
                SELECT 
                    table_schema as 'database_name',
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size_mb'
                FROM information_schema.tables 
                WHERE table_schema = ?
                GROUP BY table_schema
            ");
            $stmt->execute([$this->dbname]);
            return $stmt->fetch() ?: ['database_name' => $this->dbname, 'size_mb' => 0];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>