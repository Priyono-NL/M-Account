<?php
class ReportsHistoryView {
    public static function render($transactions) {
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Riwayat Transaksi</h5>
                <p class="text-muted small mb-0">Log aktivitas keluar masuk barang dan stok.</p>
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
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari No Referensi atau Nama Barang...">                            
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

                    <div class="col-md-3">
                        <div class="row g-1">
                            <div class="col-8">
                                <select class="form-select form-select-sm" id="filterWarehouse">
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
                    <table class="table table-hover align-middle mb-0" id='historyTable'>
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4 py-3">Waktu Transaksi</th>
                                <th>No Referensi</th>
                                <th>Items</th>
                                <th class="text-center">Warehouse</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Qty</th>
                                <th class="pe-4">Catatan</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted italic">Belum ada riwayat transaksi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">
                                            <?= date('d M Y', strtotime($t['transaction_date'])) ?><br>
                                            <small style="font-size: 10px;"><?= date('H:i', strtotime($t['transaction_date'])) ?></small>
                                        </td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($t['reference_no']) ?></td>
                                        <td>
                                            <div class="fw-bold text-primary"><?= htmlspecialchars($t['item_name']) ?></div>
                                            <small class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($t['item_code']) ?></small>
                                        </td>
                                        <td class="text-center fw-medium">
                                            <?= $t['warehouse'] == '1' ? 'Gudang BS' : ($t['warehouse'] == '2' ? 'Gudang Sampah' : htmlspecialchars($t['warehouse'])) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($t['type'] === 'IN'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border-0 px-2 py-1">IN</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border-0 px-2 py-1">OUT</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold fs-6">
                                            <?= $t['type'] === 'IN' ? '+' : '-' ?><?= $t['qty'] ?>
                                        </td>
                                        <td class="pe-4 text-muted small"><?= htmlspecialchars($t['notes']) ?></td>
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
        $extra_js = '<script src="assets/js/reports.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>