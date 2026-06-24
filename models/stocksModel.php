<?php
require_once '_dbHelper.php';

class StocksModel extends DatabaseHelper {
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * MENGAMBIL STOK SAAT INI (SNAPSHOT)
     * Sangat cepat dan ringkas karena tabel stocks sekarang hanya 1 baris per item per gudang.
     */
    public function getLatestStock($search = '', $warehouse = '') {
        $sql = "SELECT 
                    i.id as id,
                    i.item_name, 
                    i.item_code, 
                    i.unit_price, 
                    i.item_uom,
                    s.warehouse,
                    COALESCE(s.qty_total, 0) AS current_stock
                FROM items i
                LEFT JOIN stocks s ON i.id = s.item_id
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

    /**
     * FUNGSI SINKRONISASI STOK GLOBAL (Fungsi Penyelamat / Single Source of Truth)
     * Dapat dipanggil dari modul manapun untuk merapikan saldo stok akhir ke tabel `stocks`
     */
    public function syncCurrentStock($item_id, $warehouse) {
        $sqlCalc = "SELECT SUM(CASE WHEN type = 'IN' THEN qty ELSE -qty END) as real_stock 
                    FROM item_transactions 
                    WHERE item_id = :item_id AND warehouse = :warehouse";
                    
        $result = $this->query_one($sqlCalc, ['item_id' => $item_id, 'warehouse' => $warehouse]);
        $real_stock = $result ? (float)$result['real_stock'] : 0;

        $cekStock = $this->query_one("SELECT id FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse LIMIT 1", [
            'item_id' => $item_id, 'warehouse' => $warehouse
        ]);

        if ($cekStock) {
            $this->query("UPDATE stocks SET qty_total = :qty_total WHERE id = :id", [
                'qty_total' => $real_stock, 'id' => $cekStock['id']
            ]);
        } else {
            $this->insert('stocks', [
                'item_id'   => $item_id, 'warehouse' => $warehouse, 'qty_total' => $real_stock
            ]);
        }
    }

    /**
     * FUNGSI PRIVATE: Menghasilkan struktur SQL filter stok bulanan
     * Dihitung murni dari tabel `item_transactions` tanpa melihat tabel `stocks`
     */
    private function buildFilterQuery($search = '', $warehouse = '', $periodDate = '') {
        if (empty($periodDate)) {
            return ['sql' => '', 'params' => []];
        }
        
        $referenceDate = (strlen($periodDate) === 7) ? $periodDate . '-01' : $periodDate;        
        $startDateTime = date('Y-m-01 00:00:00', strtotime($referenceDate));
        $endDateTime   = date('Y-m-t 23:59:59', strtotime($referenceDate));

        $sql = "SELECT 
                    i.id as id,
                    i.item_name, 
                    i.item_code, 
                    i.unit_price, 
                    i.item_uom,
                    base.warehouse,
                    
                    COALESCE(saldo_awal.qty_open, 0) AS qty_open,
                    COALESCE(mutasi.total_in, 0) AS qty_in,
                    COALESCE(mutasi.total_out, 0) AS qty_out,
                    (COALESCE(saldo_awal.qty_open, 0) + COALESCE(mutasi.total_in, 0) - COALESCE(mutasi.total_out, 0)) AS qty_close,
                    
                    mutasi.last_date AS date
                FROM items i
                
                -- Ambil daftar barang yang pernah ada transaksinya sampai bulan tersebut
                INNER JOIN (
                    SELECT DISTINCT item_id, warehouse FROM item_transactions WHERE transaction_date <= :endDateTime
                ) base ON i.id = base.item_id
                
                -- Hitung Saldo Awal (Total IN - OUT sebelum bulan ini)
                LEFT JOIN (
                    SELECT item_id, warehouse, SUM(CASE WHEN type = 'IN' THEN qty ELSE -qty END) as qty_open
                    FROM item_transactions
                    WHERE transaction_date < :startDateTime
                    GROUP BY item_id, warehouse
                ) saldo_awal ON base.item_id = saldo_awal.item_id AND base.warehouse = saldo_awal.warehouse
                
                -- Hitung Mutasi IN & OUT (Hanya pada bulan ini)
                LEFT JOIN (
                    SELECT item_id, warehouse, 
                           SUM(CASE WHEN type = 'IN' THEN qty ELSE 0 END) as total_in, 
                           SUM(CASE WHEN type = 'OUT' THEN qty ELSE 0 END) as total_out, 
                           MAX(transaction_date) as last_date
                    FROM item_transactions
                    WHERE transaction_date >= :startDateTime AND transaction_date <= :endDateTime
                    GROUP BY item_id, warehouse
                ) mutasi ON base.item_id = mutasi.item_id AND base.warehouse = mutasi.warehouse
                
                WHERE 1=1";

        $params = [
            'startDateTime' => $startDateTime,
            'endDateTime'   => $endDateTime
        ];

        if (!empty($search)) {
            $sql .= " AND (i.item_code LIKE :search OR i.item_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($warehouse !== '') {
            $sql .= " AND base.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }       
        
        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    /**
     * UNTUK PROSES INTERNAL CLOSING (TANPA LIMIT DATA)
     */
    public function getFiltered($search = '', $warehouse = '', $periodDate = '') {
        $query = $this->buildFilterQuery($search, $warehouse, $periodDate);
        if (empty($query['sql'])) return [];

        $sql = $query['sql'] . " ORDER BY i.item_name ASC, base.warehouse ASC";
        return $this->query_all($sql, $query['params']);
    }

    /**
     * UNTUK VIEW DATA TABEL JQUERY STOK BULANAN (DENGAN PAGINASI SERVER-SIDE)
     */
    public function getFilteredPaginated($search = '', $warehouse = '', $periodDate = '', $limit = 10, $offset = 0) {
        $query = $this->buildFilterQuery($search, $warehouse, $periodDate);
        if (empty($query['sql'])) return ['data' => [], 'total' => 0];

        $sql = $query['sql'] . " ORDER BY i.item_name ASC, base.warehouse ASC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

    public function getMonthlyReportPaginated($search = '', $warehouse = '', $monthPeriod = '', $limit = 25, $offset = 0) {
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

        return $this->query_paginated($sql, $params, $limit, $offset);
    }

    public function getClosingDataPaginated($search = '', $warehouse = '', $monthPeriod = '', $limit = 25, $offset = 0) {
        $sqlCheck = "SELECT is_closed FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :monthPeriod LIMIT 1";
        $lock = $this->query_one($sqlCheck, ['monthPeriod' => $monthPeriod]);
        $isClosed = ($lock && $lock['is_closed'] == 1);
        
        if ($isClosed) {
            $result = $this->getMonthlyReportPaginated($search, $warehouse, $monthPeriod, $limit, $offset);
            return [
                'status' => 'CLOSED',
                'data'   => $result['data'],
                'total'  => $result['total']
            ];
        } else {
            $periodDate = date('Y-m-t', strtotime($monthPeriod . '-01'));
            $result = $this->getFilteredPaginated($search, $warehouse, $periodDate, $limit, $offset);
            return [
                'status' => 'ONGOING',
                'data'   => $result['data'],
                'total'  => $result['total']
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
    
    public function isPeriodClosed($monthPeriod, $warehouse = '') {
        $sql = "SELECT is_closed FROM stock_closing WHERE DATE_FORMAT(date, '%Y-%m') = :monthPeriod";
        $params = ['monthPeriod' => $monthPeriod];
        
        if ($warehouse !== '') {
            $sql .= " AND warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }
        $sql .= " LIMIT 1";
        
        $check = $this->query_one($sql, $params);
        return ($check && $check['is_closed'] == 1);
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