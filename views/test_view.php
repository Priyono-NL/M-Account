<?php
class TestView {
    public static function render($data) {
        $sso_warehouse = $_SESSION['user']['extra_config']['warehouse'] ?? null;
        $current_warehouse = $sso_warehouse ?? ($_GET['warehouse'] ?? '');
        $is_locked = ($sso_warehouse !== null);

        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i>Pivot & Chart Analysis
                </h5>
                <p class="text-muted small mb-0">Eksplorasi data dengan tabel dan grafik interaktif.</p>
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

                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">Dari</span>
                            <input type="date" class="form-control" id="startDate">
                            <span class="input-group-text bg-light border-start-0 border-end-0">Sampai</span>
                            <input type="date" class="form-control" id="endDate">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="row g-1">
                            <div class="col-5">
                                <select class="form-select form-select-sm shadow-none" id="filterWarehouse" <?= $is_locked ? 'disabled' : '' ?>>>
                                    <option value="">Semua Gudang</option>
                                    <option value="1" <?= $current_warehouse == '1' ? 'selected' : '' ?>>Gudang BS</option>
                                    <option value="2" <?= $current_warehouse == '2' ? 'selected' : '' ?>>Gudang Sampah</option>
                                </select>
                            </div>
                            <div class="col-5">
                                <select class="form-select form-select-sm" id="filterType">
                                    <option value="">Semua Tipe</option>
                                    <option value="SLS">Normal</option>
                                    <option value="EXP">Expense</option>
                                </select>
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
                <div id="pivot_loading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status"></div><br>
                    <span class="text-muted small">Menghubungkan ke database...</span>
                </div>
                
                <div id="pivot_output" class="p-3" style="display:none; min-height: 500px;"></div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();

        $extra_css = "
        <link rel='stylesheet' type='text/css' href='/m-account/vendors/jquery-ui/jquery-ui.min.css'>
        <link rel='stylesheet' type='text/css' href='/m-account/vendors/pivottable/pivot.min.css'>
        <link rel='stylesheet' type='text/css' href='/m-account/assets/css/test.css'>";

        $extra_js = "
        <script src='/m-account/vendors/jquery-ui/jquery-ui.min.js'></script>
        <script src='/m-account/vendors/plotly/plotly-basic.min.js'></script>
        <script src='/m-account/vendors/pivottable/pivot.min.js'></script>
        <script src='/m-account/vendors/pivottable/plotly_renderers.min.js'></script>
        <script src='/m-account/vendors/pivottable/export_renderers.min.js'></script>
        <script src='/m-account/assets/js/test.js'></script>";

        include __DIR__ . '/layouts/main.php';
    }
}