<?php
require_once 'BaseController.php';

class StockCloseController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        
        $this->model = new StocksModel();
    }

    /**
     * HALAMAN UTAMA: Menampilkan layout kerangka laporan stok bulanan
     */
    public function index() {
        // REVISI: Mengirim array kosong karena pemuatan data dikendalikan penuh oleh tombol cari via AJAX
        StockCloseView::render([]);
    }

    /**
     * API ENDPOINT: Mengambil data ringkasan mutasi stok dengan Paginasi Server-Side (Limit 25)
     */
    public function filter_api() {
        $search     = $this->getPost('search', '');
        $warehouse  = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', date('Y-m'));

        // 1. Ambil parameter halaman dan offset dari BaseController (Limit 25)
        $paging = $this->getPaginationParams(25);

        // 2. Tarik data terpaginasi dari model yang memeriksa status DRAFT/CLOSED secara dinamis
        $result = $this->model->getClosingDataPaginated($search, $warehouse, $closeMonth, $paging['limit'], $paging['offset']);
        
        // 3. Susun susunan meta data paginasi berdasarkan jumlah data riil dari database
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
        
        // 4. Balas dengan menyisipkan objek array 'pagination' ke komponen AJAX global
        return $this->jsonSuccess("Data Filtered", [
            'status' => $result['status'],
            'stocks' => $result['data']
        ], ['pagination' => $paginationMeta]);
    }

    /**
     * EXPORT EXCEL: Mengunduh semua data ringkasan stok berjalan tanpa batasan limit halaman
     */
    public function export_xls() {
        $search     = $this->getPost('search', '');
        $warehouse  = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', date('Y-m'));
        
        // Tetap memanggil fungsi getClosingData lama tanpa limit data agar Excel terisi lengkap
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