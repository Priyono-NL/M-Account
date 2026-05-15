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
                    s.qty_total AS qty_close,
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

        return $this->query_all($sql, $params);
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

        return $this->query_all($sql, $params);
    }

    public function getClosingData($search = '', $warehouse = '', $monthPeriod = '') {
        $sqlCheck = "SELECT is_closed FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :monthPeriod LIMIT 1";
        $lock = $this->query_one($sqlCheck, ['monthPeriod' => $monthPeriod]);
        $isClosed = ($lock && $lock['is_closed'] == 1);
        
        if ($isClosed) {
            return [
                'status' => 'CLOSED',
                'data' => $this->getMonthlyReport($search, $warehouse, $monthPeriod)
            ];
        } else {
            $startDate = date('Y-m-01', strtotime($monthPeriod . '-01'));
            $endDate = date('Y-m-t', strtotime($monthPeriod .'-01'));
            return [
                'status' => 'ONGOING',
                'data' => $this->getFiltered($search, $warehouse, $startDate, $endDate)
            ];
        }
    }

    public function doClosing($monthPeriod = '') {
        $endDate = date('Y-m-t', strtotime($monthPeriod . '-01'));
        $prevMonth = date('Y-m', strtotime($monthPeriod . '-01 -1 month'));

        $sqlCheck = "SELECT is_closed FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :monthPeriod LIMIT 1";
        $checkLock = $this->query_one($sqlCheck, ['monthPeriod' => $monthPeriod]);

        if ($checkLock && $checkLock['is_closed'] == 1) throw new Exception("Periode $monthPeriod sudah dikunci permanen! Buka gembok terlebih dahulu jika ingin mengulang closing.");

        $currentStock = $this->getFiltered('', '', '', $endDate);
        $insertedCount = 0;
        if (!empty($currentStock)) {
            try {
                $this->beginTransaction();
                $sqlPrev = "SELECT item_id, warehouse, qty_close FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :prevMonth";
                $prevData = $this->query_all($sqlPrev, ['prevMonth' => $prevMonth]);
                
                $prevStockData = [];
                foreach ($prevData as $row) {
                    $key = $row['item_id'] . '_' . $row['warehouse'];
                    $prevStockData[$key] = $row['qty_close']; 
                }

                $stmtDelete = $this->db->prepare("DELETE FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :monthPeriod");
                $stmtDelete->execute(['monthPeriod' => $monthPeriod]);

                foreach ($currentStock as $stock) {
                    $key = $stock['id'] . '_' . $stock['warehouse'];
                    $qtyOpen = isset($prevStockData[$key]) ? $prevStockData[$key] : 0;

                    $dataInsert = [
                        'item_id'   => $stock['id'],
                        'warehouse' => $stock['warehouse'],
                        'date'      => $endDate,
                        'qty_open'  => $qtyOpen,
                        'qty_close' => $stock['qty_close'],
                        'is_closed' => 1
                    ];

                    $this->insert('stock_closing', $dataInsert);
                    
                    $insertedCount++;
                }
                $this->commit();
            } catch (Exception $e) {
                $this->rollback();
                throw $e;
            }
        }

        return $insertedCount;
    }
}
?>