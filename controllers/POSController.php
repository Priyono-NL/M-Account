<?php
require_once 'BaseController.php';

class POSController extends BaseController {
    private $stocksModel;
    private $buyerModel;
    private $salesModel;

    public function __construct() {
        parent::__construct();

        $this->stocksModel = new StocksModel();
        $this->buyerModel  = new BuyerModel();
        $this->salesModel  = new SalesModel();
    }

    /**
     * HALAMAN UTAMA POS: Transaksi Baru & Lihat Detail Transaksi Terkunci
     */
    public function index() {
        $mode = $this->getPost('mode', 'create');        
        $sales_id = isset($_POST['id']) ? (int)$_POST['id'] : null;

        $transactionData = null;

        if ($mode === 'view' && $sales_id > 0) {
            $header = $this->salesModel->getSalesHeader($sales_id);
            
            if ($header) {
                $items = $this->salesModel->getTransactionItems($sales_id);
                
                $transactionData = [
                    'header' => $header,
                    'items'  => $items
                ];
            }
        }

        $warehouseContext = $this->getWarehouseContext();
        
        POSView::render([
            'transactionData' => $transactionData,
            'warehouses' => $warehouseContext['warehouses'],
            'current_warehouse' => $warehouseContext['current_warehouse']
        ]);
    }    

    /**
     * API AUTOCOMPLETE: Mengambil list produk dan sisa stok untuk POS (Tanpa Paginasi)
     */
    public function get_products() {
        $keyword   = $this->getPost('keyword', '');
        $warehouse = $this->getPost('warehouse', '');
        $results   = $this->stocksModel->getLatestStock($keyword, $warehouse); 
        
        return $this->jsonSuccess("Data produk berhasil dimuat", $results);
    }

    /**
     * API AUTOCOMPLETE: Mengambil list Pelanggan/Buyer untuk POS (Tanpa Paginasi)
     */
    public function get_buyers() {
        $keyword = $this->getPost('keyword', '');        
        $results = $this->buyerModel->getFiltered($keyword);

        return $this->jsonSuccess("Data pelanggan berhasil dimuat", $results);
    }

    public function get_invoice_list() {
        $keyword = trim($_POST['keyword'] ?? '');
        $invoices = $this->salesModel->searchInvoiceList($keyword);
        
        echo json_encode([
            'status' => 'success',
            'data' => $invoices
        ]);
        exit;
    }

    public function search_invoice_detail() {
        $sale_id = $_POST['id'] ?? 0;
        $header = $this->salesModel->getSalesHeader($sale_id);
        
        if (!$header) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice tidak ditemukan.']);
            exit;
        }
        
        $warehouse_id = $header['warehouse']; 
        $items = $this->salesModel->getTransactionItems($sale_id, $warehouse_id);
        
        $formattedItems = array_map(function($item) {
            return [
                'item_id'       => $item['item_id'],
                'item_code'     => $item['item_code'],
                'item_name'     => $item['item_name'],
                'item_uom'      => $item['item_uom'],
                'unit_price'    => $item['unit_price'],
                'item_qty'      => $item['item_qty'],
                'current_stock' => $item['current_stock'] ?? 0 
            ];
        }, $items);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'header' => $header,
                'items'  => $formattedItems
            ]
        ]);
        exit;
    }

    /**
     * PROSES CHECKOUT: Menyimpan seluruh entri transaksi penjualan POS ke Database
     */
    public function checkout() {
        $cartRaw    = $this->getPost('cart');
        $buyer_id   = $this->getPost('buyer_id');
        $warehouse  = $this->getPost('warehouse');
        $sales_date = $this->getPost('sales_date');
        $sales_type = $this->getPost('sales_type');
        $last_updated_at = $this->getPost('last_updated_at');

        $is_edit_mode = (int)$this->getPost('is_edit_mode');
        $sale_id      = $this->getPost('sale_id');
        if ($is_edit_mode === 1 && empty($sale_id)) return $this->jsonError("ID Transaksi tidak ditemukan untuk melakukan proses edit.");

        if (empty($cartRaw)) return $this->jsonError("Keranjang belanja kosong.");

        $cart = json_decode($cartRaw, true);
        if (!$cart) return $this->jsonError("Data keranjang tidak valid.");

        if (empty($buyer_id)) return $this->jsonError("Harap pilih pelanggan terlebih dahulu.");

        $rolename = strtolower($_SESSION['user']['rolename'] ?? '');
        if ($sales_type === 'EXP' && $rolename !== 'all') return $this->jsonError("Akses Ditolak! Tipe penjualan EXP hanya boleh dilakukan oleh pengguna dengan hak akses ALL.");

        $result = $this->salesModel->saveTransaction($cart, $buyer_id, $warehouse, $sales_date, $sales_type, $is_edit_mode, $sale_id, $last_updated_at);
        
        if ($result && isset($result['status']) && $result['status'] === 'success') {
            return $this->jsonSuccess($result['message'], ['sale_id' => $result['sale_id']]);
        } else {
            $errorMessage = $result['message'] ?? "Gagal menyimpan transaksi ke database.";
            return $this->jsonError($errorMessage);
        }
    }
	
	/**
     * PRINT INVOICE HTML: Menampilkan struk belanja cetak via browser
     */
    public function print_invoice() {
        $sales_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$sales_id || $sales_id <= 0) die("ID Transaksi tidak valid.");

        $header = $this->salesModel->getSalesHeader($sales_id);
        $items  = $this->salesModel->getTransactionItems($sales_id);

        if (!$header) die("Data transaksi tidak ditemukan.");
		
		if ($header['print_count'] == 0 ) {
            $header['is_reprint'] = false;
            $header['reprint'] = 0;
        } else {
            $header['is_reprint'] = true;
            $header['reprint'] = $header['print_count'];
        }
        $this->salesModel->incrementPrintCount($sales_id);

        if ($header['warehouse'] == 1) {
            InvoiceView::render($header, $items);
        } else {
            SuratView::render($header, $items);
        }        
    }

    /**
     * PRINT INVOICE PDF: Menghasilkan berkas PDF cetak thermal/surat jalan otomatis via Dompdf
     */
    public function print_invoice_pdf() {
        $sales_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$sales_id || $sales_id <= 0) die("ID Transaksi tidak valid.");

        $header = $this->salesModel->getSalesHeader($sales_id);
        $items  = $this->salesModel->getTransactionItems($sales_id);

        if (!$header) die("Data transaksi tidak ditemukan.");

        if ($header['print_count'] == 0 ) {
            $header['is_reprint'] = false;
            $header['reprint'] = 0;
        } else {
            $header['is_reprint'] = true;
            $header['reprint'] = $header['print_count'];
        }
        $this->salesModel->incrementPrintCount($sales_id);

        require_once 'vendors/dompdf/autoload.inc.php'; 
    
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);

        ob_start();
        $cm_to_pt = 28.3465;
        if ($header['warehouse'] == 1) {
            $width  = 9.5 * $cm_to_pt;
            $height = 13.97 * $cm_to_pt;
            InvoiceViewPdf::render($header, $items);
        } else {
            $width  = 21.44 * $cm_to_pt;
            $height = 13.97 * $cm_to_pt;
            SuratViewPdf::render($header, $items);
        }
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, $width, $height], 'portrait');
        
        $dompdf->render();
        $dompdf->stream("Invoice-" . $header['invoice_no'] . ".pdf", [
            "Attachment" => false
        ]);
        exit;     
    }
	
	/**
     * HALAMAN LIST RIWAYAT: Menampilkan Tabel Riwayat Transaksi Penjualan
     */
    public function history() {
        $warehouseContext = $this->getWarehouseContext();
        SalesView::render([
            'warehouses'=> $warehouseContext['warehouses'],
            'current_warehouse' => $warehouseContext['current_warehouse'],
            'is_locked' => $warehouseContext['is_locked']
        ]);
    }

    /**
     * API ENDPOINT: Mengambil data riwayat dengan Paginasi Server-Side (Limit 25)
     */
    public function filter_api() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $type      = $this->getPost('type', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $paging = $this->getPaginationParams(25);
		$result = $this->salesModel->getFilteredPaginated(
            $search, 
            $warehouse, 
            $startDate, 
            $endDate, 
            $type, 
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
    
}
?>