<?php
require_once '_dbHelper.php';

class ReportModel extends DatabaseHelper {
    public function __construct() {
        parent::__construct();
    }

    public function getFilteredTransactions($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $sql = "SELECT t.*, i.item_name, i.item_code 
                FROM item_transactions t
                LEFT JOIN items i ON t.item_id = i.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (i.item_code LIKE :search OR i.item_name LIKE :search OR t.reference_no LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($warehouse !== '') {
            $sql .= " AND t.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(t.transaction_date) >= :start_date";
            $params['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= " AND DATE(t.transaction_date) <= :end_date";
            $params['end_date'] = $endDate;
        }

        $sql .= " ORDER BY t.transaction_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>