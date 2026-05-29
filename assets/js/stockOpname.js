let opnameItems = [];

$(document).ready(function() {
    let tbody = $('#opnameTableBody');
    let itemDraft = [];
    let itemTimeout = null;
    const jedaMengetik = 500;

    // ==========================================
    // 1. OTOMATISASI & VALIDASI UTAMA
    // ==========================================

    $('#opnameWarehouse').on('change', function() {
        if (opnameItems.length > 0) {
            showNotification('Gudang diubah! Daftar item dikosongkan untuk memuat ulang Qty Sistem gudang baru.', 'warning');
            opnameItems = [];
            renderOpnameTable();
        }
    });

    $('#btnCancelOpname').click(function() {
        if (opnameItems.length > 0 && !confirm("Batalkan pengisian formulir stock opname saat ini?")) return;
        
        opnameItems = [];
        $('#opnameNotes').val('');
        $('#opnameDate').val(new Date().toISOString().split('T')[0]);
        renderOpnameTable();
        showNotification('Formulir opname berhasil dibersihkan.', 'info');
    });

    // ==========================================
    // 2. RENDER TABEL FORM UTAMA
    // ==========================================
    function renderOpnameTable() {
        tbody.empty();

        if (opnameItems.length === 0) {
            tbody.append(`
                <tr id="emptyRow">
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                        Belum ada barang yang didaftarkan.<br>
                        <small class="text-muted">Silakan klik tombol <b>Tambah Barang</b> di atas untuk memulai pencatatan.</small>
                    </td>
                </tr>
            `);
            return;
        }

        opnameItems.forEach((item, index) => {
            tbody.append(`
                <tr style="font-size: 13px;">
                    <td class="text-center fw-medium text-muted">${index + 1}</td>
                    <td>
                        <div class="fw-bold text-dark">${item.nama}</div>
                        <small class="text-muted fw-mono" style="font-size: 11px;">${item.kode}</small>
                    </td>
                    <td class="text-center fw-bold text-secondary">${item.qty_system}</td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center">
                            <input type="number" class="form-control form-control-sm text-center qty-physical-input fw-bold border-primary shadow-sm" 
                                   style="width: 110px;" 
                                   value="${item.qty_physical}" 
                                   min="0" 
                                   step="any"
                                   data-index="${index}">
                        </div>
                    </td>
                    <td class="text-center fw-medium text-muted">${item.uom}</td>
                    <td class="text-center pe-4">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item border-0" data-index="${index}" title="Hapus Barang">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    $(document).on('change input', '.qty-physical-input', function() {
        let index = $(this).data('index');
        let val = parseFloat($(this).val());

        if (isNaN(val) || val < 0) {
            opnameItems[index].qty_physical = 0;
            $(this).val(0);
            showNotification('Kuantitas fisik tidak boleh minus atau kosong!', 'warning');
        } else {
            opnameItems[index].qty_physical = val;
        }
    });

    $(document).on('click', '.btn-remove-item', function() {
        let index = $(this).data('index');
        opnameItems.splice(index, 1);
        renderOpnameTable();
    });

    // ==========================================
    // 3. LOGIKA INTERAKSI MODAL BARANG (MULTI-SELECT)
    // ==========================================
    function setItemEmpty() {
        $('#itemTableBody').html(`<tr><td colspan="4" class="text-center text-muted py-5"><i class="fa-solid fa-magnifying-glass fs-3 mb-3 d-block opacity-25"></i>Silakan ketik nama atau kode barang...</td></tr>`);
    }

    function renderItemSummary() {
        let container = $('#modalItemSummary');
        container.empty();
        $('#selectedItemBadge').text(itemDraft.length);

        if (itemDraft.length === 0) {
            container.html(`<div class="text-center text-muted mt-5 opacity-50 empty-summary"><i class="fa-solid fa-cart-arrow-down fs-1 mb-2"></i><br><small>Belum ada barang dipilih</small></div>`);
            return;
        }

        itemDraft.forEach((item) => {
            container.append(`
                <div class="card shadow-sm border-0 mb-2 bg-white">
                    <div class="card-body p-2 d-flex justify-content-between align-items-center">
                        <div class="me-2 text-truncate" style="font-size: 12px; max-width: 170px;">
                            <div class="fw-bold text-dark text-truncate" title="${item.nama}">${item.nama}</div>
                            <span class="text-muted fw-mono" style="font-size: 11px;">${item.kode}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-light text-danger border btn-remove-item-draft py-1 px-2" data-id="${item.id}">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            `);
        });
    }

    function loadModalItems(keyword = '') {
        let tbodyModal = $('#itemTableBody');
        tbodyModal.html('<tr><td colspan="4" class="text-center text-muted py-5"><i class="fa-solid fa-spinner fa-spin me-2 fs-3 mb-2 d-block opacity-50"></i> Mencari produk...</td></tr>');

        $.ajax({
            url: 'index.php?page=stockOpname',
            type: 'POST',
            dataType: 'json',
            data: { 
                action: 'get_products', 
                keyword: keyword, 
                warehouse: $('#opnameWarehouse').val() 
            },
            success: function(response) {
                tbodyModal.empty();
                const data = response.data || [];

                if (data.length === 0) {
                    tbodyModal.append('<tr><td colspan="4" class="text-center text-muted py-5">Barang tidak ditemukan di gudang ini.</td></tr>');
                    return;
                }

                data.forEach(item => {
                    let isExistInMain = opnameItems.find(m => m.id == item.id);
                    let isChecked = itemDraft.find(d => d.id == item.id) ? 'checked' : '';
                    let disabledAttr = isExistInMain ? 'disabled' : '';
                    
                    let stokBuku = parseFloat(item.current_stock || 0);

                    tbodyModal.append(`
                        <tr class="${isExistInMain ? 'table-light opacity-50' : ''}">
                            <td class="text-muted fw-mono align-middle ps-3">${item.item_code}</td>
                            <td class="align-middle">
                                <span class="fw-bold text-dark d-block">${item.item_name}</span>
                                <small class="text-muted" style="font-size:11px;">Harga: ${formatRupiah(item.unit_price)}</small>
                            </td>
                            <td class="text-center align-middle fw-bold text-primary">${stokBuku} ${item.item_uom || 'Pcs'}</td>
                            <td class="text-center align-middle pe-3">
                                <input type="checkbox" class="form-check-input item-chk border-secondary shadow-sm m-0" 
                                       style="width: 22px; height: 22px; cursor: ${isExistInMain ? 'not-allowed' : 'pointer'};" 
                                       data-id="${item.id}" data-kode="${item.item_code}" data-nama="${item.item_name}" 
                                       data-uom="${item.item_uom || 'Pcs'}" data-stok="${stokBuku}" ${isChecked} ${disabledAttr}>
                            </td>
                        </tr>
                    `);
                });
            },
            error: function() {
                tbodyModal.html('<tr><td colspan="4" class="text-center text-danger py-4">Gagal memuat barang dari server.</td></tr>');
            }
        });
    }

    $('#itemModal').on('show.bs.modal', function () {
        $('#modalSearchItem').val(''); 
        $('#btnClearItemSearch').hide();
        itemDraft = []; 
        setItemEmpty(); 
        renderItemSummary();
    });

    $('#itemModal').on('hide.bs.modal', function () {
        $('button[data-bs-target="#itemModal"]').focus(); 
    });

    $('#modalSearchItem').on('input', function() {
        clearTimeout(itemTimeout);
        let keyword = $(this).val().trim();
        
        if (keyword.length > 0) $('#btnClearItemSearch').show(); else $('#btnClearItemSearch').hide();
        if (keyword === '') { setItemEmpty(); return; }
        
        itemTimeout = setTimeout(() => loadModalItems(keyword), jedaMengetik);
    });

    $('#btnClearItemSearch').click(function() {
        $('#modalSearchItem').val('').focus(); 
        $(this).hide(); 
        setItemEmpty();
    });

    $(document).on('change', '.item-chk', function() {
        let $chk = $(this); 
        let id = $chk.data('id');
        
        if ($chk.is(':checked')) {
            itemDraft.push({
                id: id, 
                kode: $chk.data('kode'), 
                nama: $chk.data('nama'), 
                uom: $chk.data('uom'), 
                qty_system: parseFloat($chk.data('stok')),
                qty_physical: parseFloat($chk.data('stok'))
            });
        } else {
            itemDraft = itemDraft.filter(i => i.id != id);
        }
        renderItemSummary();
    });

    $(document).on('click', '.btn-remove-item-draft', function() {
        let id = $(this).data('id');
        itemDraft = itemDraft.filter(i => i.id != id);
        $(`.item-chk[data-id="${id}"]`).prop('checked', false);
        renderItemSummary();
    });

    $('#btnSubmitItems').click(function() {
        if (itemDraft.length === 0) { 
            showNotification('Pilih minimal satu barang terlebih dahulu!', 'warning'); 
            return; 
        }

        itemDraft.forEach(draft => {
            opnameItems.push({ ...draft });
        });

        $('#itemModal').modal('hide');
        renderOpnameTable();
        showNotification(`Berhasil menambahkan ${itemDraft.length} jenis barang ke formulir opname.`, 'success');
    });

    // ==========================================
    // 4. PROSES SUBMIT TRANSACTION (CHECKOUT OPNAME)
    // ==========================================
    $('#btnSaveOpname').click(function() {
        if (opnameItems.length === 0) { 
            showNotification('Gagal menyimpan! Daftar item opname masih kosong.', 'danger'); 
            return; 
        }

        let opnameDate = $('#opnameDate').val();
        if (!opnameDate) {
            showNotification('Harap isi tanggal pelaksanaan opname!', 'danger');
            return;
        }

        if (!confirm('Apakah hasil hitung fisik barang sudah di-kroscek dengan benar dan siap disimpan ke sistem?')) return;

        let $btn = $(this);
        let originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Menyimpan...');

        $.ajax({
            url: 'index.php?page=stockOpname',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'save_opname',
                opname_date: opnameDate,
                warehouse: $('#opnameWarehouse').val(),
                notes: $('#opnameNotes').val().trim(),
                items: JSON.stringify(opnameItems)
            },
            success: function(res) {
                if (res.status === 'success') {
                    showNotification(res.message || 'Data Stock Opname berhasil disimpan sebagai draft!', 'success');
                    
                    // Reset total isi form setelah sukses eksekusi
                    opnameItems = [];
                    $('#opnameNotes').val('');
                    $('#opnameDate').val(new Date().toISOString().split('T')[0]);
                    renderOpnameTable();
                } else {
                    showNotification(res.message || 'Gagal menyimpan transaksi opname.', 'danger');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Terjadi kesalahan sistem saat menghubungi database.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showNotification(errorMsg, 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

});