<?php
require_once 'BaseController.php';
require_once 'models/companyModel.php';

class CompanyController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new CompanyModel();
    }

    public function index() {
         CompanyView::render([]);
    }

    public function filter_api() {
        $search = $this->getPost('search', '');
        
        $paging = $this->getPaginationParams(10);
        $result = $this->model->getFilteredPaginated($search, $paging['limit'], $paging['offset']);
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
        
        foreach ($result['data'] as &$row) {
            $row['warehouses'] = $this->model->getWarehousesByCompanyId($row['id']);
        }
        
        return $this->jsonSuccess(
            "Data Filtered", 
            $result['data'], 
            ['pagination' => $paginationMeta]
        );
    }

    public function add() {
        $company_name  = $this->getPost('company_name');
        $company_short = $this->getPost('company_short');
        $warehouses    = isset($_POST['warehouses']) ? $_POST['warehouses'] : [];

        if (empty($company_name) || empty($company_short)) {
            return $this->jsonError("Nama Company dan Singkatan wajib diisi.");
        }

        try {
            $this->model->beginTransaction();

            $dataCompany = $this->sanitize([
                'company_name'  => $company_name,
                'company_short' => $company_short
            ]);

            $company_id = $this->model->insert('company', $dataCompany);

            if ($company_id && is_array($warehouses)) {
                foreach ($warehouses as $wh_name) {
                    if (!empty(trim($wh_name))) {
                        $this->model->insert('warehouse', [
                            'company_id'     => $company_id,
                            'warehouse_name' => trim($wh_name)
                        ]);
                    }
                }
            }

            $this->model->commit();
            return $this->jsonSuccess("Company dan Gudang berhasil ditambah");

        } catch (Exception $e) {
            $this->model->rollBack();
            return $this->jsonError("Gagal menambah data: " . $e->getMessage());
        }
    }

    public function update() {
        $id = (int)$this->getPost('id');
        if ($id <= 0) return $this->jsonError("ID Company tidak valid.");

        $company_name = $this->getPost('company_name');
        $company_short = $this->getPost('company_short');
        $warehouse_ids = isset($_POST['warehouse_ids']) ? $_POST['warehouse_ids'] : [];
        $warehouse_names = isset($_POST['warehouses']) ? $_POST['warehouses'] : [];

        try {
            $this->model->beginTransaction();

            $dataCompany = $this->sanitize([
                'company_name' => $company_name,
                'company_short' => $company_short,
            ]);

            $this->model->update('company', $dataCompany, "id = $id");

            $processed_wh_ids = [];

            // Looping data gudang yang dikirim dari form
            foreach ($warehouse_names as $index => $wh_name) {
                $wh_name = trim($wh_name);
                if (empty($wh_name)) continue;

                $wh_id = isset($warehouse_ids[$index]) ? (int)$warehouse_ids[$index] : 0;
                if ($wh_id > 0) {
                    $this->model->update('warehouse', [
                        'warehouse_name' => $wh_name,
                        'is_active'         => 0 
                    ], "id = $wh_id");
                    
                    $processed_wh_ids[] = $wh_id;
                } else {
                    $new_wh_id = $this->model->insert('warehouse', [
                        'company_id'     => $id,
                        'warehouse_name' => $wh_name,
                        'is_active'         => 0
                    ]);
                    if ($new_wh_id) {
                        $processed_wh_ids[] = $new_wh_id;
                    }
                }
            }

            // Bersihkan Gudang yang Dihapus oleh User
            $current_db_warehouses = $this->model->getWarehousesByCompanyId($id);            
            foreach ($current_db_warehouses as $db_wh) {
                if (!in_array($db_wh['id'], $processed_wh_ids)) {
                    $this->model->delete('warehouse', "id = {$db_wh['id']}");
                }
            }

            $this->model->commit();
            return $this->jsonSuccess("Data Company dan Gudang diperbarui");

        } catch (Exception $e) {
            $this->model->rollBack();
            return $this->jsonError("Gagal memperbarui data: " . $e->getMessage());
        }
    }

    public function delete() {
        $id = (int)$this->getPost('id');
        if ($id <= 0) return $this->jsonError("ID Company tidak valid.");

        try {
            $this->model->beginTransaction();
            
            $this->model->delete('company', "id = $id");
            $this->model->delete('warehouse', "company_id = $id");

            $this->model->commit();
            return $this->jsonSuccess("Company berhasil dihapus");
        } catch (Exception $e) {
            $this->model->rollBack();
            return $this->jsonError("Gagal menghapus company.");
        }
    }
}
?>