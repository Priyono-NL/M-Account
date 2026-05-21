<?php
require_once './vendors/SimpleXLSX/SimpleXLSX.php';
require_once './vendors/SimpleXLSX/SimpleXLSXGen.php';

class BaseController {

    public function __construct() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $sso_login_url = getenv('SSO_LOGIN_URL');
            header("Location: " . $sso_login_url);
            exit;
        }
    }
    
    protected function jsonSuccess($message = "Success", $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ]);
        exit;
    }

    protected function jsonError($message = "Error", $code = 400) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            'status'  => 'error',
            'message' => $message
        ]);
        exit;
    }

    protected function getPost($key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    protected function sanitize(array $data) {
        return array_map(function($value) {
            return htmlspecialchars(trim($value));
        }, $data);
    }

}