<?php

$url_path = $_GET['page'] ?? 'dashboard';
$url_path = rtrim($url_path, '/');
$segments = explode('/', $url_path);

$uri_page   = $segments[0] ?? 'dashboard';
$uri_action = $segments[1] ?? ($_GET['action'] ?? 'index');

$isView = (isset($_POST['mode']) && $_POST['mode'] == 'view') || (isset($_GET['mode']) && $_GET['mode'] == 'view');

$config_source = require dirname(__DIR__, 2) . '/config/config_module.php';
$menu_items    = $config_source['modules'] ?? [];
$rolename = strtolower($_SESSION['user']['rolename'] ?? '');
?>

<nav id="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-store text-primary"></i>
        <span class="logo-text">M-Account</span>
    </div>

    <ul class="nav flex-column mt-3">
        <?php 
        // Penanda status untuk mereduksi divider ganda / kosong
        $pending_divider = false; 
        $rendered_any    = false; 

        foreach ($menu_items as $item) : 
        ?>
            <?php
            // =======================================================
            // 1. RENDERING TYPE: DIVIDER (GARIS PEMBATAS)
            // =======================================================
            if (isset($item['type']) && $item['type'] === 'divider') {
                $rule = $item['rule'] ?? 'public';
                
                // Proteksi awal berdasarkan level role dasar
                if ($rule === 'superadmin' && $rolename !== 'superadmin') continue;
                if ($rule === 'admin' && !in_array($rolename, ['admin', 'superadmin'])) continue;

                // Jangan cetak dulu, tandai sebagai PENDING jika sebelumnya sudah ada menu yang tampil
                if ($rendered_any) {
                    $pending_divider = true;
                }
                continue;
            }

            // =======================================================
            // 2. VALIDASI HAK AKSES (GATEKEEPER)
            // =======================================================
            $item_key = $item['key'] ?? ''; 
            $module_path = '/' . $item_key;
            
            $my_paths = $_SESSION['user']['paths'] ?? [];
            $is_visible = in_array($module_path, $my_paths);

            if ($rolename === 'superadmin') $is_visible = true;
            if (!$is_visible) continue;

            // =======================================================
            // [BARU] EKSEKUSI DIVIDER JIKA ADA MENU YANG VALID
            // =======================================================
            if ($pending_divider) {
                echo '<hr class="mx-3 my-2 text-secondary opacity-25">';
                $pending_divider = false; // Reset penanda setelah dicetak
            }
            $rendered_any = true; // Menandakan minimal sudah ada 1 item menu yang aktif di layar

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
                case 'sales_history':
                    $active_class = ($uri_page == 'pos' && ($uri_action == 'history' || $isView)) ? 'active' : '';
                    break;
                case 'receive_main':
                    $active_class = ($uri_page == 'receive' && $uri_action != 'history' && !$isView) ? 'active' : '';
                    break;                
                case 'receive_history':
                    $active_class = ($uri_page == 'receive' && ($uri_action == 'history' || $isView)) ? 'active' : '';
                    break;
                case 'stock_main':
                    $active_class = ($uri_page == 'stocks' && $uri_action != 'card') ? 'active' : '';
                    break;
                case 'stock_card':
                    $active_class = ($uri_page == 'stocks' && $uri_action == 'card') ? 'active' : '';
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