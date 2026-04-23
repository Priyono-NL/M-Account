<?php
// =========================================================================
// MODEL: Mengelola data Pelanggan (Buyer)
// =========================================================================

require_once '_dbHelper.php';

class BuyerModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function getAllBuyers() {
        return $this->getAll('buyer', 'is_active = 0');
    }

    public function searchBuyers($keyword) {
        $sql = "SELECT id, buyer_code, buyer_name 
                FROM buyer 
                WHERE is_active = 0 
                AND (buyer_name LIKE :keyword OR buyer_code LIKE :keyword) 
                LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['keyword' => "%{$keyword}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>