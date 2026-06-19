<?php
require_once '_dbHelper.php';

class UsersModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }
    
    private function buildFilterQuery($search, $active_company = 'all') {
        $sql = "SELECT u.*,
                       b.buyer_name, b.buyer_code, 
                       r.name AS rolename,
                       CASE 
                           WHEN u.company = 'all' THEN 'All Company'
                           ELSE IFNULL(c.company_name, '-') 
                       END AS company_name
                FROM m_users u 
                LEFT JOIN buyer b ON u.person_id = b.id
                LEFT JOIN m_role r ON u.role_id = r.id
                LEFT JOIN company c ON u.company = c.id
                WHERE u.is_active = 0";
        $params = [];

        if ($active_company !== 'all') {
            $sql .= " AND (u.company = :active_company)";
            $params['active_company'] = $active_company;
        }
        
        if (!empty($search)) {
            $sql .= " AND (u.username LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    public function getFiltered($search = '', $active_company = 'all') {
        $query = $this->buildFilterQuery($search, $active_company);        
        $sql = $query['sql'] . " ORDER BY username ASC";         
        return $this->query_all($sql, $query['params']);
    }
    
    public function getFilteredPaginated($search = '', $active_company = 'all', $limit = 10, $offset = 0) {
        $query = $this->buildFilterQuery($search, $active_company);
        $sql = $query['sql'] . " ORDER BY u.id ASC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }

    public function getByUsername($username) {
        $sql = "SELECT u.*, r.name AS rolename 
                FROM m_users u
                LEFT JOIN m_role r ON u.role_id = r.id 
                WHERE u.username = :username AND u.is_active = 0";
        return $this->query_one($sql, ['username' => $username]);
    }

    public function checkExists($table, $column, $value) {
        $sql = "SELECT COUNT(*) as total FROM {$table} WHERE {$column} = :value";
        $params = ['value' => $value];

        $result = $this->query_one($sql, $params);
        return ($result && $result['total'] > 0);
    }
}
?>