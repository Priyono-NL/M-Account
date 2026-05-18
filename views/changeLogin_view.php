<?php
class ChangeLoginView {
    public static function render() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
            echo "<div class='alert alert-danger m-4'>Akses Ditolak. Halaman ini hanya untuk Superadmin.</div>";
            return;
        }
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Change Login As</h5>
                <p class="text-muted small mb-0">Pilih pengguna untuk masuk ke dalam sistem sebagai mereka.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="input-group input-group-sm w-50">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 shadow-none" id="searchUser" placeholder="Cari nama atau username...">
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="usersTable">
                        <thead class="bg-light text-muted" style="font-size: 12px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-3">User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted" id="loadingRow">
                                    <i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br> Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        
        // HANYA muat JS logic Anda
        $extra_js = '<script src="/m-account/assets/js/changeLogin.js"></script>';
        
        include __DIR__ . '/layouts/main.php';
    }
}
?>