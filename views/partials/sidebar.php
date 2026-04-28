<?php
$current_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$uri_page   = $current_uri[1] ?? 'dashboard';
$uri_action = $current_uri[2] ?? '';

$isView = (isset($_POST['mode']) && $_POST['mode'] == 'view');

$extra_config = $_SESSION['user']['extra_config'] ?? [];
$can_sell = $extra_config['can_sell'] ?? false;
$can_buy  = $extra_config['can_buy'] ?? false;
?>

<nav id="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-store text-primary"></i>
        <span class="logo-text">M-Account</span>
    </div>

    <ul class="nav flex-column mt-3">

        <li class="nav-item">
            <a href="/m-account/dashboard" class="nav-link <?= ($uri_page == 'dashboard' || $uri_page == '') ? 'active' : '' ?>">
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
            <a href="/m-account/pos" class="nav-link <?= $activePOS ?>">
                <i class="fa-solid fa-cash-register"></i>
                <span class="link-text">Kasir (POS)</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if($can_buy) : ?>
        <li class="nav-item">
            <a href="/m-account/receive" class="nav-link <?= ($uri_page == 'receive' && $uri_action != 'history' && !$isView) ? 'active' : '' ?>">
                <i class="fa-solid fa-truck-ramp-box"></i>
                <span class="link-text">Receivement</span>
            </a>
        </li>
        <?php endif; ?>

        <hr class="mx-3 my-2 text-secondary opacity-25">

        <li class="nav-item">
            <a href="/m-account/items" class="nav-link <?= ($uri_page == 'items') ? 'active' : '' ?>">
                <i class="fa-solid fa-box"></i>
                <span class="link-text">Data Barang</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/m-account/buyers" class="nav-link <?= ($uri_page == 'buyers') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i>
                <span class="link-text">Data Buyer</span>
            </a>
        </li>

        <hr class="mx-3 my-2 text-secondary opacity-25">

        <li class="nav-item">
            <a href="/m-account/stocks" class="nav-link <?= ($uri_page == 'stocks') ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span class="link-text">Stock Item</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/m-account/history" class="nav-link <?= ($uri_page == 'history') ? 'active' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span class="link-text">Item History</span>
            </a>
        </li>
        
        <?php if($can_sell) : ?>
        <li class="nav-item">
            <?php 
                $activeSalesHistory = ($uri_page == 'pos' && ($uri_action == 'history' || $isView)) ? 'active' : ''; 
            ?> 
            <a href="/m-account/pos/history" class="nav-link <?= $activeSalesHistory ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span class="link-text">Laporan Penjualan</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if($can_buy) : ?>
        <li class="nav-item">
            <?php 
                $activeReceiveHistory = ($uri_page == 'receive' && ($uri_action == 'history' || $isView)) ? 'active' : ''; 
            ?>
            <a href="/m-account/receive/history" class="nav-link <?= $activeReceiveHistory ?>">
                <i class="fa-solid fa-clipboard-check"></i>
                <span class="link-text">Laporan Penerimaan</span>
            </a>
        </li>
        <?php endif; ?>
        
    </ul>
</nav>