<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-store"></i>
        <span class="logo-text">MyPOS</span>
    </div>
    <ul class="nav flex-column mt-3">
        <!-- Dashboard / POS -->
        <li class="nav-item">
            <a href="/m-account/pos" class="nav-link <?= (!isset($_GET['page']) || $_GET['page'] == 'pos') ? 'active' : '' ?>">
                <i class="fa-solid fa-cash-register"></i>
                <span class="link-text">Kasir (POS)</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/m-account/receive" class="nav-link <?= (!isset($_GET['page']) || $_GET['page'] == 'receive') ? 'active' : '' ?>">
                <i class="fa-solid fa-truck-ramp-box"></i>
                <span class="link-text">Receivement</span>
            </a>
        </li>

        <!-- Group: Master Data -->
        <li class="px-3 mt-4 mb-1 text-muted d-none sidebar-label" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px;">
            Master Data
        </li>
        <hr class="mx-3 my-2 text-secondary opacity-25">

        <li class="nav-item">
            <a href="/m-account/items" class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'items') ? 'active' : '' ?>">
                <i class="fa-solid fa-box"></i>
                <span class="link-text">Data Barang</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/m-account/buyers" class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'buyers') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i>
                <span class="link-text">Data Buyer</span>
            </a>
        </li>

        <!-- Group: Laporan -->
        <li class="px-3 mt-4 mb-1 text-muted d-none sidebar-label" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px;">
            Laporan
        </li>
        <hr class="mx-3 my-2 text-secondary opacity-25">

        <li class="nav-item">
            <a href="/m-account/stocks" class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'stocks') ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span class="link-text">Stock Item</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="/m-account/history" class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'history') ? 'active' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span class="link-text">Item History</span>
            </a>
        </li>
        
    </ul>
</nav>
