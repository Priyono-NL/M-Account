<?php
require_once 'BaseController.php';

class testController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new StocksModel();
        parent::__construct();
    }

    public function index() {
        $stocks = $this->model->getMonthlyReport();
        TestView::render($stocks);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', '');

        $items = $this->model->getMonthlyReport($search, $warehouse, $closeMonth);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

}
?>