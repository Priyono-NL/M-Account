<?php
require_once 'BaseController.php';

class AuthController extends BaseController {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UsersModel(); 
    }

    /**
     * Menampilkan Halaman Login
     */
    public function index() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header("Location: index.php?page=dashboard");
            exit;
        }

        LoginView::render();
    }

    /**
     * Proses Validasi Login via AJAX
     */
    public function process_login() {
        $username = trim($this->getPost('username', ''));
        $password = trim($this->getPost('password', ''));

        // Validasi input kosong
        if (empty($username) || empty($password)) return $this->jsonError("Username dan password wajib diisi.");

        $user = $this->userModel->getByUsername($username);

        if (!$user) return $this->jsonError("Username tidak ditemukan.");

        if ($user['is_active'] == 1) return $this->jsonError("Akun Anda dinonaktifkan. Silakan hubungi Administrator.");

        if (password_verify($password, $user['password'])) {

            $sql_perm = "SELECT permission FROM m_permission WHERE role_id = :role_id LIMIT 1";
            $perm_data = $this->userModel->query_one($sql_perm, ['role_id' => $user['role_id']]);
            
            $my_paths = [];
            if ($perm_data && !empty($perm_data['permission'])) {
                $my_paths = json_decode($perm_data['permission'], true);
                if (!is_array($my_paths)) $my_paths = [];
            }

            $is_assigned = isset($user['company']) && is_numeric($user['company']);
            
            $_SESSION['logged_in'] = true;
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'rolename' => $user['rolename'],
                'person_name' => $user['buyer_name'],
                'active_company_id' => $is_assigned ? (int)$user['company'] : 1,
                'can_switch' => !$is_assigned,
                'paths' => $my_paths
            ];

            return $this->jsonSuccess("Login berhasil! Mengalihkan...");
        } else {
            return $this->jsonError("Password yang Anda masukkan salah.");
        }
    }

    /**
     * Proses Logout
     */
    public function logout() {
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit;
    }

    /**
     * Mengecek apakah session masih aktif (Bisa dipanggil berkala via AJAX oleh frontend)
     */
    public function checkSession() {
        header('Content-Type: application/json');
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) echo json_encode(['status' => 'active']);
        else echo json_encode(['status' => 'expired', 'redirect_url' => 'index.php?page=login']);
        exit;
    }

    /**
     * Berhenti dari mode Impersonate (kembali ke akun Superadmin)
     */
    public function stopImpersonate() {
        if (!isset($_SESSION['impersonator_user'])) return $this->jsonError("Anda tidak sedang berada dalam mode impersonate.");

        $_SESSION['logged_in'] = true;
        $_SESSION['user']      = $_SESSION['impersonator_user'];

        unset($_SESSION['impersonator_user']);

        if (isset($_SESSION['user']['is_impersonating'])) unset($_SESSION['user']['is_impersonating']);

        $adminName = $_SESSION['user']['username'] ?? 'Superadmin';
        return $this->jsonSuccess("Berhasil kembali ke akun utama ({$adminName})");
    }
}
?>