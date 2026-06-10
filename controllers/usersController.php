<?php
require_once 'BaseController.php';

class UsersController extends BaseController {
    private $model;
    private $buyer;

    public function __construct() {
        parent::__construct();
        $this->model = new UsersModel();
        $this->buyer = new BuyerModel();
    }

    public function index() {
        UserView::render([]);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');		
		$paging = $this->getPaginationParams(10);
		$result = $this->model->getFilteredPaginated($search, $paging['limit'], $paging['offset']);
		$paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
		
        return $this->jsonSuccess(
            "Data Filtered", 
            $result['data'], 
            ['pagination' => $paginationMeta]
        );
    }

    public function add() {
        $data = $this->sanitize([
            'username' => $this->getPost('username'),
            'password' => $this->getPost('password'),
            'person_id' => $this->getPost('person_id'),
            'role_id' => $this->getPost('role_id')
        ]);

        if (empty($data['username']) || empty($data['password'])) {
            return $this->jsonError("Username dan Password Wajib Diisi.");
        }
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        $res = $this->model->insert('m_users', $data);
        return $res ? $this->jsonSuccess("User berhasil ditambah") : $this->jsonError("Gagal menambah User");
    }

    public function update() {
        $id = (int)$this->getPost('id');        
        if ($id <= 0) return $this->jsonError("ID User tidak valid.");

        $password = $this->getPost('password');

        $updateData = [
            'person_id' => $this->getPost('person_id'),
            'role_id'   => $this->getPost('role_id')
        ];
        if (!empty($password)) $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
        $updateData = $this->sanitize($updateData);

        $res = $this->model->update('m_users', $updateData, "id = $id");
        return $res ? $this->jsonSuccess("Data User diperbarui") : $this->jsonError("Gagal memperbarui data");
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

}