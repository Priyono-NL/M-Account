<?php
require_once 'BaseController.php';

class DashboardController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();        
        $this->model = new DashboardModel();
    }

    public function index() {
        $warehouse = isset($_GET['warehouse']) ? (int)$_GET['warehouse'] : 1;
		$dashboardData = $this->model->getData($warehouse);
        DashboardView::render($dashboardData);
    }
}