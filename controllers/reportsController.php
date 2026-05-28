<?php
require_once 'BaseController.php';

class ReportsController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        
        $this->model = new ReportModel();
    }

    public function index() {
        ReportsHistoryView::render([]);
    }

    public function filter_api() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');
		
		$paging = $this->getPaginationParams(25);
        $result = $this->model->getFilteredPaginated(
            $search, 
            $warehouse, 
            $startDate, 
            $endDate, 
            $paging['limit'], 
            $paging['offset']
        );
		$paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
        
        return $this->jsonSuccess(
            "Data Filtered", 
            $result['data'], 
            ['pagination' => $paginationMeta]
        );
    }

    public function export_xls() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');
        
        $data = $this->model->getFiltered($search, $warehouse, $startDate, $endDate);

        $rows = [[
            '<b>No</b>', 
            '<b>Tanggal Transaksi</b>', 
            '<b>No Referensi</b>', 
            '<b>Kode Barang</b>',  
            '<b>Nama Barang</b>',
            '<b>Gudang</b>', 
            '<b>Tipe</b>', 
            '<b>Qty</b>', 
            '<b>Catatan</b>'
        ]];

        foreach ($data as $index => $item) {
            $namaGudang = $item['warehouse'];
            if ($item['warehouse'] == '1') {
                $namaGudang = 'Gudang BS';
            } elseif ($item['warehouse'] == '2') {
                $namaGudang = 'Gudang Sampah';
            }

            $tanggalFormatted = date('d-m-Y H:i', strtotime($item['transaction_date']));

            $rows[] = [
                $index + 1,
                $tanggalFormatted,
                $item['reference_no'],
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