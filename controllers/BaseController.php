<?php
require_once './vendors/SimpleXLSXGen.php.php';

class BaseController {
    
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