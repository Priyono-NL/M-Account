<?php
require_once 'BaseController.php';

class BuyerController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new BuyerModel();
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
        ]);

        $res = $this->model->insert('buyer', $data);
        return $res ? $this->jsonSuccess("Pelanggan berhasil ditambah") : $this->jsonError("Gagal menambah pelanggan");
    }

    public function update() {
        $id = $this->getPost('id');
        $data = $this->sanitize([
            'buyer_name' => $this->getPost('buyer_name'),
            'is_active'  => $this->getPost('is_active')
        ]);

        $res = $this->model->update('buyer', $data, "id = $id");
        return $res ? $this->jsonSuccess("Data pelanggan diperbarui") : $this->jsonError("Gagal memperbarui data");
    }
}