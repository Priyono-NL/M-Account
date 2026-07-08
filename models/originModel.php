<?php
require_once '_dbHelper.php';

class OriginModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }
    
    private function buildFilterQuery($search) {
        $sql = "SELECT * FROM origin_code WHERE is_active = 0";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (origin_code LIKE :search OR origin_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    public function getFilteredPaginated($search = '', $limit = 10, $offset = 0) {
        $query = $this->buildFilterQuery($search);
        $sql = $query['sql'] . " ORDER BY origin_code ASC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

}
?>