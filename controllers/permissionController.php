<?php
require_once 'BaseController.php';
require_once 'models/PermissionModel.php';

class PermissionController extends BaseController {
    private $permissionModel;

    public function __construct() {
        parent::__construct();
        $this->permissionModel = new PermissionModel(); 

        $rolename = $_SESSION['user']['rolename'];
        if ($rolename != "superadmin") { 
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                return $this->jsonError("Akses ditolak.", 403);
            } else {
                die("Akses Ditolak: Khusus Superadmin.");
            }
        }
    }

    public function index() {
        $roles = $this->permissionModel->getAllRoles();
        $config_source = require dirname(__DIR__) . '/config/config_module.php';
        $modules = $config_source['modules'] ?? [];

        PermissionView::render([
            'roles' => $roles,
            'modules' => $modules
        ]);
    }

    public function get_role_permission() {
        $role_id = (int)$this->getPost('role_id');
        if ($role_id <= 0) return $this->jsonError("Role tidak valid.");

        $data = $this->permissionModel->getPermissionByRole($role_id);

        $paths = [];
        if ($data && !empty($data['permission'])) {
            $paths = json_decode($data['permission'], true);
            if (!is_array($paths)) $paths = [];
        }

        return $this->jsonSuccess("Data dimuat", ['paths' => $paths]);
    }

    public function save() {
        $role_id = (int)$this->getPost('role_id');
        $paths   = $_POST['paths'] ?? []; 

        if ($role_id <= 0) return $this->jsonError("Pilih Role terlebih dahulu.");

        $json_paths = json_encode($paths);
        
        $res = $this->permissionModel->savePermission($role_id, $json_paths);

        if ($res) {
            return $this->jsonSuccess("Hak akses berhasil diperbarui!");
        } else {
            return $this->jsonError("Gagal memperbarui hak akses.");
        }
    }
}
?>