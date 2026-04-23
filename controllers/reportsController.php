<?php
// =========================================================================
// REPORTS CONTROLLER (Halaman Riwayat Transaksi)
// =========================================================================

require_once 'BaseController.php';

class ReportsController extends BaseController {
    private $model;

    public function __construct() {
        // Memuatkan model laporan
        $this->model = new ReportModel();
    }

    public function index() {
        $transactions = $this->model->getFilteredTransactions();
        ReportsHistoryView::render($transactions);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $items = $this->model->getFilteredTransactions($search, $warehouse, $startDate, $endDate);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

}
?>