<?php
require_once '_dbHelper.php';

class PermissionModel extends DatabaseHelper {
    
    public function __construct() {
        parent::__construct();
    }

    public function getAllRoles() {
        return $this->query_all("SELECT id, name FROM m_role WHERE is_active = 0 ORDER BY id ASC");
    }

    public function getPermissionByRole($role_id) {
        $sql = "SELECT permission FROM m_permission WHERE role_id = :role_id LIMIT 1";
        return $this->query_one($sql, ['role_id' => $role_id]);
    }

    public function checkPermissionExists($role_id) {
        $sql = "SELECT id FROM m_permission WHERE role_id = :role_id LIMIT 1";
        $result = $this->query_one($sql, ['role_id' => $role_id]);
        return ($result !== false && !empty($result));
    }

    public function savePermission($role_id, $json_paths) {
        $now = date('Y-m-d H:i:s');
        $exists = $this->checkPermissionExists($role_id);

        if ($exists) {
            // Pengecekan 7: Mengubah role_id menjadi placeholder aman :role_id
            return $this->update('m_permission', [
                'permission' => $json_paths,
                'updated_at' => $now
            ], "role_id = :role_id", ['role_id' => $role_id]);
        } else {
            // Insert
            return $this->insert('m_permission', [
                'role_id'    => $role_id,
                'permission' => $json_paths,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
    }
}
?>