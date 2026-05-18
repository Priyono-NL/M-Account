<?php
session_start();

//load env
require_once 'env_loader.php';

// Load Konfigurasi Database & Helper
require_once 'models/_dbHelper.php';

// Load Semua Models (Auto-load sederhana)
require_once 'models/dashboardModel.php';
require_once 'models/ItemsModel.php';
require_once 'models/BuyerModel.php';
require_once 'models/SalesModel.php';
require_once 'models/reportModel.php';
require_once 'models/stocksModel.php';
require_once 'models/stockInModel.php';

// Load Semua Views
require_once 'views/dashboard_view.php';
require_once 'views/pos_view.php';
require_once 'views/sales_view.php';
require_once 'views/invoice_view.php';
require_once 'views/invoice_pdf.php';
require_once 'views/surat_angkut_view.php';
require_once 'views/surat_angkut_pdf.php';
require_once 'views/items_view.php';
require_once 'views/buyer_view.php';
require_once 'views/report_view.php';
require_once 'views/stocks_view.php';
require_once 'views/stock_close_view.php';
require_once 'views/stockIn_view.php';
require_once 'views/receive_view.php';

require_once 'views/salesPivot_view.php';

$url_path = $_GET['page'] ?? 'dashboard';
$url_path = rtrim($url_path, '/');
$segments = explode('/', $url_path);
$page = $segments[0];
$action = $segments[1] ?? 'index';

// Mapping Page ke Controller
$controllers = [
    'auth' => 'authController',
    'dashboard' => 'dashboardController',
    'pos' => 'POSController',
    'receive' => 'stockInController',
    'items' => 'itemsController',
    'buyers' => 'buyerController',
    'history' => 'reportsController',
    'stocks' => 'stocksController',
    'stockClose' => 'stockCloseController',
    'sales' => 'salesPivotController'
];

$controllerName = $controllers[$page] ?? 'DashboardController';

if (file_exists("controllers/{$controllerName}.php")) {
    require_once "controllers/{$controllerName}.php";
    $app = new $controllerName();
} else {
    die("Error: Controller file 'controllers/{$controllerName}.php' tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'index';

    if (method_exists($app, $action)) {
        $app->$action();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error', 
            'message' => "Method '{$action}' tidak ditemukan di {$controllerName}"
        ]);
    }
    exit;
    
} else {    
    if (method_exists($app, $action)) {
        $app->$action();
    } else {
        die("Error: Method {$action}() tidak ditemukan di {$controllerName}.");
    }
}