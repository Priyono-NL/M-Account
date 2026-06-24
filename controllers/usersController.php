<?php
require_once 'BaseController.php';

class UsersController extends BaseController {
    private $model;
    private $buyer;
    private $permission;
    private $company;

    public function __construct() {
        parent::__construct();
        $this->model = new UsersModel();
        $this->buyer = new BuyerModel();
        $this->permission = new PermissionModel();
        $this->company = new CompanyModel();
    }

    public function index() {
        $roles = $this->permission->getAllRoles();
        $active_comp_id = BaseController::$active_comp_id;
        $my_companies = BaseController::$my_companies;
        $c_disabled  = BaseController::$c_disabled;
        $can_switch = isset($_SESSION['user']['can_switch']) ? $_SESSION['user']['can_switch'] : false;
        
        if ($can_switch) $active_comp_id = 'all';
        
        if (!$can_switch && !empty($my_companies)) $companies = $my_companies;
        else $companies = $this->company->getAllCompanies();
        
        UserView::render([
            'roles' => $roles,
            'companies' => $companies,
            'active_comp_id' => $active_comp_id,
            'can_switch' => $can_switch,
            'c_disabled' => $c_disabled
        ]);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');       
        $paging = $this->getPaginationParams(10);
        
        $local_company = $this->getPost('filter_company', 'all');
        $global_company = BaseController::$active_comp_id;
        $can_switch = isset($_SESSION['user']['can_switch']) ? $_SESSION['user']['can_switch'] : false;

        if ($can_switch) $final_company = $local_company;
        else $final_company = $global_company;
        
        $result = $this->model->getFilteredPaginated($search, $final_company, $paging['limit'], $paging['offset']);
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
        
        return $this->jsonSuccess(
            "Data Filtered", 
            $result['data'], 
            ['pagination' => $paginationMeta]
        );
    }

    public function get_person() {
        $keyword = $this->getPost('keyword', '');  
        $results = $this->buyer->getFiltered($keyword);
        $select2Data = [];
        foreach ($results as $row) {
            $select2Data[] = [
                'id'   => $row['id'],
                'text' => $row['buyer_code'] . " | ". $row['buyer_name']
            ];
        }
        return $this->jsonSuccess("Data person berhasil dimuat", $select2Data);
    }

    public function add() {
        $data = $this->sanitize([
            'username' => $this->getPost('username'),
            'password' => $this->getPost('password'),
            'person_id' => $this->getPost('person_id'),
            'role_id' => $this->getPost('role_id'),
            'company' => $this->getPost('company')
        ]);

        if (empty($data['username']) || empty($data['password'])) {
            return $this->jsonError("Username dan Password Wajib Diisi.");
        }

        $isDuplicate = $this->model->checkExists('m_users', 'username', $data['username']);
        if ($isDuplicate) return $this->jsonError("Gagal! Username '{$data['username']}' sudah terdaftar.");

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        $res = $this->model->insert('m_users', $data);
        return $res ? $this->jsonSuccess("User berhasil ditambah") : $this->jsonError("Gagal menambah User");
    }

    public function update() {
        $id = (int)$this->getPost('id');        
        if ($id <= 0) return $this->jsonError("ID User tidak valid.");

        $password = $this->getPost('password');

        $updateData = [
            'username' => $this->getPost('username'),
            'person_id' => $this->getPost('person_id'),
            'role_id' => $this->getPost('role_id'),
            'company' => $this->getPost('company')
        ];
        if (!empty($password)) $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
        $updateData = $this->sanitize($updateData);

        $res = $this->model->update('m_users', $updateData, "id = $id");
        return $res ? $this->jsonSuccess("Data User diperbarui") : $this->jsonError("Gagal memperbarui data");
    }

    public function delete() {
        $id = (int)$this->getPost('id');
        if ($id <= 0) return $this->jsonError("ID User tidak valid.");

        $res = $this->model->delete('m_users', "id = $id");
        return $res ? $this->jsonSuccess("User berhasil dihapus") : $this->jsonError("Gagal menghapus User");
    }

    // ==========================================
    // UBAH PASSWORD DARI NAVBAR (CURRENT LOGGED IN USER)
    // ==========================================
    public function change_password() {
        $old_password = $this->getPost('old_password');
        $new_password = $this->getPost('new_password');

        // Ambil ID user yang sedang login dari session
        $user_id = $_SESSION['user']['id'] ?? null;

        if (!$user_id) {
            return $this->jsonError("Sesi login tidak valid atau telah berakhir. Silakan login ulang.");
        }

        if (empty($old_password) || empty($new_password)) {
            return $this->jsonError("Password lama dan password baru wajib diisi.");
        }

        if (strlen($new_password) < 6) {
            return $this->jsonError("Password baru minimal 6 karakter.");
        }

        // Ambil data user dari database untuk memverifikasi password lamanya
        $user = $this->model->getById('m_users', $user_id);

        if (!$user) {
            return $this->jsonError("Data user tidak ditemukan di database.");
        }

        // Verifikasi kesesuaian password lama
        if (!password_verify($old_password, $user['password'])) {
            return $this->jsonError("Password lama yang Anda masukkan salah!");
        }

        // Enkripsi password baru
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update ke database
        $res = $this->model->update('m_users', ['password' => $hashed_password], "id = :id", ['id' => $user_id]);

        if ($res) {
            return $this->jsonSuccess("Password berhasil diubah!");
        } else {
            return $this->jsonError("Terjadi kesalahan sistem saat mengubah password.");
        }
    }
}