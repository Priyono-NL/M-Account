<?php
require_once '_dbHelper.php';

class CompanyModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }
    
    private function buildFilterQuery($search) {
        $sql = "SELECT * FROM company WHERE is_active = 0";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (company_name LIKE :search OR company_short LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    public function getFilteredPaginated($search = '', $limit = 10, $offset = 0) {
        $query = $this->buildFilterQuery($search);
        $sql = $query['sql'] . " ORDER BY id DESC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

    // Fungsi untuk mengambil daftar gudang milik sebuah company
    public function getWarehousesByCompanyId($company_id) {
        $sql = "SELECT * FROM warehouse WHERE company_id = :cid AND is_active = 0";
        return $this->query_all($sql, ['cid' => $company_id]);
    }

    public function getAllCompanies() {
        $sql = "SELECT id, company_name AS name FROM company WHERE is_active = 0 ORDER BY company_name ASC";
        return $this->query_all($sql);
    }
}
?>