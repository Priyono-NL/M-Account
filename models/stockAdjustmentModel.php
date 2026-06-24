<?php
require_once '_dbHelper.php';

class StockAdjustmentModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    /**
     * FUNGSI SINKRONISASI STOK GLOBAL (Single Source of Truth)
     */
    private function syncCurrentStock($item_id, $warehouse) {
        $sqlCalc = "SELECT SUM(CASE WHEN type = 'IN' THEN qty ELSE -qty END) as real_stock 
                    FROM item_transactions 
                    WHERE item_id = :item_id AND warehouse = :warehouse";
                    
        $result = $this->query_one($sqlCalc, ['item_id' => $item_id, 'warehouse' => $warehouse]);
        $real_stock = $result ? (float)$result['real_stock'] : 0;

        $cekStock = $this->query_one("SELECT id FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse LIMIT 1", [
            'item_id' => $item_id, 'warehouse' => $warehouse
        ]);

        if ($cekStock) {
            $this->query("UPDATE stocks SET qty_total = :qty_total WHERE id = :id", [
                'qty_total' => $real_stock, 'id' => $cekStock['id']
            ]);
        } else {
            $this->insert('stocks', [
                'item_id'   => $item_id, 'warehouse' => $warehouse, 'qty_total' => $real_stock
            ]);
        }
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
     * Sudah menggunakan Snapshot Sync untuk memperbarui tabel `stocks`
     */
    public function executeAdjustment($opname_id, $adjustedItems, $adminUsername) {
        try {
            $this->beginTransaction();

            $sqlHeader = "SELECT opname_no, warehouse, opname_date, status FROM stock_opname WHERE id = :id FOR UPDATE";
            $header = $this->query_one($sqlHeader, ['id' => $opname_id]);

            if (!$header) throw new Exception("Dokumen induk opname tidak ditemukan.");
            if ($header['status'] === 'ADJUSTED' || $header['status'] == 1) throw new Exception("Dokumen ini sudah pernah di-adjust sebelumnya!");

            $warehouse = $header['warehouse'];
            $docNumber = $header['opname_no'];
            $stockLogTimestamp = date('Y-m-d H:i:s');
            
            $affected_items = []; // Kumpulkan ID item yang butuh disinkronisasi

            $reasonsMap = [];
            foreach ($adjustedItems as $ai) {
                $reasonsMap[$ai['item_id']] = $ai['reason'];
            }

            $details = $this->getOpnameDetails($opname_id);

            foreach ($details as $row) {
                $itemId = $row['item_id'];
                $qtySystem = (float)$row['qty_sistem'];
                $qtyPhysical = (float)$row['qty_fisik'];
                
                // Rumus Konsep: Selisih = Fisik - Komputer
                $selisih = $qtyPhysical - $qtySystem;
                $reasonText = !empty($reasonsMap[$itemId]) ? $reasonsMap[$itemId] : 'Adjustment Opname';

                if ($selisih == 0) continue; // Abaikan jika tidak ada selisih stok

                $affected_items[] = $itemId;

                if ($selisih > 0) {
                    $txType = 'IN';
                    $logNotes = "Adj Plus ({$docNumber}) - " . $reasonText;
                } else {
                    $txType = 'OUT';
                    $logNotes = "Adj Minus ({$docNumber}) - " . $reasonText;
                }

                // CUKUP INSERT LOG TRANSAKSI (Tabel stocks akan di-handle oleh fungsi sync)
                $this->insert('item_transactions', [
                    'item_id'          => $itemId,
                    'warehouse'        => $warehouse,
                    'transaction_date' => $stockLogTimestamp,
                    'type'             => $txType,
                    'qty'              => abs($selisih),
                    'reference_no'     => $docNumber,
                    'notes'            => $logNotes
                ]);
            }

            // ==========================================
            // LANGKAH TERAKHIR: SINKRONISASI STOK FINAL
            // ==========================================
            $affected_items = array_unique($affected_items);
            foreach ($affected_items as $item_id) {
                $this->syncCurrentStock($item_id, $warehouse);
            }

            // Update status dokumen opname
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