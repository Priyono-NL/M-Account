<?php
require_once 'BaseController.php';

class StocksController extends BaseController {
    private $model;

    public function __construct() {
        // Memuatkan model laporan
        $this->model = new StocksModel();
    }

    public function index() {
        $stocks = $this->model->getFiltered();
        StocksView::render($stocks);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $items = $this->model->getFiltered($search, $warehouse, $startDate, $endDate);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

}
?>