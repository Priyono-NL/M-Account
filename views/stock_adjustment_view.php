<?php
class StockAdjustmentView {
    public static function render($data) {
        extract($data);
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-sliders text-primary me-2"></i>Otorisasi Stock Adjustment
                </h5>
                <p class="text-muted small mb-0">Persetujuan dokumen opname dan penyelarasan selisih stok fisik ke komputer.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari nomor dokumen opname...">
                            <button class="btn border border-start-0 bg-white text-muted" type="button" id="btnClearSearch"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <?php 
                            Component::warehouseSelect(
                                $warehouses, 
                                $current_warehouse, 
                                $is_locked, 
                                'filterWarehouse',
                                true
                            ); 
                        ?>
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="btnFilter" class="btn btn-primary btn-sm w-100 fw-medium"><i class="fa-solid fa-filter me-1"></i> Cari Pending</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="adjustmentTable">
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4">No. Opname</th>
                                <th class="text-center">Tanggal Buat</th>
                                <th class="text-center">Lokasi Gudang</th>
                                <th class="text-center">Dibuat Oleh</th>
                                <th class="text-center">Status</th>
                                <th class="text-center pe-4" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-spinner fa-spin fs-4 mb-2 d-block"></i>Memuat antrean dokumen...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
                    <div class="small text-muted" id="paginationInfo">Menampilkan 0 data</div>
                    <nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0" id="paginationControls"></ul></nav>
                </div>
            </div>
        </div>

        <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light py-3">
                        <h6 class="modal-title fw-bold text-dark"><i class="fa-solid fa-scale-balanced text-primary me-2"></i>Lembar Verifikasi Selisih Nilai Barang</h6>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-white">
                        <div class="row mb-3 pb-3 border-bottom text-muted small fw-medium">
                            <div class="col-md-4">NOMOR: <span id="lblDetailNo" class="text-dark fw-bold"></span></div>
                            <div class="col-md-4">GUDANG: <span id="lblDetailGudang" class="text-dark fw-bold"></span></div>
                            <div class="col-md-4">TANGGAL: <span id="lblDetailTanggal" class="text-dark fw-bold"></span></div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="modalDetailTable" style="font-size: 13px;">
                                <thead class="table-light text-secondary text-center small">
                                    <tr>
                                        <th>Nama & Kode Barang</th>
                                        <th style="width: 110px;">Qty Sistem</th>
                                        <th style="width: 110px;">Qty Fisik</th>
                                        <th style="width: 110px;">Selisih</th>
                                        <th>Alasan Penyesuaian (Wajib diisi jika ada selisih) <span class="text-danger">*</span></th>
                                    </tr>
                                </thead>
                                <tbody id="modalDetailTableBody">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-3">
                        <button type="button" class="btn btn-light border btn-sm px-3 rounded-3 text-muted" data-bs-dismiss="modal">Tutup Lembar</button>
                        <button type="button" id="btnExecuteAdjustment" class="btn btn-danger btn-sm px-4 rounded-3 shadow-smfw-bold">
                            <i class="fa-solid fa-square-check me-2"></i>Sahkan & Update Stok Komputer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="/maccount/assets/js/stockAdjustment.js?v=' . time() . '"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>