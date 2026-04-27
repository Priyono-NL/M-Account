<?php
require_once 'BaseController.php';
require_once './vendors/php-jwt/autoloads.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends BaseController {

    public function __construct() {
        
    }

    public function callback() {
        $token = $_GET['access_token'] ?? null;

        if (!$token) {
            die("Token tidak ditemukan.");
        }

        $app_id     = "m-account_def9b732";
        $app_secret = "iZkTfM8L0llxTV3aowAX5Y51MqrA4ovBJ7QEAVLFn8c";
        $sso_verify_url = "http://localhost:5005/api/verify";

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
            $_SESSION['user_id']   = $result['user']['id'];
            $_SESSION['user_name'] = $result['user']['full_name'];
            $_SESSION['role']      = $result['user']['role'];

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