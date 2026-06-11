<?php
require_once './vendors/SimpleXLSX/SimpleXLSX.php';
require_once './vendors/SimpleXLSX/SimpleXLSXGen.php';
require_once './models/companyModel.php';

class BaseController {

    protected $companyModel;

    public static $my_companies = [];
    public static $company_count = 0;
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

        $companyModel = new CompanyModel();
        self::$my_companies  = $companyModel->getAllCompanies();
        self::$company_count = count(self::$my_companies);

        if (!isset($_SESSION['user']['active_company_id']) && self::$company_count > 0) {
            $_SESSION['user']['active_company_id'] = self::$my_companies[0]['id'];
        }

        self::$active_comp_id = $_SESSION['user']['active_company_id'] ?? null;
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

    /** Helper untuk mengambil data context warehouse yang seragam di semua controller **/
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