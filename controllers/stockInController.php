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
    }

    public function index() {
        StockIn_view::render();
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

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
        
        if ($result) return $this->jsonSuccess("Transaksi berhasil disimpan.");
        else return $this->jsonError("Gagal menyimpan transaksi ke database.");
    }
}
?>