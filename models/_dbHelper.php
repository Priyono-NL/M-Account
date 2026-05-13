<?php
class DatabaseHelper {
    protected $db;
    protected $configs = [];

    public function __construct() {
        $host = DB_HOST;
        $dbname = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;

        try {
            $this->db = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Koneksi Database Gagal: " . $e->getMessage());
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->configs = $_SESSION['user']['extra_config'] ?? [];
    }

    private function getFilterMapping() {
        return [
            // 'Key_di_SSO' => ['tabel1.kolom', 'tabel2.kolom']
            'warehouse' => ['stocks.warehouse', 'sales.warehouse', 'receivement.warehouse', 'item_transactions.warehouse'],
        ];
    }

    private function applySsoFilter($table, $where) {
        $mapping = $this->getFilterMapping();
        $active_filters = [];

        foreach ($this->configs as $key => $value) {
            if (isset($mapping[$key])) {
                foreach ($mapping[$key] as $target) {
                    list($mapTable, $mapColumn) = explode('.', $target);
                    
                    if ($table === $mapTable) {
                        if (is_array($value)) {
                            $vals = "'" . implode("','", $value) . "'";
                            $active_filters[] = "{$mapColumn} IN ({$vals})";
                        } else {
                            $active_filters[] = "{$mapColumn} = '{$value}'";
                        }
                    }
                }
            }
        }

        if (!empty($active_filters)) {
            $sso_where = implode(' AND ', $active_filters);
            return $where ? "({$where}) AND {$sso_where}" : $sso_where;
        }

        return $where;
    }

    public function query_one($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function query_all($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAll($table, $where = null, $orderBy = null) {
        $where = $this->applySsoFilter($table, $where);
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
        $mapping = $this->getFilterMapping();
        foreach ($this->configs as $key => $value) {
            if (isset($mapping[$key])) {
                foreach ($mapping[$key] as $target) {
                    list($mapTable, $mapColumn) = explode('.', $target);                    
                    if ($table === $mapTable) $data[$mapColumn] = $value; 
                }
            }
        }

        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute($data)) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error di tabel {$table}: " . $errorInfo[2]);
        }
        return $this->db->lastInsertId();
    }

    public function update($table, $data, $where) {
        $where = $this->applySsoFilter($table, $where);
        $mapping = $this->getFilterMapping();
        foreach ($this->configs as $key => $value) {
            if (isset($mapping[$key])) {
                foreach ($mapping[$key] as $target) {
                    list($mapTable, $mapColumn) = explode('.', $target);
                    if ($table === $mapTable) unset($data[$mapColumn]);
                }
            }
        }

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
        $where = $this->applySsoFilter($table, $where);
        $sql = "UPDATE {$table} SET is_active = 0 WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }
}
?>