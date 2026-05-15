<?php
class StockCloseView {
    public static function render($result) {
        $sso_warehouse = $_SESSION['user']['extra_config']['warehouse'] ?? null;
        $current_warehouse = $sso_warehouse ?? ($_GET['warehouse'] ?? '');
        $is_locked = ($sso_warehouse !== null);
        $summaryStatus = $result['status'];

        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Informasi Stok Bulanan
                </h5>
                <p class="text-muted small mb-0">Pantau stok awal, pergerakan, dan stok akhir barang.</p>
            </div>
            <div>
                <button type="button" id="btnClosing" class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm">
                    <i class="fa-solid fa-shop-lock me-2"></i> Closing Stock
                </button>

                <button type="button" id="btnExportExcel" class="btn btn-success btn-sm px-3 rounded-3 shadow-sm">
                    <i class="fa-solid fa-file-excel me-2"></i> Export Excel
                </button>
            </div>
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

                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fa-regular fa-calendar me-1"></i> Bulan</span>
                            <input type="month" class="form-control shadow-none" id="closeMonth" value="<?php echo date('Y-m'); ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="row g-1">
                            <div class="col-8">
                                <select class="form-select form-select-sm shadow-none" id="filterWarehouse" <?= $is_locked ? 'disabled' : '' ?>>>
                                    <option value="">Semua Gudang</option>
                                    <option value="1" <?= $current_warehouse == '1' ? 'selected' : '' ?>>Gudang BS</option>
                                    <option value="2" <?= $current_warehouse == '2' ? 'selected' : '' ?>>Gudang Sampah</option>
                                </select>
                            </div>
                            <div class="col-4">
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
                                    <i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>
                                    Memuat data ...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="/m-account/assets/js/stocksClose.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>