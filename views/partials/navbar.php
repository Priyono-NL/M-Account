<nav class="navbar d-flex align-items-center justify-content-between">
    <button class="btn-toggle" id="sidebarToggle">
        <i class="fa-solid fa-bars"></i>
    </button>
    
    <div class="dropdown">
        <div class="user-profile d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
            <div class="text-end d-none d-sm-block">
                <div class="fw-bold text-dark" style="font-size: 13px;">
                    <?= $_SESSION['user']['username'] ?? 'Unknown User' ?>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    <?= $_SESSION['user']['role_name'] ?? 'Staff' ?>
                </div>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['username'] ?? 'User') ?>&background=0d6efd&color=fff" 
                 class="rounded-circle" width="32" alt="Profile">
        </div>

        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
            <li>
                <a class="dropdown-item text-danger" href="index.php?page=auth/logout">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Keluar
                </a>
            </li>
        </ul>
    </div>
</nav>