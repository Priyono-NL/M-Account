<?php
require_once 'BaseController.php';

class POSController extends BaseController {
    private $stocksModel;
    private $buyerModel;
    private $salesModel;

    public function __construct() {
        $this->stocksModel = new StocksModel();
        $this->buyerModel = new BuyerModel();
        $this->salesModel = new SalesModel();
        parent::__construct();
    }

    public function index() {
        $mode = $_POST['mode'] ?? 'create';
        $sales_id = $_POST['id'] ?? null;
        $transactionData = null;

        if ($mode === 'view' && $sales_id) {
            $header = $this->salesModel->getSalesHeader($sales_id);
            
            if ($header) {
                $id_aman = intval($sales_id);
                $items = $this->salesModel->getTransactionItems($id_aman);
                
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
        $search   = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $type = $this->getPost('type', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $items = $this->salesModel->getFiltered($search, $warehouse, $startDate, $endDate, $type);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function get_products() {
        $keyword = $this->getPost('keyword', '');
        $warehouse = $this->getPost('warehouse', '');
        $results = $this->stocksModel->getFiltered($keyword, $warehouse); 
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    public function get_buyers() {
        $keyword = $this->getPost('keyword', '');        
        $results = $this->buyerModel->getFiltered($keyword);

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    public function checkout() {
        $cartRaw = $this->getPost('cart');
        $buyer_id   = $this->getPost('buyer_id');
        $warehouse  = $this->getPost('warehouse');
        $sales_date = $this->getPost('sales_date');
        $sales_type = $this->getPost('sales_type');

        if (empty($cartRaw)) return $this->jsonError("Keranjang belanja kosong.");

        $cart = json_decode($cartRaw, true);
        if (!$cart) return $this->jsonError("Data keranjang tidak valid.");

        if (empty($buyer_id)) return $this->jsonError("Harap pilih pelanggan terlebih dahulu.");

        $result = $this->salesModel->saveTransaction($cart, $buyer_id, $warehouse, $sales_date, $sales_type);
        
        if ($result) return $this->jsonSuccess($result['message'], ['sale_id' => $result['sale_id']]);
        else return $this->jsonError("Gagal menyimpan transaksi ke database.");
    }

    public function export_xls() {
        $search    = $_POST['search'] ?? '';
        $warehouse = $_POST['warehouse'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate   = $_POST['end_date'] ?? '';
        $type      = $_POST['type'] ?? '';

        $data = $this->salesModel->getFiltered($search, $warehouse, $startDate, $endDate, $type);

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

            $total = ($item['sale_type'] === 'EXP') ? 0 : (float)$item['total'];

            $rows[] = [
                $index + 1,
                $namaGudang,
                $tipeTransaksi,
                $item['invoice_no'],
                $item['buyer_name'],
                $tanggal,
                $total
            ];
        }

        $fileName = "Laporan_Penjualan_" . date('Ymd_His') . ".xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

    public function print_invoice() {
        $sales_id = $_GET['id'] ?? null;
        if (!$sales_id) die("ID Transaksi tidak ditemukan.");

        $header = $this->salesModel->getSalesHeader($sales_id);
        $items = $this->salesModel->getTransactionItems($sales_id);
        
        InvoiceView::render($header, $items);
    }
}
?>