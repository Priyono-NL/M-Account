<?php
class StocksView {
    public static function render($stocks) {
        $sso_warehouse = $_SESSION['user']['extra_config']['warehouse'] ?? null;
        $current_warehouse = $sso_warehouse ?? ($_GET['warehouse'] ?? '');
        $is_locked = ($sso_warehouse !== null);

        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Closing Stok
                </h5>
                <p class="text-muted small mb-0">Pantau mutasi barang dan kunci saldo stok akhir periode</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari nama atau kode barang...">
                            <button class="btn border border-start-0 bg-white text-muted hover-bg-light" type="button" id="btnClearSearch">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-start-0 border-end-0 text-muted">Periode</span>
                            <input type="month" class="form-control shadow-none" id="periodMonth" value="<?= date('Y-m') ?>">
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="row g-1">
                            <div class="col-4">
                                <select class="form-select form-select-sm shadow-none" id="filterWarehouse" <?= $is_locked ? 'disabled' : '' ?>>>
                                    <option value="">Semua Gudang</option>
                                    <option value="1" <?= $current_warehouse == '1' ? 'selected' : '' ?>>Gudang BS</option>
                                    <option value="2" <?= $current_warehouse == '2' ? 'selected' : '' ?>>Gudang Sampah</option>
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
                            <div class="col-4">								
                                <button type="button" id="btnMulaiClosing" class="btn btn-danger btn-sm w-100 rounded-3 shadow-sm" disabled>
                                    <i class="fa-solid fa-lock me-2"></i> Mulai Proses Closing
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3" id="closingTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="mutasi-tab" data-bs-toggle="tab" data-bs-target="#mutasi" type="button" role="tab">Data Mutasi Stok</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-muted" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#riwayat" type="button" role="tab">Riwayat Closing</button>
            </li>
        </ul>

        <div class="tab-content" id="closingTabContent">
            <div class="tab-pane fade show active" id="mutasi" role="tabpanel">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="stockTable">
                                <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                                    <tr>                                                                
                                        <th class="text-center ps-4">Gudang</th>
                                        <th>Barang</th>
                                        <th class="text-center">Awal</th>
                                        <th class="text-center">Masuk</th>
                                        <th class="text-center">Keluar</th>
                                        <th class="text-center text-primary">Akhir</th>
                                        <th class="pe-4">Update Terakhir</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size: 13px;">
									<tr id="emptyStateRow">
										<td colspan="7" class="text-center py-5 text-muted">
											<i class="fa-solid fa-filter fs-4 mb-3 text-secondary"></i><br>
											Silakan pilih Periode & Gudang, lalu klik tombol <b>Filter (<i class="fa-solid fa-arrow-right-from-bracket"></i>)</b> untuk menampilkan data.
										</td>
									</tr>
								</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="riwayat" role="tabpanel">
				<div class="card border-0 shadow-sm overflow-hidden">
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover align-middle mb-0" id="historyTable">
								<thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
									<tr>
										<th class="ps-4">Periode Bulan</th>
										<th class="text-center">Gudang Toko</th>
										<th class="text-center">Status Data</th>
										<th class="text-center">Eksekutor (User)</th>
										<th class="pe-4 text-end">Tanggal Dikunci</th>
									</tr>
								</thead>
								<tbody style="font-size: 13px;">
									</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
        </div>

        <div class="modal fade" id="modalClosing" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i> Konfirmasi Closing Stok</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning mb-4">
                            <strong>PERHATIAN!</strong> Proses ini akan mengunci seluruh transaksi pada periode <span id="lblPeriode" class="fw-bold"></span> untuk <span id="lblGudang" class="fw-bold"></span>. Saldo akhir akan dijadikan saldo awal bulan berikutnya. <strong>Tindakan ini tidak dapat dibatalkan.</strong>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Ketik <span class="text-danger">TUTUP</span> untuk melanjutkan proses:</label>
                            <input type="text" class="form-control text-center fw-bold text-danger" id="txtKonfirmasi" placeholder="Ketik TUTUP disini..." autocomplete="off">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" id="btnEksekusiClosing" class="btn btn-danger px-4 fw-bold" disabled>Eksekusi Closing</button>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="/maccount/assets/js/stocks.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>