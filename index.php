<?php
// =========================================================================
// INDEX.PHP (Main Router / Dispatcher)
// =========================================================================
session_start();

// Load Konfigurasi Database
require_once 'config/database.php';

// Load Models & Helper
require_once 'models/_dbHelper.php';
require_once 'models/ItemsModel.php';
require_once 'models/BuyerModel.php';
require_once 'models/SalesModel.php';
require_once 'models/reportModel.php';
require_once 'models/stocksModel.php';

// Load Views
require_once 'views/pos_view.php';
require_once 'views/items_view.php';
require_once 'views/buyer_view.php';
require_once 'views/report_view.php';
require_once 'views/stocks_view.php';

$page = $_GET['page'] ?? 'pos';
$action = $_POST['action'] ?? 'index';

$controllers = [
    'pos'    => 'POSController',
    'items'  => 'itemsController',
    'buyers' => 'buyerController',
    'history' => 'reportsController',
    'stocks' => 'stocksController',
];

$controllerName = $controllers[$page] ?? 'POSController';

require_once "controllers/{$controllerName}.php";

$app = new $controllerName();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $method = str_replace(['_item', '_buyer'], '', $action);
    
    if (method_exists($app, $method)) {
        $app->$method();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => "Method '{$method}' tidak ditemukan di {$controllerName}"]);
    }
    
} else {
    $app->index();
}
?>