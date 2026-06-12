<?php
class StockOpnameView {
    public static function render($data = null) {
        extract($data);

        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-clipboard-list text-primary me-2"></i>Form Input Stock Opname
                </h5>
                <p class="text-muted small mb-0">Pencatatan hasil hitung fisik realitas barang di area gudang.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" id="btnCancelOpname" class="btn btn-light border btn-sm px-3 rounded-3 shadow-sm text-muted">
                    Batal
                </button>
                <button type="button" id="btnSaveOpname" class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm">
                    <i class="fa-solid fa-cloud-arrow-up me-2"></i>Simpan Draft Opname
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 bg-white">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted mb-1 small fw-bold">TANGGAL OPNAME <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm shadow-none fw-medium" id="opnameDate" value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-muted mb-1 small fw-bold">LOKASI GUDANG <span class="text-danger">*</span></label>
                        <?php 
                            Component::warehouseSelect(
                                $warehouses, 
                                $current_warehouse, 
                                $is_locked, 
                                'filterWarehouse',
                                false
                            ); 
                        ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1 small fw-bold">KETERANGAN / CATATAN</label>
                        <input type="text" class="form-control form-control-sm shadow-none" id="opnameNotes" placeholder="Contoh: Perhitungan fisik rutin akhir kuartal...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                <span class="fw-bold text-dark mb-0 fs-6"><i class="fa-solid fa-boxes-stacked text-secondary me-2"></i>Daftar Item Penghitungan</span>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-3 px-3 fw-medium" data-bs-toggle="modal" data-bs-target="#itemModal">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Barang
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="opnameTable">
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-4 text-center" style="width: 60px;">No</th>
                                <th>Kode & Nama Barang</th>
                                <th class="text-center" style="width: 150px;">Qty Sistem</th>
                                <th class="text-center" style="width: 180px;">Qty Fisik (Real)</th>
                                <th class="text-center" style="width: 120px;">Satuan</th>
                                <th class="text-center pe-4" style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="opnameTableBody" style="font-size: 13px;">
                            <tr id="emptyRow">
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                                    Belum ada barang yang didaftarkan.<br>
                                    <small class="text-muted">Silakan klik tombol <b>Tambah Barang</b> di atas untuk memulai pencatatan.</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light py-3 border-0">
                        <h6 class="modal-title fw-bold text-dark" id="itemModalLabel">
                            <i class="fa-solid fa-magnifying-glass-plus text-primary me-2"></i>Pilih Barang untuk Opname
                        </h6>
                        <button type="button" class="btn-close shadow-none" data-bs-dash="modal" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0 bg-light bg-opacity-50">
                        <div class="row g-0 h-100">
                            
                            <div class="col-md-8 p-3 bg-white border-end d-flex flex-column" style="min-height: 500px;">
                                <div class="input-group input-group-sm mb-3 shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="modalSearchItem" placeholder="Ketik kode atau nama barang...">
                                    <button class="btn border border-start-0 bg-white text-muted" type="button" id="btnClearItemSearch" style="display: none;">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <div class="table-responsive flex-grow-1">
                                    <table class="table table-sm table-hover align-middle mb-0" id="modalItemTable">
                                        <thead class="bg-light text-muted small" style="font-size: 11px; text-transform: uppercase;">
                                            <tr>
                                                <th class="ps-3">Kode</th>
                                                <th>Nama Barang</th>
                                                <th class="text-center">Stok Buku</th>
                                                <th class="text-center pe-3" style="width: 50px;">Pilih</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemTableBody" class="small">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-5">
                                                    <i class="fa-solid fa-keyboard fs-3 mb-2 d-block opacity-25"></i>Silakan ketik nama atau kode barang...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-md-4 p-3 d-flex flex-column" style="background-color: #fafafa;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-secondary small text-uppercase">Item Terpilih (<span id="selectedItemBadge">0</span>)</span>
                                </div>
                                <div id="modalItemSummary" class="flex-grow-1 overflow-auto pe-1" style="max-height: 380px;">
                                    <div class="text-center text-muted mt-5 opacity-50 empty-summary">
                                        <i class="fa-solid fa-cart-arrow-down fs-1 mb-2"></i><br>
                                        <small>Belum ada barang dipilih</small>
                                    </div>
                                </div>
                                <div class="pt-3 border-top mt-3">
                                    <button type="button" id="btnSubmitItems" class="btn btn-primary w-100 shadow-sm rounded-3 fw-medium py-2">
                                        Masukkan ke Form Opname
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="' . BASE_URL . '/assets/js/stockOpname.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>