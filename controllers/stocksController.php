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
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $items = $this->model->getFiltered($search, $warehouse, $startDate, $endDate);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function export_xls() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');
        
        $data = $this->model->getFiltered($search, $warehouse, $startDate, $endDate);

        $rows = [[
            '<b>No</b>', '<b>Tanggal Update</b>', 
            '<b>Kode Barang</b>', '<b>Nama Barang</b>', '<b>Gudang</b>',
            '<b>Qty Awal</b>', '<b>Qty Masuk</b>', '<b>Qty Keluar</b>', '<b>Saldo Akhir</b>'
        ]];

        foreach ($data as $index => $item) {
            $namaGudang = $item['warehouse'];
            if ($item['warehouse'] == '1') {
                $namaGudang = 'Gudang BS';
            } elseif ($item['warehouse'] == '2') {
                $namaGudang = 'Gudang Sampah';
            }

            $tanggalFormatted = !empty($item['date']) ? date('d-m-Y H:i', strtotime($item['date'])) : '-';

            $rows[] = [
                $index + 1,
                $tanggalFormatted,
                $item['item_code'],
                $item['item_name'],
                $namaGudang,
                (float)$item['qty_open'],
                (float)$item['qty_in'],
                (float)$item['qty_out'],
                (float)$item['qty_total']
            ];
        }

        $fileName = "Laporan_Stok_" . date('Ymd_His') . ".xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

}
?>