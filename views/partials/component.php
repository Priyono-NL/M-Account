<?php
class Component {
    /**
     * Render dropdown warehouse secara dinamis
     */
    public static function warehouseSelect($warehouses, $current_warehouse, $is_locked, $id = 'warehouseFilter', $showAllOption = false, $onchange = '') {
        ?>
        <select 
            class="form-select form-select-sm shadow-none" 
            id="<?= $id ?>" 
            <?= $is_locked ? 'disabled' : '' ?>
            <?= !empty($onchange) ? "onchange=\"$onchange\"" : "" ?>
        >
            <?php if ($showAllOption): ?>
                <option value="">Semua Gudang</option>
            <?php endif; ?>

            <?php if (!empty($warehouses)): ?>
                <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= $current_warehouse == $wh['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($wh['warehouse_name']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
    }

    public static function warehouseFormSelect($warehouses, $selected_id, $is_locked, $isViewMode, $sso_warehouse, $id = 'warehouseSelect', $name = 'warehouse') {
        $isDisabled = ($isViewMode || $is_locked);
        ?>
        <select class="form-select form-select-sm" id="<?= $id ?>" name="<?= $name ?>" <?= $isDisabled ? 'disabled' : '' ?>>
            <?php if (!empty($warehouses)): ?>
                <?php foreach ($warehouses as $wh): ?>
                    <?php if (!$sso_warehouse || $sso_warehouse == $wh['id'] || ($isViewMode && $selected_id == $wh['id'])): ?>
                        <option value="<?= $wh['id'] ?>" <?= $selected_id == $wh['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wh['warehouse_name']) ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <?php if ($isDisabled): ?>
            <input type="hidden" name="<?= $name ?>" value="<?= $selected_id ?>">
        <?php endif; ?>
        <?php
    }

    public static function shortcutLegend($showHistory = false, $page = false) {
        ?>
        <div class="fixed-bottom bg-dark text-white py-2 shadow-lg border-top border-secondary" style="z-index: 1030; font-size: 12px;">
            <div class="container-fluid d-flex justify-content-center gap-4">
                <?php if ($page): ?>
                    <div><kbd class="bg-light text-dark">F1</kbd> Cari Pelanggan</div>
                <?php endif; ?>
                <div><kbd class="bg-light text-dark">F2</kbd> Tambah Barang</div>                
                <div><kbd class="bg-light text-dark">F3</kbd> Masukkan Draft</div>
                <?php if ($showHistory): ?>
                    <div><kbd class="bg-light text-dark">F8</kbd> Cari & Edit</div>
                <?php endif; ?>
                
                <div><kbd class="bg-light text-dark">F4</kbd> Simpan Transaksi</div>
                <div><kbd class="bg-light text-dark">ESC</kbd> Bersihkan / Tutup</div>
            </div>
        </div>
        <style>body { padding-bottom: 40px; }</style>
        <?php
    }
}