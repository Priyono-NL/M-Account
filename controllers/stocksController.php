<?php
require_once 'BaseController.php';

class StocksController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new StocksModel();
    }

    /**
     * HALAMAN UTAMA: Menampilkan layout kerangka laporan stok bulanan
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
     * API ENDPOINT: Mengambil data ringkasan mutasi stok dengan Paginasi Server-Side (Limit 25)
     */
    public function filter_api() {
        $search     = $this->getPost('search', '');
        $warehouse  = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', date('Y-m'));

        // 1. Ambil parameter halaman dan offset dari BaseController (Limit 25)
        $paging = $this->getPaginationParams(25);

        // 2. Cek apakah bulan ini sudah di-closing atau masih berjalan (ONGOING)
        $isClosed = $this->model->isPeriodClosed($closeMonth, $warehouse);
        $status = $isClosed ? 'CLOSED' : 'ONGOING';

        // 3. Tarik data terpaginasi dari model (otomatis mengambil dari vw_laporan_stok_bulanan)
        $result = $this->model->getMonthlyReportPaginated($search, $warehouse, $closeMonth, $paging['limit'], $paging['offset']);
                
        // 4. Susun meta data paginasi berdasarkan jumlah data riil
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
        
        // 5. Balas dengan menyisipkan objek array 'pagination' ke komponen AJAX global
        return $this->jsonSuccess("Data Filtered", [
            'status' => $status,
            'stocks' => $result['data']
        ], ['pagination' => $paginationMeta]);
    }

    /**
     * EXPORT EXCEL: Mengunduh semua data ringkasan stok berjalan
     */
    public function export_xls() {
        $search     = $this->getPost('search', '');
        $warehouse  = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', date('Y-m'));
        
        // Kita gunakan limit 9.999.999 agar SEMUA data terambil ke dalam Excel tanpa terpotong paginasi
        $result = $this->model->getMonthlyReportPaginated($search, $warehouse, $closeMonth, 9999999, 0);
        $data = $result['data'];

        $rows = [[
            '<b>No</b>', '<b>Gudang</b>', '<b>Kode Barang</b>', '<b>Nama Barang</b>',
            '<b>Qty Open</b>', '<b>Qty In</b>', '<b>Qty Out</b>',
            '<b>Qty Close</b>', '<b>Qty Onhand</b>', '<b>Selisih</b>',
        ]];

        foreach ($data as $index => $item) {
            // Mapping nama gudang
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

        // Format nama file: Laporan_Stok_072026.xlsx
        $fileName = "Laporan_Stok_" . date('mY', strtotime($closeMonth . '-01')) . ".xlsx";
        
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

    /**
     * API ENDPOINT: Mengeksekusi proses Closing Stok
     */
    public function do_closing() {
        $warehouse  = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', date('Y-m'));
        
        // Ambil nama user yang mengeksekusi dari session, default ke System
        $user = $_SESSION['user']['person_name'] ?? 'System';

        try {
            $insertedCount = $this->model->doClosing($closeMonth, $warehouse, $user);
            return $this->jsonSuccess("Closing Berhasil! {$insertedCount} barang telah dicatat sebagai saldo awal bulan depan.");
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }
}
?>