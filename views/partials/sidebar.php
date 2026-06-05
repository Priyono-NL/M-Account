<?php
// M-Account - sidebar.php

$url_path = $_GET['page'] ?? 'dashboard';
$url_path = rtrim($url_path, '/');
$segments = explode('/', $url_path);

$uri_page   = $segments[0] ?? 'dashboard';
$uri_action = $segments[1] ?? ($_GET['action'] ?? 'index');

$isView = (isset($_POST['mode']) && $_POST['mode'] == 'view') || (isset($_GET['mode']) && $_GET['mode'] == 'view');

// Informasi Akun User dari Session
$role         = $_SESSION['user']['role_name'] ?? '';
$extra_config = $_SESSION['user']['extra_config'] ?? [];
$isAdmin      = in_array($role, ['superadmin', 'admin']);

// Ambil data paths hasil centang SSO
$my_paths     = $_SESSION['user']['paths'] ?? []; 

$can_sell = $isAdmin || ($extra_config['can_sell'] ?? false);
$can_buy  = $isAdmin || ($extra_config['can_buy'] ?? false);

// Muat data source dari config module tunggal
$config_source = require_once dirname(__DIR__, 2) . '/config/config_module.php';
$menu_items    = $config_source['modules'] ?? [];
?>

<nav id="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-store text-primary"></i>
        <span class="logo-text">M-Account</span>
    </div>

    <ul class="nav flex-column mt-3">
        <?php foreach ($menu_items as $item) : ?>
            <?php
            // =======================================================
            // 1. RENDERING TYPE: DIVIDER (GARIS PEMBATAS)
            // =======================================================
            // Dipindah ke urutan pertama agar langsung di-skip dan tidak memicu error "key" kosong
            if (isset($item['type']) && $item['type'] === 'divider') {
                echo '<hr class="mx-3 my-2 text-secondary opacity-25">';
                continue;
            }

            // =======================================================
            // 2. VALIDASI HAK AKSES (GATEKEEPER)
            // =======================================================
            $is_visible = false;
            $rule = $item['rule'] ?? 'public';
            
            // Ambil key dengan aman, beri nilai string kosong jika tidak ada
            $item_key = $item['key'] ?? ''; 
            $module_path = '/' . $item_key; 
            
            $has_path_access = in_array($module_path, $my_paths);
            $is_visible = $has_path_access;

            // Khusus role 'superadmin' otomatis bypass semua hak akses
            if ($role === 'superadmin') {
                $is_visible = true;
            }

            // Jika user tidak memiliki hak akses, lewati menu ini
            if (!$is_visible) continue;

            // =======================================================
            // 3. LOGIKA AKURASI STATE ACTIVE MENU
            // =======================================================
            $active_class = '';
            $active_rule  = $item['active_rule'] ?? 'default';

            switch ($active_rule) {
                case 'dashboard':
                    $active_class = ($uri_page == 'dashboard' || $uri_page == '') ? 'active' : '';
                    break;
                case 'pos_main':
                    $active_class = ($uri_page == 'pos' && $uri_action != 'history' && !$isView) ? 'active' : '';
                    break;
                case 'receive_main':
                    $active_class = ($uri_page == 'receive' && $uri_action != 'history' && !$isView) ? 'active' : '';
                    break;
                case 'sales_history':
                    $active_class = ($uri_page == 'pos' && ($uri_action == 'history' || $isView)) ? 'active' : '';
                    break;
                case 'receive_history':
                    $active_class = ($uri_page == 'receive' && ($uri_action == 'history' || $isView)) ? 'active' : '';
                    break;
                default:
                    $active_class = ($uri_page == $item_key) ? 'active' : '';
                    break;
            }
            ?>

            <li class="nav-item">
                <a href="<?= htmlspecialchars($item['url']) ?>" class="nav-link <?= $active_class ?>">
                    <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                    <span class="link-text"><?= htmlspecialchars($item['name']) ?></span>
                </a>
            </li>

        <?php endforeach; ?>
    </ul>
</nav>