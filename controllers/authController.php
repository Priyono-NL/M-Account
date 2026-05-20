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
        $sso_verify_url = getenv('SSO_BASE_URL'). 'verify';

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

    public function checkSession() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['token'])) {
            echo json_encode(['status' => 'expired', 'redirect_url' => getenv('SSO_LOGIN_URL') ]);
            exit;
        }

        $app_id     = getenv('APP_ID');
        $app_secret = getenv('APP_SECRET');        
        $sso_api_url = getenv('SSO_BASE_URL') . "validate-token";

        $sso_token = $_SESSION['token'];
        $payload = json_encode([ 'access_token' => $sso_token ]);

        $ch = curl_init($sso_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-App-ID: ' . $app_id,
            'X-App-Secret: '. $app_secret
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $data = json_decode($response, true);
            if (isset($data['valid']) && $data['valid'] === true) {
                echo json_encode(['status' => 'active']);
                exit;
            }
        }

        session_unset();
        session_destroy();
        echo json_encode([ 'status' => 'expired', 'redirect_url' => getenv('SSO_LOGIN_URL') ]);
        exit;
    }

    public function stopImpersonate() {
        if (!isset($_SESSION['impersonator_user'])) return $this->jsonError("Anda tidak sedang berada dalam mode impersonate.");

        $_SESSION['logged_in']  = true;
        $_SESSION['user']       = $_SESSION['impersonator_user'];
        $_SESSION['token']      = $_SESSION['impersonator_token'];
        $_SESSION['expires_at'] = $_SESSION['impersonator_expires'];

        unset($_SESSION['impersonator_user']);
        unset($_SESSION['impersonator_token']);
        unset($_SESSION['impersonator_expires']);

        if (isset($_SESSION['user']['is_impersonating'])) unset($_SESSION['user']['is_impersonating']);

        return $this->jsonSuccess("Berhasil kembali ke akun utama (" . $_SESSION['user']['full_name'] . ")");
    }
}