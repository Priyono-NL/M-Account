<?php
require_once '_dbHelper.php';

class StocksModel extends DatabaseHelper {
    public function __construct() {
        parent::__construct();
    }
	
	public function getLatestStock($search = '', $warehouse = '') {
		$sql = "SELECT 
					i.id as id,
					i.item_name, 
					i.item_code, 
					i.unit_price, 
					i.item_uom,
					s.warehouse,
					s.qty_total AS current_stock
				FROM stocks s
				INNER JOIN (
					SELECT item_id, warehouse, MAX(id) as max_id 
					FROM stocks 
					GROUP BY item_id, warehouse
				) latest ON s.id = latest.max_id
				LEFT JOIN items i ON s.item_id = i.id
				WHERE 1=1";
		
		$params = [];

		if (!empty($search)) {
			$sql .= " AND (i.item_code LIKE :search OR i.item_name LIKE :search)";
			$params['search'] = "%{$search}%";
		}
		
		if ($warehouse !== '') {
			$sql .= " AND s.warehouse = :warehouse";
			$params['warehouse'] = $warehouse;
		}       
		
		$sql .= " ORDER BY i.item_name ASC";
		
		return $this->query_all($sql, $params);
	}

    public function getFiltered($search = '', $warehouse = '', $periodDate = '') {
		if (empty($periodDate)) return [];
		
        $referenceDate = (strlen($periodDate) === 7) ? $periodDate . '-01' : $periodDate;        
        $startDate = date('Y-m-01', strtotime($referenceDate));
        $endDate   = date('Y-m-t', strtotime($referenceDate));

        $sql = "SELECT 
                    i.id as id,
                    i.item_name, 
                    i.item_code, 
                    i.unit_price, 
                    i.item_uom,
                    base.warehouse,
                    
                    -- 1. SALDO AWAL: Ambil saldo akhir dari transaksi terakhir SEBELUM bulan ini dimulai
                    COALESCE((SELECT qty_total FROM stocks WHERE item_id = base.item_id AND warehouse = base.warehouse AND date < :startDate ORDER BY id DESC LIMIT 1), 0) AS qty_open,
                    
                    -- 2. TOTAL MASUK: Jumlahkan semua barang masuk khusus dalam range bulan ini
                    COALESCE(mutasi.total_in, 0) AS qty_in,
                    
                    -- 3. TOTAL KELUAR: Jumlahkan semua barang keluar khusus dalam range bulan ini
                    COALESCE(mutasi.total_out, 0) AS qty_out,
                    
                    -- 4. SALDO AKHIR: Ambil saldo akhir dari transaksi paling terakhir SAMPAI akhir bulan ini
                    COALESCE((SELECT qty_total FROM stocks WHERE item_id = base.item_id AND warehouse = base.warehouse AND date <= :endDate ORDER BY id DESC LIMIT 1), 0) AS qty_close,
                    
                    mutasi.last_date AS date
                FROM items i
                
                -- Ambil daftar barang yang pernah ada di gudang sampai bulan tersebut
                INNER JOIN (
                    SELECT DISTINCT item_id, warehouse FROM stocks WHERE date <= :endDate
                ) base ON i.id = base.item_id
                
                -- Hitung Total Mutasi (SUM) hanya pada bulan tersebut
                LEFT JOIN (
                    SELECT item_id, warehouse, SUM(qty_in) as total_in, SUM(qty_out) as total_out, MAX(date) as last_date
                    FROM stocks
                    WHERE date >= :startDate AND date <= :endDate
                    GROUP BY item_id, warehouse
                ) mutasi ON base.item_id = mutasi.item_id AND base.warehouse = mutasi.warehouse
                
                WHERE 1=1";

        $params = [
            'startDate' => $startDate,
            'endDate'   => $endDate
        ];

        if (!empty($search)) {
            $sql .= " AND (i.item_code LIKE :search OR i.item_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($warehouse !== '') {
            $sql .= " AND base.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }       
        
        $sql .= " ORDER BY i.item_name ASC, base.warehouse ASC";
        
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
            $periodDate = date('Y-m-t', strtotime($monthPeriod . '-01'));
            return [
                'status' => 'ONGOING',
                'data' => $this->getFiltered($search, $warehouse, $periodDate)
            ];
        }
    }
	
	public function getClosingHistory($warehouse = '') {
        $sql = "SELECT id, periode, warehouse, executed_by, executed_at 
                FROM stock_closing_log 
                WHERE 1=1";
        $params = [];

        if ($warehouse !== '') {
            $sql .= " AND warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }

        $sql .= " ORDER BY executed_at DESC";
        return $this->query_all($sql, $params);
    }

    public function doClosing($monthPeriod = '', $warehouse = '', $executedBy = 'System') {
        $endDate = date('Y-m-t', strtotime($monthPeriod . '-01'));
        $prevMonth = date('Y-m', strtotime($monthPeriod . '-01 -1 month'));

        $sqlCheck = "SELECT is_closed FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :monthPeriod";
        $checkParams = ['monthPeriod' => $monthPeriod];
        if ($warehouse !== '') {
            $sqlCheck .= " AND warehouse = :warehouse";
            $checkParams['warehouse'] = $warehouse;
        }
        $sqlCheck .= " LIMIT 1";

        $checkLock = $this->query_one($sqlCheck, $checkParams);
        if ($checkLock && $checkLock['is_closed'] == 1) {
            throw new Exception("Periode {$monthPeriod} sudah dikunci permanen!");
        }

        $currentStock = $this->getFiltered('', $warehouse, $monthPeriod);
        $insertedCount = 0;
        
        if (empty($currentStock)) {
            throw new Exception("Tidak ada pergerakan stok pada periode ini untuk di-closing.");
        }

        try {
            $this->beginTransaction();
            
            $sqlPrev = "SELECT item_id, warehouse, qty_close FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :prevMonth";
            $prevParams = ['prevMonth' => $prevMonth];
            if ($warehouse !== '') {
                $sqlPrev .= " AND warehouse = :warehouse";
                $prevParams['warehouse'] = $warehouse;
            }
            $prevData = $this->query_all($sqlPrev, $prevParams);
            
			$gudangTerproses = [];
            $prevStockData = [];
            foreach ($prevData as $row) {
                $key = $row['item_id'] . '_' . $row['warehouse'];
                $prevStockData[$key] = $row['qty_close']; 
            }

            $sqlDel = "DELETE FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :monthPeriod";
            if ($warehouse !== '') $sqlDel .= " AND warehouse = :warehouse";
            $stmtDel = $this->db->prepare($sqlDel);
            $stmtDel->execute($checkParams);

            foreach ($currentStock as $stock) {
                $key = $stock['id'] . '_' . $stock['warehouse'];
                $qtyOpen = isset($prevStockData[$key]) ? $prevStockData[$key] : 0;
				$gudangTerproses[$stock['warehouse']] = true;

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
			            
            foreach (array_keys($gudangTerproses) as $whID) {
                $logData = [
                    'periode'     => $monthPeriod,
                    'warehouse'   => $whID,
                    'executed_by' => $executedBy . ' (LOCKED)'
                ];
                $this->insert('stock_closing_log', $logData);
            }
            $this->commit();
            return $insertedCount;
            
        } catch (Exception $e) {
            $this->rollBack();
            throw new Exception("Gagal menyimpan closing: " . $e->getMessage());
        }
    }
}
?>