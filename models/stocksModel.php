<?php
require_once '_dbHelper.php';

class StocksModel extends DatabaseHelper {
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 1. MENGAMBIL STOK SAAT INI (ON-HAND)
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
     * 2. DATA KARTU STOK (STOCK CARD) KRONOLOGIS
     */
    public function getStockCard($item_id, $warehouse, $startDate, $endDate) {
        $sqlOpen = "SELECT (
                        COALESCE((
                            SELECT SUM(rd.item_qty) 
                            FROM receievement_detail rd JOIN receievement r ON rd.receive_id = r.id
                            WHERE rd.item_id = :item_id AND r.warehouse = :warehouse AND r.date_receive < :startDate
                        ), 0)
                        -
                        COALESCE((
                            SELECT SUM(sd.item_qty) 
                            FROM sales_detail sd JOIN sales s ON sd.sale_id = s.id
                            WHERE sd.item_id = :item_id AND s.warehouse = :warehouse AND s.sales_date < :startDate
                        ), 0)
                    ) as qty_opening";
        
        $resOpen = $this->query_one($sqlOpen, [
            'item_id' => $item_id, 'warehouse' => $warehouse, 'startDate' => $startDate . ' 00:00:00'
        ]);
        $openingBalance = $resOpen ? (float)$resOpen['qty_opening'] : 0;

        $sqlTrans = "SELECT * FROM (
                        SELECT r.id AS trans_id, r.date_receive AS trans_date, r.doc_number AS trans_code, 
                               'IN' AS type, rd.item_qty AS qty, 'Penerimaan Barang' AS notes
                        FROM receievement_detail rd JOIN receievement r ON rd.receive_id = r.id
                        WHERE rd.item_id = :item_id AND r.warehouse = :warehouse
                        
                        UNION ALL
                        
                        SELECT s.id AS trans_id, s.sales_date AS trans_date, s.invoice_no AS trans_code, 
                               'OUT' AS type, sd.item_qty AS qty, 'Penjualan' AS notes
                        FROM sales_detail sd JOIN sales s ON sd.sale_id = s.id
                        WHERE sd.item_id = :item_id AND s.warehouse = :warehouse
                     ) AS mutasi
                     WHERE trans_date BETWEEN :startDate AND :endDate
                     ORDER BY trans_date ASC, trans_id ASC";

        $transactions = $this->query_all($sqlTrans, [
            'item_id' => $item_id, 'warehouse' => $warehouse, 
            'startDate' => $startDate . ' 00:00:00', 'endDate' => $endDate . ' 23:59:59'
        ]);

        $cardData = [];
        $running = $openingBalance;
        $cardData[] = ['date' => $startDate, 'code' => '-', 'notes' => 'SALDO AWAL', 'in' => 0, 'out' => 0, 'balance' => $running];

        foreach ($transactions as $t) {
            $in  = ($t['type'] === 'IN') ? (float)$t['qty'] : 0;
            $out = ($t['type'] === 'OUT') ? (float)$t['qty'] : 0;
            $running += ($in - $out);
            $cardData[] = ['date' => $t['trans_date'], 'code' => $t['trans_code'], 'notes' => $t['notes'], 'in' => $in, 'out' => $out, 'balance' => $running];
        }

        return ['opening' => $openingBalance, 'closing' => $running, 'mutations' => $cardData];
    }

    /**
     * 3. LAPORAN STOK BULANAN (PAGINASI JQUERY DATATABLES)
     */
    public function getMonthlyReportPaginated($search = '', $warehouse = '', $monthPeriod = '', $limit = 25, $offset = 0) {
        $sql = "SELECT * FROM vw_laporan_stok WHERE 1=1";        
        $params = [];

        if (!empty($monthPeriod)) {
            $sql .= " AND period = :monthPeriod";
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

    /**
     * 4. CEK APAKAH PERIODE SUDAH DI-CLOSING
     */
    public function isPeriodClosed($monthPeriod, $warehouse = '') {
        $sql = "SELECT id FROM stock_closing_log WHERE periode = :monthPeriod";
        $params = ['monthPeriod' => $monthPeriod];
        if ($warehouse !== '') {
            $sql .= " AND warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }
        $sql .= " LIMIT 1";
        $check = $this->query_one($sql, $params);
        return !empty($check);
    }

    /**
     * 5. PROSES CLOSING STOK (HIGH PERFORMANCE OPTIMIZED)
     */
    public function doClosing($monthPeriod = '', $warehouse = '', $executedBy = 'System') {
        if ($this->isPeriodClosed($monthPeriod, $warehouse)) {
            throw new Exception("Periode {$monthPeriod} sudah dikunci permanen!");
        }

        // Pengecekan 9: Deteksi gudang terdampak secara ringan tanpa me-load seluruh baris produk ke memory RAM PHP
        $sqlCheck = "SELECT DISTINCT warehouse FROM vw_laporan_stok WHERE period = :monthPeriod";
        $checkParams = ['monthPeriod' => $monthPeriod];
        if ($warehouse !== '') {
            $sqlCheck .= " AND warehouse = :warehouse";
            $checkParams['warehouse'] = $warehouse;
        }
        $warehousesToLog = $this->query_all($sqlCheck, $checkParams);

        if (empty($warehousesToLog)) {
            throw new Exception("Tidak ada data stok pada periode ini untuk di-closing.");
        }

        // Tanggal 1 bulan depan (Untuk record saldo awal bulan depan)
        $nextMonthDate = date('Y-m-01', strtotime($monthPeriod . '-01 +1 month'));
        $insertedCount = 0;

        try {
            $this->beginTransaction();
            
            // Hapus data awal bulan depan jika sebelumnya pernah ada error parsial (Mencegah duplikat)
            $sqlDel = "DELETE FROM stocks_period WHERE stock_date = :nextMonthDate";
            $delParams = ['nextMonthDate' => $nextMonthDate];
            if ($warehouse !== '') {
                $sqlDel .= " AND warehouse = :warehouse";
                $delParams['warehouse'] = $warehouse;
            }
            $stmtDel = $this->db->prepare($sqlDel);
            $stmtDel->execute($delParams);

            // Pengecekan 9: Bulk Insert langsung di level database MySQL (INSERT INTO ... SELECT)
            $sqlInsert = "INSERT INTO stocks_period (item_id, warehouse, stock_date, qty)
                          SELECT item_id, warehouse, :nextMonthDate, qty_close 
                          FROM vw_laporan_stok 
                          WHERE period = :monthPeriod";
            
            $insertParams = [
                'nextMonthDate' => $nextMonthDate,
                'monthPeriod'   => $monthPeriod
            ];

            if ($warehouse !== '') {
                $sqlInsert .= " AND warehouse = :warehouse";
                $insertParams['warehouse'] = $warehouse;
            }

            $stmtInsert = $this->db->prepare($sqlInsert);
            $stmtInsert->execute($insertParams);
            $insertedCount = $stmtInsert->rowCount(); // Tangkap jumlah baris data yang sukses terproses
                        
            // Catat log closing untuk masing-masing gudang yang sukses terproses
            foreach ($warehousesToLog as $wh) {
                $this->insert('stock_closing_log', [
                    'periode'     => $monthPeriod,
                    'warehouse'   => $wh['warehouse'],
                    'executed_by' => $executedBy
                ]);
            }
            
            $this->commit();
            return $insertedCount;
            
        } catch (Exception $e) {
            $this->rollBack();
            throw new Exception("Gagal menyimpan closing: " . $e->getMessage());
        }
    }

    /**
     * MENGAMBIL RIWAYAT CLOSING STOK
     */
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
}
?>