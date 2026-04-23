<?php
require_once '_dbHelper.php';

class StocksModel extends DatabaseHelper {
    public function __construct() {
        parent::__construct();
    }

    public function getFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $sql = "SELECT s.*, i.item_name, i.item_code 
                FROM stocks s
                INNER JOIN (
                    SELECT item_id, warehouse, MAX(id) as max_id 
                    FROM stocks 
                    WHERE 1=1";
        
        $params = [];

        if (!empty($startDate)) {
            $sql .= " AND DATE(date) >= :start_date";
            $params['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= " AND DATE(date) <= :end_date";
            $params['end_date'] = $endDate;
        }

        $sql .= "   GROUP BY item_id, warehouse
                ) latest ON s.id = latest.max_id
                LEFT JOIN items i ON s.item_id = i.id
                WHERE 1=1";

        if (!empty($search)) {
            $sql .= " AND (i.item_code LIKE :search OR i.item_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($warehouse !== '') {
            $sql .= " AND s.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }

        $sql .= " ORDER BY i.item_name ASC, s.warehouse ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>