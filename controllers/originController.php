<?php
require_once 'BaseController.php';
require_once 'models/originModel.php';

class OriginController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new OriginModel();
    }

    public function index() {
         OriginView::render([]);
    }

    public function filter_api() {
        $search = $this->getPost('search', '');
        
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
            'origin_code'  => $this->getPost('origin_code'),
            'origin_name'  => $this->getPost('origin_name'),
            'origin_type'  => $this->getPost('origin_type'),
        ]);

        if (empty($data['origin_code']) || empty($data['origin_name'])) {
            return $this->jsonError("Origin Code dan Name wajib diisi.");
        }
        
        $res = $this->model->insert('origin_code', $data);
        if ($res !== false) return $this->jsonSuccess("Origin barang berhasil ditambah");
        else return $this->jsonError("Gagal menambah origin barang");
    }

    public function update() {
        $origin_code = $this->getPost('origin_code');
        if (empty($origin_code)) return $this->jsonError("Origin Code tidak boleh kosong.");

        $data = $this->sanitize([
            'origin_name'  => $this->getPost('origin_name'),
            'origin_type'  => $this->getPost('origin_type'),
        ]);
        if (empty($data['origin_name'])) return $this->jsonError("Origin Name wajib diisi.");

        $res = $this->model->update('origin_code', $data, "origin_code = :origin_code", ['origin_code' => $origin_code]);
        return $res ? $this->jsonSuccess("Origin barang diperbarui") : $this->jsonError("Gagal memperbarui data");
    }

    public function delete() {
        $origin_code = $this->getPost('origin_code'); 
        if (empty($origin_code)) return $this->jsonError("Origin Code tidak valid atau kosong.");

        $res = $this->model->delete('origin_code', "origin_code = :origin_code", ['origin_code' => $origin_code]);
        return $res ? $this->jsonSuccess("Origin Barang berhasil dihapus") : $this->jsonError("Gagal menghapus barang");
    }

}
?>