<?php
require_once '_dbHelper.php';

class SalesModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    /**
     * FUNGSI SINKRONISASI STOK GLOBAL (Single Source of Truth)
     */
    private function syncCurrentStock($item_id, $warehouse) {
        // 1. Dapatkan string periode bulan berjalan (Contoh: '2026-07')
        $currentPeriod = date('Y-m'); 
        $startOfMonth = date('Y-m-01'); // Untuk mencocokkan tanggal di stocks_period

        // 2. Ambil Saldo Awal Bulan Ini (Dari hasil Closing bulan lalu)
        $sqlOpen = "SELECT qty FROM stocks_period 
                    WHERE item_id = :item_id AND warehouse = :warehouse AND stock_date = :start_date 
                    LIMIT 1";
        $resOpen = $this->query_one($sqlOpen, [
            'item_id' => $item_id, 'warehouse' => $warehouse, 'start_date' => $startOfMonth
        ]);
        $qtyOpen = $resOpen ? (float)$resOpen['qty'] : 0;

        // 3. Ambil Total IN dan OUT Bulan Ini SAJA dari vw_mutasi_bulanan menggunakan filter `period`
        $sqlMutasi = "SELECT 
                        COALESCE(SUM(qty_in), 0) as total_in, 
                        COALESCE(SUM(qty_out), 0) as total_out 
                      FROM vw_mutasi_bulanan 
                      WHERE item_id = :item_id 
                        AND warehouse = :warehouse 
                        AND period = :current_period";
                        
        $mutasi = $this->query_one($sqlMutasi, [
            'item_id' => $item_id, 
            'warehouse' => $warehouse, 
            'current_period' => $currentPeriod
        ]);
        
        $totalIn = $mutasi ? (float)$mutasi['total_in'] : 0;
        $totalOut = $mutasi ? (float)$mutasi['total_out'] : 0;

        // 4. Kalkulasi Stok Riil Saat Ini
        $real_stock = $qtyOpen + $totalIn - $totalOut;

        // 5. Simpan/Update ke tabel `stocks`
        $cekStock = $this->query_one("SELECT id FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse LIMIT 1", [
            'item_id' => $item_id, 'warehouse' => $warehouse
        ]);

        if ($cekStock) {
            $this->query("UPDATE stocks SET qty_total = :qty_total, updated_at = NOW() WHERE id = :id", [
                'qty_total' => $real_stock, 'id' => $cekStock['id']
            ]);
        } else {
            $this->insert('stocks', [
                'item_id'   => $item_id, 
                'warehouse' => $warehouse, 
                'qty_total' => $real_stock
            ]);
        }
    }

    /**
     * TRANSACTION MUTATION: Menyimpan data POS Penjualan & Memotong Stok
     * Menggunakan Snapshot Sync & Wipe Replace untuk mode edit
     */
    public function saveTransaction($cart, $buyer_id, $warehouse, $sales_date, $sales_type, $is_edit_mode = 0, $sale_id = null, $last_updated_at = null) {
        try {
            $this->satpamGembok($sales_date, $warehouse);
            $this->beginTransaction();

            $stockLogTimestamp = $sales_date . ' ' . date('H:i:s');
            $current_sale_id = null;
            $affected_items = []; // Kumpulkan ID item yang butuh disinkronisasi

            if ($is_edit_mode == 1 && $sale_id) {
                // ==========================================
                // EDIT INVOICE LAMA (WIPE & REPLACE)
                // ==========================================
                $oldHeader = $this->query_one("SELECT invoice_no, updated_at FROM sales WHERE id = :id FOR UPDATE", ['id' => $sale_id]);
                
                if (!$oldHeader) throw new Exception("Data transaksi lama tidak ditemukan.");

                $js_updated_at = (string)($last_updated_at ?? '');
                $db_updated_at = (string)($oldHeader['updated_at'] ?? '');
                
                if ($js_updated_at !== '' && $db_updated_at !== '' && $db_updated_at !== $js_updated_at) {
                    throw new Exception("Gagal menyimpan! Invoice ini baru saja diedit oleh user lain. Silakan tutup dan buka ulang.");
                }

                $invoice_no = $oldHeader['invoice_no'];
                $current_sale_id = $sale_id;

                // 1. Kumpulkan ID item lama untuk di-sync
                $oldItems = $this->query_all("SELECT item_id FROM sales_detail WHERE sale_id = :id", ['id' => $sale_id]);
                foreach ($oldItems as $old) {
                    $affected_items[] = $old['item_id'];
                }

                // 2. WIPE (HAPUS BERSIH DATA LAMA)
                $this->query("DELETE FROM sales_detail WHERE sale_id = :id", ['id' => $sale_id]);

                // 3. UPDATE HEADER
                $this->query("UPDATE sales SET buyer = :buyer, sales_date = :sales_date, warehouse = :warehouse, updated_at = NOW() WHERE id = :id", [
                    'buyer' => $buyer_id, 'sales_date' => $sales_date, 'warehouse' => $warehouse, 'id' => $sale_id
                ]);

                // 4. GABUNGKAN ITEM KERANJANG BARU
                $aggregatedCart = [];
                foreach ($cart as $item) {
                    $iid = $item['id'];
                    if (!isset($aggregatedCart[$iid])) $aggregatedCart[$iid] = $item;
                    else $aggregatedCart[$iid]['qty'] += (float)$item['qty'];
                }

                // 5. MASUKKAN DATA BARU
                foreach ($aggregatedCart as $item) {
                    $item_id = $item['id'];
                    $new_qty = (float)$item['qty'];
                    $affected_items[] = $item_id;

                    $this->insert('sales_detail', [
                        'sale_id' => $sale_id, 'item_id' => $item_id, 'item_qty' => $new_qty 
                    ]);
                }

            } else {
                // ==========================================
                // TRANSAKSI BARU
                // ==========================================
                $prefix = ($sales_type == 'EXP') ? 'EXP-' : 'SLS-';            
                $year = date('Y');

                $searchPrefix = $prefix . $year . '-';                
                $lastRecord = $this->query_one(
                    "SELECT invoice_no FROM sales WHERE invoice_no LIKE :prefix ORDER BY id DESC LIMIT 1 FOR UPDATE", 
                    ['prefix' => $searchPrefix . '%']
                );

                $nextNum = 1;
                if ($lastRecord) {
                    $parts = explode('-', $lastRecord['invoice_no']);                    
                    $lastNum = (int) end($parts);
                    $nextNum = $lastNum + 1; 
                }                
                $formattedNum = str_pad($nextNum, 5, '0', STR_PAD_LEFT);

                $invoice_no = $searchPrefix . $formattedNum;

                $saleData = [
                    'sale_type'  => $sales_type,
                    'invoice_no' => $invoice_no,
                    'buyer'      => $buyer_id,
                    'warehouse'  => $warehouse,
                    'sales_date' => $sales_date,
                ];
                
                $sale_id = $this->insert('sales', $saleData);
                if (!$sale_id) throw new Exception("Gagal menyimpan data Master Sales.");
                
                $current_sale_id = $sale_id;

                $aggregatedCart = [];
                foreach ($cart as $item) {
                    $iid = $item['id'];
                    if (!isset($aggregatedCart[$iid])) $aggregatedCart[$iid] = $item;
                    else $aggregatedCart[$iid]['qty'] += (float)$item['qty'];
                }

                foreach ($aggregatedCart as $item) {
                    $item_id = $item['id'];
                    $qty = (float)$item['qty'];
                    $affected_items[] = $item_id;

                    $this->insert('sales_detail', [
                        'sale_id'  => $sale_id, 'item_id'  => $item_id, 'item_qty' => $qty 
                    ]);
                }
            }

            // ==========================================
            // LANGKAH TERAKHIR: SINKRONISASI STOK FINAL
            // ==========================================
            $affected_items = array_unique($affected_items);
            foreach ($affected_items as $item_id) {
                $this->syncCurrentStock($item_id, $warehouse);
            }

            $this->commit();
            return ['sale_id' => $current_sale_id, 'status' => 'success', 'message' => 'Transaksi berhasil disimpan!'];

        } catch (Exception $e) {
            $this->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * FUNGSI PRIVATE: Menghasilkan query filter WHERE yang bersih dari instruksi ORDER BY.
     */
    private function buildFilterQuery($search = '', $warehouse = '', $startDate = '', $endDate = '', $type = '') {
        $sql = "SELECT s.*, b.buyer_name, b.buyer_code, w.warehouse_name,
                    (SELECT SUM(sd.item_qty * i.unit_price) 
                    FROM sales_detail sd
                    JOIN items i ON sd.item_id = i.id
                    WHERE sd.sale_id = s.id) as total
                FROM sales s
                LEFT JOIN buyer b ON s.buyer = b.id
                LEFT JOIN warehouse w ON s.warehouse = w.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (b.buyer_name LIKE :search OR b.buyer_code LIKE :search OR s.invoice_no LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        if ($warehouse !== '') {
            $sql .= " AND s.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }
        if ($type !== '') {
            $sql .= " AND s.sale_type = :type";
            $params['type'] = $type;
        }
        if (!empty($startDate)) {
            $sql .= " AND s.sales_date >= :start_date";
            $params['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= " AND s.sales_date <= :end_date";
            $params['end_date'] = $endDate;
        }

        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    /**
     * UNTUK EXPORT EXCEL (TANPA LIMIT/OFFSET)
     */
    public function getFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '', $type = '') {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate, $type);
        $sql = $query['sql'] . " ORDER BY s.sales_date DESC, s.id DESC";
        return $this->query_all($sql, $query['params']);
    }

    /**
     * UNTUK TABEL WEB INTERAKTIF (PAGINASI SERVER-SIDE)
     */
    public function getFilteredPaginated($search = '', $warehouse = '', $startDate = '', $endDate = '', $type = '', $limit = 25, $offset = 0) {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate, $type);
        $sql = $query['sql'] . " ORDER BY s.sales_date DESC, s.id DESC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

    /**
     * VIEW DETAIL HEADER
     */
    public function getSalesHeader($sales_id) {
        $sql = "SELECT s.*, b.buyer_name, b.buyer_code,
                    (SELECT SUM(sd.item_qty * i.unit_price) 
                    FROM sales_detail sd
                    JOIN items i ON sd.item_id = i.id
                    WHERE sd.sale_id = s.id) as total
                FROM sales s
                LEFT JOIN buyer b ON s.buyer = b.id
                WHERE s.id = :id";
        
        return $this->query_one($sql, ['id' => (int)$sales_id]);
    }   
    
    /**
     * VIEW DETAIL ITEMS
     * Subquery dihilangkan karena tabel stocks sudah berupa Snapshot / Saldo Akhir.
     */
    public function getTransactionItems($sale_id, $warehouse_id = null) {
        $sql = "SELECT sd.*, i.item_code, i.item_name, i.unit_price, i.item_uom,
                       COALESCE(s.qty_total, 0) as current_stock
                FROM sales_detail sd 
                LEFT JOIN items i ON sd.item_id = i.id
                LEFT JOIN stocks s ON s.item_id = i.id AND s.warehouse = :warehouse_id
                WHERE sd.sale_id = :sale_id";
                
        return $this->query_all($sql, ['sale_id' => (int)$sale_id, 'warehouse_id' => (int)$warehouse_id]);
    }

    public function searchInvoiceList($keyword) {
        $sql = "SELECT s.id, s.invoice_no, s.sales_date, b.buyer_name, b.buyer_code,
                       (SELECT SUM(sd.item_qty * i.unit_price) 
                        FROM sales_detail sd JOIN items i ON sd.item_id = i.id 
                        WHERE sd.sale_id = s.id) as grand_total
                FROM sales s
                LEFT JOIN buyer b ON s.buyer = b.id
                WHERE s.invoice_no LIKE :keyword OR b.buyer_name LIKE :keyword
                ORDER BY s.sales_date DESC, s.id DESC 
                LIMIT 20";
        
        return $this->query_all($sql, ['keyword' => "%$keyword%"]);
    }

    public function incrementPrintCount($sales_id) {
        $sql = "UPDATE sales SET print_count = print_count + 1 WHERE id = :id";
        return $this->query($sql, ['id' => $sales_id]);
    }
}
?>