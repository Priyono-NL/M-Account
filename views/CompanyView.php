<?php
class CompanyView {
    public static function render($data = []) {
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Master Data Company</h5>
                <p class="text-muted small mb-0">Kelola daftar perusahaan dan cabang gudang yang terkait.</p>
            </div>
            <div>
                <button class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm" id="btnAddCompany">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Company
                </button>
            </div>            
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" id="search" class="form-control border-start-0 bg-light" placeholder="Cari nama company atau singkatan...">
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="companyTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center text-muted small" width="5%">NO</th>
                            <th class="text-muted small">NAMA COMPANY</th>
                            <th class="text-muted small">SINGKATAN</th>
                            <th class="text-muted small">DAFTAR GUDANG</th>
                            <th class="text-center text-muted small" width="12%">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted" id="paginationInfo"></small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
                    </nav>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalCompany" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="modalTitle">Tambah Company</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="companyForm">
                        <div class="modal-body">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="id" id="companyId">
                            
                            <div class="row">
                                <div class="col-8 mb-3">
                                    <label class="form-label text-muted small fw-bold">NAMA COMPANY</label>
                                    <input type="text" class="form-control form-control-sm" name="company_name" id="companyName" required>
                                </div>
                                <div class="col-4 mb-3">
                                    <label class="form-label text-muted small fw-bold">SINGKATAN</label>
                                    <input type="text" class="form-control form-control-sm" name="company_short" id="companyShort" required>
                                </div>
                            </div>
                            
                            <hr class="text-muted opacity-25">

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label text-muted small fw-bold mb-0">DAFTAR GUDANG</label>
                                <button type="button" class="btn btn-sm btn-light border text-primary" id="btnAddWarehouse">
                                    <i class="fa-solid fa-plus"></i> Baris
                                </button>
                            </div>
                            
                            <div id="warehouseContainer">
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

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="' . BASE_URL . '/assets/js/company-min.js"></script>'; 
        include __DIR__ . '/layouts/main.php';
    }
}
?>