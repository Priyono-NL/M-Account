<?php
require_once '_dbHelper.php';

class StockAdjustmentModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function getPendingOpnamesPaginated($search = '', $warehouse = '', $limit = 10, $offset = 0) {
        $sql = "SELECT o.* FROM stock_opname o WHERE o.status = 0";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND o.opname_no LIKE :search";
            $params['search'] = "%{$search}%";
        }
        if ($warehouse !== '') {
            $sql .= " AND o.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }

        $sql .= " ORDER BY o.opname_date DESC, o.id DESC";
        return $this->query_paginated($sql, $params, $limit, $offset);
    }

    public function getOpnameHeader($id) {
        $sql = "SELECT o.* FROM stock_opname o WHERE o.id = :id";
        return $this->query_one($sql, ['id' => $id]);
    }

    public function getOpnameDetails($opname_id) {
        $sql = "SELECT od.*, i.item_name, i.item_code, i.item_uom 
                FROM stock_opname_detail od
                LEFT JOIN items i ON od.item_id = i.id
                WHERE od.opname_id = :opname_id 
                ORDER BY i.item_name ASC";
        return $this->query_all($sql, ['opname_id' => $opname_id]);
    }

    /**
     * ACID TRANSACTION MUTATION: Proses mengubah stok digital agar klop dengan stok fisik
     */
    public function executeAdjustment($opname_id, $adjustedItems, $adminUsername) {
        try {
            $this->beginTransaction();

            $sqlHeader = "SELECT opname_no, warehouse, opname_date, status FROM stock_opname WHERE id = :id FOR UPDATE";
            $header = $this->query_one($sqlHeader, ['id' => $opname_id]);

            if (!$header) throw new Exception("Dokumen induk opname tidak ditemukan.");
            if ($header['status'] === 'ADJUSTED') throw new Exception("Dokumen ini sudah pernah di-adjust sebelumnya!");

            $warehouse = $header['warehouse'];
            $docNumber = $header['opname_no'];
            $stockLogTimestamp = date('Y-m-d H:i:s');

            $reasonsMap = [];
            foreach ($adjustedItems as $ai) {
                $reasonsMap[$ai['item_id']] = $ai['reason'];
            }

            $details = $this->getOpnameDetails($opname_id);
            $sqlLastStock = "SELECT qty_total FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse ORDER BY id DESC LIMIT 1 FOR UPDATE";

            foreach ($details as $row) {
                $itemId = $row['item_id'];
                $qtySystem = (float)$row['qty_sistem'];
                $qtyPhysical = (float)$row['qty_fisik'];
                
                // Rumus Konsep: Selisih = Fisik - Komputer
                $selisih = $qtyPhysical - $qtySystem;
                $reasonText = !empty($reasonsMap[$itemId]) ? $reasonsMap[$itemId] : 'Adjustment Opname';

                if ($selisih == 0) continue;

                $lastStockRow = $this->query_one($sqlLastStock, ['item_id' => $itemId, 'warehouse' => $warehouse]);
                $qtyOpen = $lastStockRow ? (float)$lastStockRow['qty_total'] : 0;

                if ($selisih > 0) {
                    $qtyIn = abs($selisih);
                    $qtyOut = 0;
                    $qtyTotal = $qtyOpen + $qtyIn;
                    $txType = 'IN';
                    $logNotes = "Adj Plus ({$docNumber}) - " . $reasonText;
                } else {
                    $qtyIn = 0;
                    $qtyOut = abs($selisih);
                    $qtyTotal = $qtyOpen - $qtyOut;
                    $txType = 'OUT';
                    $logNotes = "Adj Minus ({$docNumber}) - " . $reasonText;
                }

                $this->insert('item_transactions', [
                    'item_id'          => $itemId,
                    'warehouse'        => $warehouse,
                    'transaction_date' => $stockLogTimestamp,
                    'type'             => $txType,
                    'qty'              => abs($selisih),
                    'reference_no'     => $docNumber,
                    'notes'            => $logNotes
                ]);

                $this->insert('stocks', [
                    'item_id'   => $itemId,
                    'warehouse' => $warehouse,
                    'date'      => $stockLogTimestamp,
                    'qty_open'  => $qtyOpen,
                    'qty_in'    => $qtyIn,
                    'qty_out'   => $qtyOut,
                    'qty_total' => $qtyTotal,
                ]);

            }

            $this->db->prepare("UPDATE stock_opname SET status = 1, updated_by = ? WHERE id = ?")
                    ->execute([$adminUsername, $opname_id]);

            $this->commit();
            return [
                'status'  => 'success',
                'message' => "Sukses! Angka selisih dokumen {$docNumber} resmi disesuaikan ke kartu stok digital."
            ];

        } catch (Exception $e) {
            $this->rollBack();
            return ['status' => 'error', 'message' => "Gagal eksekusi: " . $e->getMessage()];
        }
    }
}
?>