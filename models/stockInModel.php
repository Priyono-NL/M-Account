<?php
require_once '_dbHelper.php';

class StockInModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function saveReceivement($cart, $doc_number, $received_by, $warehouse, $date_receive) {
        try {
            $this->db->beginTransaction();

            $receiveData = [
                'doc_number'    => $doc_number,
                'received_by'   => $received_by,
                'warehouse'     => $warehouse,
                'date_receive'  => $date_receive,
            ];
            
            $receive_id = $this->insert('receivement', $receiveData);
            
            if (!$receive_id) {
                throw new Exception("Gagal menyimpan data Master Receivement.");
            }

            foreach ($cart as $item) {
                $sqlLastStock = "SELECT qty_close FROM stocks 
                                WHERE item_id = :item_id AND warehouse = :warehouse 
                                ORDER BY id DESC LIMIT 1";
                $stmt = $this->db->prepare($sqlLastStock);
                $stmt->execute([':item_id' => $item['id'], ':warehouse' => $warehouse]);
                $lastStockRow = $stmt->fetch(PDO::FETCH_ASSOC);
                $qty_open = $lastStockRow ? $lastStockRow['qty_close'] : 0;
                $qty_in = $item['qty'];
                $qty_close = $qty_open + $qty_in;

                $this->insert('receivement_detail', [
                    'receive_id'  => $receive_id,
                    'item_id'  => $item['id'],
                    'item_qty' => $item['qty'] 
                ]);
                
                $this->insert('item_transactions', [
                    'item_id'          => $item['id'],
                    'warehouse'        => $warehouse,
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'type'             => 'IN',
                    'qty'              => $item['qty'],
                    'reference_no'     => $doc_number,
                    'notes'            => 'Penerimaan Barang Baru'
                ]);

                $this->insert('stocks', [
                    'item_id'          => $item['id'],
                    'warehouse'        => $warehouse,
                    'date' => date('Y-m-d H:i:s'),
                    'qty_open'         => $qty_open,
                    'qty_in'           => $qty_in,
                    'qty_out'          => 0,
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
        return $this->getAll('sales', null, 'date_receive DESC');
    }
}
?>