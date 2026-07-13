<?php
require_once 'BaseController.php';

class BuyerController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new BuyerModel();
    }

    public function index() {
        BuyerView::render([]);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');		
		$paging = $this->getPaginationParams(10);
		$result = $this->model->getFilteredPaginated($search, $paging['limit'], $paging['offset']);
		$paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
		
        return $this->jsonSuccess(
            "Data Filtered", 
            $result['data'], 
            ['pagination' => $paginationMeta]
        );
    }

    public function add() {
        $data = $this->sanitize([
            'buyer_code'    => $this->getPost('buyer_code'),
            'buyer_name'    => $this->getPost('buyer_name'),
            'buyer_status'  => $this->getPost('buyer_status'),
            'buyer_address' => $this->getPost('buyer_address')
        ]);

        if (empty($data['buyer_code']) || empty($data['buyer_name'])) {
            return $this->jsonError("Kode dan Nama Pembeli wajib diisi.");
        }

        $res = $this->model->insert('buyer', $data);
        return $res ? $this->jsonSuccess("Pelanggan berhasil ditambah") : $this->jsonError("Gagal menambah pelanggan");
    }

    public function update() {
        $id = (int)$this->getPost('id');
        
        if ($id <= 0) {
            return $this->jsonError("ID Pelanggan tidak valid.");
        }

        $data = $this->sanitize([
            'buyer_name'    => $this->getPost('buyer_name'),
            'buyer_status'  => $this->getPost('buyer_status'),
            'buyer_address' => $this->getPost('buyer_address')
        ]);

        // Pengecekan 7: Amankan klausul update buyer
        $res = $this->model->update('buyer', $data, "id = :id", ['id' => $id]);
        return $res ? $this->jsonSuccess("Data pelanggan diperbarui") : $this->jsonError("Gagal memperbarui pelanggan");
    }

    public function delete() {
        $id = (int)$this->getPost('id');

        if ($id <= 0) {
            return $this->jsonError("ID Pelanggan tidak valid.");
        }

        // Pengecekan 7: Amankan klausul delete buyer
        $res = $this->model->delete('buyer', "id = :id", ['id' => $id]);
        return $res ? $this->jsonSuccess("Data pelanggan dihapus") : $this->jsonError("Gagal menghapus pelanggan");
    }

    public function download_template() {
        $rows = [[ 
            '<b>Buyer Code(NRP)</b>', '<b>Name</b>',
            '<b>Status</b>', '<b>Address/Department</b>',
        ]];
        $rows[] = [ '100xx', 'Contoh', 'REG/EXP', 'Alamat/Departemen' ];

        $fileName = "Format Buyer.xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

    public function upload() {
        try {
            if (!isset($_FILES['file_excel'])) {
                throw new Exception("Tidak ada file yang diterima oleh server.");
            }

            $file = $_FILES['file_excel'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error upload file dengan kode error: " . $file['error']);
            }

            if ($xlsx = \Shuchkin\SimpleXLSX::parse($file['tmp_name'])) {
                $rows = $xlsx->rows();
                $header = array_shift($rows);

                $successCount = 0;
                $errorCount = 0;

                foreach ($rows as $row) {
                    $code    = trim($row[0] ?? '');
                    $name    = trim($row[1] ?? '');
                    $status  = trim($row[2] ?? '');
                    $address = trim($row[3] ?? '');

                    if (empty($code) || empty($name)) {
                        continue;
                    }

                    $data = $this->sanitize([
                        'buyer_code'    => $code,
                        'buyer_name'    => $name,
                        'buyer_status'  => $status,
                        'buyer_address' => $address
                    ]);
                    
                    $res = $this->model->insert('buyer', $data);                    
                    if ($res) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }

                return $this->jsonSuccess("Proses Excel selesai. Berhasil: {$successCount}, Gagal/Duplikat: {$errorCount}");

            } else {
                throw new Exception("Gagal membaca format Excel: " . \Shuchkin\SimpleXLSX::parseError());
            }

        } catch (Exception $e) {
            return $this->jsonError($e->getMessage(), 400);
        }
    }
}
?>