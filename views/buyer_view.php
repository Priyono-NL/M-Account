<?php
class BuyerView {
    public static function render($buyers) {
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Master Data Buyer</h5>
                <p class="text-muted small mb-0">Kelola daftar buyer.</p>
            </div>
            <button class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm" id="btnAddBuyer">
                <i class="fa-solid fa-user-plus me-1"></i> Tambah Buyer
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2">

                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 border-end-0 shadow-none" id="search" placeholder="Cari nama atau kode barang...">
                            <button class="btn border border-start-0 bg-white text-muted" type="button" id="btnClearSearch" title="Bersihkan Pencarian">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="buyerTable">
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4 py-3">Kode</th>
                                <th>Nama Buyer</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <?php if (empty($buyers)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted italic">Belum ada data buyer.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($buyers as $b): ?>
                                    <tr>
                                        <td class="ps-4 fw-medium text-primary"><?= htmlspecialchars($b['buyer_code']) ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($b['buyer_name']) ?></td>                                        
                                        <td class="text-center pe-4">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-light border btn-action edit-btn" data-item='<?= json_encode($b) ?>'>
                                                    <i class="fa-solid fa-pen-to-square text-primary"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light border btn-action delete-btn" data-id="<?= $b['id'] ?>" data-name="<?= htmlspecialchars($b['buyer_name']) ?>">
                                                    <i class="fa-solid fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL BUYER -->
        <div class="modal fade" id="modalBuyer" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold" id="modalTitle">Tambah Buyer</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formBuyer">
                        <div class="modal-body py-4">
                            <input type="hidden" name="id" id="buyerId">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">KODE BUYER (NRP)</label>
                                <input type="text" class="form-control form-control-sm" name="buyer_code" id="buyerCode" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">NAMA PELANGGAN</label>
                                <input type="text" class="form-control form-control-sm" name="buyer_name" id="buyerName" required>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSave">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        $extra_js = '<script src="assets/js/buyer.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>