<?php
require_once 'BaseController.php';

class StocksController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        
        $this->model = new StocksModel();
    }

    public function index() {
        $stocks = $this->model->getFiltered();
        StocksView::render($stocks);
    }

    public function filter_api() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $periodDate = $this->getPost('periodMonth', '');

        $items = $this->model->getFiltered($search, $warehouse, $periodDate);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }
	
	public function do_closing() {
        $periodMonth = $this->getPost('periodMonth', '');
		$warehouse   = $this->getPost('warehouse', '');

        if (empty($periodMonth)) {
            return $this->jsonError('Bulan tidak boleh kosong!', 400);
        }

        try {
			$executedBy = $_SESSION['user']['username'] ?? 'Superadmin';
            $jmlClosing = $this->model->doClosing($periodMonth, $warehouse, $executedBy);
            
            return $this->jsonSuccess("Proses Closing berhasil! Sebanyak {$jmlClosing} barang untuk periode {$periodMonth} telah dikunci.");
        } catch (Exception $e) {
            return $this->jsonError('Gagal melakukan closing: ' . $e->getMessage(), 500);
        }
    }
	
	public function get_history() {
        $history = $this->model->getClosingHistory();
        return $this->jsonSuccess("Data riwayat ditemukan", $history);
    }
    
}
?>