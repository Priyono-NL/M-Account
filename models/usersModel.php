<?php
require_once '_dbHelper.php';

class UsersModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }
	
	private function buildFilterQuery($search) {
        $sql = "SELECT u.*, b.buyer_name, b.buyer_code, r.name AS rolename 
                FROM m_users u 
                LEFT JOIN buyer b ON u.person_id = b.id
                LEFT JOIN m_role r ON u.role_id = r.id
                WHERE u.is_active = 0";
        $params = [];

        if (!empty($search)) {
            // $sql .= " AND (username LIKE :search OR buyer_name LIKE :search)";
            $sql .= " AND (username LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    public function getFiltered($search = '') {
        $query = $this->buildFilterQuery($search);        
        $sql = $query['sql'] . " ORDER BY username ASC";         
        return $this->query_all($sql, $query['params']);
    }
	
	public function getFilteredPaginated($search = '', $limit = 10, $offset = 0) {
        $query = $this->buildFilterQuery($search);
        $sql = $query['sql'] . " ORDER BY id ASC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

    public function getByUsername($username) {
        $query = $this->buildFilterQuery($username);
        return $this->query_one($query['sql'], $query['params']);
    }

    public function checkExists($table, $column, $value) {
        $sql = "SELECT COUNT(*) as total FROM {$table} WHERE {$column} = :value";
        $params = ['value' => $value];

        $result = $this->query_one($sql, $params);
        return ($result && $result['total'] > 0);
    }

}
?>