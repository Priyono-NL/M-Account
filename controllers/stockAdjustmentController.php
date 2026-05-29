<?php
require_once 'BaseController.php';

class StockAdjustmentController extends BaseController {
    private $adjustmentModel;

    public function __construct() {
        parent::__construct();
        
        $this->adjustmentModel = new StockAdjustmentModel();
    }

    public function index() {
        // Render view utama panel admin adjustment
        StockAdjustmentView::render();
    }

    /**
     * API: Mengambil list dokumen Stock Opname yang berstatus 'DRAFT' (Pending)
     */
    public function filter_api() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        
        $paging = $this->getPaginationParams(10); // Mengikuti limit 10 halaman stok

        $result = $this->adjustmentModel->getPendingOpnamesPaginated($search, $warehouse, $paging['limit'], $paging['offset']);
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);

        return $this->jsonSuccess("Data Loaded", $result['data'], ['pagination' => $paginationMeta]);
    }

    /**
     * API: Mengambil rincian item di dalam satu dokumen opname untuk ditinjau selisihnya
     */
    public function get_opname_detail() {
        $opname_id = (int)$this->getPost('opname_id', 0);
        if ($opname_id <= 0) return $this->jsonError("ID Dokumen Opname tidak valid.");

        $header = $this->adjustmentModel->getOpnameHeader($opname_id);
        if (!$header) return $this->jsonError("Dokumen tidak ditemukan.");

        $items = $this->adjustmentModel->getOpnameDetails($opname_id);

        return $this->jsonSuccess("Success", [
            'header' => $header,
            'items'  => $items
        ]);
    }

    /**
     * API ACTION: Eksekusi Final - Menyetujui adjustment dan merubah stok komputer
     */
    public function submit_adjustment() {
        $opname_id = (int)$this->getPost('opname_id', 0);
        $itemsRaw  = $this->getPost('items'); // Membawa array rincian alasan per item id

        if ($opname_id <= 0) return $this->jsonError("ID Dokumen tidak valid.");
        if (empty($itemsRaw)) return $this->jsonError("Data item adjustment kosong.");

        $items = json_decode($itemsRaw, true);
        if (!$items) return $this->jsonError("Format data item adjustment rusak.");

        $adminUsername = $_SESSION['user']['username'] ?? 'Admin';

        // Kirim ke model untuk eksekusi query mutasi stok berantai
        $result = $this->adjustmentModel->executeAdjustment($opname_id, $items, $adminUsername);

        if ($result && $result['status'] === 'success') {
            return $this->jsonSuccess($result['message']);
        } else {
            return $this->jsonError($result['message'] ?? "Gagal mengeksekusi adjustment.");
        }
    }
}
?>