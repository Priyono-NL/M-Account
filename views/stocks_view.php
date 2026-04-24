<?php
class StocksView {
    public static function render($stocks) {
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Informasi Stok
                </h5>
                <p class="text-muted small mb-0">Pantau stok awal, pergerakan, dan stok akhir barang.</p>
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
                    
                    <div class="col-md-4">
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

                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fa-regular fa-calendar me-1"></i> Dari</span>
                            <input type="date" class="form-control shadow-none" id="startDate">
                            <span class="input-group-text bg-light border-start-0 border-end-0 text-muted">Sampai</span>
                            <input type="date" class="form-control shadow-none" id="endDate">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="row g-1">
                            <div class="col-8">
                                <select class="form-select form-select-sm shadow-none" id="filterWarehouse">
                                    <option value="">Semua Gudang</option>
                                    <option value="1">Gudang BS</option>
                                    <option value="2">Gudang Sampah</option>
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
                                <th class="ps-4 py-3">Update Terakhir</th>                                
                                <th class="text-center">Gudang</th>
                                <th>Barang</th>
                                <th class="text-center">Awal</th>
                                <th class="text-center">Masuk</th>
                                <th class="text-center">Keluar</th>
                                <th class="text-center pe-4 text-primary">Akhir</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <?php if (empty($stocks)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted italic">Belum ada data stok yang tercatat.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stocks as $t): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">
                                            <?= date('d M Y', strtotime($t['date'])) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-2 fw-normal">
                                                <?= $t['warehouse'] == '1' ? 'Gudang BS' : ($t['warehouse'] == '2' ? 'Gudang Sampah' : htmlspecialchars($t['warehouse'])) ?>
                                            </span>
                                        </td>                                        
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($t['item_name']) ?></div>
                                            <small class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($t['item_code']) ?></small>
                                        </td>
                                        <td class="text-center fw-medium text-muted"><?= $t['qty_open'] ?></td>
                                        <td class="text-center fw-bold text-success"><?= $t['qty_in'] > 0 ? '+'.$t['qty_in'] : '0' ?></td>
                                        <td class="text-center fw-bold text-danger"><?= $t['qty_out'] > 0 ? '-'.$t['qty_out'] : '0' ?></td>
                                        <td class="text-center fw-bold fs-6 text-primary pe-4"><?= $t['qty_close'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="assets/js/stocks.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>