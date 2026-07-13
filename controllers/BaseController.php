<?php
require_once './vendors/SimpleXLSX/SimpleXLSX.php';
require_once './vendors/SimpleXLSX/SimpleXLSXGen.php';
require_once './models/companyModel.php';

class BaseController {

    protected $companyModel;

    public static $my_companies = [];
    public static $c_disabled = 'disabled';
    public static $active_comp_id = null;

    public function __construct() {
        $this->companyModel = new CompanyModel();

        if (in_array(get_class($this), ['AuthController', 'ApiController'])) {
            return;
        }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {            
            $local_login_url = 'index.php?page=auth'; 
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['status' => 'expired', 'redirect_url' => $local_login_url]);
                exit;
            }

            echo '<!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="refresh" content="0;url=' . $local_login_url . '">
                <title>Mengalihkan...</title>
                <script>
                    window.location.href = "' . $local_login_url . '";
                </script>
            </head>
            <body style="background-color: #f4f4f4; text-align: center; padding-top: 50px; font-family: sans-serif;">
                <p>Sesi telah berakhir atau Anda belum login. Mengalihkan ke halaman Login... Jika tidak dialihkan secara otomatis, <a href="' . $local_login_url . '">klik di sini</a>.</p>
            </body>
            </html>';
            
            exit;
        }

        // =======================================================================
        // PENGECEKAN 8: GLOBAL FUNCTION-LEVEL AUTHORIZATION GATEKEEPER
        // =======================================================================
        $currentPage = $_GET['page'] ?? 'dashboard';
        $currentPage = rtrim($currentPage, '/');
        $segments = explode('/', $currentPage);
        if (isset($segments[0]) && $segments[0] === 'maccount') {
            array_shift($segments); 
        }
        $pageKey   = $segments[0] ?? 'dashboard';
        $actionKey = $_GET['action'] ?? ($_POST['action'] ?? 'index');

        // Mapping sub-routing khusus halaman detail/history
        $targetKey = $pageKey;
        if ($pageKey === 'pos' && $actionKey === 'history') {
            $targetKey = 'sales_detail';
        } elseif ($pageKey === 'receive' && $actionKey === 'history') {
            $targetKey = 'receive_history';
        }

        $config_file = dirname(__DIR__) . '/config/config_module.php';
        if (file_exists($config_file)) {
            $config_source = require $config_file;
            $menu_items = $config_source['modules'] ?? [];
            
            $currentModule = null;
            foreach ($menu_items as $item) {
                if (isset($item['key']) && $item['key'] === $targetKey) {
                    $currentModule = $item;
                    break;
                }
            }

            if ($currentModule) {
                $rolename = strtolower($_SESSION['user']['rolename'] ?? '');
                $rule = $currentModule['rule'] ?? 'public';

                $unauthorized = false;
                
                // 1. Validasi Level Dasar Hak Akses Role Dasar
                if ($rule === 'superadmin' && $rolename !== 'superadmin') {
                    $unauthorized = true;
                }
                if ($rule === 'admin' && !in_array($rolename, ['admin', 'superadmin'])) {
                    $unauthorized = true;
                }

                // 2. Validasi Modul Berdasarkan Mapping Permission Path Sesi (Kecuali Superadmin)
                if (!$unauthorized && $rolename !== 'superadmin') {
                    $my_paths = $_SESSION['user']['paths'] ?? [];
                    
                    // Definisikan toleransi jalur induk-anak agar AJAX API filter_api tidak terblokir
                    $allowed_paths = ['/' . $targetKey];
                    if ($targetKey === 'pos') {
                        $allowed_paths[] = '/sales_detail';
                    } elseif ($targetKey === 'receive') {
                        $allowed_paths[] = '/receive_history';
                    }

                    $has_access = false;
                    foreach ($allowed_paths as $ap) {
                        if (in_array($ap, $my_paths)) {
                            $has_access = true;
                            break;
                        }
                    }

                    if (!$has_access) {
                        $unauthorized = true;
                    }
                }

                // Jika terbukti tidak memiliki otorisasi, langsung putus request ke server secara tegas!
                if ($unauthorized) {
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        http_response_code(403);
                        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Anda tidak memiliki izin otorisasi fungsi untuk modul ini.']);
                        exit;
                    }

                    http_response_code(403);
                    die("<!DOCTYPE html>
                    <html lang='id'>
                    <head>
                        <meta charset='UTF-8'>
                        <title>Akses Ditolak</title>
                    </head>
                    <body style='background-color: #f8d7da; color: #721c24; text-align: center; padding-top: 100px; font-family: sans-serif;'>
                        <div style='display: inline-block; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-top: 5px solid #dc3545;'>
                            <h2>⚠️ Akses Ditolak!</h2>
                            <p>Anda tidak memiliki hak otorisasi tingkat fungsi untuk membuka halaman atau modul ini secara langsung.</p>
                            <p><a href='index.php?page=dashboard' style='color: #0d6efd; text-decoration: none; font-weight: bold;'>Kembali ke Dashboard</a></p>
                        </div>
                    </body>
                    </html>");
                }
            }
        }
        // =======================================================================

        self::$my_companies  = $this->companyModel->getAllCompanies();
        if ($_SESSION['user']['can_switch']) self::$c_disabled = null;

        if (!isset($_SESSION['user']['active_company_id']) && count(self::$my_companies) > 0) {
            $_SESSION['user']['active_company_id'] = self::$my_companies[0]['id'];
        }

        self::$active_comp_id = $_SESSION['user']['active_company_id'] ?? 1;
    }
    
    protected function jsonSuccess($message = "Success", $data = [], $extra = []) {
        header('Content-Type: application/json');
        $response = [
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ];

        if (!empty($extra) && is_array($extra)) {
            $response = array_merge($response, $extra);
        }
        echo json_encode($response);
        exit;
    }

    protected function jsonError($message = "Error", $code = 400) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            'status'  => 'error',
            'message' => $message
        ]);
        exit;
    }

    protected function getPost($key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    protected function sanitize(array $data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }
	
	protected function getPaginationParams($defaultLimit = 25) {
        $page = (int) $this->getPost('page', 1);
        $limit = (int) $this->getPost('limit', $defaultLimit);
        
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = $defaultLimit;
        
        $offset = ($page - 1) * $limit;

        return [
            'page'   => $page,
            'limit'  => $limit,
            'offset' => $offset
        ];
    }
	
	protected function buildPaginationMeta($totalRecords, $page, $limit) {
        return [
            'total'      => (int) $totalRecords,
            'totalPages' => ceil($totalRecords / $limit),
            'page'       => (int) $page,
            'limit'      => (int) $limit
        ];
    }

    protected function getWarehouseContext() {
        $companyId = $_SESSION['user']['active_company_id'] ?? null;
        $warehouses = $companyId ? $this->companyModel->getWarehousesByCompanyId($companyId) : [];
        
        $defaultWarehouseId = !empty($warehouses) ? $warehouses[0]['id'] : '';
        $sso_warehouse = $_SESSION['user']['extra_config']['warehouse'] ?? null;
        
        return [
            'warehouses'        => $warehouses,
            'current_warehouse' => $sso_warehouse ?? ($_GET['warehouse'] ?? $defaultWarehouseId),
            'is_locked'         => ($sso_warehouse !== null)
        ];
    }

}