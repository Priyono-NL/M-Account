<?php
class SalesView {
    public static function render($sales) {
        extract($sales);

        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Transaksi Keluar Detail</h5>
                <p class="text-muted small mb-0">Penjualan Barang Details.</p>
            </div>
            <div>
                <button type="button" id="btnExportExcel" class="btn btn-success btn-sm px-3 rounded-3 shadow-sm">
                    <i class="fa-solid fa-file-excel me-2"></i> Export Excel
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2">
                    
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari No Referensi atau Nama Pembeli...">                            
                            <button class="btn border border-start-0 bg-white text-muted" type="button" id="btnClearSearch">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">Dari</span>
                            <input type="date" class="form-control" id="startDate" value=<?= date('Y-m-d', strtotime('-14 days')) ?>>
                            <span class="input-group-text bg-light border-start-0 border-end-0">Sampai</span>
                            <input type="date" class="form-control" id="endDate" value=<?= date('Y-m-d') ?>>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="row g-1">
                            <div class="col-4">
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
                            <div class="col-4">
                                <select class="form-select form-select-sm" id="filterType">
                                    <option value="">Semua Tipe</option>
                                    <option value="SLS">Normal</option>
                                    <option value="EXP">Expense</option>
                                </select>
                            </div>
							<div class="col-2">
								<button type="button" id="btnFilter" class="btn btn-primary border btn-sm w-100" title="Data Filter">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                </button>
							</div>
                            <div class="col-2">								
                                <button type="button" id="btnResetAll" class="btn btn-light border btn-sm w-100 text-muted" title="Reset Filter">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
			
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id='historyTable'>
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="text-center">Warehouse</th>
                                <th class="text-center">Tipe</th>
                                <th>Invoice Number</th>                                
                                <th>Buyer</th>
                                <th>Tanggal Transaksi</th>
                                <th>Total</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <tr id="loadingRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-filter fs-4 mb-3 text-secondary"></i><br>
									Silakan pilih Periode & Gudang, lalu klik tombol <b>Filter (<i class="fa-solid fa-arrow-right-from-bracket"></i>)</b> untuk menampilkan data.
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

        <div class="modal fade" id="modalDetailSales" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-light">
                        <h6 class="modal-title fw-bold text-dark"><i class="fa-solid fa-file-invoice text-primary me-2"></i>Detail Transaksi Penjualan</h6>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" style="font-size: 13px;">
                        <div class="row mb-3 pb-3 border-bottom text-muted">
                            <div class="col-6">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr><td class="p-0 py-1" width="35%">No. Invoice</td><td class="p-0 py-1 fw-bold text-dark" id="mdInvNo">-</td></tr>
                                    <tr><td class="p-0 py-1">Buyer</td><td class="p-0 py-1 fw-bold text-dark" id="mdBuyer">-</td></tr>
                                </table>
                            </div>
                            <div class="col-6">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr><td class="p-0 py-1" width="35%">Tanggal</td><td class="p-0 py-1 text-dark" id="mdDate">-</td></tr>
                                    <tr><td class="p-0 py-1">Gudang</td><td class="p-0 py-1 text-dark" id="mdWarehouse">-</td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle" id="mdTableItems">
                                <thead class="bg-light text-center small fw-bold text-muted">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th width="10%">UOM</th>
                                        <th width="12%">Qty</th>
                                        <th width="15%">Harga</th>
                                        <th width="18%">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end align-items-center bg-light p-3 rounded border">
                            <span class="fw-bold text-muted me-3">TOTAL:</span>
                            <span class="fs-5 fw-bold text-primary" id="mdGrandTotal">Rp 0</span>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0">
                        <button type="button" class="btn btn-secondary btn-sm fw-bold px-3" data-bs-dismiss="modal">Tutup</button>
                        <a href="#" target="_blank" id="mdBtnReprint" class="btn btn-success btn-sm fw-bold px-3">
                            <i class="fa-solid fa-print me-1"></i> Reprint
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="' . BASE_URL . '/assets/js/sales.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>