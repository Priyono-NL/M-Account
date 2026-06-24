<?php
require_once 'BaseController.php';

class StockOpnameController extends BaseController {
    private $stockOpnameModel;
    private $stocksModel;

    public function __construct() {
        parent::__construct();
        
        $this->stockOpnameModel = new StockOpnameModel();
        $this->stocksModel = new StocksModel();
    }

    /**
     * TAMPILAN UTAMA FORM OPNAME
     */
    public function index() {
        $warehouseContext = $this->getWarehouseContext();
        StockOpnameView::render([
            'warehouses'=> $warehouseContext['warehouses'],
            'current_warehouse' => $warehouseContext['current_warehouse'],
            'is_locked' => $warehouseContext['is_locked']
        ]);
    }

    /**
     * API AUTOCOMPLETE MODAL: Meminjam fungsi dari StocksModel existing
     */
    public function get_products() {
        $keyword   = $this->getPost('keyword', '');
        $warehouse = $this->getPost('warehouse', '');
        
        $results = $this->stocksModel->getLatestStock($keyword, $warehouse); 
        
        return $this->jsonSuccess("Data produk berhasil dimuat", $results);
    }

    /**
     * PROSES SIMPAN DRAFT OPNAME (AJAX POST)
     */
    public function save_opname() {
        $opname_date = $this->getPost('opname_date');
        $warehouse   = $this->getPost('warehouse');
        $notes       = $this->getPost('notes', '');
        $itemsRaw    = $this->getPost('items');

        if (empty($opname_date)) return $this->jsonError("Tanggal opname wajib diisi.");
        if (empty($warehouse)) return $this->jsonError("Lokasi gudang wajib ditentukan.");
        if (empty($itemsRaw)) return $this->jsonError("Daftar item opname tidak boleh kosong.");

        $items = json_decode($itemsRaw, true);
        if (!$items || !is_array($items)) return $this->jsonError("Format data barang tidak valid.");

        $username = $_SESSION['user']['username'] ?? 'Staff Gudang';

        $result = $this->stockOpnameModel->saveOpnameDraft($opname_date, $warehouse, $notes, $items, $username);

        if ($result && $result['status'] === 'success') {
            return $this->jsonSuccess($result['message']);
        } else {
            return $this->jsonError($result['message'] ?? "Gagal menyimpan draft opname.");
        }
    }
}
?>