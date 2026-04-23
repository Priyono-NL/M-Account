<?php
// =========================================================================
// REPORTS CONTROLLER (Halaman Riwayat Transaksi)
// =========================================================================

require_once 'BaseController.php';

class ReportsController extends BaseController {
    private $reportModel;

    public function __construct() {
        // Memuatkan model laporan
        $this->reportModel = new ReportModel();
    }

    public function index() {
        $search   = $_GET['q'] ?? '';
        $warehouse = $_GET['w'] ?? '';
        $startDate = $_GET['f'] ?? '';
        $endDate   = $_GET['e'] ?? '';
        // 1. Ambil data log transaksi
        $transactions = $this->reportModel->getFilteredTransactions($search, $warehouse, $startDate, $endDate);
        
        // 2. Paparkan View
        ReportsHistoryView::render($transactions);
    }
}
?>