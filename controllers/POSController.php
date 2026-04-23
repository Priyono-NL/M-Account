<?php
// =========================================================================
// POS CONTROLLER (Logika Halaman Kasir)
// =========================================================================

require_once 'BaseController.php';

class POSController extends BaseController {
    private $itemsModel;
    private $buyerModel;
    private $salesModel;

    public function __construct() {
        $this->itemsModel = new ItemsModel();
        $this->buyerModel = new BuyerModel();
        $this->salesModel = new SalesModel();
    }

    public function index() {
        POSView::render();
    }

    public function search_product() {
        $keyword = $this->getPost('keyword', '');
        $results = $this->itemsModel->searchProducts($keyword); 
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    public function search_buyer() {
        $keyword = $this->getPost('keyword', '');        
        $results = $this->buyerModel->searchBuyers($keyword);

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    public function checkout() {
        $cartRaw = $this->getPost('cart');
        $buyerId = $this->getPost('buyer_id');
        $warehouse = $this->getPost('warehouse', 1);

        if (empty($cartRaw)) {
            return $this->jsonError("Keranjang belanja kosong.");
        }

        $cart = json_decode($cartRaw, true);
        if (!$cart) {
            return $this->jsonError("Data keranjang tidak valid.");
        }

        if (empty($buyerId)) {
            return $this->jsonError("Harap pilih pelanggan terlebih dahulu.");
        }

        $result = $this->salesModel->saveTransaction($cart, $buyerId, $warehouse);
        
        if ($result) {
            return $this->jsonSuccess("Transaksi berhasil disimpan.");
        } else {
            return $this->jsonError("Gagal menyimpan transaksi ke database.");
        }
    }
}
?>