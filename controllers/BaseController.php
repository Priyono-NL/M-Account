<?php
require_once './vendors/SimpleXLSX/SimpleXLSX.php';
require_once './vendors/SimpleXLSX/SimpleXLSXGen.php';

class BaseController {

    public function __construct() {
        if (get_class($this) === 'AuthController') {
            return; 
        }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $sso_login_url = getenv('SSO_LOGIN_URL');
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['status' => 'expired', 'redirect_url' => $sso_login_url]);
                exit;
            }

            echo '<!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="refresh" content="0;url=' . $sso_login_url . '">
                <title>Mengalihkan...</title>
                <script>
                    window.location.href = "' . $sso_login_url . '";
                </script>
            </head>
            <body style="background-color: #f4f4f4; text-align: center; padding-top: 50px; font-family: sans-serif;">
                <p>Mengalihkan ke halaman SSO... Jika tidak dialihkan secara otomatis, <a href="' . $sso_login_url . '">klik di sini</a>.</p>
            </body>
            </html>';
            
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
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }

}