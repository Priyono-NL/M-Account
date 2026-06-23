<?php
require_once '_dbHelper.php';

class SalesModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    /**
     * TRANSACTION MUTATION: Menyimpan data POS Penjualan Baru & Memotong Stok Kartu
     */
    public function saveTransaction($cart, $buyer_id, $warehouse, $sales_date, $sales_type, $is_edit_mode = 0, $sale_id = null) {
        try {
            $this->satpamGembok($sales_date, $warehouse);
            $this->beginTransaction();

            $stockLogTimestamp = $sales_date . ' ' . date('H:i:s');
            $current_sale_id = null;

            if ($is_edit_mode == 1 && $sale_id) {
                // ==========================================
                // EDIT INVOICE LAMA
                // ==========================================
                $oldHeader = $this->query_one("SELECT invoice_no FROM sales WHERE id = :id", ['id' => $sale_id]);
                if (!$oldHeader) throw new Exception("Data transaksi lama tidak ditemukan.");
                $invoice_no = $oldHeader['invoice_no'];
                $current_sale_id = $sale_id;

                $oldItems = $this->query_all("SELECT item_id, item_qty FROM sales_detail WHERE sale_id = :id", ['id' => $sale_id]);
                $oldItemsMap = [];
                foreach ($oldItems as $old) {
                    $oldItemsMap[$old['item_id']] = (float)$old['item_qty'];
                }
                
                foreach ($oldItemsMap as $old_item_id => $old_qty) {
                    $lastStockRow = $this->query_one("SELECT qty_total FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse ORDER BY id DESC LIMIT 1 FOR UPDATE", [
                        'item_id' => $old_item_id, 'warehouse' => $warehouse
                    ]);
                    
                    $qty_open = $lastStockRow ? (float)$lastStockRow['qty_total'] : 0;
                    $qty_total = $qty_open + $old_qty;

                    $this->insert('stocks', [
                        'item_id'   => $old_item_id,
                        'warehouse' => $warehouse,
                        'date'      => $stockLogTimestamp,
                        'qty_open'  => $qty_open,
                        'qty_in'    => $old_qty, 
                        'qty_out'   => 0,
                        'qty_total' => $qty_total,
                    ]);
                }

                foreach ($cart as $item) {
                    $item_id = $item['id'];
                    $new_qty = (float)$item['qty'];

                    $lastStockRow = $this->query_one("SELECT qty_total FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse ORDER BY id DESC LIMIT 1 FOR UPDATE", [
                        'item_id' => $item_id, 'warehouse' => $warehouse
                    ]);
                    
                    $qty_open = $lastStockRow ? (float)$lastStockRow['qty_total'] : 0;
                    $qty_total = $qty_open - $new_qty; 

                    $this->insert('stocks', [
                        'item_id'   => $item_id,
                        'warehouse' => $warehouse,
                        'date'      => $stockLogTimestamp,
                        'qty_open'  => $qty_open,
                        'qty_in'    => 0, 
                        'qty_out'   => $new_qty,     
                        'qty_total' => $qty_total,
                    ]);

                    if (isset($oldItemsMap[$item_id])) {
                        $this->query_one("UPDATE sales_detail SET item_qty = :qty WHERE sale_id = :sale_id AND item_id = :item_id", [
                            'qty' => $new_qty, 'sale_id' => $sale_id, 'item_id' => $item_id
                        ]);

                        $this->query_one("UPDATE item_transactions SET qty = :qty, transaction_date = :date WHERE reference_no = :inv AND item_id = :item_id AND type = 'OUT'", [
                            'qty' => $new_qty, 'date' => $stockLogTimestamp, 'inv' => $invoice_no, 'item_id' => $item_id
                        ]);
                        
                        unset($oldItemsMap[$item_id]);
                    } else {
                        $this->insert('sales_detail', [
                            'sale_id' => $sale_id, 'item_id' => $item_id, 'item_qty' => $new_qty 
                        ]);

                        $this->insert('item_transactions', [
                            'item_id' => $item_id, 'warehouse' => $warehouse, 'transaction_date' => $stockLogTimestamp,
                            'type' => 'OUT', 'qty' => $new_qty, 'reference_no' => $invoice_no, 'notes' => "Penjualan - $invoice_no (Edit)"
                        ]);
                    }
                }

                foreach ($oldItemsMap as $old_item_id => $old_qty) {
                    $this->query_one("DELETE FROM sales_detail WHERE sale_id = :sale_id AND item_id = :item_id", [
                        'sale_id' => $sale_id, 'item_id' => $old_item_id
                    ]);
                    $this->query_one("DELETE FROM item_transactions WHERE reference_no = :inv AND item_id = :item_id AND type = 'OUT'", [
                        'inv' => $invoice_no, 'item_id' => $old_item_id
                    ]);
                }

            } else {
                // ==========================================
                // TRANSAKSI BARU
                // ==========================================
                $prefix = ($sales_type == 'EXP') ? 'EXP - ' : 'SLS - ';            
                
                $lastRecord = $this->query_one(
                    "SELECT invoice_no FROM sales WHERE invoice_no LIKE :prefix ORDER BY id DESC LIMIT 1 FOR UPDATE", 
                    ['prefix' => $prefix . '%']
                );
                
                $nextNum = 1;
                if ($lastRecord) {
                    $lastNum = (int) substr($lastRecord['invoice_no'], 6);
                    $nextNum = $lastNum + 1; 
                }
                $invoice_no = $prefix . $nextNum;

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

                $sqlLastStock = "SELECT qty_total FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse ORDER BY id DESC LIMIT 1 FOR UPDATE";

                foreach ($cart as $item) {
                    $lastStockRow = $this->query_one($sqlLastStock, [
                        'item_id'   => $item['id'], 
                        'warehouse' => $warehouse
                    ]);
                    
                    $qty_open  = $lastStockRow ? (float)$lastStockRow['qty_total'] : 0;
                    $qty_out   = (float)$item['qty'];
                    $qty_total = $qty_open - $qty_out;

                    $this->insert('sales_detail', [
                        'sale_id'  => $sale_id,
                        'item_id'  => $item['id'],
                        'item_qty' => $item['qty'] 
                    ]);
                    
                    $this->insert('item_transactions', [
                        'item_id'          => $item['id'],
                        'warehouse'        => $warehouse,
                        'transaction_date' => $stockLogTimestamp,
                        'type'             => 'OUT',
                        'qty'              => $item['qty'],
                        'reference_no'     => $invoice_no,
                        'notes'            => "Penjualan - $invoice_no"
                    ]);

                    $this->insert('stocks', [
                        'item_id'   => $item['id'],
                        'warehouse' => $warehouse,
                        'date'      => $stockLogTimestamp,
                        'qty_open'  => $qty_open,
                        'qty_in'    => 0,
                        'qty_out'   => $qty_out,
                        'qty_total' => $qty_total,
                    ]);
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
        $sql = "SELECT s.*, b.buyer_name, b.buyer_code,
                    (SELECT SUM(sd.item_qty * i.unit_price) 
                    FROM sales_detail sd
                    JOIN items i ON sd.item_id = i.id
                    WHERE sd.sale_id = s.id) as total
                FROM sales s
                LEFT JOIN buyer b ON s.buyer = b.id
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
     * VIEW DETAIL: Mengambil produk-produk di dalam satu nomor transaksi keluar
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
	
    public function getTransactionItems($sale_id, $warehouse_id = null) {
        $sql = "SELECT sd.*, i.item_code, i.item_name, i.unit_price, i.item_uom,
                    COALESCE((
                        SELECT s.qty_total 
                        FROM stocks s 
                        WHERE s.item_id = i.id AND s.warehouse = :warehouse_id
                        ORDER BY s.id DESC 
                        LIMIT 1
                    ), 0) as current_stock
                FROM sales_detail sd 
                LEFT JOIN items i ON sd.item_id = i.id
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
        return $this->query_one($sql, ['id' => $sales_id]);
    }
}
?>