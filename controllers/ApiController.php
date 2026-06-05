<?php
require_once 'BaseController.php';

class ApiController extends BaseController {
    
    public function index() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); 
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: X-App-ID, X-App-Secret, Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

        $config = require_once dirname(__DIR__) . '/config/config_module.php';

        $headers = getallheaders();
        $incoming_id     = $headers['X-App-ID'] ?? $headers['x-app-id'] ?? $_SERVER['HTTP_X_APP_ID'] ?? '';
        $incoming_secret = $headers['X-App-Secret'] ?? $headers['x-app-secret'] ?? $_SERVER['HTTP_X_APP_SECRET'] ?? '';
        
        // Cari tahu key mana yang kamu pakai di config_module.php ('app_secret' atau 'secret_key')
        $expected_secret = $config['app_secret'] ?? $config['secret_key'] ?? '';

        // ---- TEMPORARY DEBUGGER (Ubah biar kita bisa lihat isi dalemnya) ----
        if (empty($incoming_secret) || $incoming_id !== $config['app_id'] || $incoming_secret !== $expected_secret) {
            http_response_code(401);
            echo json_encode([
                'status'  => 'error', 
                'message' => 'App ID atau App Secret tidak cocok!',
                'DEBUG_SISTEM' => [
                    'Diterima_dari_SSO' => [
                        'X-App-ID'     => $incoming_id,
                        'X-App-Secret' => $incoming_secret
                    ],
                    'Diharapkan_oleh_PHP' => [
                        'app_id'     => $config['app_id'] ?? 'Kosong!',
                        'app_secret' => $expected_secret ?: 'Kosong!'
                    ]
                ]
            ]);
            exit();
        }
        // ---------------------------------------------------------------------

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data'   => $config['modules']
        ]);
        exit();
    }
}