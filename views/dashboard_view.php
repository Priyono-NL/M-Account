<?php
class DashboardView {
    public static function render($data) {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $sso_warehouse = $_SESSION['user']['extra_config']['warehouse'] ?? null;
        $current_warehouse = $sso_warehouse ?? ($_GET['warehouse'] ?? '1');
        $is_locked = ($sso_warehouse !== null);
        
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">Dashboard</h4>
            <div class="d-flex align-items-center">
                <span class="me-2 fw-bold small">Warehouse:</span>
                <select 
                    class="form-select form-select-sm" 
                    id="warehouseFilter" 
                    style="width: 170px;" 
                    onchange="filterByWarehouse(this.value)"
                    <?= $is_locked ? 'disabled' : '' ?>>
                >
                    <option value="1" <?= $current_warehouse == '1' ? 'selected' : '' ?>>Gudang BS</option>
                    <option value="2" <?= $current_warehouse == '2' ? 'selected' : '' ?>>Gudang Sampah</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 position-relative overflow-hidden h-100">
                    <div class="position-relative z-1">
                        <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Total Item di Warehouse</small>
                        <h3 class="fw-bold mb-1"><?= number_format($data['inWarehouse']) ?></h3>
                        <small class="text-muted">Stok Terkini</small>
                    </div>
                    <div class="bg-success opacity-10 position-absolute end-0 top-0 bottom-0 d-flex align-items-center px-4" style="border-radius: 50% 0 0 50%">
                        <i class="fa-solid fa-box fs-1 text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 position-relative overflow-hidden h-100">
                    <div class="position-relative z-1">
                        <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Total Penjualan (SLS)</small>
                        <h3 class="fw-bold mb-1">Rp <?= number_format($data['total_sales'], 0, ',', '.') ?></h3>
                        <small class="text-muted">Bulan ini</small>
                    </div>
                    <div class="bg-primary opacity-10 position-absolute end-0 top-0 bottom-0 d-flex align-items-center px-4" style="border-radius: 50% 0 0 50%">
                        <i class="fa-solid fa-dollar-sign fs-1 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4">
                    <h6 class="fw-bold mb-4 text-muted">Total Penjualan (7 Hari)</h6>
                    <canvas id="salesChart" height="150"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4">
                    <h6 class="fw-bold mb-4 text-muted">Total Penerimaan (7 Hari)</h6>
                    <canvas id="receiveChart" height="150"></canvas>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();

        // --- PREPARE DATA UNTUK CHART ---
        // Mapping labels dan data dari format database
        $label_sales = json_encode(array_column($data['sales7'], 'sales_date'));
        $val_sales   = json_encode(array_column($data['sales7'], 'total_transaksi'));

        $label_rec   = json_encode(array_column($data['in7'], 'date_receive'));
        $val_rec     = json_encode(array_column($data['in7'], 'total_transaksi'));

        $extra_js = "
        <script src='/m-account/vendors/chart-js-4.5.1/chart.umd.min.js'></script>
        <script>
            // Fungsi untuk memfilter data berdasarkan Warehouse
            function filterByWarehouse(val) {
                window.location.href = '/m-account/dashboard?warehouse=' + val;
            }

            // Sales Chart
            new Chart(document.getElementById('salesChart'), {
                type: 'bar',
                data: {
                    labels: $label_sales,
                    datasets: [{
                        label: 'Transaksi',
                        data: $val_sales,
                        backgroundColor: '#6ea8fe',
                        borderRadius: 5
                    }]
                },
                options: { 
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });

            // Receive Chart
            new Chart(document.getElementById('receiveChart'), {
                type: 'bar',
                data: {
                    labels: $label_rec,
                    datasets: [{
                        label: 'Transaksi',
                        data: $val_rec,
                        backgroundColor: '#75b798',
                        borderRadius: 5
                    }]
                },
                options: { 
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        </script>";

        include __DIR__ . '/layouts/main.php';
    }
}