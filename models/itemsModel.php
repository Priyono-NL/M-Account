<?php
require_once '_dbHelper.php';

class ItemsModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }
	
	private function buildFilterQuery($search, $category) {
        $sql = "SELECT * FROM items WHERE is_active = 0";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (item_code LIKE :search OR item_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($category !== '') {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }

        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    public function getFiltered($search = '', $category = '') {
        $query = $this->buildFilterQuery($search, $category);        
        $sql = $query['sql'] . " ORDER BY item_name ASC";         
        return $this->query_all($sql, $query['params']);
    }
	
	public function getFilteredPaginated($search = '', $category = '', $limit = 10, $offset = 0) {
        $query = $this->buildFilterQuery($search, $category);
        $sql = $query['sql'] . " ORDER BY id ASC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

}
?>