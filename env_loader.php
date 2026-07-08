<?php

function loadEnv($filename) {
    // __DIR__ akan mengarah ke folder tempat file env_loader.php ini berada
    $path = __DIR__ . '/' . $filename;

    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);

        if (empty($line) || strpos($line, '#') === 0) continue;

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            
            $name = trim($name);
            $value = trim($value);
            
            if (preg_match('/^"([^"]*)"$/', $value, $matches) || preg_match('/^\'([^\']*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            
            if (!defined($name)) define($name, $value);
        }
    }
    return true;
}

// =======================================================
// REVISI DI SINI: PROTEKSI PANDUAN JIKA .ENV GAGAL MUAT
// =======================================================
if (!loadEnv('.env')) {
    echo "<div style='padding: 20px; background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; font-family: sans-serif; border-radius: 5px; margin: 20px;'>";
    echo "<h3 style='margin-top:0;'>⚠️ File .env Tidak Terdeteksi!</h3>";
    echo "<p>Sistem gagal membaca konfigurasi karena file <b>.env</b> tidak ditemukan.</p>";
    echo "<b>Langkah Perbaikan:</b>";
    echo "<ol style='margin-top: 5px;'>";
    echo "<li>Pastikan kamu sudah membuat file bernama murni <code>.env</code> (bukan <code>.env.txt</code>).</li>";
    echo "<li>Letakkan file <code>.env</code> tersebut di dalam folder utama project (satu jajar dengan file <code>index.php</code>).</li>";
    echo "</ol>";
    echo "</div>";
    exit; // Hentikan aplikasi agar tidak memicu error 'Undefined Constant' ke file lain
}

// =======================================================
// 2. OTOMATISASI STATE ENVIRONMENT (DEV VS PROD)
// =======================================================
if (defined('APP_ENV') && APP_ENV === 'development') {
    // Mode Lokal Dev
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Mode Production Live
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    
    // Catat log error ke file eksternal secara rahasia
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php_error.log'); 
}

if (false) {
    define('APP_ENV', 'development');
    define('BASE_URL', '/maccount');
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', 'db_m_account_dev');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}