<?php
require_once '_dbHelper.php';

class BuyerModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function getFiltered($search = '') {
        $sql = "SELECT * FROM buyer WHERE is_active = 0";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (buyer_code LIKE :search OR buyer_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY buyer_name ASC"; // Tambahan opsional: Urutkan alfabetis agar front-end rapi

        return $this->query_all($sql, $params);
    }

}
?>