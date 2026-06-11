<?php
class ItemsView {
    public static function render($items) {
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Master Data Barang</h5>
                <p class="text-muted small mb-0">Kelola daftar inventaris, harga jual, dan status barang.</p>
            </div>
            <div>
                <button class="btn btn-sm px-3 rounded-3 shadow-sm" id="btnTemplate">
                    <i class="fa-solid fa-download me-1"></i> Template
                </button>   
                <input type="file" id="fileCari" class="d-none" accept=".xlsx, .xls, .csv">
                <button class="btn btn-success btn-sm px-3 rounded-3 shadow-sm" id="btnUpload">
                    <i class="fa-solid fa-upload me-1"></i> Upload
                </button>

                <button class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm" id="btnAddItem">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Barang
                </button>
            </div>            
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2">

                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari nama atau kode barang...">
                            <button class="btn border border-start-0 bg-white text-muted" type="button" id="btnClearSearch" title="Bersihkan Pencarian">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="filterCategory">
                            <option value="">Semua Kategori</option>
                            <option value="1">ByProduct</option>
                            <option value="2">Sampah</option>
                        </select>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="itemTable">
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4 py-3">Kode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th class="text-center">UOM</th>
                                <th class="text-end">Harga Jual</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <tr id="loadingRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>
                                    Memuat data ...
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

        <div class="modal fade" id="modalItem" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold" id="modalTitle">Tambah Barang</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formItem">
                        <div class="modal-body py-4">
                            <input type="hidden" name="id" id="itemId">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">KODE BARANG</label>
                                <input type="text" class="form-control form-control-sm" name="item_code" id="itemCode" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">NAMA BARANG</label>
                                <input type="text" class="form-control form-control-sm" name="item_name" id="itemName" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">KATEGORI</label>
                                    <select class="form-select form-select-sm" name="category" id="itemCategory">
                                        <option value="1">ByProduct</option>
                                        <option value="2">Sampah</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">UOM</label>
                                    <input type="text" class="form-control form-control-sm" name="item_uom" id="itemUom" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">HARGA JUAL</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control input-harga" name="unit_price" id="itemPrice" required>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">HARGA COST</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control input-harga" name="unit_cost" id="itemCost" required>
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

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="' . BASE_URL . '/assets/js/items.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>