<?php
require_once '_dbHelper.php';

class StocksModel extends DatabaseHelper {
    public function __construct() {
        parent::__construct();
    }

    public function getFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $sql = "SELECT 
                    s.id as stock_id,
                    i.id as id,
                    i.item_name, 
                    i.item_code, 
                    i.unit_price, 
                    i.item_uom,
                    s.warehouse,
                    s.qty_open,
                    s.qty_in,
                    s.qty_out,
                    s.qty_total,
                    s.date
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

    public function getMonthlyReport($search = '', $warehouse = '', $monthPeriod = '') {
        $sql = "SELECT * FROM vw_stock_report WHERE 1=1";
        
        $params = [];

        if (!empty($monthPeriod)) {
            $sql .= " AND periode = :monthPeriod";
            $params['monthPeriod'] = $monthPeriod;
        }

        if (!empty($search)) {
            $sql .= " AND (item_code LIKE :search OR item_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($warehouse !== '') {
            $sql .= " AND warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }

        $sql .= " ORDER BY item_name ASC, warehouse ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function doClosing($monthPeriod = '') {
        $endDate = date('Y-m-t', strtotime($monthPeriod . '-01'));
        $prevMonth = date('Y-m', strtotime($monthPeriod . '-01 -1 month'));
        $currentStock = $this->getFiltered('', '', '', $endDate);

        $insertedCount = 0;
        if (!empty($currentStock)) {
            $stmtPrev = $this->db->prepare("SELECT item_id, warehouse, qty_close FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :prevMonth");
            $stmtPrev->execute(['prevMonth' => $prevMonth]);
            
            $prevStockData = [];
            while ($row = $stmtPrev->fetch(PDO::FETCH_ASSOC)) {
                $key = $row['item_id'] . '_' . $row['warehouse'];
                $prevStockData[$key] = $row['qty_close']; 
            }

            $stmtDelete = $this->db->prepare("DELETE FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :monthPeriod");
            $stmtDelete->execute(['monthPeriod' => $monthPeriod]);

            $insertSql = "INSERT INTO stock_closing (item_id, warehouse, date, qty_open, qty_close) 
                            VALUES (:item_id, :warehouse, :date, :qty_open, :qty_close)";
            $stmtInsert = $this->db->prepare($insertSql);

            foreach ($currentStock as $stock) {
                $key = $stock['id'] . '_' . $stock['warehouse'];
                $qtyOpen = isset($prevStockData[$key]) ? $prevStockData[$key] : 0;

                $stmtInsert->execute([
                    'item_id'   => $stock['id'],
                    'warehouse' => $stock['warehouse'],
                    'date'      => $endDate,
                    'qty_open'  => $qtyOpen,
                    'qty_close' => $stock['qty_total']
                ]);
                
                $insertedCount++;
            }
        }

        return $insertedCount;
    }
}
?>