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
        $search     = $this->getPost('search', '');
        $warehouse  = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', date('Y-m'));

        // 1. Ambil parameter halaman dan offset dari BaseController (Limit 25)
        $paging = $this->getPaginationParams(25);

        // 2. Cek apakah bulan ini sudah di-closing atau masih berjalan (ONGOING)
        $isClosed = $this->model->isPeriodClosed($closeMonth, $warehouse);
        $status = $isClosed ? 'CLOSED' : 'ONGOING';

        // 3. Tarik data terpaginasi dari model (otomatis mengambil dari vw_laporan_stok_bulanan)
        $result = $this->model->getMonthlyReportPaginated($search, $warehouse, $closeMonth, $paging['limit'], $paging['offset']);
                
        // 4. Susun meta data paginasi berdasarkan jumlah data riil
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
        
        // 5. Balas dengan menyisipkan objek array 'pagination' ke komponen AJAX global
        return $this->jsonSuccess("Data Filtered", [
            'status' => $status,
            'stocks' => $result['data']
        ], ['pagination' => $paginationMeta]);
    }

    /**
     * API ENDPOINT: Mengeksekusi proses Closing Stok
     */
    public function do_closing() {
        $warehouse  = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', date('Y-m'));
        
        // Ambil nama user yang mengeksekusi dari session, default ke System
        $user = $_SESSION['user']['person_name'] ?? 'System';

        try {
            $insertedCount = $this->model->doClosing($closeMonth, $warehouse, $user);
            return $this->jsonSuccess("Closing Berhasil! {$insertedCount} barang telah dicatat sebagai saldo awal bulan depan.");
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

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
}
?>