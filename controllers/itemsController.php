<?php
// =========================================================================
// ITEMS CONTROLLER (Master Data Barang)
// =========================================================================

require_once 'BaseController.php';

class ItemsController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new ItemsModel();
    }

    public function index() {
        $items = $this->model->getFilteredItems();
        ItemsView::render($items);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $category = $this->getPost('category', '');

        $items = $this->model->getFilteredItems($search, $category);
        
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

        $res = $this->model->insert('items', $data);
        return $res ? $this->jsonSuccess("Barang berhasil ditambah") : $this->jsonError("Gagal menambah barang");
    }

    public function update() {
        $id = $this->getPost('id');
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
        $id = $this->getPost('id');
        $res = $this->model->delete('items', "id = $id");
        return $res ? $this->jsonSuccess("Barang berhasil dihapus") : $this->jsonError("Gagal menghapus barang");
    }
}