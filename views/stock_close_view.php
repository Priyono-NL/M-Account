<?php
class StockCloseView {
    public static function render($result) {
        extract($result);
        $summaryStatus = $result['status'] ?? 'ONGOING';

        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Informasi Stok Bulanan
                </h5>
                <p class="text-muted small mb-0">Pantau stok awal, pergerakan, dan stok akhir barang.</p>
            </div>
            <!-- <div>
                <button type="button" id="btnExportExcel" class="btn btn-success btn-sm px-3 rounded-3 shadow-sm">
                    <i class="fa-solid fa-file-excel me-2"></i> Export Excel
                </button>
            </div> -->
        </div>

        <div id="statusBannerContainer"></div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari nama atau kode barang...">
                            <button class="btn border border-start-0 bg-white text-muted hover-bg-light" type="button" id="btnClearSearch" title="Bersihkan Pencarian">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fa-regular fa-calendar me-1"></i> Bulan</span>
                            <input type="month" class="form-control shadow-none" id="closeMonth" value="<?php echo date('Y-m'); ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="row g-1">
                            <div class="col-6">
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
							<div class="col-3">
								<button type="button" id="btnFilter" class="btn btn-primary border btn-sm w-100" title="Data Filter">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                </button>
							</div>
                            <div class="col-3">
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
                    <table class="table table-hover align-middle mb-0" id="stockTable">
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <tr>
                                <th>Barang</th>
                                <th class="text-center">Qty Open</th>
                                <th class="text-center">Qty In</th>
                                <th class="text-center">Qty Out</th>
                                <th class="text-center pe-4 text-primary">Qty Close</th>
                                <th class="text-center pe-4 text-success">Qty OnHand</th>
                                <th class="text-center pe-4 text-danger">Selisih</th>
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

        <?php
        $content = ob_get_clean();
        // Mempertahankan nama file asset script stocksClose.js asli milikmu dengan tambahan anti-cache otomatis
        $extra_js = '<script src="/maccount/assets/js/stocksClose.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>