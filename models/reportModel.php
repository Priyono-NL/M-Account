<?php
// =========================================================================
// MODEL: Mengelola data Laporan dan Riwayat
// =========================================================================

require_once '_dbHelper.php';

class ReportModel extends DatabaseHelper {
    public function __construct() {
        parent::__construct();
    }

    public function getItemTransactions() {
        $sql = "SELECT t.*, i.item_name, i.item_code 
                FROM item_transactions t
                LEFT JOIN items i ON t.item_id = i.id
                ORDER BY t.transaction_date DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>