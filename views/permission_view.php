<?php
class PermissionView {
    public static function render($data = []) {
        extract($data); 
        
        ob_start();
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Role & Permission</h5>
                <p class="text-muted small mb-0">Atur hak akses menu untuk masing-masing role.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <label class="form-label text-muted small fw-bold">PILIH ROLE<span class="text-danger">*</span></label>
                        <select id="selectRole" class="form-select form-select-sm">
                            <option value="">-- Pilih Role --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="loadingIndicator" class="text-primary mt-2 small d-none">
                            <i class="fa-solid fa-circle-notch fa-spin"></i> Memuat data...
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form id="formPermission">
                            <input type="hidden" name="role_id" id="formRoleId" value="">
                            
                            <h6 class="fw-bold border-bottom pb-2 mb-3">Daftar Akses Menu</h6>
                            
                            <div class="row" id="menuCheckboxes">
                                <?php 
                                foreach ($modules as $item): 
                                    if (isset($item['type']) && $item['type'] === 'divider') continue;                                    
                                    $item_key = $item['key'] ?? '';
                                    $path_value = '/' . $item_key;
                                    $name = $item['name'] ?? 'Unknown';
                                    $icon = $item['icon'] ?? 'fa-solid fa-circle';
                                ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input menu-checkbox" type="checkbox" name="paths[]" value="<?= $path_value ?>" id="chk_<?= $item_key ?>" disabled>
                                        <label class="form-check-label" for="chk_<?= $item_key ?>">
                                            <i class="<?= $icon ?> text-muted me-1" style="width: 20px;"></i> <?= htmlspecialchars($name) ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="text-end mt-4 pt-3 border-top">
                                <button type="button" id="btnSelectAll" class="btn btn-sm btn-outline-secondary me-2" disabled>Pilih Semua</button>
                                <button type="submit" id="btnSave" class="btn btn-sm btn-primary px-4" disabled>
                                    <i class="fa-solid fa-save me-1"></i> Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $content = ob_get_clean();
        
        // Panggil file JS eksternal
        $extra_js = '<script src="/maccount/assets/js/permission.js"></script>';

        include __DIR__ . '/layouts/main.php';
    }
}
?>