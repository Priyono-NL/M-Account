<?php
require_once '_dbHelper.php';

class StockInModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    /**
     * TRANSACTION MUTATION: Menyimpan data penerimaan dan memperbarui stok (ACID Transaction)
     */
    public function saveReceivement($cart, $doc_number, $received_by, $warehouse, $date_receive, $notes) {
        try {
            $this->satpamGembok($date_receive, $warehouse);

            $this->beginTransaction();

            $receiveData = [
                'doc_number'    => $doc_number,
                'received_by'   => $received_by,
                'warehouse'     => $warehouse,
                'date_receive'  => $date_receive,
                'notes'         => $notes
            ];
            
            $receive_id = $this->insert('receivement', $receiveData);
            
            if (!$receive_id) {
                throw new Exception("Gagal menyimpan data Master Receivement.");
            }

            $sqlLastStock = "SELECT qty_total FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse ORDER BY id DESC LIMIT 1 FOR UPDATE";

            $stockLogTimestamp = $date_receive . ' ' . date('H:i:s');

            foreach ($cart as $item) {
                $lastStockRow = $this->query_one($sqlLastStock, [
                    'item_id'   => $item['id'], 
                    'warehouse' => $warehouse
                ]);
                
                $qty_open  = $lastStockRow ? (float)$lastStockRow['qty_total'] : 0;
                $qty_in    = (float)$item['qty'];
                $qty_total = $qty_open + $qty_in;

                $this->insert('receivement_detail', [
                    'receive_id' => $receive_id,
                    'item_id'    => $item['id'],
                    'item_qty'   => $item['qty'] 
                ]);
                
                $this->insert('item_transactions', [
                    'item_id'          => $item['id'],
                    'warehouse'        => $warehouse,
                    'transaction_date' => $stockLogTimestamp,
                    'type'             => 'IN',
                    'qty'              => $item['qty'],
                    'reference_no'     => $doc_number,
                    'notes'            => "Penerimaan Baru - $doc_number"
                ]);

                $this->insert('stocks', [
                    'item_id'   => $item['id'],
                    'warehouse' => $warehouse,
                    'date'      => $stockLogTimestamp,
                    'qty_open'  => $qty_open,
                    'qty_in'    => $qty_in,
                    'qty_out'   => 0,
                    'qty_total' => $qty_total,
                ]);
            }

            $this->commit();
            
            return [
                'status'     => 'success', 
                'message'    => 'Transaksi berhasil disimpan!',
                'receive_id' => $receive_id
            ];

        } catch (Exception $e) {
            $this->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()]; 
        }
    }

    /**
     * FUNGSI PRIVATE: Menyusun string SQL base dan binding parameters untuk filter data.
     */
    private function buildFilterQuery($search = '', $warehouse = '', $startDate = '', $endDate = '') {
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
            $sql .= " AND r.date_receive >= :start_date";
            $params['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= " AND r.date_receive <= :end_date";
            $params['end_date'] = $endDate;
        }

        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    /**
     * UNTUK EXPORT EXCEL (TANPA LIMIT/PAGINASI)
     */
    public function getFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate);
        
        $sql = $query['sql'] . " ORDER BY r.date_receive DESC, r.id DESC";

        return $this->query_all($sql, $query['params']);
    }

    /**
     * UNTUK VIEW DATA TABEL JQUERY (DENGAN PAGINASI SERVER-SIDE)
     */
    public function getFilteredPaginated($search = '', $warehouse = '', $startDate = '', $endDate = '', $limit = 25, $offset = 0) {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate);
        
        $sql = $query['sql'] . " ORDER BY r.date_receive DESC, r.id DESC";

        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

    /**
     * VIEW DETAIL: Mengambil produk-produk di dalam satu nomor transaksi penerimaan
     */
    public function getTransactionItems($receive_id) {
        $sql = "SELECT rd.*, i.item_code, i.item_name, i.unit_price, i.item_uom
                FROM receivement_detail rd
                LEFT JOIN items i ON rd.item_id = i.id 
                WHERE rd.receive_id = :receive_id";
                
        return $this->query_all($sql, ['receive_id' => (int)$receive_id]);
    }

    /**
     * CEK NOMOR DOKUMEN DUPLIKAT
     */
    public function getByDocNumber($doc_number) {
        $sql = "SELECT * FROM receivement WHERE doc_number = :doc_number LIMIT 1";
        $result = $this->query_one($sql, ['doc_number' => $doc_number]);        
        return $result ? true : false;
    }
}
?>