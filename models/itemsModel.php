<?php
require_once '_dbHelper.php';

class ItemsModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function getFilteredItems($search = '', $category = '') {
        $sql = "SELECT * FROM items WHERE is_active=0";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (item_code LIKE :search OR item_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($category !== '') {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }        

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchProducts($keyword) {
        $sql = "SELECT id, item_code, item_name, unit_price 
                FROM items 
                WHERE is_active = 0 
                AND (item_name LIKE :keyword OR item_code LIKE :keyword) 
                LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['keyword' => "%{$keyword}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
?>