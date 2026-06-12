<?php
require_once 'BaseController.php';

class ChangeLoginController extends BaseController {
    private $userModel;

    public function __construct() {
        parent::__construct();

        $this->userModel = new UsersModel(); 

        $current_role_id = $_SESSION['user']['rolename'];
        
        if ($current_role_id != "superadmin") {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                return $this->jsonError("Akses ilegal. Anda bukan Superadmin.", 403);
            } else {
                http_response_code(403);
                die("<div style='color:red; font-family:sans-serif; padding:20px; text-align:center;'>
                        <h3>Akses Ditolak</h3>
                        <p>Halaman ini hanya dapat diakses oleh akun dengan level Superadmin.</p>
                     </div>");
            }
        }
    }

    public function index() {
        ChangeLoginView::render();
    }

    public function get_all_users() {
        $sql = "SELECT u.id, u.username, r.name AS role, u.is_active 
                FROM m_users u 
                LEFT JOIN m_role r ON u.role_id = r.id 
                WHERE u.is_active = 0
                ORDER BY u.role_id ASC, u.username ASC";                
        $users = $this->userModel->query_all($sql);

        if ($users !== false) return $this->jsonSuccess("Data user berhasil dimuat", $users);
        else return $this->jsonError("Gagal mengambil data dari database.");
    }

    public function switchAccount() {
        $target_user_id = (int)$this->getPost('target_user_id', 0);
        if ($target_user_id <= 0) return $this->jsonError("Pilih pengguna terlebih dahulu.");

        if (isset($_SESSION['user']['is_impersonating']) && $_SESSION['user']['is_impersonating'] === true) {
            return $this->jsonError("Anda sedang dalam mode impersonate. Silahkan kembali ke akun utama terlebih dahulu.");
        }

        $sql = "SELECT u.*, r.name as rolename, b.buyer_name
                FROM m_users u 
                LEFT JOIN m_role r ON u.role_id = r.id 
                LEFT JOIN buyer b ON u.person_id = b.id 
                WHERE u.id = :id LIMIT 1";
        $target_user = $this->userModel->query_one($sql, ['id' => $target_user_id]);

        if (!$target_user) return $this->jsonError("Gagal! User target tidak ditemukan di database.");

        if ($target_user['is_active'] == 1) return $this->jsonError("Tidak dapat login. Akun target sedang dinonaktifkan.");

        $sql_perm = "SELECT permission FROM m_permission WHERE role_id = :role_id LIMIT 1";
        $perm_data = $this->userModel->query_one($sql_perm, ['role_id' => $target_user['role_id']]);
        
        $my_paths = [];
        if ($perm_data && !empty($perm_data['permission'])) {
            $my_paths = json_decode($perm_data['permission'], true);
            if (!is_array($my_paths)) $my_paths = [];
        }

        if (!isset($_SESSION['impersonator_user'])) $_SESSION['impersonator_user'] = $_SESSION['user'];

        $is_assigned = isset($target_user['company']) && is_numeric($target_user['company']);

        $_SESSION['user'] = [
            'id' => $target_user['id'],
            'username' => $target_user['username'],
            'rolename' => $target_user['rolename'],
            'person_name' => $target_user['buyer_name'],
            'active_company_id' => $is_assigned ? (int)$target_user['company'] : 1,
            'can_switch' => !$is_assigned,
            'paths' => $my_paths,
            'is_impersonating' => true
        ];

        return $this->jsonSuccess("Berhasil login sebagai " . $target_user['username'], ['redirect' => 'index.php?page=dashboard']);
    }

}
?>