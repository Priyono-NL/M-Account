<?php
class UserView {
    public static function render($users) {
        extract($users);
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Master Data User</h5>
                <p class="text-muted small mb-0">Kelola daftar user.</p>
            </div>
            <div>
                <button class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm" id="btnAddUser">
                    <i class="fa-solid fa-user-plus me-1"></i> Tambah User
                </button>
            </div>            
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2">

                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari username atau nama...">
                            <button class="btn border border-start-0 bg-white text-muted" type="button" id="btnClearSearch" title="Bersihkan Pencarian">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
					
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
			
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="UserTable">
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4 py-3">Username</th>
                                <th>Full Name</th>
                                <th>User Role</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <tr id="loadingRow">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-magnifying-glass fs-2 mb-3 d-block opacity-25"></i>
									Ketik di Pencarian untuk memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
				
				<div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
                    <div class="small text-muted" id="paginationInfo">
                        Menampilkan 0 data
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0" id="paginationControls">
                            </ul>
                    </nav>
                </div>
				
            </div>
        </div>

        <!-- MODAL User -->
        <div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold" id="modalTitle">Tambah User</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formUser">
                        <div class="modal-body py-4">

                            <input type="hidden" name="id" id="userId">

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">USERNAME<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="username" id="username" required>
                                </div>

                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">PASSWORD<span class="text-danger">*</span></label>
                                    <input type="password" class="form-control form-control-sm" name="password" id="password" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">PERSON<span class="text-danger">*</span></label>
                                    <select class="form-control form-select-sm" name="person_id" id="person_id" required style="width: 100%;">
                                        </select>
                                </div>

                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">ROLE<span class="text-danger">*</span></label>
                                    <select id="role_id" name="role_id" class="form-select form-select-sm">
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="loadingIndicator" class="text-primary mt-2 small d-none">
                                        <i class="fa-solid fa-circle-notch fa-spin"></i> Memuat data...
                                    </div>
                                </div>

                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">COMPANY<span class="text-danger">*</span></label>
                                    <select id="company" name="company" class="form-select form-select-sm">
                                        <option value="all">All Company</option>
                                        <?php foreach ($companies as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="loadingIndicator" class="text-primary mt-2 small d-none">
                                        <i class="fa-solid fa-circle-notch fa-spin"></i> Memuat data...
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSave">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="buyerModal" tabindex="-1" aria-labelledby="buyerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable"> 
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="buyerModalLabel"><i class="fa-solid fa-users text-primary me-2"></i> Pilih Buyer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="p-3 bg-white border-bottom">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="modalSearchBuyer" class="form-control form-control-sm border-start-0 border-end-0" placeholder="Ketik nama atau kode buyer...">
                                <button class="btn btn-outline-secondary border-start-0 border bg-white text-muted" type="button" id="btnClearBuyerSearch" style="display: none;">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-hover align-middle m-0" style="font-size: 13px;">
                                <thead class="table-light text-muted sticky-top">
                                    <tr>
                                        <th class="ps-3">Nama Buyer</th>
                                        <th class="text-center pe-3" width="80">Pilih</th>
                                    </tr>
                                </thead>
                                <tbody id="buyerTableBody">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="' . BASE_URL . '/assets/js/user.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>