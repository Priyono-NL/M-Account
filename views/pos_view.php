<?php
class POSView {
    public static function render($transactionData = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $sso_warehouse = $_SESSION['user']['extra_config']['warehouse'] ?? null;
        $current_warehouse = $sso_warehouse ?? ($_GET['warehouse'] ?? '1');
        $is_locked = ($sso_warehouse !== null);
        $isViewMode = ($transactionData !== null);
        
        ob_start();
        ?>
        
        <div class="row g-3">
            <div class="col-lg-8">
                
                <div class="card border-0 shadow-sm mb-3 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="card-title fw-bold mb-0 text-dark">
                                <?php if ($isViewMode): ?>
                                    <i class="fa-solid fa-eye me-2 text-info"></i>Detail Transaksi: <?= $transactionData['header']['invoice_no'] ?>
                                <?php else: ?>
                                    <i class="fa-solid fa-file-invoice me-2 text-primary"></i>Informasi Transaksi
                                <?php endif; ?>
                            </h6>
                            
                            <?php if ($isViewMode): ?>
                            <div>
                                <button type="button" class="btn btn-sm btn-light border me-1 text-muted" onclick="window.location.href='/m-account/pos/history'">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="button" class="btn btn-sm btn-primary shadow-sm" 
                                        onclick="printReceipt('<?= $transactionData['header']['id'] ?>')">
                                    <i class="fa-solid fa-print me-1"></i> Reprint
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">NO INVOICE</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="invoiceNo" 
                                        placeholder="[ AUTO GENERATE ]" readonly 
                                        value="<?= $isViewMode ? $transactionData['header']['invoice_no'] : '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">TANGGAL TRANSAKSI</label>
                                <input type="date" class="form-control form-control-sm" id="salesDate" 
                                        value="<?= $isViewMode ? date('Y-m-d', strtotime($transactionData['header']['sales_date'])) : date('Y-m-d') ?>" 
                                        <?= $isViewMode ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">TIPE SALES</label>
                                <select class="form-select form-select-sm" id="salesType" <?= $isViewMode ? 'disabled' : '' ?>>
                                    <option value="SLS" <?= ($isViewMode && $transactionData['header']['sale_type'] == 'SLS') ? 'selected' : '' ?>>Normal Sales (SLS)</option>
                                    <option value="EXP" <?= ($isViewMode && $transactionData['header']['sale_type'] == 'EXP') ? 'selected' : '' ?>>Expense Sales (EXP)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">PELANGGAN (BUYER)</label>
                                <select class="form-select" id="buyerSelect" style="width: 100%;" <?= $isViewMode ? 'disabled' : '' ?>>
                                    <?php if($isViewMode): ?>
                                        <option value="<?= $transactionData['header']['buyer'] ?>" selected>
                                            <?= htmlspecialchars($transactionData['header']['buyer_name']) ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">GUDANG ASAL</label>
                                <select class="form-select" id="warehouseSelect" <?= ($isViewMode || $is_locked) ? 'disabled' : '' ?> >

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
                            <table class="table align-middle table-hover" id="cartTable">
                                <thead class="text-muted" style="font-size: 12px; background-color: #f8f9fa; text-transform: uppercase;">
                                    <tr>
                                        <th class="ps-3">Produk</th>
                                        <th class="text-center" width="15%">Harga</th>
                                        <th class="text-center" width="15%">Qty</th>
                                        <th class="text-end" width="20%">Subtotal</th>
                                        <?php if (!$isViewMode): ?>
                                            <th class="text-center pe-3" width="10%">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <tr id="emptyCartRow">
                                        <td colspan="<?= $isViewMode ? '4' : '5' ?>" class="text-center text-muted py-5">
                                            <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                                            <?= $isViewMode ? 'Memuat data transaksi...' : 'Belum ada barang di keranjang.<br><small>Silakan cari dan pilih produk di atas.</small>' ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm bg-white">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">
                            <i class="fa-solid fa-calculator me-2 text-primary"></i>Ringkasan
                        </h6>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted fw-medium">Subtotal</span>
                            <span id="summarySubtotal" class="fw-bold text-dark">Rp 0</span>
                        </div>

                        <div class="my-3 border-top border-dashed"></div>
                        
                        <div class="d-flex justify-content-between mb-4 align-items-center">
                            <span class="fw-bold fs-5 text-dark">Total</span>
                            <span id="summaryTotal" class="fw-bold fs-3 text-primary">Rp 0</span>
                        </div>

                        <?php if (!$isViewMode): ?>
                        <div class="mt-auto">
                            <button class="btn btn-primary w-100 fw-bold rounded-3 mb-2 shadow-sm" id="btnCheckout">
                                <i class="fa-solid fa-check-double me-2"></i> SIMPAN TRANSAKSI
                            </button>
                            <button class="btn btn-light border w-100 text-danger fw-medium" id="btnClearCart">
                                <i class="fa-solid fa-rotate-left me-1"></i> Bersihkan Form
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        $content = ob_get_clean();
        
        // --- SUNTIKKAN DATA KE JAVASCRIPT ---
        $extra_js = '<script>';
        $extra_js .= 'const IS_VIEW_MODE = ' . ($isViewMode ? 'true' : 'false') . ';';
        $extra_js .= 'const VIEW_DATA_ITEMS = ' . ($isViewMode ? json_encode($transactionData['items']) : '[]') . ';';
        $extra_js .= '</script>';
        
        $extra_js .= '<script src="/m-account/assets/js/pos.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>