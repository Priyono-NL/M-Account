<?php
$my_companies   = BaseController::$my_companies;
$c_disabled  = BaseController::$c_disabled;
$active_comp_id = BaseController::$active_comp_id;
?>

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
                    <?= htmlspecialchars($_SESSION['user']['rolename'] ?? 'Staff') ?>
                </div>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['username'] ?? 'User') ?>&background=0d6efd&color=fff" 
                 class="rounded-circle" width="32" alt="Profile">
        </div>

        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">

            <li class="px-3 py-2">
                <label class="form-label text-muted mb-1 fw-bold" style="font-size: 10.5px; letter-spacing: .5px; text-transform: uppercase;">
                    <i class="fa-solid fa-building me-1 text-primary"></i> Perusahaan
                </label>
                
                <select class="form-select form-select-sm text-dark fw-semibold" 
                        name="company" 
                        id="companySelect" 
                        onchange="changeActiveCompany(this.value)"
                        <?= $c_disabled; ?> 
                        style="font-size: 12.5px; cursor: pointer;">
                    
                    <?php foreach ($my_companies as $comp): ?>
                        <option value="<?= $comp['id'] ?>" <?= ($comp['id'] == $active_comp_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($comp['name']) ?>
                        </option>
                    <?php endforeach; ?>

                    <?php if (empty($my_companies)): ?>
                        <option value="">Tidak ada data diplot</option>
                    <?php endif; ?>
                </select>
            </li>

            <li><hr class="dropdown-divider"></li>

            <li>                
                <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fa-solid fa-key me-2 text-secondary"></i> Ubah Password
                </a>
            </li>

            <li>                
                <a class="dropdown-item py-2 text-danger" href="index.php?page=auth&action=logout">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Keluar
                </a>
            </li>
        </ul>

    </div>
</nav>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h6 class="modal-title fw-bold" id="changePasswordModalLabel"><i class="fa-solid fa-lock text-primary me-2"></i>Ubah Password</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formChangePassword">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold" style="font-size: 12px;">Password Lama</label>
                        <input type="password" class="form-control form-control-sm" id="old_password" required placeholder="Masukkan password saat ini...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold" style="font-size: 12px;">Password Baru</label>
                        <input type="password" class="form-control form-control-sm" id="new_password" required placeholder="Minimal 6 karakter...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold" style="font-size: 12px;">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control form-control-sm" id="confirm_password" required placeholder="Ketik ulang password baru...">
                        <small id="passwordError" class="text-danger mt-1 d-none" style="font-size: 11px;">Password baru tidak cocok!</small>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-sm fw-bold" id="btnSubmitPassword">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>