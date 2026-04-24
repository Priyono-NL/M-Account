<?php
require_once '_dbHelper.php';

class SalesModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function saveTransaction($cart, $buyer_id, $warehouse, $sales_date, $sales_type) {
        try {
            $this->db->beginTransaction();

            $invoice_no = 'INV/' . date('Ymd') . '/' . rand(100, 999); 

            $saleData = [
                'sale_type'  => $sales_type,
                'invoice_no' => $invoice_no,
                'buyer'      => $buyer_id,
                'warehouse'  => $warehouse,
                'sales_date' => $sales_date,
            ];
            
            $sale_id = $this->insert('sales', $saleData);
            
            if (!$sale_id) {
                throw new Exception("Gagal menyimpan data Master Sales.");
            }

            foreach ($cart as $item) {
                $sqlLastStock = "SELECT qty_close FROM stocks 
                                WHERE item_id = :item_id AND warehouse = :warehouse 
                                ORDER BY id DESC LIMIT 1";
                $stmt = $this->db->prepare($sqlLastStock);
                $stmt->execute([':item_id' => $item['id'], ':warehouse' => $warehouse]);
                $lastStockRow = $stmt->fetch(PDO::FETCH_ASSOC);
                $qty_open = $lastStockRow ? $lastStockRow['qty_close'] : 0;
                $qty_out  = $item['qty'];
                $qty_close = $qty_open - $qty_out;

                $this->insert('sales_detail', [
                    'sale_id'  => $sale_id,
                    'item_id'  => $item['id'],
                    'item_qty' => $item['qty'] 
                ]);
                
                $this->insert('item_transactions', [
                    'item_id'          => $item['id'],
                    'warehouse'        => $warehouse,
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'type'             => 'OUT',
                    'qty'              => $item['qty'],
                    'reference_no'     => $invoice_no,
                    'notes'            => 'Penjualan POS'
                ]);

                $this->insert('stocks', [
                    'item_id'          => $item['id'],
                    'warehouse'        => $warehouse,
                    'date' => date('Y-m-d H:i:s'),
                    'qty_open'         => $qty_open,
                    'qty_in'           => 0,
                    'qty_out'          => $qty_out,
                    'qty_close'        => $qty_close,
                ]);
            }

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Transaksi berhasil disimpan!'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => 'Gagal DB: ' . $e->getMessage()];
        }
    }

    public function getFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '', $type = '') {
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
            $sql .= " AND DATE(s.sales_date) >= :start_date";
            $params['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= " AND DATE(s.sales_date) <= :end_date";
            $params['end_date'] = $endDate;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransactionItems($sale_id) {
        $sql = "SELECT sd.*, i.item_code, i.item_name, i.unit_price, i.item_uom
                FROM sales_detail sd 
                LEFT JOIN items i ON sd.item_id = i.id 
                WHERE sd.sale_id = :sale_id";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sale_id' => $sale_id]);
        return $stmt->fetchAll();
    }
}
?>