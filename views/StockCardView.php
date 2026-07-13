<?php
class StockCardView {
    public static function render($data) {
        extract($data);
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-clipboard-list text-primary me-2"></i>Kartu Stok</h5>
                <p class="text-muted small mb-0">Pantau aliran keluar masuk barang secara terperinci per transaksi.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-bold mb-1">GUDANG ASAL <span class="text-danger">*</span></label>
                        <?php 
                        Component::warehouseSelect($warehouses, $current_warehouse, $is_locked, 'filterWarehouse', false); 
                        ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small fw-bold mb-1">PILIH BARANG <span class="text-danger">*</span></label>
                        <select id="filterItem" class="form-select form-select-sm shadow-none">
                            <option value="">-- Pilih Barang --</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold mb-1">PERIODE TANGGAL</label>
                        <div class="input-group input-group-sm">
                            <input type="date" class="form-control" id="startDate" value="<?= date('Y-m-01') ?>">
                            <span class="input-group-text bg-light border-start-0 border-end-0">s/d</span>
                            <input type="date" class="form-control" id="endDate" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="btnCariCard" class="btn btn-primary btn-sm w-100 fw-bold py-2 shadow-sm">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Tarik Data
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="stockCardTable">
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4">Tanggal Transaksi</th>
                                <th>No. Dokumen / Ref</th>
                                <th>Keterangan Mutasi</th>
                                <th class="text-center">UOM</th>
                                <th class="text-center text-success">Qty In</th>
                                <th class="text-center text-danger">Qty Out</th>
                                <th class="text-center text-primary pe-4">Saldo Stok (Total)</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <tr id="initialRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-arrow-pointer fs-3 mb-2 d-block opacity-25"></i>
                                    Silakan tentukan nama barang dan rentang tanggal, lalu klik <b>Tarik Data</b>.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="' . BASE_URL . '/assets/js/stockCard.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>