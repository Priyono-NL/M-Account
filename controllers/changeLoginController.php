<?php
require_once 'BaseController.php';

class ChangeLoginController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new DatabaseHelper(); 
        
        parent::__construct();

        $current_role = $_SESSION['user']['role_name'] ?? '';
        if ($current_role !== 'superadmin') {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['status' => 'error', 'message' => 'Akses ilegal. Anda bukan Superadmin.']);
                exit;
            } else {
                die("<div style='color:red; font-family:sans-serif; padding:20px;'>Akses Ditolak. Halaman ini hanya untuk Superadmin.</div>");
            }
        }
    }

    public function index() {
        ChangeLoginView::render();
    }

    public function get_all_users() {
        $sql = "SELECT u.id, u.username, r.name AS role, u.is_active FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.role_id ASC, u.username ASC";
        $users = $this->model->query_all($sql);

        if ($users !== false) {
            return $this->jsonSuccess("Data user berhasil dimuat", $users);
        } else {
            return $this->jsonError("Gagal mengambil data dari database.");
        }
    }

    public function switchAccount() {
        $target_user_id = $this->getPost('target_user_id');

        if (empty($target_user_id)) {
            return $this->jsonError("Pilih pengguna terlebih dahulu.");
        }

        $app_id     = getenv('APP_ID');
        $app_secret = getenv('APP_SECRET');
        $sso_impersonate_url = getenv('SSO_BASE_URL') . 'impersonate'; 

        $data = json_encode(['target_user_id' => $target_user_id]);

        $ch = curl_init($sso_impersonate_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-App-ID: ' . $app_id,
            'X-App-Secret: ' . $app_secret
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['access_token'])) {
            if (!isset($_SESSION['impersonator_user'])) {
                $_SESSION['impersonator_user']  = $_SESSION['user'];
                $_SESSION['impersonator_token'] = $_SESSION['token'];
                $_SESSION['impersonator_expires'] = $_SESSION['expires_at'];
            }

            $_SESSION['logged_in'] = true;
            $_SESSION['user']      = $result['user']; 
            $_SESSION['user']['is_impersonating'] = true; 
            $_SESSION['token']      = $result['access_token'];
            $_SESSION['expires_at'] = $result['expires_at']; 

            return $this->jsonSuccess("Berhasil login sebagai " . ($result['user']['full_name'] ?? $result['user']['username']));

        } else {
            $error_msg = $result['error'] ?? 'Gagal menghubungi server SSO';
            return $this->jsonError("SSO Error: " . $error_msg);
        }
    }

}
?>