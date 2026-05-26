<?php
$url_path = $_GET['page'] ?? 'dashboard';
$url_path = rtrim($url_path, '/');
$segments = explode('/', $url_path);

$uri_page   = $segments[0] ?? 'dashboard';
$uri_action = $segments[1] ?? ($_GET['action'] ?? 'index');

$isView = (isset($_POST['mode']) && $_POST['mode'] == 'view') || (isset($_GET['mode']) && $_GET['mode'] == 'view');

$role         = $_SESSION['user']['role_name'] ?? '';
$extra_config = $_SESSION['user']['extra_config'] ?? [];
$isAdmin      = in_array($role, ['superadmin', 'admin']);

$can_sell = $isAdmin || ($extra_config['can_sell'] ?? false);
$can_buy  = $isAdmin || ($extra_config['can_buy'] ?? false);
?>

<nav id="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-store text-primary"></i>
        <span class="logo-text">M-Account</span>
    </div>

    <ul class="nav flex-column mt-3">

        <li class="nav-item">
            <a href="index.php?page=dashboard" class="nav-link <?= ($uri_page == 'dashboard' || $uri_page == '') ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i>
                <span class="link-text">Dashboard</span>
            </a>
        </li>

        <hr class="mx-3 my-2 text-secondary opacity-25">
        
        <?php if($can_sell) : ?>
        <li class="nav-item">
            <?php 
                $activePOS = ($uri_page == 'pos' && $uri_action != 'history' && !$isView) ? 'active' : ''; 
            ?>
            <a href="index.php?page=pos" class="nav-link <?= $activePOS ?>">
                <i class="fa-solid fa-cash-register"></i>
                <span class="link-text">Kasir (POS)</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if($can_buy) : ?>
        <li class="nav-item">
            <a href="index.php?page=receive" class="nav-link <?= ($uri_page == 'receive' && $uri_action != 'history' && !$isView) ? 'active' : '' ?>">
                <i class="fa-solid fa-truck-ramp-box"></i>
                <span class="link-text">Receivement</span>
            </a>
        </li>
        <?php endif; ?>

        <hr class="mx-3 my-2 text-secondary opacity-25">

        <li class="nav-item">
            <a href="index.php?page=items" class="nav-link <?= ($uri_page == 'items') ? 'active' : '' ?>">
                <i class="fa-solid fa-box"></i>
                <span class="link-text">Data Barang</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="index.php?page=buyers" class="nav-link <?= ($uri_page == 'buyers') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i>
                <span class="link-text">Data Buyer</span>
            </a>
        </li>

        <hr class="mx-3 my-2 text-secondary opacity-25">

        <li class="nav-item">
            <a href="index.php?page=stockClose" class="nav-link <?= ($uri_page == 'stockClose') ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span class="link-text">Stock Item</span>
            </a>
        </li>
		
		<li class="nav-item">
            <a href="index.php?page=stockClosing" class="nav-link <?= ($uri_page == 'stockClosing') ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-packing"></i>
                <span class="link-text">Closing Stok</span>
            </a>
        </li>
		
		<hr class="mx-3 my-2 text-secondary opacity-25">

        <?php if($can_sell) : ?>
        <li class="nav-item">
            <a href="index.php?page=sales" class="nav-link <?= ($uri_page == 'sales') ? 'active' : '' ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span class="link-text">Laporan Penjualan</span>
            </a>
        </li>
        
        <li class="nav-item">
            <?php 
                $activeSalesHistory = ($uri_page == 'pos' && ($uri_action == 'history' || $isView)) ? 'active' : ''; 
            ?> 
            <a href="index.php?page=pos&action=history" class="nav-link <?= $activeSalesHistory ?>">
                <i class="fa-solid fa-file-invoice"></i>
                <span class="link-text">Laporan Penjualan Detail</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if($can_buy) : ?>
        <li class="nav-item">
            <?php 
                $activeReceiveHistory = ($uri_page == 'receive' && ($uri_action == 'history' || $isView)) ? 'active' : ''; 
            ?>
            <a href="index.php?page=receive&action=history" class="nav-link <?= $activeReceiveHistory ?>">
                <i class="fa-solid fa-clipboard-check"></i>
                <span class="link-text">Laporan Penerimaan</span>
            </a>
        </li>
        <?php endif; ?>
		
		<li class="nav-item">
            <a href="index.php?page=history" class="nav-link <?= ($uri_page == 'history') ? 'active' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span class="link-text">Item Log</span>
            </a>
        </li>

        <hr class="mx-3 my-2 text-secondary opacity-25">
        
        <?php if($role == 'superadmin') :?>
        <li class="nav-item">
            <a href="index.php?page=changeLogin" class="nav-link <?= ($uri_page == 'changeLogin') ? 'active' : '' ?>">
                <i class="fa-solid fa-user-gear"></i>
                <span class="link-text">Change Login</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>