<?php
require_once 'BaseController.php';

class StockCloseController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        
        $this->model = new StocksModel();
    }

    public function index() {
        $closeMonth = date('Y-m');
        $result = $this->model->getClosingData('', '', $closeMonth);
        StockCloseView::render($result);
    }

    public function filter_api() {
        $search     = $this->getPost('search', '');
        $warehouse  = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', date('Y-m'));

        $result = $this->model->getClosingData($search, $warehouse, $closeMonth);
        
        return $this->jsonSuccess("Data Filtered", [
            'status' => $result['status'],
            'stocks' => $result['data']
        ]);
    }

    public function export_xls() {
        $search     = $this->getPost('search', '');
        $warehouse  = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', date('Y-m'));
        
        $result = $this->model->getClosingData($search, $warehouse, $closeMonth);
        $data = $result['data'];

        $rows = [[
            '<b>No</b>', '<b>Gudang</b>', '<b>Kode Barang</b>', '<b>Nama Barang</b>',
            '<b>Qty Open</b>', '<b>Qty In</b>', '<b>Qty Out</b>',
            '<b>Qty Close</b>', '<b>Qty Onhand</b>', '<b>Selisih</b>',
        ]];

        foreach ($data as $index => $item) {
            $namaGudang = $item['warehouse'];
            if ($item['warehouse'] == '1') {
                $namaGudang = 'Gudang BS';
            } elseif ($item['warehouse'] == '2') {
                $namaGudang = 'Gudang Sampah';
            }

            $rows[] = [
                $index + 1,
                $namaGudang,
                $item['item_code'],
                $item['item_name'],
                (float)$item['qty_open'],
                (float)$item['qty_in'],
                (float)$item['qty_out'],
                (float)$item['qty_close'],
                (float)($item['qty_onhand'] ?? 0),
                (float)($item['selisih'] ?? 0)
            ];
        }

        $fileName = "Laporan_Stok_" . date('mY') . ".xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

}
?>