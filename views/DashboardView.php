<?php
class DashboardView {
    public static function render($data) {
        extract($data);

        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">Dashboard</h4>
            <div class="d-flex align-items-center">
                <span class="me-2 fw-bold small">Warehouse:</span>
                <?php 
                Component::warehouseSelect(
                    $warehouses, 
                    $current_warehouse, 
                    $is_locked, 
                    'warehouseFilter',
                    false,
                    'filterByWarehouse(this.value)'
                ); 
                ?>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 position-relative overflow-hidden h-100">
                    <div class="position-relative z-1">
                        <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Total Item di Warehouse</small>
                        <h3 class="fw-bold mb-1"><?= number_format($data["dashboardData"]['inWarehouse']) ?></h3>
                        <small class="text-muted">Stok Terkini</small>
                    </div>
                    <div class="bg-success opacity-10 position-absolute end-0 top-0 bottom-0 d-flex align-items-center px-4" style="border-radius: 50% 0 0 50%">
                        <i class="fa-solid fa-box fs-1 text-success"></i>
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
        $label_sales = json_encode(array_column($data["dashboardData"]['sales7'], 'sales_date'));
        $val_sales   = json_encode(array_column($data["dashboardData"]['sales7'], 'total_transaksi'));

        $label_rec   = json_encode(array_column($data["dashboardData"]['in7'], 'date_receive'));
        $val_rec     = json_encode(array_column($data["dashboardData"]['in7'], 'total_transaksi'));

        $extra_js = "
        <script src='" . BASE_URL . "/vendors/chart-js-4.5.1/chart.umd.min.js'></script>
        <script>
            // Fungsi untuk memfilter data berdasarkan Warehouse
            function filterByWarehouse(val) {
                window.location.href = '" . BASE_URL . "/index.php?page=dashboard&warehouse=' + val;
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