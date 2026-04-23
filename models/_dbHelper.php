<?php
class DatabaseHelper {
    protected $db;

    public function __construct() {
        $host = DB_HOST;
        $dbname = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;

        try {
            $this->db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Koneksi Database Gagal: " . $e->getMessage());
        }
    }

    public function getAll($table, $where = null, $orderBy = null) {
        $sql = "SELECT * FROM {$table}";
        if ($where) $sql .= " WHERE {$where}";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getById($table, $id, $pk = 'id') {
        $sql = "SELECT * FROM {$table} WHERE {$pk} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($table, $data, $where) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setStr = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setStr} WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($table, $where) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->db->query($sql);
        return $stmt->rowCount();
    }
}
?>