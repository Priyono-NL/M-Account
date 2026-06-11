<?php

$url_path = $_GET['page'] ?? 'dashboard';
$url_path = rtrim($url_path, '/');
$segments = explode('/', $url_path);

$uri_page   = $segments[0] ?? 'dashboard';
$uri_action = $segments[1] ?? ($_GET['action'] ?? 'index');

$isView = (isset($_POST['mode']) && $_POST['mode'] == 'view') || (isset($_GET['mode']) && $_GET['mode'] == 'view');

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
            if (isset($item['type']) && $item['type'] === 'divider') {
                echo '<hr class="mx-3 my-2 text-secondary opacity-25">';
                continue;
            }

            // =======================================================
            // 2. VALIDASI HAK AKSES (GATEKEEPER)
            // =======================================================
            // SEMENTARA DIBYPASS: Semua menu di-set true agar tampil semua
            $is_visible = true; 
            
            // NOTE: Nanti setelah tabel permission selesai dibuat, 
            // kita akan masukkan logika validasi session di sini.

            if (!$is_visible) continue;

            $item_key = $item['key'] ?? ''; 

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