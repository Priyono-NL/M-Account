<?php
require_once 'BaseController.php';

class StockCloseController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new StocksModel();
        parent::__construct();
    }

    public function index() {
        $stocks = $this->model->getMonthlyReport();
        StockCloseView::render($stocks);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', '');

        $items = $this->model->getMonthlyReport($search, $warehouse, $closeMonth);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function export_xls() {
        $search    = $_POST['search'] ?? '';
        $warehouse = $_POST['warehouse'] ?? '';
        $closeMonth = $_POST['closeMonth'] ?? '';
        $data = $this->model->getMonthlyReport($search, $warehouse, $closeMonth);

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
                $item['item_code'],
                $item['item_name'],
                $namaGudang,
                (float)$item['qty_open'],
                (float)$item['qty_in'],
                (float)$item['qty_out'],
                (float)$item['qty_close'],
                (float)$item['qty_onhand'],
                (float)$item['selisih']
            ];
        }

        $fileName = "Laporan_Stok_" . date('mY') . ".xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

    public function do_closing() {
        $closeMonth = isset($_POST['monthPeriod']) ? $_POST['monthPeriod'] : '';

        if (empty($closeMonth)) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Bulan tidak boleh kosong!'
            ]);
            exit;
        }

        try {
            $jmlClosing = $this->model->doClosing($closeMonth);
            echo json_encode([
                'status' => 'success',
                'message' => "Proses Closing berhasil! Sebanyak $jmlClosing barang untuk periode $closeMonth telah dikunci."
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal melakukan closing: ' . $e->getMessage()
            ]);
        }

        exit;
    }

}
?>