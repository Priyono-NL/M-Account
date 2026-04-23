<?php
// =========================================================================
// MODEL: Mengelola data Produk (Items) dan Transaksi
// =========================================================================

require_once '_dbHelper.php';

class ItemsModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function getAllProducts() {
        return $this->getAll('items', 'is_active = 0');
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