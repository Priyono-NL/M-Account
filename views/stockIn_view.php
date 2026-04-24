<?php
class StockIn_view {
    public static function render() {
        ob_start();
        ?>
        
        <div class="row g-3">
            <div class="col-lg-8">
                
                <div class="card border-0 shadow-sm mb-3 bg-white">
                    <div class="card-body">
                        <h6 class="card-title fw-bold mb-3 text-dark border-bottom pb-2">
                            <i class="fa-solid fa-file-invoice me-2 text-primary"></i>Informasi Transaksi
                        </h6>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">TANGGAL TRANSAKSI</label>
                                <input type="date" class="form-control form-control-sm" id="date_receive" value="<?= date('Y-m-d') ?>">
                            </div> 
                            <div class="col-md-2">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">DOCUMENT NUMBER</label>
                                <input type="text" class="form-control form-control-sm" id="docNumber" placeholder="RCV-001">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">PENERIMA</label>
                                <select class="form-select" id="personSelect" style="width: 100%;">
                                    </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted mb-1" style="font-size: 11px; font-weight: 600;">GUDANG</label>
                                <select class="form-select" id="warehouseSelect" style="width: 100%;">
                                    <option value="1">Gudang BS</option>
                                    <option value="2">Gudang Sampah</option>
                                </select>
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
                        
                        <div class="mb-3">
                            <select class="form-control" id="productSearch" style="width: 100%;">
                                </select>
                        </div>

                        <div class="table-responsive" style="min-height: 250px;">
                            <table class="table align-middle table-hover">
                                <thead class="text-muted" style="font-size: 12px; background-color: #f8f9fa; text-transform: uppercase;">
                                    <tr>
                                        <th class="ps-3">Produk</th>
                                        <th class="text-center" width="15%">Qty</th>
                                        <th class="text-center pe-3" width="10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                                            Belum ada barang di keranjang.<br>
                                            <small>Silakan cari dan pilih produk di atas.</small>
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

                        <div class="mt-auto">
                            <button class="btn btn-primary w-100 fw-bold rounded-3 mb-2 shadow-sm" id="btnCheckout">
                                <i class="fa-solid fa-check-double me-2"></i> SIMPAN TRANSAKSI
                            </button>
                            <button class="btn btn-light border w-100 text-danger fw-medium" id="btnClearCart">
                                <i class="fa-solid fa-rotate-left me-1"></i> Bersihkan Form
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="/m-account/assets/js/receive.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>