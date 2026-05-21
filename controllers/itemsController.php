<?php
require_once 'BaseController.php';

class ItemsController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new ItemsModel();
    }

    public function index() {
        $items = $this->model->getFiltered();
        ItemsView::render($items);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $category = $this->getPost('category', '');

        $items = $this->model->getFiltered($search, $category);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function add() {
        $data = $this->sanitize([
            'item_code'  => $this->getPost('item_code'),
            'item_name'  => $this->getPost('item_name'),
            'category'   => $this->getPost('category'),
            'item_uom'   => $this->getPost('item_uom'),
            'unit_price' => $this->getPost('unit_price'),
            'unit_cost'  => $this->getPost('unit_cost')
        ]);

        if (empty($data['item_code']) || empty($data['item_name'])) {
            return $this->jsonError("Kode Barang dan Nama Barang wajib diisi.");
        }

        $res = $this->model->insert('items', $data);
        return $res ? $this->jsonSuccess("Barang berhasil ditambah") : $this->jsonError("Gagal menambah barang");
    }

    public function update() {
        $id = (int)$this->getPost('id');
        
        if ($id <= 0) {
            return $this->jsonError("ID Barang tidak valid.");
        }

        $data = $this->sanitize([
            'item_name'  => $this->getPost('item_name'),
            'category'   => $this->getPost('category'),
            'item_uom'   => $this->getPost('item_uom'),
            'unit_price' => $this->getPost('unit_price'),
            'unit_cost'  => $this->getPost('unit_cost')
        ]);

        $res = $this->model->update('items', $data, "id = $id");
        return $res ? $this->jsonSuccess("Data barang diperbarui") : $this->jsonError("Gagal memperbarui data");
    }

    public function delete() {
        $id = (int)$this->getPost('id');

        if ($id <= 0) {
            return $this->jsonError("ID Barang tidak valid.");
        }

        $res = $this->model->delete('items', "id = $id");
        return $res ? $this->jsonSuccess("Barang berhasil dihapus") : $this->jsonError("Gagal menghapus barang");
    }

    public function download_template() {
        $rows = [[ 
            '<b>Kode Barang</b>', 
            '<b>Nama Barang</b>',
            '<b>Kategori</b>',
            '<b>UoM</b>',
            '<b>Harga Jual</b>',
            '<b>Harga Cost</b>',
        ]];
        $rows[] = [ 
            'C123456', 
            'TEST',
            'ByProduct/Sampah',
            'Kg/Pcs/Zak',
            '1000',
            '1000',
        ];

        $fileName = "Format Barang.xlsx";
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
                throw new Exception("Error upload file: " . $file['error']);
            }

            if ($xlsx = \Shuchkin\SimpleXLSX::parse($file['tmp_name'])) {
                $rows = $xlsx->rows();
                $header = array_shift($rows);

                $successCount = 0;
                $errorCount = 0;

                foreach ($rows as $row) {
                    $item_code  = trim($row[0] ?? '');
                    $item_name  = trim($row[1] ?? '');
                    $category   = trim($row[2] ?? '1');
                    $item_uom   = trim($row[3] ?? '');
                    $unit_price = trim($row[4] ?? 0);
                    $unit_cost  = trim($row[5] ?? 0);
                    
                    if (empty($item_code) || empty($item_name)) {
                        continue;
                    }

                    $data = $this->sanitize([
                        'item_code'  => $item_code,
                        'item_name'  => $item_name,
                        'category'   => $category,
                        'item_uom'   => $item_uom,
                        'unit_price' => $unit_price,
                        'unit_cost'  => $unit_cost
                    ]);
                    
                    $res = $this->model->insert('items', $data);
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