<?php
class StockInView {
    public static function render($data) {
        extract($data);

        $sso_warehouse = $_SESSION['user']['extra_config']['warehouse'] ?? null;
        $user_role = $_SESSION['user']['rolename'] ?? '';
        $is_locked = ($sso_warehouse !== null);
        $isViewMode = ($transactionData !== null);
        $selected_id = $isViewMode ? ($transactionData['header']['warehouse'] ?? null) : ($current_warehouse ?? '');

        $allowed_roles = ['all', 'superadmin'];                                    
        $is_allowed_edit = in_array(strtolower($user_role), $allowed_roles);

        ob_start();
        ?>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0 text-dark">
                <?php if ($isViewMode): ?>
                    <i class="fa-solid fa-eye me-2 text-info"></i>Detail Penerimaan: <?= htmlspecialchars($transactionData['header']['doc_number']) ?>
                <?php else: ?>
                    <i class="fa-solid fa-file-invoice me-2 text-primary"></i>Transaksi Penerimaan Barang
                <?php endif; ?>
            </h5>

            <?php if (!$isViewMode && $is_allowed_edit): ?>
                <div>
                    <p id="info-state"></p>
                    <button class="btn btn-primary px-3" type="button" id="btnFindDoc" data-bs-toggle="modal" data-bs-target="#receiveModal">
                        <i class="fa-solid fa-search"></i> Cari & Edit
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($isViewMode): ?>
                <button type="button" class="btn btn-sm btn-light border text-muted fw-semibold shadow-sm" onclick="window.location.href='index.php?page=receive&action=history'">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Riwayat
                </button>
            <?php endif; ?>
        </div>
        
        <div class="card border-0 shadow-sm mb-4 bg-white">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">GUDANG <span class="text-danger">*</span></label>
                        <?php 
                            Component::warehouseFormSelect(
                                $warehouses, 
                                $selected_id, 
                                $is_locked, 
                                $isViewMode, 
                                $sso_warehouse
                            ); 
                        ?>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">TANGGAL TRANSAKSI <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" id="date_receive" readonly
                                value="<?= $isViewMode ? date('Y-m-d', strtotime($transactionData['header']['date_receive'])) : date('Y-m-d') ?>"
                                <?= $isViewMode ? 'disabled' : '' ?> min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">DOCUMENT NUMBER <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm" id="docNumber" 
                                    placeholder="IN-year-00000" readonly
                                    value="<?= $isViewMode ? htmlspecialchars($transactionData['header']['doc_number']) : '' ?>"
                                    <?= $isViewMode ? 'disabled' : '' ?>>
                            <?php if (!$isViewMode && $is_allowed_edit): ?>                                        
                                <button class="btn btn-danger px-3" type="button" id="btnCancelEditReceive" style="display: none;" title="Batalkan Edit Document Number">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div> 
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">NAMA PENERIMA <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="received_by" 
                                placeholder="Nama Penerima ..."
                                value="<?= $isViewMode ? htmlspecialchars($transactionData['header']['received_by']) : '' ?>"
                                <?= $isViewMode ? 'disabled' : '' ?>>
                    </div>                                                                    
                    <div class="col-md-4 col-sm-12">
                        <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">CATATAN</label>
                        <input type="text" class="form-control form-control-sm" id="notes" 
                                placeholder="Detail Dokumen ..."
                                value="<?= $isViewMode ? htmlspecialchars($transactionData['header']['notes']) : '' ?>"
                                <?= $isViewMode ? 'disabled' : '' ?>>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm bg-white h-100">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="card-title fw-bold m-0 text-dark">
                            <i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Detail Barang
                        </h6>
                        <?php if (!$isViewMode): ?>
                        <button type="button" class="btn btn-primary btn-sm fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#itemModal">
                            <i class="fa-solid fa-plus me-1"></i> Pilih & Tambah
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive" style="min-height: 300px;">
                            <table class="table align-middle table-hover m-0" id="cartTable">
                                <thead class="text-muted border-bottom" style="font-size: 12px; background-color: #f8f9fa;">
                                    <tr>
                                        <th class="ps-4">PRODUK</th>
                                        <th class="text-center" width="160">QTY</th>
                                        <?php if (!$isViewMode): ?>
                                            <th class="text-center pe-4" width="80">AKSI</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <tr id="emptyCartRow">
                                        <td colspan="<?= $isViewMode ? '2' : '3' ?>" class="text-center text-muted py-5 border-bottom-0">
                                            <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                                            <?= $isViewMode ? 'Memuat rincian barang...' : 'Belum ada barang di keranjang.<br><small>Silakan klik tombol tambah di atas.</small>' ?>
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
                    <div class="card border-0 shadow-sm bg-white mb-3" id="mainCartSummaryWrapper" style="display: none;">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-layer-group text-primary me-2"></i>Rekap Total Barang</h6>
                        </div>
                        <div class="card-body p-3 bg-light">
                            <div class="d-flex flex-column gap-2" id="mainCartSummaryCards"></div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm bg-white">
                        <div class="card-body p-3">
                            <?php if (!$isViewMode): ?>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary fw-bold py-2 shadow-sm" id="btnCheckout">
                                        <i class="fa-solid fa-check-double me-2"></i> Save Transaksi
                                    </button>
                                    <button class="btn btn-light border text-danger fw-semibold py-2" id="btnClearCart">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Clear Form
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info border-0 small m-0 text-center">
                                    <i class="fa-solid fa-lock mb-2 fs-4 d-block"></i>
                                    Mode Pratinjau.<br>Data ini terkunci dan tidak dapat diubah.
                                </div>
                            <?php endif; ?>
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
                                        <input type="text" id="modalSearchItem" class="form-control form-control-sm border-start-0 border-end-0" placeholder="Ketik minimal 2 huruf untuk mencari barang...">
                                        <button class="btn btn-outline-secondary border-start-0 border bg-white text-muted" type="button" id="btnClearModalSearch" style="display: none;">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive flex-grow-1" style="min-height: 400px;">
                                    <table class="table table-hover align-middle m-0" id="modalItemTable" style="font-size: 13px;">
                                        <thead class="table-light text-muted sticky-top" style="z-index: 1;">
                                            <tr>                                                    
                                                <th width="20%" class="ps-3">Kode</th>
                                                <th width="70%">Nama Barang</th>
                                                <th width="10%" class="text-center pe-3">Pilih</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modalItemTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4 bg-light d-flex flex-column" style="height: 100%;">
                                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark"><i class="fa-solid fa-clipboard-check text-success me-2"></i>Ringkasan Pilihan</span>
                                    <span class="badge bg-primary rounded-pill" id="selectedCountBadge">0</span>
                                </div>
                                <div class="flex-grow-1 p-2 overflow-auto" id="modalSummaryList" style="max-height: 400px;">
                                    <div class="text-center text-muted mt-5 opacity-50 empty-summary">
                                        <i class="fa-solid fa-cart-arrow-down fs-1 mb-2"></i><br>
                                        <small>Belum ada barang dipilih</small>
                                    </div>
                                </div>
                                <div class="p-3 border-top bg-white">
                                    <button type="button" id="btnSubmitModalItems" class="btn btn-primary btn-sm w-100 fw-bold py-2 shadow-sm">
                                        <i class="fa-solid fa-plus me-1"></i> Masukkan ke Keranjang Utama
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="receiveModal" tabindex="-1" aria-labelledby="receiveModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="receiveModalLabel"><i class="fa-solid fa-truck-ramp-box me-2 text-primary"></i> Cari & Edit Data Penerimaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control form-control-sm border-start-0 border-end-0"  id="modalSearchReceive" placeholder="Ketik No Dokumen atau Nama Penerima...">
                            <button class="btn btn-outline-secondary" type="button" id="btnClearReceiveSearch" style="display: none;"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-hover align-middle">
                                <thead class="table-light" style="font-size: 13px;"> 
                                    <tr>
                                        <th class="ps-3">Doc No. & Tanggal</th>
                                        <th>Penerima</th>
                                        <th>Catatan</th>
                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="receiveTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fa-solid fa-search fs-3 mb-2 d-block opacity-25"></i>Ketik nomor dokumen untuk mencari...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        $content = ob_get_clean();

        $extra_js = '<script>';
        $extra_js .= 'const IS_VIEW_MODE = ' . ($isViewMode ? 'true' : 'false') . ';';
        $extra_js .= 'const VIEW_DATA_ITEMS = ' . ($isViewMode ? json_encode($transactionData['items']) : '[]') . ';';
        $extra_js .= 'const USER_ROLE = "' . $user_role . '";';
        $extra_js .= '</script>';
        
        $extra_js .= '<script src="' . BASE_URL . '/assets/js/receive-min.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>