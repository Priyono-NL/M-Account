<?php
class Receive_view {
    public static function render($sales) {
        extract($sales);

        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Transaksi Masuk Detail</h5>
                <p class="text-muted small mb-0">Laporan Penerimaan Barang.</p>
            </div>
            <!-- <div>
                <button type="button" id="btnExportExcel" class="btn btn-success btn-sm px-3 rounded-3 shadow-sm">
                    <i class="fa-solid fa-file-excel me-2"></i> Export Excel
                </button>
            </div> -->
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari No Referensi atau Nama Penerima...">                            
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
                    <table class="table table-hover align-middle mb-0" id='historyTable'>
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="text-center">Warehouse</th>
                                <th>Document Number</th>
                                <th>Penerima</th>
                                <th>Tanggal Terima</th>
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
        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="/maccount/assets/js/r_history.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>