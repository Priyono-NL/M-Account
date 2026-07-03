<?php
if (!ob_start("ob_gzhandler")) {
    ob_start();
}

session_start();
//nama sesuai dengan folder yang ditaro
define('BASE_URL', '/maccount');

// load env + dbHelper (Wajib di-load di awal karena dipakai global)
require_once 'env_loader.php';
require_once 'models/_dbHelper.php';

// =======================================================
// 1. SMART SPL AUTOLOADER (MENGGANTIKAN 34 REQUIRE_ONCE)
// =======================================================
spl_autoload_register(function ($class_name) {
    
    // A. Otomatis Load Controllers (Contoh: DashboardController -> controllers/dashboardController.php)
    if (str_contains($class_name, 'Controller')) {
        $files = [
            "controllers/" . lcfirst($class_name) . ".php",
            "controllers/{$class_name}.php"
        ];
        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }

    // B. Otomatis Load Models (Contoh: ItemsModel atau dashboardModel)
    if (str_contains($class_name, 'Model')) {
        $files = [
            "models/" . lcfirst($class_name) . ".php",
            "models/{$class_name}.php"
        ];
        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }

    // C. Otomatis Load Views (Contoh: DashboardView -> views/dashboard_view.php)
    if (str_contains($class_name, 'View')) {
        $formatted_view = lcfirst(str_replace('View', '_view', $class_name));
        $files = [
            "views/{$formatted_view}.php",
            "views/{$class_name}.php"
        ];
        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }

    // D. Otomatis Load Partials/Component (Contoh: Component -> views/partials/component.php)
    if ($class_name === 'Component' || str_contains($class_name, 'Helper')) {
        $files = [
            "views/partials/" . lcfirst($class_name) . ".php", // views/partials/component.php
            "views/partials/{$class_name}.php"                 // views/partials/Component.php
        ];
        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// =======================================================
// 2. PARSING ROUTING TANPA .HTACCESS
// =======================================================
$url_path = $_GET['page'] ?? 'dashboard';
$url_path = rtrim($url_path, '/');
$segments = explode('/', $url_path);

if (isset($segments[0]) && $segments[0] === 'maccount') array_shift($segments); 

$page = $segments[0] ?? 'dashboard';
$action = $segments[1] ?? ($_GET['action'] ?? 'index');

// =======================================================
// 3. MAPPING PAGE KE CONTROLLER & FIX LINUX CASE SENSITIVE
// =======================================================
$controllers = [
    'auth' => 'AuthController',
    'dashboard' => 'DashboardController',
    'pos' => 'POSController',
    'receive' => 'StockInController',
    'items' => 'ItemsController',
    'buyers' => 'BuyerController',
    'history' => 'ReportsController',
    'stocks' => 'StocksController',
    'stockClosing' => 'StockCloseController',
    'sales' => 'SalesPivotController',
    'changeLogin' => 'ChangeLoginController',
    'company' => 'CompanyController',
    'users' => 'UsersController',
    'permission' => 'PermissionController'
];

$controllerName = $controllers[$page] ?? 'DashboardController';

// Cek keberadaan file secara fleksibel (mengantisipasi error Case-Sensitive di Linux Server)
$controllerFile = "controllers/{$controllerName}.php";
if (!file_exists($controllerFile)) {
    $controllerFile = "controllers/" . lcfirst($controllerName) . ".php";
}

if (file_exists($controllerFile)) {
    // Saat di-instansiasi, fungsi Autoloader di atas akan otomatis me-require filenya
    $app = new $controllerName();
} else {
    die("Error: Controller file 'controllers/{$controllerName}.php' tidak ditemukan.");
}

// =======================================================
// 4. JALANKAN METHOD BERDASARKAN REQUEST METHOD
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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