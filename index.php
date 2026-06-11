<?php
session_start();
//nama sesuai dengan folder yang ditaro
define('BASE_URL', '/maccount');

// load env
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
require_once 'models/stockOpnameModel.php';
require_once 'models/stockAdjustmentModel.php';
require_once 'models/companyModel.php';
require_once 'models/usersModel.php';
require_once 'models/permissionModel.php';

// Load Semua Views
require_once 'views/changeLogin_view.php';
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
require_once 'views/stock_opname_view.php';
require_once 'views/stock_adjustment_view.php';
require_once 'views/stockIn_view.php';
require_once 'views/receive_view.php';
require_once 'views/salesPivot_view.php';
require_once 'views/company_view.php';
require_once 'views/user_view.php';
require_once 'views/login_view.php';
require_once 'views/permission_view.php';

// =======================================================
// PARSING ROUTING TANPA .HTACCESS
// =======================================================
$url_path = $_GET['page'] ?? 'dashboard';
$url_path = rtrim($url_path, '/');
$segments = explode('/', $url_path);

// Jaga-jaga jika kata 'maccount' tidak sengaja masuk ke dalam query string page
if (isset($segments[0]) && $segments[0] === 'maccount') array_shift($segments); 

// Tentukan Nama Page / Controller
$page = $segments[0] ?? 'dashboard';

// Ambil Action secara fleksibel:
// 1. Dari segment URL setelah slash (misal: ?page=items/create)
// 2. Dari parameter $_GET['action'] (misal: ?page=items&action=create)
// 3. Default ke 'index'
$action = $segments[1] ?? ($_GET['action'] ?? 'index');
// =======================================================

// Mapping Page ke Controller
$controllers = [
    'auth' => 'AuthController',
    'dashboard' => 'DashboardController',
    'pos' => 'POSController',
    'receive' => 'StockInController',
    'items' => 'ItemsController',
    'buyers' => 'BuyerController',
    'history' => 'ReportsController',
    'stockClosing' => 'StocksController',
    'stockClose' => 'StockCloseController',
    'stockOpname' => 'StockOpnameController',
    'stockAdjustment' => 'StockAdjustmentController',
    'sales' => 'SalesPivotController',
    'changeLogin' => 'ChangeLoginController',
    'company' => 'CompanyController',
    'users' => 'UsersController',
    'permission' => 'PermissionController'
];

$controllerName = $controllers[$page] ?? 'DashboardController';

if (file_exists("controllers/{$controllerName}.php")) {
    require_once "controllers/{$controllerName}.php";
    $app = new $controllerName();
} else {
    die("Error: Controller file 'controllers/{$controllerName}.php' tidak ditemukan.");
}

// Jalankan Method Berdasarkan Request Method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Di request POST, prioritaskan $_POST['action'], jika tidak ada gunakan $action dari URL
    $action = $_POST['action'] ?? $action;

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