<?php
require_once '_dbHelper.php';

class BuyerModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }
	
	private function buildFilterQuery($search) {
        $sql = "SELECT * FROM buyer WHERE is_active = 0";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (buyer_code LIKE :search OR buyer_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    public function getFiltered($search = '') {
        $query = $this->buildFilterQuery($search);        
        $sql = $query['sql'] . " ORDER BY buyer_name ASC";         
        return $this->query_all($sql, $query['params']);
    }
	
	public function getFilteredPaginated($search = '', $limit = 10, $offset = 0) {
        $query = $this->buildFilterQuery($search);
        $sql = $query['sql'] . " ORDER BY id ASC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

}
?>