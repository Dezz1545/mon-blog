<?php
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
    
    public static function query($sql, $params = []) {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public static function fetch($sql, $params = []) {
        return self::query($sql, $params)->fetch();
    }
    
    public static function fetchAll($sql, $params = []) {
        return self::query($sql, $params)->fetchAll();
    }
    
    public static function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute(array_values($data));
        
        return self::getInstance()->lastInsertId();
    }
    
    public static function update($table, $data, $where, $whereParams = []) {
        $set = [];
        $params = [];
        
        foreach($data as $column => $value) {
            $set[] = "$column = ?";
            $params[] = $value;
        }
        
        $params = array_merge($params, $whereParams);
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
        
        $stmt = self::getInstance()->prepare($sql);
        return $stmt->execute($params);
    }
    
    public static function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = self::getInstance()->prepare($sql);
        return $stmt->execute($params);
    }
}
?>