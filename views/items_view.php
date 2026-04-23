<?php
// =========================================================================
// VIEW: Master Data Barang (Items)
// =========================================================================
class ItemsView {
    public static function render($items) {
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Master Data Barang</h5>
                <p class="text-muted small mb-0">Kelola daftar inventaris, harga jual, dan status barang.</p>
            </div>
            <button class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm" id="btnAddItem">
                <i class="fa-solid fa-plus me-1"></i> Add Item
            </button>
        </div>

        <!-- Tabel Master Data -->
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted" style="font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4 py-3">Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th class="text-center">UoM</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted italic">Belum ada data barang.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="ps-4 fw-medium text-primary"><?= htmlspecialchars($item['item_code']) ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border-0 fw-normal px-2">
                                                <?= $item['category'] == '1' ? 'ByProd' : 'Sampah' ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($item['item_uom']) ?></td>
                                        <td class="text-end fw-bold text-dark">Rp <?= number_format($item['unit_price'], 0, ',', '.') ?></td>
                                        <td class="text-center pe-4">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-light border btn-action edit-btn" data-item='<?= json_encode($item) ?>'>
                                                    <i class="fa-solid fa-pen-to-square text-primary"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light border btn-action delete-btn" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['item_name']) ?>">
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

        <!-- MODAL FORM ITEM -->
        <div class="modal fade" id="modalItem" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold" id="modalTitle">Add Item</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formItem">
                        <div class="modal-body py-4">
                            <input type="hidden" name="id" id="itemId">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">ITEM CODE</label>
                                <input type="text" class="form-control form-control-sm" name="item_code" id="itemCode" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">ITEM NAME</label>
                                <input type="text" class="form-control form-control-sm" name="item_name" id="itemName" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">CATEGORY</label>
                                    <select class="form-select form-select-sm" name="category" id="itemCategory">
                                        <option value="1">By Product</option>
                                        <option value="2">Sampah</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">UOM</label>
                                    <input type="text" class="form-control form-control-sm" name="item_uom" id="itemUom" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">UNIT PRICE</label>
                                    <input type="number" class="form-control form-control-sm" name="unit_price" id="itemPrice" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">UNIT COST</label>
                                    <input type="number" class="form-control form-control-sm" name="unit_cost" id="itemCost" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSave">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        // Memanggil file JS eksternal
        $extra_js = '<script src="assets/js/items.js"></script>';
        include __DIR__ . '/layouts/main.php';
    }
}
?>