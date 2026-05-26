<?php
class POSView {
    public static function render($transactionData = null) {
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
                                <button type="button" class="btn btn-sm btn-light border me-1 text-muted fw-semibold shadow-sm" onclick="window.location.href='index.php?page=pos&action=history'">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Riwayat
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">NO INVOICE</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-bold text-muted" id="invoiceNo" 
                                        placeholder="[ AUTO GENERATE ]" readonly 
                                        value="<?= $isViewMode ? $transactionData['header']['invoice_no'] : '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">TANGGAL TRANSAKSI</label>
                                <input type="date" class="form-control form-control-sm" id="salesDate" 
                                        value="<?= $isViewMode ? date('Y-m-d', strtotime($transactionData['header']['sales_date'])) : date('Y-m-d') ?>" 
                                        <?= $isViewMode ? 'disabled' : '' ?> min="<?= date('Y-m-d', strtotime('-14 days')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">TIPE SALES</label>
                                <select class="form-select form-select-sm" id="salesType" <?= $isViewMode ? 'disabled' : '' ?>>
                                    <option value="SLS" <?= ($isViewMode && $transactionData['header']['sale_type'] == 'SLS') ? 'selected' : '' ?>>Normal Sales (SLS)</option>
                                    <option value="EXP" <?= ($isViewMode && $transactionData['header']['sale_type'] == 'EXP') ? 'selected' : (!$isViewMode ? 'disabled' : '') ?>>Expense Sales (EXP)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">PELANGGAN (BUYER) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control bg-white" id="buyerNameDisplay" placeholder="-- Pilih Pelanggan --" readonly 
                                           value="<?= $isViewMode ? htmlspecialchars($transactionData['header']['buyer_name']) : '' ?>">
                                    <?php if (!$isViewMode): ?>
                                    <button class="btn btn-primary px-3" type="button" data-bs-toggle="modal" data-bs-target="#buyerModal">
                                        <i class="fa-solid fa-magnifying-glass"></i> Cari
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="buyerId" value="<?= $isViewMode ? $transactionData['header']['buyer'] : '' ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">GUDANG ASAL <span class="text-danger">*</span></label>
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
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="card-title fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Detail Barang
                        </h6>
                        <?php if (!$isViewMode): ?>
                        <button type="button" class="btn btn-primary btn-sm fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#itemModal">
                            <i class="fa-solid fa-plus me-1"></i> Pilih & Tambah Barang
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive" style="min-height: 250px;">
                            <table class="table align-middle table-hover m-0" id="cartTable">
                                <thead class="text-muted border-bottom" style="font-size: 12px; background-color: #f8f9fa;">
                                    <tr>
                                        <th class="ps-4">PRODUK</th>
                                        <th class="text-center" width="15%">HARGA</th>
                                        <th class="text-center" width="20%">QTY</th>
                                        <th class="text-end" width="20%">SUBTOTAL</th>
                                        <?php if (!$isViewMode): ?>
                                            <th class="text-center pe-3" width="10%">AKSI</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <tr id="emptyCartRow">
                                        <td colspan="<?= $isViewMode ? '4' : '5' ?>" class="text-center text-muted py-5 border-bottom-0">
                                            <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                                            <?= $isViewMode ? 'Memuat data transaksi...' : 'Belum ada barang di keranjang.<br><small>Silakan klik tombol tambah di atas.</small>' ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="position-sticky" style="top: 20px;">
                    <div class="card border-0 shadow-sm bg-white mb-3">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title fw-bold mb-3 text-dark border-bottom pb-2">
                                <i class="fa-solid fa-calculator me-2 text-primary"></i>Ringkasan
                            </h6>
                            
                            <div class="d-flex flex-column gap-2 my-2" id="mainCartSummaryCards">
                                </div>

                            <div class="my-2 border-top border-dashed"></div>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted fw-medium">Subtotal</span>
                                <span id="summarySubtotal" class="fw-bold text-dark">Rp 0</span>
                            </div>

                            <div class="my-2 border-top border-dashed"></div>
                            
                            <div class="d-flex justify-content-between mb-4 align-items-center">
                                <span class="fw-bold fs-5 text-dark">Total</span>
                                <span id="summaryTotal" class="fw-bold fs-3 text-primary">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm bg-white">
                        <div class="card-body p-3">
                            <div class="mt-auto">
                                <?php if (!$isViewMode): ?>
                                <button class="btn btn-primary w-100 py-2 fw-bold mb-2 shadow-sm" id="btnCheckout">
                                    <i class="fa-solid fa-check-double me-2"></i> Save Transaksi
                                </button>
                                <button class="btn btn-light border w-100 py-2 text-danger fw-medium" id="btnClearCart">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Clear Form
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($isViewMode): ?>               
                                    <button type="button" class="btn btn-success w-100 py-2 fw-bold shadow-sm" 
                                            onclick="printReceipt('<?= $transactionData['header']['id'] ?>')">
                                        <i class="fa-solid fa-print me-1"></i> Re-print Invoice
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="buyerModal" tabindex="-1" aria-labelledby="buyerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable"> 
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="buyerModalLabel"><i class="fa-solid fa-users text-primary me-2"></i> Pilih Buyer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="p-3 bg-white border-bottom">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="modalSearchBuyer" class="form-control form-control-sm border-start-0 border-end-0" placeholder="Ketik nama atau kode buyer...">
                                <button class="btn btn-outline-secondary border-start-0 border bg-white text-muted" type="button" id="btnClearBuyerSearch" style="display: none;">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-hover align-middle m-0" style="font-size: 13px;">
                                <thead class="table-light text-muted sticky-top">
                                    <tr>
                                        <th class="ps-3">Nama Buyer</th>
                                        <th class="text-center pe-3" width="80">Pilih</th>
                                    </tr>
                                </thead>
                                <tbody id="buyerTableBody">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable"> 
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="itemModalLabel"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Pilih Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="row g-0 h-100">
                            <div class="col-lg-8 d-flex flex-column border-end">
                                <div class="p-3 bg-white border-bottom">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                        <input type="text" id="modalSearchItem" class="form-control form-control-sm border-start-0 border-end-0" placeholder="Ketik nama atau kode barang...">
                                        <button class="btn btn-outline-secondary border-start-0 border bg-white text-muted" type="button" id="btnClearItemSearch" style="display: none;">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive flex-grow-1" style="min-height: 400px;">
                                    <table class="table table-hover align-middle m-0" id="modalItemTable" style="font-size: 13px;">
                                        <thead class="table-light text-muted sticky-top">
                                            <tr>													
                                                <th width="20%" class="ps-3">Kode</th>
                                                <th width="50%">Nama Barang</th>
                                                <th width="20%" class="text-center">Sisa Stok</th>
                                                <th width="10%" class="text-center pe-3">Pilih</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemTableBody">
                                            </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="col-lg-4 bg-light d-flex flex-column" style="height: 100%;">
                                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark"><i class="fa-solid fa-clipboard-check text-success me-2"></i>Draft Pilihan</span>
                                    <span class="badge bg-primary rounded-pill" id="selectedItemBadge">0</span>
                                </div>
                                <div class="flex-grow-1 p-2 overflow-auto" id="modalItemSummary" style="max-height: 400px;">
                                    <div class="text-center text-muted mt-5 opacity-50 empty-summary">
                                        <i class="fa-solid fa-cart-arrow-down fs-1 mb-2"></i><br>
                                        <small>Belum ada barang dipilih</small>
                                    </div>
                                </div>
                                <div class="p-3 border-top bg-white">
                                    <button type="button" id="btnSubmitItems" class="btn btn-primary btn-sm w-100 fw-bold py-2 shadow-sm">
                                        <i class="fa-solid fa-plus me-1"></i> Tambah ke Keranjang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		
		<div class="modal fade" id="modalCheckoutSuccess" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content border-0 shadow-lg">
					<div class="modal-header bg-success text-white">
						<h5 class="modal-title fw-bold">
							<i class="fa-solid fa-circle-check me-2"></i>Transaksi Berhasil!
						</h5>
						</div>
					<div class="modal-body p-4 text-center">
						<div class="mb-3">
							<i class="fa-solid fa-receipt text-success opacity-75" style="font-size: 4rem;"></i>
						</div>
						<h5 class="fw-bold mb-2 text-dark">Data telah disimpan.</h5>
						<p class="text-muted mb-0">Apakah Anda ingin mencetak untuk transaksi ini?</p>
					</div>
					<div class="modal-footer bg-light justify-content-center border-top-0 pt-0 pb-4">
						<button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
							<i class="fa-solid fa-plus me-2"></i>Transaksi Baru
						</button>
						<button type="button" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnPrintInvoice">
							<i class="fa-solid fa-print me-2"></i>Cetak Invoice
						</button>
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
        
        $extra_js .= '<script src="/maccount/assets/js/pos.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>