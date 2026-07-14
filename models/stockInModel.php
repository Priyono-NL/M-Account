<?php
require_once '_dbHelper.php';

class StockInModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function searchReceiveList($keyword) {
        $sql = "SELECT id, doc_number, date_receive, received_by, notes 
                FROM receivement 
                WHERE doc_number LIKE :keyword OR received_by LIKE :keyword
                ORDER BY date_receive DESC, id DESC 
                LIMIT 20";
        
        return $this->query_all($sql, ['keyword' => "%$keyword%"]);
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
     * TRANSACTION MUTATION: Menyimpan data penerimaan dan memperbarui stok
     */
    public function saveReceivement($cart, $doc_number, $received_by, $warehouse, $date_receive, $notes, $is_edit_mode = 0, $receive_id = null, $last_updated_at = '', $username = 'System') {
        try {
            $this->satpamGembok($date_receive, $warehouse);
            $this->beginTransaction();
            
            $current_receive_id = null;

            if ($is_edit_mode == 1 && $receive_id) {
                // ==========================================
                // EDIT PENERIMAAN (WIPE & REPLACE)
                // ==========================================
                $oldHeader = $this->query_one("SELECT doc_number, updated_at FROM receivement WHERE id = :id FOR UPDATE", ['id' => $receive_id]);
                if (!$oldHeader) throw new Exception("Data penerimaan lama tidak ditemukan.");

                $db_updated_at = (string)($oldHeader['updated_at'] ?? '');
                $js_updated_at = (string)($last_updated_at ?? '');

                if ($js_updated_at !== '' && $db_updated_at !== '' && $db_updated_at !== $js_updated_at) {
                    throw new Exception("Gagal menyimpan! Dokumen ini baru saja diedit oleh user lain. Silakan cari ulang data.");
                }

                $current_receive_id = $receive_id;

                $oldItems = $this->query_all("SELECT item_id, item_qty FROM receivement_detail WHERE receive_id = :id", ['id' => $receive_id]);
                foreach ($oldItems as $old) {
                    $this->adjustStock($old['item_id'], $warehouse, -((float)$old['item_qty']));
                }

                $this->query("DELETE FROM receivement_detail WHERE receive_id = :id", ['id' => $receive_id]);

                $this->query("UPDATE receivement SET received_by = :received_by, notes = :notes, updated_by = :updated_by, updated_at = NOW() WHERE id = :id", [
                    'received_by' => $received_by,
                    'notes'       => $notes,
                    'updated_by'  => $username,
                    'id'          => $receive_id
                ]);

                $aggregatedCart = [];
                foreach ($cart as $item) {
                    $iid = $item['id'];
                    if (!isset($aggregatedCart[$iid])) $aggregatedCart[$iid] = $item;
                    else $aggregatedCart[$iid]['qty'] += (float)$item['qty'];
                }

                foreach ($aggregatedCart as $item) {
                    $item_id = $item['id'];
                    $new_qty = (float)$item['qty'];

                    $this->insert('receivement_detail', [
                        'receive_id' => $receive_id, 'item_id' => $item_id, 'item_qty' => $new_qty 
                    ]);

                    $this->adjustStock($item_id, $warehouse, $new_qty);
                }

            } else {
                // ==========================================
                // TRANSAKSI PENERIMAAN BARU
                // ==========================================
                $prefix = 'IN-';
                $year = date('Y');

                $searchPrefix = $prefix . $year . '-';
                $lastRecord = $this->query_one(
                    "SELECT doc_number FROM receivement WHERE doc_number LIKE :prefix ORDER BY id DESC LIMIT 1 FOR UPDATE", 
                    ['prefix' => $searchPrefix . '%']
                );

                $nextNum = 1;
                if ($lastRecord) {
                    $parts = explode('-', $lastRecord['doc_number']);                    
                    $lastNum = (int) end($parts);
                    $nextNum = $lastNum + 1; 
                }                
                $formattedNum = str_pad($nextNum, 5, '0', STR_PAD_LEFT);

                $doc_number = $searchPrefix . $formattedNum;

                $receiveData = [
                    'doc_number'   => $doc_number,
                    'received_by'  => $received_by,
                    'warehouse'    => $warehouse,
                    'date_receive' => $date_receive,
                    'notes'        => $notes,
                    'created_by'   => $username,
                    'updated_by'   => null
                ];
                
                $receive_id = $this->insert('receivement', $receiveData);
                if (!$receive_id) throw new Exception("Gagal menyimpan data Master Receivement.");
                
                $current_receive_id = $receive_id;

                $aggregatedCart = [];
                foreach ($cart as $item) {
                    $iid = $item['id'];
                    if (!isset($aggregatedCart[$iid])) $aggregatedCart[$iid] = $item;
                    else $aggregatedCart[$iid]['qty'] += (float)$item['qty'];
                }

                foreach ($aggregatedCart as $item) {
                    $item_id = $item['id'];
                    $qty = (float)$item['qty'];
                    
                    $this->insert('receivement_detail', [
                        'receive_id' => $receive_id,
                        'item_id'    => $item_id,
                        'item_qty'   => $qty 
                    ]);

                    $this->adjustStock($item_id, $warehouse, $qty);
                }
            }

            $this->commit();
            
            return [
                'status'     => 'success', 
                'message'    => $is_edit_mode ? 'Dokumen Penerimaan berhasil diperbarui!' : 'Transaksi berhasil disimpan!',
                'receive_id' => $current_receive_id
            ];

        } catch (Exception $e) {
            $this->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()]; 
        }
    }

    private function buildFilterQuery($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $sql = "SELECT r.*, w.warehouse_name 
                FROM receivement r 
                LEFT JOIN warehouse w ON r.warehouse = w.id
                WHERE 1=1";
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

    public function getFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate);
        $sql = $query['sql'] . " ORDER BY r.date_receive DESC, r.id DESC";
        return $this->query_all($sql, $query['params']);
    }

    public function getFilteredPaginated($search = '', $warehouse = '', $startDate = '', $endDate = '', $limit = 25, $offset = 0) {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate);
        $sql = $query['sql'] . " ORDER BY r.date_receive DESC, r.id DESC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

    public function getTransactionItems($receive_id) {
        $sql = "SELECT rd.*, i.item_code, i.item_name, i.unit_price, i.item_uom
                FROM receivement_detail rd
                LEFT JOIN items i ON rd.item_id = i.id 
                WHERE rd.receive_id = :receive_id";
                
        return $this->query_all($sql, ['receive_id' => (int)$receive_id]);
    }

    public function getByDocNumber($doc_number) {
        $sql = "SELECT * FROM receivement WHERE doc_number = :doc_number LIMIT 1";
        $result = $this->query_one($sql, ['doc_number' => $doc_number]);        
        return $result ? true : false;
    }

    public function getDetailedFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $sql = "SELECT 
                    r.date_receive,
                    r.doc_number,
                    r.received_by,
                    w.warehouse_name,
                    i.item_code,
                    i.item_name,
                    i.item_uom,
                    rd.item_qty
                FROM receivement_detail rd
                JOIN receivement r ON rd.receive_id = r.id
                LEFT JOIN items i ON rd.item_id = i.id
                LEFT JOIN warehouse w ON r.warehouse = w.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " AND r.date_receive BETWEEN :startDate AND :endDate";
            $params['startDate'] = $startDate . ' 00:00:00';
            $params['endDate'] = $endDate . ' 23:59:59';
        }
        if ($warehouse !== '') {
            $sql .= " AND r.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }
        if (!empty($search)) {
            $sql .= " AND (r.doc_number LIKE :search OR r.received_by LIKE :search OR i.item_name LIKE :search OR i.item_code LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        $sql .= " ORDER BY r.date_receive ASC, r.doc_number ASC, i.item_name ASC";
        return $this->query_all($sql, $params);
    }
}
?>