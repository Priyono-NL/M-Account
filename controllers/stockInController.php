<?php
require_once 'BaseController.php';

class StockInController extends BaseController {
    private $itemsModel;
    private $personModel;
    private $stockInModel;

    public function __construct() {
        parent::__construct();

        $this->itemsModel   = new ItemsModel();
        $this->personModel  = new BuyerModel();
        $this->stockInModel = new StockInModel();
    }

    /**
     * MENU UTAMA: Input Transaksi Masuk (POS / Create & View Detail)
     */
    public function index() {
        $mode       = $this->getPost('mode', 'create');
        $receive_id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        
        $transactionData = null;

        if ($mode === 'view' && $receive_id > 0) {
            $header = $this->stockInModel->getById('receivement', $receive_id);
            
            if ($header) {
                $items = $this->stockInModel->getTransactionItems($receive_id);
                
                $transactionData = [
                    'header' => $header,
                    'items'  => $items
                ];
            }
        }
        
        $warehouseContext = $this->getWarehouseContext();
        
        StockInView::render([
            'transactionData'   => $transactionData,
            'warehouses'        => $warehouseContext['warehouses'],
            'current_warehouse' => $warehouseContext['current_warehouse']
        ]);
    }
    
    /**
     * API AUTOCOMPLETE: Mengambil list produk untuk input barang POS (Tanpa Paginasi)
     */
    public function get_products() {
        $keyword = $this->getPost('keyword', '');
        $warehouse_id = $this->getPost('warehouse_id', '');
        $category = '';
        if ($warehouse_id == '1') $category = '1';
        elseif ($warehouse_id == '2') $category = '2';

        $results = $this->itemsModel->getFiltered($keyword, $category); 
        
        return $this->jsonSuccess("Data produk berhasil dimuat", $results);
    }

    /**
     * API AUTOCOMPLETE: Mengambil list Pihak Kedua/Buyer untuk POS (Tanpa Paginasi)
     */
    public function get_buyers() {
        $keyword = $this->getPost('keyword', '');        
        $results = $this->personModel->getFiltered($keyword);

        return $this->jsonSuccess("Data pihak/pembeli berhasil dimuat", $results);
    }

    // ==========================================
    // TAMBAHAN: API UNTUK MODAL CARI & EDIT PENERIMAAN
    // ==========================================

    /**
     * Mengambil daftar penerimaan untuk Modal Pencarian
     */
    public function get_receive_list() {
        $keyword = trim($this->getPost('keyword', ''));
        
        // Pastikan Anda membuat method searchReceiveList di StockInModel
        $receives = $this->stockInModel->searchReceiveList($keyword);
        
        echo json_encode([
            'status' => 'success',
            'data'   => $receives
        ]);
        exit;
    }

    /**
     * Mengambil detail penerimaan untuk diedit
     */
    public function search_receive_detail() {
        $receive_id = (int)$this->getPost('id', 0);
        
        $header = $this->stockInModel->getById('receivement', $receive_id);
        
        if (!$header) {
            echo json_encode(['status' => 'error', 'message' => 'Dokumen tidak ditemukan.']);
            exit;
        }
        
        $items = $this->stockInModel->getTransactionItems($receive_id);
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'header' => $header,
                'items'  => $items
            ]
        ]);
        exit;
    }

    // ==========================================

    /**
     * PROSES CHECKOUT: Menyimpan seluruh inputan Penerimaan ke Database
     */
    public function checkout() {
        $cartRaw      = $this->getPost('cart');
        $doc_number   = $this->getPost('doc_number');
        $received_by  = $this->getPost('received_by');
        $warehouse    = $this->getPost('warehouse');
        $date_receive = $this->getPost('date_receive');        
        $notes        = $this->getPost('notes');

        // TANGKAP PAYLOAD EDIT MODE DARI JAVASCRIPT
        $is_edit_mode    = (int)$this->getPost('is_edit_mode', 0);
        $receive_id      = $this->getPost('receive_id');
        $last_updated_at = $this->getPost('last_updated_at');

        if (empty($cartRaw)) return $this->jsonError("Keranjang belanja kosong.");

        $cart = json_decode($cartRaw, true);
        if (!$cart) return $this->jsonError("Data keranjang tidak valid.");

        if (empty($received_by)) return $this->jsonError("Harap isi penerima terlebih dahulu.");

        // Validasi edit
        if ($is_edit_mode === 1 && empty($receive_id)) {
            return $this->jsonError("ID Penerimaan tidak ditemukan untuk proses edit.");
        }

        // UPDATE PEMANGGILAN MODEL
        $result = $this->stockInModel->saveReceivement(
            $cart, 
            $doc_number, 
            $received_by, 
            $warehouse, 
            $date_receive, 
            $notes, 
            $is_edit_mode, 
            $receive_id, 
            $last_updated_at
        );
        
        if ($result && isset($result['status']) && $result['status'] === 'success') {
            $returnId = $result['receive_id'] ?? null;
            return $this->jsonSuccess($result['message'], ['receive_id' => $returnId]);
        } else {
            $errorMessage = $result['message'] ?? "Gagal menyimpan transaksi ke database.";
            return $this->jsonError($errorMessage);
        }
    }

    /**
     * HALAMAN LIST RIWAYAT: Menampilkan Tabel Riwayat Transaksi Penerimaan
     */
    public function history() {
        $warehouseContext = $this->getWarehouseContext();
        ReceiveView::render([
            'warehouses'=> $warehouseContext['warehouses'],
            'current_warehouse' => $warehouseContext['current_warehouse'],
            'is_locked' => $warehouseContext['is_locked']
        ]);
    }

    /**
     * API ENDPOINT: Mengambil data riwayat dengan Paginasi Server-Side (Limit 10)
     */
    public function filter_api() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $paging = $this->getPaginationParams(10);

        $result = $this->stockInModel->getFilteredPaginated(
            $search, 
            $warehouse, 
            $startDate, 
            $endDate, 
            $paging['limit'], 
            $paging['offset']
        );
        
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
        
        return $this->jsonSuccess(
            "Data Filtered", 
            $result['data'], 
            ['pagination' => $paginationMeta]
        );
    }

    /**
     * API ENDPOINT: Cek ketersediaan Nomor Dokumen
     */
    public function check_doc() {
        $doc_number = $this->getPost('doc_number', '');        
        if (empty($doc_number)) return $this->jsonError("Nomor dokumen kosong.");

        $isExists = $this->stockInModel->getByDocNumber($doc_number);                
        if ($isExists) return $this->jsonSuccess("Dokumen sudah ada", ['status' => 'exists']);
        else return $this->jsonSuccess("Dokumen tersedia", ['status' => 'available']);
    }
    
}
?>