<?php
require_once 'BaseController.php';

class DashboardController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();        
        $this->model = new DashboardModel();
    }

    public function index() {
        $warehouseContext = $this->getWarehouseContext();
        $dashboardData = $this->model->getData($warehouseContext['current_warehouse']);

        DashboardView::render([
            'dashboardData' => $dashboardData,
            'warehouses' => $warehouseContext['warehouses'],
            'current_warehouse' => $warehouseContext['current_warehouse'],
            'is_locked' => $warehouseContext['is_locked']
        ]);
    }
}