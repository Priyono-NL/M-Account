<?php
require_once '_dbHelper.php';

class ReportModel extends DatabaseHelper {
    public function __construct() {
        parent::__construct();
    }
	
	private function buildFilterQuery($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $sql = "SELECT t.*, i.item_name, i.item_code 
                FROM item_transactions t
                LEFT JOIN items i ON t.item_id = i.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (i.item_code LIKE :search OR i.item_name LIKE :search OR t.reference_no LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($warehouse !== '') {
            $sql .= " AND t.warehouse = :warehouse";
            $params['warehouse'] = $warehouse;
        }

        if (!empty($startDate)) {
            $sql .= " AND t.transaction_date >= :start_date";
            $params['start_date'] = $startDate . " 00:00:00";
        }
        if (!empty($endDate)) {
            $sql .= " AND t.transaction_date <= :end_date";
            $params['end_date'] = $endDate . " 23:59:59";
        }

        return [
            'sql'    => $sql,
            'params' => $params
        ];
    }

    public function getFiltered($search = '', $warehouse = '', $startDate = '', $endDate = '') {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate);
        $sql = $query['sql'] . " ORDER BY t.transaction_date ASC";
        return $this->query_all($sql, $query['params']);
    }
	
	public function getFilteredPaginated($search = '', $warehouse = '', $startDate = '', $endDate = '', $limit = 25, $offset = 0) {
        $query = $this->buildFilterQuery($search, $warehouse, $startDate, $endDate);
        $sql = $query['sql'] . " ORDER BY t.transaction_date DESC";
        return $this->query_paginated($sql, $query['params'], $limit, $offset);
    }
}
?>