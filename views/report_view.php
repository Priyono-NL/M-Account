<?php
// =========================================================================
// VIEW: Laporan Riwayat Transaksi (Log)
// =========================================================================
class ReportsHistoryView {
    public static function render($transactions) {
        $title = "Riwayat Transaksi - MyPOS";
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Riwayat Transaksi</h5>
                <p class="text-muted small mb-0">Log aktiviti keluar masuk barang dan stok.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
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
        include __DIR__ . '/layouts/main.php';
    }
}
?>