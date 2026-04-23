<?php
class POSView {
    /**
     * Render halaman POS. 
     * Parameter dihapus karena data item dan buyer sekarang dimuat via AJAX (Select2).
     */
    public static function render() {
        ob_start();
        ?>
        
        <div class="row g-3">
            <!-- Kolom Kiri: Pilihan Produk & Keranjang -->
            <div class="col-lg-8">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title fw-bold mb-3 text-dark">Transaksi Baru</h6>
                        
                        <!-- Pilihan Pelanggan & Gudang -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted" style="font-size: 11px; font-weight: 600;">PELANGGAN (BUYER)</label>
                                <select class="form-select" id="buyerSelect" style="width: 100%;">
                                    <!-- Diisi via Select2 AJAX (pos.js) -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted" style="font-size: 11px; font-weight: 600;">GUDANG ASAL</label>
                                <select class="form-select" id="warehouseSelect" style="font-size: 13px;">
                                    <option value="1">Gudang BS (1)</option>
                                    <option value="2">Gudang Sampah (2)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Pencarian Produk -->
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size: 11px; font-weight: 600;">CARI PRODUK</label>
                            <select class="form-control" id="productSearch" style="width: 100%;">
                                <!-- Diisi via Select2 AJAX (pos.js) -->
                            </select>
                        </div>

                        <!-- Tabel Keranjang -->
                        <div class="table-responsive" style="min-height: 300px;">
                            <table class="table align-middle">
                                <thead class="text-muted" style="font-size: 12px; background-color: #f8f9fa;">
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-center">Harga</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fa-solid fa-cart-arrow-down fs-2 mb-3 d-block opacity-25"></i>
                                            Pilih produk untuk memulai transaksi.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Ringkasan & Pembayaran -->
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm bg-white">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">Ringkasan</h6>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span id="summarySubtotal" class="fw-bold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Pajak (11%)</span>
                            <span id="summaryTax" class="fw-bold">Rp 0</span>
                        </div>
                        
                        <div class="my-3 border-top border-dashed"></div>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold fs-5">Total</span>
                            <span id="summaryTotal" class="fw-bold fs-4 text-primary">Rp 0</span>
                        </div>

                        <div class="mt-auto">
                            <button class="btn btn-primary w-100 py-3 fw-bold rounded-3 mb-2 shadow-sm" id="btnCheckout">
                                <i class="fa-solid fa-check-double me-2"></i> SELESAIKAN TRANSAKSI
                            </button>
                            <button class="btn btn-link text-danger w-100 text-decoration-none fw-medium" id="btnClearCart">
                                <i class="fa-solid fa-rotate-left me-1"></i> Bersihkan Keranjang
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="assets/js/pos.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>