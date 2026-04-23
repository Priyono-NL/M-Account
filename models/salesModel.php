<?php
require_once '_dbHelper.php';

class SalesModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function saveTransaction($cart, $buyer_id, $warehouse) {
        try {
            $this->db->beginTransaction();

            $invoice_no = 'INV/' . date('Ymd') . '/' . rand(100, 999); 
            $date_now = date('Y-m-d');

            $saleData = [
                'sale_type'  => 'SLS',
                'invoice_no' => $invoice_no,
                'buyer'      => $buyer_id,
                'warehouse'  => $warehouse,
                'sales_date' => $date_now
            ];
            $sale_id = $this->insert('sales', $saleData);

            foreach ($cart as $item) {
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
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getSalesHistory() {
        return $this->getAll('sales', null, 'sales_date DESC');
    }
}
?>