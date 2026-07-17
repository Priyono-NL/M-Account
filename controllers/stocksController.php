<?php
require_once 'BaseController.php';

class StocksController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new StocksModel();
    }

    /**
     * HALAMAN UTAMA: Menampilkan layout kerangka laporan stok bulanan
     */
    public function index() {
        $warehouseContext = $this->getWarehouseContext();
        StocksView::render([
            'warehouses'=> $warehouseContext['warehouses'],
            'current_warehouse' => $warehouseContext['current_warehouse'],
            'is_locked' => $warehouseContext['is_locked']
        ]);
    }

    /**
     * API ENDPOINT: Mengambil data ringkasan mutasi stok dengan Paginasi Server-Side (Limit 25)
     */
    public function filter_api() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        
        // Menangkap parameter rentang tanggal bebas lintas bulan
        $startDate = $this->getPost('start_date', date('Y-m-01'));
        $endDate   = $this->getPost('end_date', date('Y-m-d'));

        // =========================================================================
        // TAHAP 3: Tangkap parameter sorting yang dikirim oleh jQuery AJAX
        // =========================================================================
        $sortCol   = $this->getPost('sort_col', ''); // Default kosong (mengikuti penarikan awal)
        $sortDir   = $this->getPost('sort_dir', ''); // Default kosong

        // 1. Ambil parameter halaman dan offset (Limit 10)
        $paging = $this->getPaginationParams(10);

        // 2. Tarik data terpaginasi dari model harian (Sisipkan parameter sort baru)
        $result = $this->model->getDailyStockReportPaginated(
            $search, 
            $warehouse, 
            $startDate, 
            $endDate, 
            $paging['limit'], 
            $paging['offset'],
            $sortCol, // Ditambahkan
            $sortDir  // Ditambahkan
        );
                
        // 3. Susun meta data paginasi
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
        
        // 4. Balas langsung melempar array data murni (Tanpa bungkus status)
        return $this->jsonSuccess("Data Filtered", $result['data'], ['pagination' => $paginationMeta]);
    }

    /**
     * EXPORT EXCEL: Mengunduh semua data mutasi stok sesuai hasil filter harian
     */
    public function export_xls() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', date('Y-m-01'));
        $endDate   = $this->getPost('end_date', date('Y-m-d'));

        // Tarik semua data tanpa batas halaman paginasi (Limit diset maksimal)
        $result = $this->model->getDailyStockReportPaginated($search, $warehouse, $startDate, $endDate, 9999999, 0);
        $data = $result['data'];

        // Menyusun Header Tabel Excel
        $rows = [[
            '<b>No</b>', 
            '<b>Gudang</b>', 
            '<b>Kode Barang</b>', 
            '<b>Nama Barang</b>',
            '<b>Qty Open</b>', 
            '<b>Qty In</b>', 
            '<b>Qty Out</b>',
            '<b>Qty Close</b>', 
            '<b>Qty Onhand</b>', 
            '<b>Selisih</b>',
        ]];

        foreach ($data as $index => $item) {
            $rows[] = [
                $index + 1,
                $item['warehouse_name'] ?? $item['warehouse'],
                $item['item_code'],
                $item['item_name'],
                (float)$item['qty_open'],
                (float)$item['qty_in'],
                (float)$item['qty_out'],
                (float)$item['qty_close'],
                (float)$item['qty_onhand'],
                (float)$item['selisih']
            ];
        }

        $fileName = "Laporan_Stok_" . date('Ymd') . ".xlsx";
        
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

    /**
     * Stock Card Controller
     */
    public function card() {
        $warehouseContext = $this->getWarehouseContext();
        
        StockCardView::render([
            'warehouses'        => $warehouseContext['warehouses'],
            'current_warehouse' => $warehouseContext['current_warehouse'],
            'is_locked'         => $warehouseContext['is_locked']
        ]);
    }

    public function get_items_by_warehouse_api() {
        $warehouse = $this->getPost('warehouse');
        if (empty($warehouse)) {
            return $this->jsonError("Gudang tidak boleh kosong.");
        }
        
        // Ambil daftar master barang yang organization_id nya cocok dengan gudang terpilih
        $sql = "SELECT id, item_code, item_name, item_uom 
                FROM items 
                WHERE organization_id = :warehouse AND is_active = 0 
                ORDER BY item_name ASC";
                
        $items = $this->model->query_all($sql, ['warehouse' => $warehouse]);
        
        return $this->jsonSuccess("Data barang per gudang berhasil dimuat", $items);
    }

    public function get_card_api() {
        $item_id   = (int)$this->getPost('item_id');
        $warehouse = $this->getPost('warehouse');
        $startDate = $this->getPost('start_date', date('Y-m-01'));
        $endDate   = $this->getPost('end_date', date('Y-m-d'));

        if ($item_id <= 0 || empty($warehouse)) {
            return $this->jsonError("Harap pilih Barang dan Gudang terlebih dahulu.");
        }

        // Memanggil fungsionalitas pencatatan kronologis yang sudah kamu buat di StocksModel
        $result = $this->model->getStockCard($item_id, $warehouse, $startDate, $endDate);

        return $this->jsonSuccess("Data Kartu Stok Berhasil Dimuat", $result);
    }

    public function export_card_xls() {
        $itemId    = (int)$this->getPost('item_id');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', date('Y-m-01'));
        $endDate   = $this->getPost('end_date', date('Y-m-d'));

        if ($itemId <= 0 || empty($warehouse)) {
            die("Parameter tidak valid. Harap tentukan barang dan gudang.");
        }

        // 1. Ambil informasi nama barang & gudang untuk header Excel
        $itemInfo = $this->model->query_one("SELECT item_code, item_name, item_uom FROM items WHERE id = :id", ['id' => $itemId]);
        $whInfo   = $this->model->query_one("SELECT warehouse_name FROM warehouse WHERE id = :id", ['id' => $warehouse]);
        
        $itemCode = $itemInfo['item_code'] ?? '-';
        $itemName = $itemInfo['item_name'] ?? '-';
        $itemUom  = $itemInfo['item_uom'] ?? '-';
        $whName   = $whInfo['warehouse_name'] ?? $warehouse;

        // 2. Tarik data mutasi kronologis dari model asli kamu
        $result = $this->model->getStockCard($itemId, $warehouse, $startDate, $endDate);
        $mutations = $result['mutations'] ?? [];

        // 3. Susun Informasi Profil Kartu Stok di baris atas Excel
        $rows = [
            ['<b>KARTU STOK BARANG</b>', ''],
            ['Nama Barang:', $itemCode . ' - ' . $itemName],
            ['Gudang Asal:', $whName],
            ['Periode:', date('d-M-Y', strtotime($startDate)) . ' s/d ' . date('d-M-Y', strtotime($endDate))],
            ['UOM:', $itemUom],
            [], // Baris Kosong Pembatas
            [
                '<b>Tanggal Transaksi</b>', 
                '<b>No. Dokumen / Ref</b>', 
                '<b>Keterangan Mutasi</b>', 
                '<b>Qty In</b>', 
                '<b>Qty Out</b>', 
                '<b>Saldo Stok</b>'
            ]
        ];

        // 4. Masukkan baris mutasi ke dalam array Excel
        foreach ($mutations as $row) {
            // Format tanggal mutasi agar rapi tanpa jam
            $tanggalTrans = ($row['date'] !== '-' && $row['code'] !== '-') 
                ? date('d-M-Y', strtotime($row['date'])) 
                : $row['date'];

            $rows[] = [
                $tanggalTrans,
                $row['code'],
                $row['notes'],
                (float)$row['in'],
                (float)$row['out'],
                (float)$row['balance']
            ];
        }

        // 5. Penamaan file dinamis: Laporan_Kartu_Stok_[KODEBARANG]_[TGL]
        $tglAwal  = date('Ymd', strtotime($startDate));
        $tglAkhir = date('Ymd', strtotime($endDate));
        $fileName = "Laporan_Kartu_Stok_" . $itemCode . "_" . $tglAwal . "_sd_" . $tglAkhir . ".xlsx";
        
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }
}
?>