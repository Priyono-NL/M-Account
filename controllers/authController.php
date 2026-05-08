<?php
require_once 'BaseController.php';
require_once './vendors/php-jwt/autoloads.php';

class AuthController extends BaseController {

    public function __construct() { }

    public function callback() {
        $token = $_GET['access_token'] ?? null;

        if (!$token) {
            die("Token tidak ditemukan.");
        }

        $app_id     = getenv('APP_ID');
        $app_secret = getenv('APP_SECRET');
        $sso_verify_url = getenv('SSO_VERIFY_URL');

        $data = json_encode(['access_token' => $token]);

        $ch = curl_init($sso_verify_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-App-ID: ' . $app_id,
            'X-App-Secret: ' . $app_secret
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['valid']) && $result['valid'] === true) {            
            $_SESSION['logged_in'] = true;
            $_SESSION['user'] = $result['user'];
            $_SESSION['token'] = $token;
            $_SESSION['expires_at'] = $result['token_info']['expires_at'];
            header("Location: /m-account/dashboard");
            exit;
        } else {
            $error_msg = $result['error'] ?? 'Verifikasi Gagal';
            die("SSO Error: " . $error_msg);
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit;
    }
}