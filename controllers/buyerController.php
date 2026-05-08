<?php
require_once 'BaseController.php';

class BuyerController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new BuyerModel();
        parent::__construct();
    }

    public function index() {
        $buyers = $this->model->getFiltered();
        BuyerView::render($buyers);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $items = $this->model->getFiltered($search);
                
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function add() {
        $data = $this->sanitize([
            'buyer_code' => $this->getPost('buyer_code'),
            'buyer_name' => $this->getPost('buyer_name'),
            'buyer_status' => $this->getPost('buyer_status'),
            'buyer_address' => $this->getPost('buyer_address')
        ]);

        $res = $this->model->insert('buyer', $data);
        return $res ? $this->jsonSuccess("Pelanggan berhasil ditambah") : $this->jsonError("Gagal menambah pelanggan");
    }

    public function update() {
        $id = $this->getPost('id');
        $data = $this->sanitize([
            'buyer_name' => $this->getPost('buyer_name'),
            'buyer_status' => $this->getPost('buyer_status'),
            'buyer_address' => $this->getPost('buyer_address')
        ]);

        $res = $this->model->update('buyer', $data, "id = $id");
        return $res ? $this->jsonSuccess("Data pelanggan diperbarui") : $this->jsonError("Gagal memperbarui data");
    }

    public function download_template() {
        $rows = [[ 
            '<b>Buyer Code(NRP)</b>',  '<b>Name</b>',
            '<b>Status</b>',  '<b>Address/Department</b>',
            ]];
        $rows[] = [ '100xx', 'Contoh', 'REG/EXP', 'Alamat/Departemen' ];

        $fileName = "Format Buyer.xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($rows)->downloadAs($fileName);
        exit;
    }

    public function upload() {
        header('Content-Type: application/json');
        try {
            if (!isset($_FILES['file_excel'])) throw new Exception("Tidak ada file yang diterima oleh server.");

            $file = $_FILES['file_excel'];

            if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("Error upload file: " . $file['error']);

            if ($xlsx = \Shuchkin\SimpleXLSX::parse($file['tmp_name'])) {
                $rows = $xlsx->rows();
                $header = array_shift($rows);

                $successCount = 0;
                $errorCount = 0;

                foreach ($rows as $index => $row) {
                    $code = trim($row[0] ?? '');
                    $name = trim($row[1] ?? '');
                    $status = trim($row[2] ?? '');
                    $address = trim($row[3] ?? '');
                    if (empty($code) || empty($name)) continue;

                    $data = $this->sanitize([
                        'buyer_code' => $code,
                        'buyer_name' => $name,
                        'buyer_status' => $status,
                        'buyer_address' => $address
                    ]);
                    $res = $this->model->insert('buyer', $data);                    
                    if ($res) $successCount++;
                }

                echo json_encode([
                    "status" => "success",
                    "message" => "Berhasil memproses data. Total: $successCount baris diperbarui."
                ]);

            } else {
                throw new Exception("Gagal membaca format Excel: " . \Shuchkin\SimpleXLSX::parseError());
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
        exit;
    }
}