<?php
require_once '_dbHelper.php';

class SalesModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    /**
     * OPTIMASI POIN 1: DELTA ADJUSTMENT (HIGH PERFORMANCE)
     * Mengubah nilai stok secara instan tanpa perlu scan view mutasi bulanan.
     */
    private function adjustStock($item_id, $warehouse, $qty_delta) {
        $cekStock = $this->query_one("SELECT id FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse LIMIT 1", [
            'item_id' => $item_id, 'warehouse' => $warehouse
        ]);

        if ($cekStock) {
            $this->query("UPDATE stocks SET qty_total = qty_total + :delta, updated_at = NOW() WHERE id = :id", [
                'delta' => $qty_delta, 'id' => $cekStock['id']
            ]);
        } else {
            $this->insert('stocks', [
                'item_id'   => $item_id, 
                'warehouse' => $warehouse, 
                'qty_total' => $qty_delta
            ]);
        }
    }

    /**
     * TRANSACTION MUTATION: Menyimpan data POS Penjualan & Memotong Stok
     */
    public function saveTransaction($cart, $buyer_id, $warehouse, $sales_date, $sales_type, $remark, $is_edit_mode = 0, $sale_id = null, $last_updated_at = null) {
        try {
            $this->satpamGembok($sales_date, $warehouse);
            $this->beginTransaction();

            $current_sale_id = null;

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

                // 1. Ambil data item lama dan KEMBALIKAN stoknya ke gudang (karena penjualan dibatalkan = +)
                $oldItems = $this->query_all("SELECT item_id, item_qty FROM sales_detail WHERE sale_id = :id", ['id' => $sale_id]);
                foreach ($oldItems as $old) {
                    $this->adjustStock($old['item_id'], $warehouse, (float)$old['item_qty']);
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

                // 5. MASUKKAN DATA BARU & POTONG STOK BARU (-)
                foreach ($aggregatedCart as $item) {
                    $item_id = $item['id'];
                    $new_qty = (float)$item['qty'];

                    $this->insert('sales_detail', [
                        'sale_id' => $sale_id, 'item_id' => $item_id, 'item_qty' => $new_qty 
                    ]);

                    $this->adjustStock($item_id, $warehouse, -$new_qty);
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
                    'remark'     => $remark,
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

                    $this->insert('sales_detail', [
                        'sale_id'  => $sale_id, 'item_id'  => $item_id, 'item_qty' => $qty 
                    ]);

                    $this->adjustStock($item_id, $warehouse, -$qty);
                }
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

    public function getFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '', $type = '') {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate, $type);
        $sql = $query['sql'] . " ORDER BY s.sales_date DESC, s.id DESC";
        return $this->query_all($sql, $query['params']);
    }

    public function getFilteredPaginated($search = '', $warehouse = '', $startDate = '', $endDate = '', $type = '', $limit = 25, $offset = 0) {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate, $type);
        $sql = $query['sql'] . " ORDER BY s.sales_date DESC, s.id DESC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

    public function getSalesHeader($sales_id) {
        $sql = "SELECT s.*, b.buyer_name, b.buyer_code, w.warehouse_name,
                    (SELECT SUM(sd.item_qty * i.unit_price) 
                    FROM sales_detail sd
                    JOIN items i ON sd.item_id = i.id
                    WHERE sd.sale_id = s.id) as total
                FROM sales s
                LEFT JOIN buyer b ON s.buyer = b.id
                LEFT JOIN warehouse w ON s.warehouse = w.id
                WHERE s.id = :id";
        
        return $this->query_one($sql, ['id' => (int)$sales_id]);
    }   
    
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

    public function getDetailedFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '', $type = '') {
        $sql = "SELECT 
                    s.sales_date,
                    s.invoice_no,
                    s.sale_type,
                    w.warehouse_name,
                    b.buyer_name,
                    i.item_code,
                    i.item_name,
                    i.item_uom,
                    sd.item_qty,
                    i.unit_price,
                    (sd.item_qty * i.unit_price) AS subtotal
                FROM sales_detail sd
                JOIN sales s ON sd.sale_id = s.id
                LEFT JOIN items i ON sd.item_id = i.id
                LEFT JOIN buyer b ON s.buyer = b.id
                LEFT JOIN warehouse w ON s.warehouse = w.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " AND s.sales_date BETWEEN :startDate AND :endDate";
            $params['startDate'] = $startDate . ' 00:00:00';
            $params['endDate'] = $endDate . ' 23:59:59';
        }
        if ($warehouse !== '') {
            $sql .= " AND s.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }
        if (!empty($type)) {
            $sql .= " AND s.sale_type = :type";
            $params['type'] = $type;
        }
        if (!empty($search)) {
            $sql .= " AND (s.invoice_no LIKE :search OR b.buyer_name LIKE :search OR i.item_name LIKE :search OR i.item_code LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        $sql .= " ORDER BY s.sales_date ASC, s.invoice_no ASC, i.item_name ASC";
        return $this->query_all($sql, $params);
    }
}
?>