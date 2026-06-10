<?php
require_once 'BaseController.php';

class StocksController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        
        $this->model = new StocksModel();
    }

    /**
     * HALAMAN UTAMA: Menampilkan halaman stok/stok opname bulanan
     */
    public function index() {
        $warehouseContext = $this->getWarehouseContext();
        StocksView::render([
            'warehouses'=> $warehouseContext['warehouses'],
            'current_warehouse' => $warehouseContext['current_warehouse'],
            'is_locked' => $warehouseContext['is_locked']
        ]);
    }

    /**
     * API ENDPOINT: Mengambil data mutasi stok bulanan dengan Paginasi Server-Side (Limit 10)
     */
    public function filter_api() {
        $search     = $this->getPost('search', '');
        $warehouse  = $this->getPost('warehouse', '');
        $periodDate = $this->getPost('periodMonth', '');
		
		$paging = $this->getPaginationParams(10);
		$result = $this->model->getFilteredPaginated(
            $search, 
            $warehouse, 
            $periodDate, 
            $paging['limit'], 
            $paging['offset']
        );
		$isClosed = $this->model->isPeriodClosed($periodDate, $warehouse);
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);		
        
        return $this->jsonSuccess(
            "Data Filtered", 
            $result['data'], 
            ['pagination' => $paginationMeta, 'is_closed'  => $isClosed]
        );
    }
	
    /**
     * PROSES CLOSING BULANAN: Mengunci pergerakan stok pada bulan dan gudang tertentu
     */
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
	
    /**
     * API LOG: Mengambil data riwayat log penutupan buku (closing history)
     */
	public function get_history() {
        $history = $this->model->getClosingHistory();
        return $this->jsonSuccess("Data riwayat ditemukan", $history);
    }
    
}
?>