<?php
class SalesPivotView {
    public static function render($data) {
        extract($data);

        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">
                    Laporan Penjualan
                </h5>
                <p class="text-muted small mb-0">Laporan Penjualan Barang Interaktif.</p>
            </div>
            <div>
                <button type="button" id="btnExportExcel" class="btn btn-success btn-sm px-3 rounded-3 shadow-sm">
                    <i class="fa-solid fa-file-excel me-2"></i> Export Excel
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    
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

                <div class="row mt-3 border-top pt-2">
                    <div class="col-12 d-flex justify-content-end align-items-center">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="showSubtotals" style="cursor: pointer;">
                            <label class="form-check-label fw-bold text-secondary" for="showSubtotals" style="cursor: pointer;">
                                Tampilkan Subtotal
                            </label>
                        </div>
                    </div>
                </div>

                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div id="pivot_loading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status"></div><br>
                    <span class="text-muted">Loading Data...</span><br>
					<small class="text-muted small">Lakukan Filter data terlebih dahulu</small>
                </div>
                
                <div id="pivot_output" class="p-3" style="display:none; min-height: 500px;"></div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
		
		$extra_css = '
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.css">
            <link rel="stylesheet" type="text/css" href="' . BASE_URL . '/assets/css/cssPivot-min.css">';

        $extra_js = "<script src='" . BASE_URL . "/assets/js/salesPivot.js' defer></script>";

        include __DIR__ . '/layouts/main.php';
    }
}