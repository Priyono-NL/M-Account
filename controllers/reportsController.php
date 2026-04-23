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
        // 1. Ambil data log transaksi
        $transactions = $this->reportModel->getItemTransactions();
        
        // 2. Paparkan View
        ReportsHistoryView::render($transactions);
    }
}
?>