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
                $sqlLastStock = "SELECT qty_total FROM stocks 
                                WHERE item_id = :item_id AND warehouse = :warehouse 
                                ORDER BY id DESC LIMIT 1";
                $stmt = $this->db->prepare($sqlLastStock);
                $stmt->execute([':item_id' => $item['id'], ':warehouse' => $warehouse]);
                $lastStockRow = $stmt->fetch(PDO::FETCH_ASSOC);
                $qty_open = $lastStockRow ? $lastStockRow['qty_total'] : 0;
                $qty_in = $item['qty'];
                $qty_total = $qty_open + $qty_in;

                $this->insert('receivement_detail', [
                    'receive_id'  => $receive_id,
                    'item_id'  => $item['id'],
                    'item_qty' => $item['qty'] 
                ]);
                
                $this->insert('item_transactions', [
                    'item_id'          => $item['id'],
                    'warehouse'        => $warehouse,
                    'transaction_date' => $date_receive,
                    'type'             => 'IN',
                    'qty'              => $item['qty'],
                    'reference_no'     => $doc_number,
                    'notes'            => "Penerimaan Baru - $doc_number"
                ]);

                $this->insert('stocks', [
                    'item_id'          => $item['id'],
                    'warehouse'        => $warehouse,
                    'date' => date('Y-m-d H:i:s'),
                    'qty_open'         => $qty_open,
                    'qty_in'           => $qty_in,
                    'qty_out'          => 0,
                    'qty_total'        => $qty_total,
                ]);
            }

            $this->db->commit();
            return ['status' => 'success', 'message' => 'Transaksi berhasil disimpan!'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'message' => 'Gagal DB: ' . $e->getMessage()];
        }
    }

    public function getFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $sql = "SELECT r.* FROM receivement r WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (r.received_by LIKE :search OR r.doc_number LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($warehouse !== '') {
            $sql .= " AND r.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(r.date_receive) >= :start_date";
            $params['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= " AND DATE(r.date_receive) <= :end_date";
            $params['end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransactionItems($receive_id) {
        $sql = "SELECT rd.*, i.item_code, i.item_name, i.unit_price, i.item_uom
                FROM receivement_detail rd
                LEFT JOIN items i ON rd.item_id = i.id 
                WHERE rd.receive_id = :receive_id";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['receive_id' => $receive_id]);
        return $stmt->fetchAll();
    }
}
?>