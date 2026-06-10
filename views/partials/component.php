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
}