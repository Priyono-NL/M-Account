<?php
require_once '_dbHelper.php';

class ItemsModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function getFiltered($search = '', $category = '') {
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

        return $this->query_all($sql, $params);
    }

}
?>