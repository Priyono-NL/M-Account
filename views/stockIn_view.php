<?php
class StockIn_view {
    public static function render($transactionData = null) {
        $sso_warehouse = $_SESSION['user']['extra_config']['warehouse'] ?? null;
        $current_warehouse = $sso_warehouse ?? ($_GET['warehouse'] ?? '');
        $is_locked = ($sso_warehouse !== null);
        $isViewMode = ($transactionData !== null);

        ob_start();
        ?>
        
        <div class="row g-3">
            <div class="col-12">
                
                <div class="card border-0 shadow-sm mb-3 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="card-title fw-bold mb-0 text-dark">
                                <?php if ($isViewMode): ?>
                                    <i class="fa-solid fa-eye me-2 text-info"></i>Detail Penerimaan: <?= htmlspecialchars($transactionData['header']['doc_number']) ?>
                                <?php else: ?>
                                    <i class="fa-solid fa-file-invoice me-2 text-primary"></i>Informasi Transaksi
                                <?php endif; ?>
                            </h6>

                            <?php if ($isViewMode): ?>
                            <div>
                                <button type="button" class="btn btn-sm btn-light border me-1 text-muted" onclick="window.location.href='index.php?page=receive&action=history'">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row g-3 mb-3">
							<div class="col-md-2">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">GUDANG <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="warehouseSelect" <?= ($isViewMode || $is_locked) ? 'disabled' : '' ?> >

                                    <?php $selected_id = $isViewMode ? ($transactionData['header']['warehouse'] ?? null) : $current_warehouse; ?>

                                    <?php if (!$sso_warehouse || $sso_warehouse == '1' || ($isViewMode && $selected_id == '1')): ?>
                                        <option value="1" <?= ($selected_id == '1') ? 'selected' : '' ?>>Gudang BS</option>
                                    <?php endif; ?>

                                    <?php if (!$sso_warehouse || $sso_warehouse == '2' || ($isViewMode && $selected_id == '2')): ?>
                                        <option value="2" <?= ($selected_id == '2') ? 'selected' : '' ?>>Gudang Sampah</option>
                                    <?php endif; ?>

                                </select>

                                <?php if ($is_locked || $isViewMode): ?>
                                    <input type="hidden" name="warehouse" value="<?= $selected_id ?>">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">TANGGAL TRANSAKSI <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="date_receive" 
                                        value="<?= $isViewMode ? date('Y-m-d', strtotime($transactionData['header']['date_receive'])) : date('Y-m-d') ?>"
                                        <?= $isViewMode ? 'disabled' : '' ?> min="<?= date('Y-m-d', strtotime('-14 days')) ?>">
                            </div>
							<div class="col-md-2">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">PENERIMA <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="received_by" 
                                        placeholder="Nama Penerima ..."
                                        value="<?= $isViewMode ? htmlspecialchars($transactionData['header']['received_by']) : '' ?>"
                                        <?= $isViewMode ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">DOCUMENT NUMBER <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="docNumber" 
                                        placeholder="W-001" 
                                        value="<?= $isViewMode ? htmlspecialchars($transactionData['header']['doc_number']) : '' ?>"
                                        <?= $isViewMode ? 'readonly' : '' ?>>
                            </div>                                                        
							<div class="col-md-4">
								<label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">NOTES</label>
								<input type="text" class="form-control form-control-sm" id="notes" 
                                        placeholder="Detail Dokumen ..."
                                        value="<?= $isViewMode ? htmlspecialchars($transactionData['header']['notes']) : '' ?>"
                                        <?= $isViewMode ? 'disabled' : '' ?>>
							</div>
                        </div>

                    </div>
                </div>

                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h6 class="card-title fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Detail Barang
                            </h6>
                        </div>
                        
                        <?php if (!$isViewMode): ?>
                        <div class="mb-3">
                            <select class="form-control" id="productSearch" style="width: 100%;">
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="table-responsive" style="min-height: 250px;">
                            <table class="table align-middle table-hover mb-0" id="cartTable">
                                <thead class="text-muted" style="font-size: 12px; background-color: #f8f9fa; text-transform: uppercase;">
                                    <tr>
                                        <th class="ps-3">Produk</th>
                                        <th class="text-center" width="15%">Qty</th>
                                        <?php if (!$isViewMode): ?>
                                            <th class="text-center pe-3" width="10%">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <tr id="emptyCartRow">
                                        <td colspan="<?= $isViewMode ? '2' : '3' ?>" class="text-center text-muted py-5 border-bottom-0">
                                            <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                                            <?= $isViewMode ? 'Memuat rincian barang...' : 'Belum ada barang di keranjang.<br><small>Silakan cari dan pilih produk di atas.</small>' ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!$isViewMode): ?>
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button class="btn btn-light border text-danger fw-medium px-4" id="btnClearCart">
                                <i class="fa-solid fa-rotate-left me-1"></i> Clear Form
                            </button>
                            <button class="btn btn-primary fw-medium px-5 shadow-sm" id="btnCheckout">
                                <i class="fa-solid fa-check-double me-2"></i> Save Transaksi
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info border-0 small mt-4 mb-0">
                            <i class="fa-solid fa-circle-info me-2"></i>
                            Anda sedang dalam mode pratinjau. Data transaksi ini sudah terkunci dan tidak dapat diubah.
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>

            </div>
            
            </div>
        
        <?php
        $content = ob_get_clean();

        $extra_js = '<script>';
        $extra_js .= 'const IS_VIEW_MODE = ' . ($isViewMode ? 'true' : 'false') . ';';
        $extra_js .= 'const VIEW_DATA_ITEMS = ' . ($isViewMode ? json_encode($transactionData['items']) : '[]') . ';';
        $extra_js .= '</script>';
        
        $extra_js .= '<script src="/maccount/assets/js/receive.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>