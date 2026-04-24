<?php
require_once 'BaseController.php';
require_once './vendors/SimpleXLSXGen.php.php';

class ReportsController extends BaseController {
    private $model;

    public function __construct() {
        // Memuatkan model laporan
        $this->model = new ReportModel();
    }

    public function index() {
        $transactions = $this->model->getFiltered();
        ReportsHistoryView::render($transactions);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $items = $this->model->getFiltered($search, $warehouse, $startDate, $endDate);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function export_xls() {
        $search    = $_POST['search'] ?? '';
        $warehouse = $_POST['warehouse'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate   = $_POST['end_date'] ?? '';
        $data = $this->model->getFiltered($search, $warehouse, $startDate, $endDate);

        $rows = [[
            '<b>No</b>', '<b>Tanggal Transaksi</b>', 
            '<b>No Referensi</b>', '<b>Kode Barang</b>',  '<b>Nama Barang</b>',
            '<b>Gudang</b>', '<b>Tipe</b>', '<b>Qty</b>', '<b>Catatan</b>'
            ]];

        foreach ($data as $index => $item) {
            $namaGudang = $item['warehouse'];
            if ($item['warehouse'] == '1') $namaGudang = 'Gudang BS';
            elseif ($item['warehouse'] == '2') $namaGudang = 'Gudang Sampah';

            $rows[] = [
                $index + 1,
                $item['reference_no'],
                $item['transaction_date'],
                $item['item_code'],
                $item['item_name'],
                $namaGudang,
                $item['type'],
                (float)$item['qty'],
                $item['notes']
            ];
        }

        $fileName = "Laporan_Transaksi_" . date('Ymd_His') . ".xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

}
?>