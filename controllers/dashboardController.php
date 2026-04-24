<?php
require_once 'BaseController.php';

class DashboardController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new DashboardModel();
    }

    public function index() {
        $warehouse = $_GET['warehouse'] ?? '1';
        $dashboardData = $this->model->getData($warehouse);

        DashboardView::render($dashboardData);
    }
}