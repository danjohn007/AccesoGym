<?php
/**
 * Base Model Class
 * Provides common database operations
 */

class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Find all records
     */
    public function findAll($orderBy = 'id', $order = 'ASC') {
        // Whitelist allowed columns and order directions for security
        $allowedOrders = ['ASC', 'DESC'];
        $order = in_array(strtoupper($order), $allowedOrders) ? strtoupper($order) : 'ASC';
        
        // Basic column name validation (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $orderBy)) {
            $orderBy = 'id';
        }
        
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY {$orderBy} {$order}");
        return $stmt->fetchAll();
    }
    
    /**
     * Find records by condition
     */
    public function where($conditions, $params = []) {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Find single record by condition
     */
    public function whereOne($conditions, $params = []) {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions} LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Insert new record
     */
    public function insert($data) {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute(array_values($data))) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "{$key} = ?";
        }
        $fieldsString = implode(', ', $fields);
        
        $sql = "UPDATE {$this->table} SET {$fieldsString} WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Execute raw query
     */
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Count records
     */
    public function count($conditions = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$conditions}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }
}
