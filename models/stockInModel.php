<?php
require_once '_dbHelper.php';

class StockInModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    // ==========================================
    // FUNGSI UNTUK MODAL SEARCH RECEIVEMENT
    // ==========================================
    public function searchReceiveList($keyword) {
        $sql = "SELECT id, doc_number, date_receive, received_by, notes 
                FROM receivement 
                WHERE doc_number LIKE :keyword OR received_by LIKE :keyword
                ORDER BY date_receive DESC, id DESC 
                LIMIT 20";
        
        return $this->query_all($sql, ['keyword' => "%$keyword%"]);
    }

    /**
     * FUNGSI SINKRONISASI STOK GLOBAL (Single Source of Truth)
     */
    private function syncCurrentStock($item_id, $warehouse) {
        // 1. Dapatkan string periode bulan berjalan (Contoh: '2026-07')
        $currentPeriod = date('Y-m'); 
        $startOfMonth = date('Y-m-01'); // Untuk mencocokkan tanggal di stocks_period

        // 2. Ambil Saldo Awal Bulan Ini (Dari hasil Closing bulan lalu)
        $sqlOpen = "SELECT qty FROM stocks_period 
                    WHERE item_id = :item_id AND warehouse = :warehouse AND stock_date = :start_date 
                    LIMIT 1";
        $resOpen = $this->query_one($sqlOpen, [
            'item_id' => $item_id, 'warehouse' => $warehouse, 'start_date' => $startOfMonth
        ]);
        $qtyOpen = $resOpen ? (float)$resOpen['qty'] : 0;

        // 3. Ambil Total IN dan OUT Bulan Ini SAJA dari vw_mutasi_bulanan menggunakan filter `period`
        $sqlMutasi = "SELECT 
                        COALESCE(SUM(qty_in), 0) as total_in, 
                        COALESCE(SUM(qty_out), 0) as total_out 
                      FROM vw_mutasi_bulanan 
                      WHERE item_id = :item_id 
                        AND warehouse = :warehouse 
                        AND period = :current_period";
                        
        $mutasi = $this->query_one($sqlMutasi, [
            'item_id' => $item_id, 
            'warehouse' => $warehouse, 
            'current_period' => $currentPeriod
        ]);
        
        $totalIn = $mutasi ? (float)$mutasi['total_in'] : 0;
        $totalOut = $mutasi ? (float)$mutasi['total_out'] : 0;

        // 4. Kalkulasi Stok Riil Saat Ini
        $real_stock = $qtyOpen + $totalIn - $totalOut;

        // 5. Simpan/Update ke tabel `stocks`
        $cekStock = $this->query_one("SELECT id FROM stocks WHERE item_id = :item_id AND warehouse = :warehouse LIMIT 1", [
            'item_id' => $item_id, 'warehouse' => $warehouse
        ]);

        if ($cekStock) {
            $this->query("UPDATE stocks SET qty_total = :qty_total, updated_at = NOW() WHERE id = :id", [
                'qty_total' => $real_stock, 'id' => $cekStock['id']
            ]);
        } else {
            $this->insert('stocks', [
                'item_id'   => $item_id, 
                'warehouse' => $warehouse, 
                'qty_total' => $real_stock
            ]);
        }
    }


    /**
     * TRANSACTION MUTATION: Menyimpan data penerimaan dan memperbarui stok (ACID Transaction)
     * Ditambahkan fitur EDIT dengan metode Wipe & Replace + Snapshot Sync
     */
    public function saveReceivement($cart, $doc_number, $received_by, $warehouse, $date_receive, $notes, $is_edit_mode = 0, $receive_id = null, $last_updated_at = '') {
        try {
            $this->satpamGembok($date_receive, $warehouse);
            $this->beginTransaction();
            
            $stockLogTimestamp = $date_receive . ' ' . date('H:i:s');
            $current_receive_id = null;
            $affected_items = []; // Menyimpan ID barang yang stoknya perlu direkalkulasi

            if ($is_edit_mode == 1 && $receive_id) {
                // ==========================================
                // EDIT PENERIMAAN (WIPE & REPLACE)
                // ==========================================
                
                // 1. Kunci Baris Header & Cek Optimistic Locking
                $oldHeader = $this->query_one("SELECT doc_number, updated_at FROM receivement WHERE id = :id FOR UPDATE", ['id' => $receive_id]);
                if (!$oldHeader) throw new Exception("Data penerimaan lama tidak ditemukan.");

                $db_updated_at = (string)($oldHeader['updated_at'] ?? '');
                $js_updated_at = (string)($last_updated_at ?? '');

                if ($js_updated_at !== '' && $db_updated_at !== '' && $db_updated_at !== $js_updated_at) {
                    throw new Exception("Gagal menyimpan! Dokumen ini baru saja diedit oleh user lain. Silakan cari ulang data.");
                }

                $doc_number_old = $oldHeader['doc_number']; 
                $current_receive_id = $receive_id;

                // 2. Kumpulkan ID item lama agar ikut disinkronisasi ulang stoknya
                $oldItems = $this->query_all("SELECT item_id FROM receivement_detail WHERE receive_id = :id", ['id' => $receive_id]);
                foreach ($oldItems as $old) {
                    $affected_items[] = $old['item_id'];
                }

                // 3. WIPE (HAPUS BERSIH) DETAIL & LOG LAMA
                $this->query("DELETE FROM receivement_detail WHERE receive_id = :id", ['id' => $receive_id]);

                // 4. UPDATE HEADER
                $this->query("UPDATE receivement SET received_by = :received_by, notes = :notes, updated_at = NOW() WHERE id = :id", [
                    'received_by' => $received_by,
                    'notes' => $notes,
                    'id' => $receive_id
                ]);

                // 5. GABUNGKAN ITEM KERANJANG BARU (Cegah Duplikat Baris)
                $aggregatedCart = [];
                foreach ($cart as $item) {
                    $iid = $item['id'];
                    if (!isset($aggregatedCart[$iid])) $aggregatedCart[$iid] = $item;
                    else $aggregatedCart[$iid]['qty'] += (float)$item['qty'];
                }

                // 6. MASUKKAN LOG BARU
                foreach ($aggregatedCart as $item) {
                    $item_id = $item['id'];
                    $new_qty = (float)$item['qty'];
                    $affected_items[] = $item_id;

                    $this->insert('receivement_detail', [
                        'receive_id' => $receive_id, 'item_id' => $item_id, 'item_qty' => $new_qty 
                    ]);
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
                    $parts = explode('-', $lastRecord['invoice_no']);                    
                    $lastNum = (int) end($parts);
                    $nextNum = $lastNum + 1; 
                }                
                $formattedNum = str_pad($nextNum, 5, '0', STR_PAD_LEFT);

                $doc_number = $searchPrefix . $formattedNum;

                $receiveData = [
                    'doc_number'    => $doc_number,
                    'received_by'   => $received_by,
                    'warehouse'     => $warehouse,
                    'date_receive'  => $date_receive,
                    'notes'         => $notes
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
                    $affected_items[] = $item['id'];
                    
                    $this->insert('receivement_detail', [
                        'receive_id' => $receive_id,
                        'item_id'    => $item['id'],
                        'item_qty'   => $item['qty'] 
                    ]);
                }
            }

            // ==========================================
            // LANGKAH TERAKHIR: SINKRONISASI STOK FINAL
            // ==========================================
            $affected_items = array_unique($affected_items);
            foreach ($affected_items as $item_id) {
                $this->syncCurrentStock($item_id, $warehouse);
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