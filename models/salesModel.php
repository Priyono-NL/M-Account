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

    public function getSalesHistory() {
        return $this->getAll('sales', null, 'sales_date DESC');
    }
}
?>