<?php
class BuyerView {
    public static function render($buyers) {
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Master Data Buyer</h5>
                <p class="text-muted small mb-0">Kelola daftar buyer.</p>
            </div>
            <div>
                <button class="btn btn-sm px-3 rounded-3 shadow-sm" id="btnTemplate">
                    <i class="fa-solid fa-download me-1"></i> Template
                </button>
                <input type="file" id="fileCari" class="d-none" accept=".xlsx, .xls, .csv">
                <button class="btn btn-success btn-sm px-3 rounded-3 shadow-sm" id="btnUpload">
                    <i class="fa-solid fa-upload me-1"></i> Upload
                </button>
                <button class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm" id="btnAddBuyer">
                    <i class="fa-solid fa-user-plus me-1"></i> Tambah Buyer
                </button>
            </div>            
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2">

                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari nama atau kode buyer...">
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
                    <table class="table table-hover align-middle mb-0" id="buyerTable">
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4 py-3">Buyer Code</th>
                                <th>Buyer Name</th>
                                <th>Buyer Status</th>
                                <th>Buyer Address</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <tr id="loadingRow">
                                <td colspan="7" class="text-center py-5 text-muted">
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

        <!-- MODAL BUYER -->
        <div class="modal fade" id="modalBuyer" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold" id="modalTitle">Tambah Buyer</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formBuyer">
                        <div class="modal-body py-4">

                            <input type="hidden" name="id" id="buyerId">

                            <div class="row mb-3">
                                <div class="col-4">
                                    <label class="form-label text-muted small fw-bold">KODE BUYER(NRP)</label>
                                    <input type="text" class="form-control form-control-sm" name="buyer_code" id="buyerCode" required>
                                </div>

                                <div class="col-8">
                                    <label class="form-label text-muted small fw-bold">NAMA PELANGGAN</label>
                                    <input type="text" class="form-control form-control-sm" name="buyer_name" id="buyerName" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4">
                                    <label class="form-label text-muted small fw-bold">STATUS</label>
                                    <select class="form-select form-select-sm" name="buyer_status" id="buyerStatus">                                            
                                            <option value="REG">Reguler</option>
                                            <option value="EXP">Expense</option>
                                        </select>
                                </div>

                                <div class="col-8">
                                    <label class="form-label text-muted small fw-bold">ALAMAT/DEPARTMENT</label>
                                    <input type="text" class="form-control form-control-sm" name="buyer_address" id="buyerAddress">
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

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="' . BASE_URL . '/assets/js/buyer.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>