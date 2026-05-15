<?php
require_once 'BaseController.php';

class StockInController extends BaseController {
    private $itemsModel;
    private $personModel;
    private $stockInModel;

    public function __construct() {
        $this->itemsModel = new ItemsModel();
        $this->personModel = new BuyerModel();
        $this->stockInModel = new StockInModel();
        parent::__construct();
    }

    public function index() {
        $mode = $_POST['mode'] ?? 'create';
        $receive_id = $_POST['id'] ?? null;
        $transactionData = null;

        if ($mode === 'view' && $receive_id) {
            $header = $this->stockInModel->getById('receivement', $receive_id);
            
            if ($header) {
                $id_aman = intval($receive_id);
                $items = $this->stockInModel->getTransactionItems($id_aman);
                
                $transactionData = [
                    'header' => $header,
                    'items'  => $items
                ];
            }
        }
        
        StockIn_view::render($transactionData);
    }

    public function history() {
        $receivement = $this->stockInModel->getFiltered();
        Receive_view::render($receivement);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $items = $this->stockInModel->getFiltered($search, $warehouse, $startDate, $endDate);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function get_products() {
        $keyword = $this->getPost('keyword', '');
        $results = $this->itemsModel->getFiltered($keyword); 
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    public function get_buyers() {
        $keyword = $this->getPost('keyword', '');        
        $results = $this->personModel->getFiltered($keyword);

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    public function checkout() {
        $cartRaw = $this->getPost('cart');
        $doc_number = $this->getPost('doc_number');
        $received_by = $this->getPost('received_by');
        $warehouse = $this->getPost('warehouse');
        $date_receive = $this->getPost('date_receive');        

        if (empty($cartRaw)) return $this->jsonError("Keranjang belanja kosong.");

        $cart = json_decode($cartRaw, true);
        if (!$cart) return $this->jsonError("Data keranjang tidak valid.");

        if (empty($received_by)) return $this->jsonError("Harap pilih pelanggan terlebih dahulu.");

        $result = $this->stockInModel->saveReceivement($cart, $doc_number, $received_by, $warehouse, $date_receive);
        
        if ($result && isset($result['status']) && $result['status'] === 'success') {
            return $this->jsonSuccess($result['message'], ['sale_id' => $result['sale_id']]);
        } else {
            $errorMessage = isset($result['message']) ? $result['message'] : "Gagal menyimpan transaksi ke database.";
            return $this->jsonError($errorMessage);
        }
    }

    public function export_xls() {
        $search    = $_POST['search'] ?? '';
        $warehouse = $_POST['warehouse'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate   = $_POST['end_date'] ?? '';

        $data = $this->stockInModel->getFiltered($search, $warehouse, $startDate, $endDate);

        $rows = [[
            '<b>No</b>', 
            '<b>Gudang</b>',
            '<b>Document Number</b>',  
            '<b>Penerima</b>',
            '<b>Tanggal Terima</b>', 
        ]];

        foreach ($data as $index => $item) {
            $namaGudang = $item['warehouse'];
            if ($item['warehouse'] == '1') $namaGudang = 'Gudang BS';
            elseif ($item['warehouse'] == '2') $namaGudang = 'Gudang Sampah';

            $tanggal = date('d M Y', strtotime($item['date_receive']));

            $rows[] = [
                $index + 1,
                $namaGudang,
                $item['doc_number'],
                $item['received_by'],
                $tanggal,
            ];
        }

        $fileName = "Laporan_Penjualan_" . date('Ymd_His') . ".xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }
}
?>