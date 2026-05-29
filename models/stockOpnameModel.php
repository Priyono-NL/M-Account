<?php
require_once '_dbHelper.php';

class StockOpnameModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    /**
     * MENYIMPAN DRAFT OPNAME KE DATABASE (Pola Header-Detail)
     */
    public function saveOpnameDraft($opnameDate, $warehouse, $notes, $items, $username) {
        try {
            $opname_no = 'OPN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $this->beginTransaction();

            // 1. Insert ke Tabel Induk (Header)
            $headerData = [
                'opname_no'   => $opname_no,
                'warehouse'   => $warehouse,
                'opname_date' => $opnameDate,
                'created_by'  => $username,
                'status'      => 0,
                'notes'       => $notes
            ];

            $opname_id = $this->insert('stock_opname', $headerData);

            if (!$opname_id) {
                throw new Exception("Gagal membuat dokumen induk Stock Opname.");
            }

            // 2. Looping Insert ke Tabel Rincian (Detail)
            foreach ($items as $item) {
                $detailData = [
                    'opname_id'     => $opname_id,
                    'item_id'       => $item['id'],
                    'qty_sistem'    => (float)$item['qty_system'],
                    'qty_fisik'  => (float)$item['qty_physical']
                ];

                $this->insert('stock_opname_detail', $detailData);
            }

            $this->commit();

            return [
                'status'  => 'success',
                'message' => "Sukses! Dokumen Draft Opname {$opname_no} berhasil disimpan."
            ];

        } catch (Exception $e) {
            $this->rollBack();
            return [
                'status'  => 'error',
                'message' => "Gagal menyimpan opname: " . $e->getMessage()
            ];
        }
    }
}
?>