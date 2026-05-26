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
        
        POSView::render($transactionData);
    }

    public function history() {
        $sales = $this->salesModel->getFiltered();
        Sales_view::render($sales);
    }

    public function filter_api() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $type      = $this->getPost('type', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $items = $this->salesModel->getFiltered($search, $warehouse, $startDate, $endDate, $type);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function get_products() {
        $keyword   = $this->getPost('keyword', '');
        $warehouse = $this->getPost('warehouse', '');
        $results   = $this->stocksModel->getLatestStock($keyword, $warehouse); 
        
        return $this->jsonSuccess("Data produk berhasil dimuat", $results);
    }

    public function get_buyers() {
        $keyword = $this->getPost('keyword', '');        
        $results = $this->buyerModel->getFiltered($keyword);

        return $this->jsonSuccess("Data pelanggan berhasil dimuat", $results);
    }

    public function checkout() {
        $cartRaw    = $this->getPost('cart');
        $buyer_id   = $this->getPost('buyer_id');
        $warehouse  = $this->getPost('warehouse');
        $sales_date = $this->getPost('sales_date');
        $sales_type = $this->getPost('sales_type');

        if (empty($cartRaw)) return $this->jsonError("Keranjang belanja kosong.");

        $cart = json_decode($cartRaw, true);
        if (!$cart) return $this->jsonError("Data keranjang tidak valid.");

        if (empty($buyer_id)) return $this->jsonError("Harap pilih pelanggan terlebih dahulu.");

        $result = $this->salesModel->saveTransaction($cart, $buyer_id, $warehouse, $sales_date, $sales_type);
        
        if ($result && isset($result['status']) && $result['status'] === 'success') {
            return $this->jsonSuccess($result['message'], ['sale_id' => $result['sale_id']]);
        } else {
            $errorMessage = $result['message'] ?? "Gagal menyimpan transaksi ke database.";
            return $this->jsonError($errorMessage);
        }
    }

    public function export_xls() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');
        $type      = $this->getPost('type', '');

        $data = $this->salesModel->getFiltered($search, $warehouse, $startDate, $endDate, $type);
        $grand_total = 0;

        $rows = [[
            '<b>No</b>', 
            '<b>Gudang</b>', 
            '<b>Tipe Transaksi</b>', 
            '<b>No. Invoice</b>',  
            '<b>Pelanggan</b>',
            '<b>Tanggal Transaksi</b>', 
            '<b>Total</b>'
        ]];

        foreach ($data as $index => $item) {
            $namaGudang = $item['warehouse'];
            if ($item['warehouse'] == '1') $namaGudang = 'Gudang BS';
            elseif ($item['warehouse'] == '2') $namaGudang = 'Gudang Sampah';

            $tipeTransaksi = ($item['sale_type'] === 'SLS') ? 'Normal Sales' : 'Expense Sales';
            $tanggal = date('d M Y', strtotime($item['sales_date']));

            $total = ($item['sale_type'] === 'EXP') ? 0 : (int)$item['total'];
            $grand_total += $total;

            $rows[] = [
                $index + 1,
                $namaGudang,
                $tipeTransaksi,
                $item['invoice_no'],
                $item['buyer_name'],
                $tanggal,
                '<style nf="#,##0">' . $total . '</style>'
            ];
        }

        $rows[] = [
            '<b></b>', '<b></b>', '<b></b>', '<b></b>', '<b></b>',
            '<b>GRAND TOTAL</b>',
            '<style nf="#,##0"><b>' . $grand_total . '</b></style>'
        ];

        $fileName = "Laporan_Penjualan_" . date('Ymd_His') . ".xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

    public function print_invoice() {
        $sales_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$sales_id || $sales_id <= 0) die("ID Transaksi tidak valid.");

        $header = $this->salesModel->getSalesHeader($sales_id);
        $items  = $this->salesModel->getTransactionItems($sales_id);

        if (!$header) die("Data transaksi tidak ditemukan.");

        if ($header['warehouse'] == 1) {
            InvoiceView::render($header, $items);
        } else {
            SuratView::render($header, $items);
        }        
    }

    public function print_invoice_pdf() {
        $sales_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$sales_id || $sales_id <= 0) die("ID Transaksi tidak valid.");

        $header = $this->salesModel->getSalesHeader($sales_id);
        $items  = $this->salesModel->getTransactionItems($sales_id);

        if (!$header) die("Data transaksi tidak ditemukan.");

        require_once 'vendors/dompdf/autoload.inc.php'; 
    
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
		

        ob_start();
        // Konversi CM ke Points: 1cm = 28.3465pt
        if ($header['warehouse'] == 1) {
            $width  = 8.5 * 28.3465;
            $height = 9.7 * 28.3465;
            InvoiceViewPdf::render($header, $items);
        } else {
            $width  = 17 * 28.3465;
            $height = 24 * 28.3465;
            SuratViewPdf::render($header, $items);
        }
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, $width, $height], 'landscape');
        
        $dompdf->render();
        $dompdf->stream("Invoice-" . $header['invoice_no'] . ".pdf", [
            "Attachment" => false
        ]);
        exit;     
    }
}
?>