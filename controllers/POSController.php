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
    }

    public function index() {
        POSView::render();
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
        
        if ($result) return $this->jsonSuccess("Transaksi berhasil disimpan.");
        else return $this->jsonError("Gagal menyimpan transaksi ke database.");
    }
}
?>