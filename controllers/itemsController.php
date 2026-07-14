<?php
require_once 'BaseController.php';

class ItemsController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new ItemsModel();
    }

    public function index() {
        $warehouseContext = $this->getWarehouseContext();
        $origins = $this->model->getOrigins();
        ItemsView::render([
            'warehouses'=> $warehouseContext['warehouses'],
            'current_warehouse' => '',
            'is_locked' => $warehouseContext['is_locked'],
            'origins' => $origins
        ]);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $category = $this->getPost('category', '');
		
		$paging = $this->getPaginationParams(10);
		$result = $this->model->getFilteredPaginated($search, $category, $paging['limit'], $paging['offset']);
		$paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
		
        return $this->jsonSuccess(
            "Data Filtered", 
            $result['data'], 
            ['pagination' => $paginationMeta]
        );
    }

    public function add() {
        $username = $_SESSION['user']['username'] ?? 'System';
        $data = $this->sanitize([
            'item_code'  => $this->getPost('item_code'),
            'item_name'  => $this->getPost('item_name'),
            'organization_id'   => $this->getPost('organization_id'),
            'item_uom'   => $this->getPost('item_uom'),
            'unit_price' => $this->getPost('unit_price'),
            'unit_cost'  => $this->getPost('unit_cost'),
            'unit_weight'  => $this->getPost('unit_weight'),
            'weight_uom'  => $this->getPost('weight_uom'),
            'origin_code'  => $this->getPost('origin_code'),
            'origin_name'  => $this->getPost('origin_name'),
            'created_by'      => $username,
            'updated_by'      => null
            
        ]);

        if (empty($data['item_code']) || empty($data['item_name'])) {
            return $this->jsonError("Kode Barang dan Nama Barang wajib diisi.");
        }
		
		$sql = "SELECT item_code FROM items WHERE is_active = 0 AND item_code LIKE :item_code";
		$ext_itemCode = $this->model->query_one($sql, ['item_code' => $data['item_code']]);
		if ($ext_itemCode) return $this->jsonError("Kode Barang sudah digunakan");

        $res = $this->model->insert('items', $data);
        return $res ? $this->jsonSuccess("Barang berhasil ditambah") : $this->jsonError("Gagal menambah barang");
    }

    public function update() {
        $id = (int)$this->getPost('id');
        
        if ($id <= 0) {
            return $this->jsonError("ID Barang tidak valid.");
        }

        $username = $_SESSION['user']['username'] ?? 'System';
        $data = $this->sanitize([
            'item_name'  => $this->getPost('item_name'),
            'organization_id'   => $this->getPost('organization_id'),
            'item_uom'   => $this->getPost('item_uom'),
            'unit_price' => $this->getPost('unit_price'),
            'unit_cost'  => $this->getPost('unit_cost'),
            'unit_weight'  => $this->getPost('unit_weight'),
            'weight_uom'  => $this->getPost('weight_uom'),
            'origin_code'  => $this->getPost('origin_code'),
            'origin_name'  => $this->getPost('origin_name'),
            'updated_by'      => $username
        ]);

        // Pengecekan 7: Amankan klausul update master barang
        $res = $this->model->update('items', $data, "id = :id", ['id' => $id]);
        return $res ? $this->jsonSuccess("Data barang diperbarui") : $this->jsonError("Gagal memperbarui data");
    }

    public function delete() {
        $id = (int)$this->getPost('id');

        if ($id <= 0) {
            return $this->jsonError("ID Barang tidak valid.");
        }

        // Pengecekan 7: Amankan klausul delete master barang
        $res = $this->model->delete('items', "id = :id", ['id' => $id]);
        return $res ? $this->jsonSuccess("Barang berhasil dihapus") : $this->jsonError("Gagal menghapus barang");
    }

    public function download_template() {
        // Susun baris instruksi yang sangat jelas agar user tidak bingung mengisi kolom select
        $rows = [
            [ 
                '<b>Kode Barang</b>', 
                '<b>Nama Barang</b>',
                '<b>Organization Id</b>',
                '<b>UoM (Satuan)</b>',
                '<b>Harga Jual</b>',
                '<b>Harga Cost</b>',
            ],
            [
                '<style color="#FF0000"><i>*Wajib Diisi (Unik)</i></style>',
                '<style color="#FF0000"><i>*Wajib Diisi</i></style>',
                '<style color="#008000"><i>Pilihan: 1 / 2</i></style>',
                '<style color="#008000"><i>Pilihan: Bal/Box/Kg/Ea/Tin/Zak</i></style>',
                '<style color="#808080"><i>*Angka saja</i></style>',
                '<style color="#808080"><i>*Angka saja</i></style>',
            ],
            [ 
                'C123456', 
                'Contoh Nama Barang',
                '1',
                'Box',
                '1000',
                '1000',
            ]
        ];

        $fileName = "Format_Master_Barang.xlsx";
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
                array_shift($rows); 
                array_shift($rows);

                $successCount = 0;
                $errorCount = 0;

                $username = $_SESSION['user']['username'] ?? 'System';

                foreach ($rows as $row) {
                    $item_code  = trim($row[0] ?? '');
                    $item_name  = trim($row[1] ?? '');
                    $organization_id   = trim($row[2] ?? '1');
                    $item_uom   = trim($row[3] ?? '');
                    $unit_price = trim($row[4] ?? 0);
                    $unit_cost  = trim($row[5] ?? 0);
                    
                    if (empty($item_code) || empty($item_name)) {
                        continue;
                    }

                    $data = $this->sanitize([
                        'item_code'  => $item_code,
                        'item_name'  => $item_name,
                        'organization_id'   => $organization_id,
                        'item_uom'   => $item_uom,
                        'unit_price' => $unit_price,
                        'unit_cost'  => $unit_cost,
                        'created_by' => $username,
                        'updated_by' => null
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
?>