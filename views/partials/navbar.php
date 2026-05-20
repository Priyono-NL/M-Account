<?php if (isset($_SESSION['user']['is_impersonating']) && $_SESSION['user']['is_impersonating'] === true): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center m-0 rounded-0 py-2 px-3 border-0 border-bottom border-warning w-100" style="z-index: 1050;">
        <span style="font-size: 13px;">
            <i class="fa-solid fa-user-secret me-2 text-dark"></i> 
            Anda sedang login sebagai <strong><?= htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username']); ?></strong> (Mode Penyamaran).
        </span>
        <button type="button" id="btnStopImpersonate" class="btn btn-danger btn-sm py-1" style="font-size: 11px;">
            <i class="fa-solid fa-right-from-bracket me-1"></i> Kembali ke Admin
        </button>
    </div>
<?php endif; ?>

<nav class="navbar d-flex align-items-center justify-content-between px-3">
    <button class="btn-toggle" id="sidebarToggle">
        <i class="fa-solid fa-bars"></i>
    </button>
    
    <div class="dropdown">
        <div class="user-profile d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
            <div class="text-end d-none d-sm-block">
                <div class="fw-bold text-dark" style="font-size: 13px;">
                    <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Unknown User') ?>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    <?= htmlspecialchars($_SESSION['user']['role_name'] ?? 'Staff') ?>
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