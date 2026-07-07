let cart = [];

$(document).ready(function() {
    let dateTimeout = null;
    const jedaMengetik = 500;
    
    const isViewOnly = (typeof IS_VIEW_MODE !== 'undefined' && IS_VIEW_MODE === true);

    // ==========================================
    // STATE UNTUK FITUR EDIT PENERIMAAN
    // ==========================================
    let isEditMode = false;
    let editingReceiveId = null;
    let lastUpdatedAt = '';
    
    // Pengecekan role (Pastikan USER_ROLE disuntikkan dari PHP seperti di POS)
    const allowedRoles = ['ALL', 'SUPERADMIN'];
    const isUserAllowed = typeof USER_ROLE !== 'undefined' && allowedRoles.includes(USER_ROLE.toUpperCase());

    // Fungsi Reset Tampilan ke Transaksi Baru
    function resetToNewReceivement() {        
        isEditMode = false;
        editingReceiveId = null;
        lastUpdatedAt = '';
        cart = [];
        
        $('#docNumber').val('').prop('disabled', false).removeClass('is-invalid is-valid');
        $('#received_by').val('').prop('disabled', false);
        $('#notes').val('').prop('disabled', false);
        $('#warehouseSelect').prop('disabled', false).val('1');
        $('#date_receive').prop('disabled', false).val(new Date().toISOString().split('T')[0]);
        
        $('#btnFindDoc').show();
        $('#info-state').empty();
        $('#docErrorText').remove();
        $('#btnCancelEditReceive').hide();
        $('#btnCheckout').prop('disabled', false).html('<i class="fa-solid fa-check-double me-2"></i> Save Transaksi');
        
        renderCart();
    }

    // ==========================================
    // 0. FITUR CARI & EDIT PENERIMAAN (MODAL)
    // ==========================================
    if (isUserAllowed && !isViewOnly) {
        let receiveSearchTimeout = null;

        function setReceiveModalEmpty() {
            $('#receiveTableBody').html(`<tr><td colspan="4" class="text-center text-muted py-4"><i class="fa-solid fa-magnifying-glass fs-3 mb-2 d-block opacity-25"></i>Ketik Nomor Dokumen atau Nama Penerima...</td></tr>`);
        }

        function loadModalReceivement(keyword = '') {
            let tbody = $('#receiveTableBody');
            tbody.html('<tr><td colspan="4" class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i> Mencari Dokumen...</td></tr>');
            
            $.ajax({
                url: 'index.php?page=receive',
                type: 'POST',
                dataType: 'json',
                data: { action: 'get_receive_list', keyword: keyword },
                success: function(response) {
                    tbody.empty();
                    let data = response.data || [];
                    if (data.length === 0) {
                        tbody.append('<tr><td colspan="4" class="text-center text-muted py-4">Dokumen penerimaan tidak ditemukan.</td></tr>');
                        return;
                    }
                    
                    data.forEach(item => {
                        tbody.append(`
                            <tr style="font-size: 13px;">
                                <td class="ps-3">
                                    <span class="fw-bold d-block text-dark">${item.doc_number}</span>
                                    <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i>${item.date_receive}</small>
                                </td>
                                <td class="align-middle">
                                    <span class="fw-semibold text-dark d-block">${item.received_by}</span>
                                </td>
                                <td class="align-middle text-muted text-truncate" style="max-width: 150px;">
                                    ${item.notes || '-'}
                                </td>
                                <td class="text-center align-middle">
                                    <button class="btn btn-sm btn-outline-warning btn-pilih-receive" data-id="${item.id}">
                                        <i class="fa-solid fa-pencil me-1"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                },
                error: function() {
                    tbody.html('<tr><td colspan="4" class="text-center text-danger py-4">Gagal memuat data dari server.</td></tr>');
                }
            });
        }

        $('#receiveModal').on('show.bs.modal', function () {
            $('#modalSearchReceive').val(''); 
            $('#btnClearReceiveSearch').hide(); 
            setReceiveModalEmpty();
        });

        $('#modalSearchReceive').on('input', function() {
            clearTimeout(receiveSearchTimeout);
            let keyword = $(this).val().trim();
            if (keyword.length > 0) $('#btnClearReceiveSearch').show(); else $('#btnClearReceiveSearch').hide();
            if (keyword === '') { setReceiveModalEmpty(); return; }
            
            receiveSearchTimeout = setTimeout(() => loadModalReceivement(keyword), jedaMengetik);
        });

        $('#btnClearReceiveSearch').click(function() {
            $('#modalSearchReceive').val('').focus(); 
            $(this).hide(); 
            setReceiveModalEmpty();
        });

        $(document).on('click', '.btn-pilih-receive', function() {
            let receiveId = $(this).data('id');

            $.ajax({
                url: 'index.php?page=receive',
                type: 'POST',
                dataType: 'json',
                data: { action: 'search_receive_detail', id: receiveId },
                success: function(res) {
                    if (res.status === 'success' && res.data) {
                        const header = res.data.header;
                        const items = res.data.items;

                        isEditMode = true;
                        editingReceiveId = header.id;
                        lastUpdatedAt = header.updated_at || '';

                        // Set header (Dikunci saat edit)
                        $('#docNumber').val(header.doc_number).prop('disabled', true).removeClass('is-invalid is-valid');
                        $('#docErrorText').remove();
                        $('#btnCancelEditReceive').show(); 
                        
                        $('#received_by').val(header.received_by).prop('disabled', true);
                        $('#notes').val(header.notes).prop('disabled', true);
                        $('#date_receive').val(header.date_receive).prop('disabled', true);
                        $('#warehouseSelect').val(header.warehouse || header.warehouse_id).prop('disabled', true);

                        // Setup Cart
                        cart = items.map(function(item) {
                            return {
                                id: item.item_id,
                                kode: item.item_code,
                                nama: item.item_name,
                                uom: item.item_uom,
                                qty: parseFloat(item.item_qty || 0), 
                                stok: 999999 // Karena penerimaan, stok limit diabaikan
                            };
                        });

                        renderCart();
                        $('#receiveModal').modal('hide');
                        $('#info-state').html(`Mode Edit Aktif untuk Dokumen: ${header.doc_number}. Informasi header dikunci.`);
                        $('#btnFindDoc').hide();
                        if (typeof showNotification !== 'undefined') {
                            showNotification(`Mode Edit Aktif untuk Dokumen: ${header.doc_number}. Informasi header dikunci.`, 'info');
                        }
                    } else {
                        if (typeof showNotification !== 'undefined') showNotification('Gagal memuat detail dokumen.', 'danger');
                    }
                }
            });
        });

        $('#btnCancelEditReceive').click(function() {
            resetToNewReceivement();
        });
    }

    // ==========================================
    // 1. VALIDASI TANGGAL TRANSAKSI
    // ==========================================
    $('#date_receive').on('input change', function() {
        if (isEditMode) return; // Abaikan validasi jika edit mode (karena dikunci)
        clearTimeout(dateTimeout);
        const $this = $(this);
        const selectedDateStr = $this.val();
        if (!selectedDateStr) return; 

        dateTimeout = setTimeout(function() {            
            let parts = selectedDateStr.split('-');
            if (parts.length !== 3) return;

            let year  = parseInt(parts[0], 10);
            let month = parseInt(parts[1], 10) - 1;
            let day   = parseInt(parts[2], 10);

            if (year < 2000) return;

            let selectedDate = new Date(year, month, day);
            selectedDate.setHours(0, 0, 0, 0);

            let minDate = new Date();
            minDate.setDate(minDate.getDate() - 14);
            minDate.setHours(0, 0, 0, 0);

            if (selectedDate.getTime() < minDate.getTime()) {
                if (typeof showNotification !== 'undefined') showNotification('Tanggal tidak valid! Maksimal 14 hari ke belakang.', 'danger');
            }            
        }, jedaMengetik);
    });

    // ==========================================
    // 2. VALIDASI AUTO-CHECK NOMOR DOKUMEN 
    // ==========================================
    $('#docNumber').on('blur', function() {
        if (isEditMode) return; // Jangan cek duplikat jika sedang mode edit

        let docNumber = $(this).val().trim();
        let docInput = $('#docNumber');
        let btnCheckout = $('#btnCheckout');

        $('#docErrorText').remove();

        if (docNumber === '') {
            docInput.removeClass('is-invalid is-valid');
            btnCheckout.prop('disabled', false).html('<i class="fa-solid fa-check-double me-2"></i> Save Transaksi');
            return;
        }

        $.ajax({
            url: 'index.php?page=receive',
            type: 'POST',
            dataType: 'json',
            data: { action: 'check_doc', doc_number: docNumber },
            success: function(response) {
                if (response.data && response.data.status === 'exists') {
                    docInput.removeClass('is-valid').addClass('is-invalid');                    
                    docInput.after(`<small id="docErrorText" class="text-danger mt-1 d-block"><i class="fa-solid fa-circle-exclamation"></i> Nomor dokumen <b>${docNumber}</b> sudah terpakai! Harap gunakan nomor lain.</small>`);
                    btnCheckout.prop('disabled', true).html('<i class="fa-solid fa-ban me-2"></i> Nomor Dokumen Duplikat');
                } else {
                    docInput.removeClass('is-invalid').addClass('is-valid');
                    btnCheckout.prop('disabled', false).html('<i class="fa-solid fa-check-double me-2"></i> Save Transaksi');
                }
            },
            error: function() {
                console.error("Gagal melakukan pengecekan dokumen.");
            }
        });
    });

    $('#docNumber').on('input', function() {
        if (isEditMode) return;
        $(this).removeClass('is-invalid is-valid');
        $('#docErrorText').remove();
        $('#btnCheckout').prop('disabled', false).html('<i class="fa-solid fa-check-double me-2"></i> Save Transaksi');
    });

    // ==========================================
    // MODE VIEW (Melihat histori)
    // ==========================================
    if (isViewOnly) {
        if (typeof VIEW_DATA_ITEMS !== 'undefined') {
            cart = VIEW_DATA_ITEMS.map(function(item) {
                return {
                    id: item.item_id || item.id,
                    kode: item.item_code,
                    nama: item.item_name,
                    uom: item.item_uom || item.uom || '',
                    qty: parseFloat(item.item_qty || item.qty || 1),
                    stok: 999999 
                };
            });
        }
        renderCart();
    }

    // ==========================================
    // FUNGSI RENDER KERANJANG & REKAP UTAMA
    // ==========================================
    function renderCart() {
        let tbody = $('#cartTableBody');
        let summaryWrapper = $('#mainCartSummaryWrapper');
        let summaryCards = $('#mainCartSummaryCards');
        
        tbody.empty();
        summaryCards.empty();
        
        if (cart.length === 0) {
            summaryWrapper.hide(); 
            tbody.append(`
                <tr id="emptyCartRow">
                    <td colspan="${isViewOnly ? 2 : 3}" class="text-center text-muted py-5 border-bottom-0">
                        <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                        ${isViewOnly ? 'Memuat rincian barang...' : 'Belum ada barang di keranjang.<br><small>Silakan klik tombol tambah di atas.</small>'}
                    </td>
                </tr>
            `);
        } else {
            summaryWrapper.show(); 
            
            let groupedSummary = {};

            cart.forEach((item, index) => {
                if (!groupedSummary[item.id]) {
                    groupedSummary[item.id] = { nama: item.nama, uom: item.uom, totalQty: 0 };
                }
                groupedSummary[item.id].totalQty += item.qty;
                
                let qtyHTML = isViewOnly 
                    ? `<span class="fw-bold fs-6 text-dark">${item.qty} <small class="text-muted fw-normal">${item.uom}</small></span>`
                    : `
                        <div class="d-flex justify-content-center align-items-center gap-1">
                            <button class="btn btn-sm btn-light border btn-action btn-minus" data-index="${index}"><i class="fa-solid fa-minus" style="font-size: 10px;"></i></button>
                            <input type="number" class="form-control form-control-sm text-center qty-input fw-bold" style="width: 80px;" value="${item.qty}" min="1" data-index="${index}">
                            <button class="btn btn-sm btn-light border btn-action btn-plus" data-index="${index}"><i class="fa-solid fa-plus" style="font-size: 10px;"></i></button>
                            <span class="text-muted small ms-1 fw-semibold text-start" style="min-width: 40px;">${item.uom}</span>
                        </div>
                    `;

                let actionHTML = isViewOnly 
                    ? '' 
                    : `
                        <td class="text-center align-middle pe-3">
                            <button class="btn btn-sm btn-outline-danger btn-action btn-remove" data-index="${index}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    `;

                let tr = `
                    <tr style="font-size: 13px;">
                        <td class="ps-4">
                            <div class="fw-bold text-dark">${item.nama}</div>
                            <small class="text-muted" style="font-size: 11px;">${item.kode}</small>
                        </td>
                        <td class="text-center align-middle">
                            ${qtyHTML}
                        </td>
                        ${actionHTML}
                    </tr>
                `;
                tbody.append(tr);
            });

            let uniqueItemCount = Object.keys(groupedSummary).length;

            Object.values(groupedSummary).forEach(group => {
                let cardHTML = `
                    <div class="p-2 border rounded bg-white shadow-sm d-flex justify-content-between align-items-center">
                        <span class="text-truncate fw-semibold text-dark" style="font-size: 12px; max-width: 65%;" title="${group.nama}">${group.nama}</span>
                        <span class="badge bg-success" style="font-size: 12px;">${group.totalQty} ${group.uom}</span>
                    </div>
                `;
                summaryCards.append(cardHTML);
            });
            
            summaryCards.append(`
                <div class="mt-2 p-3 border border-primary rounded bg-primary bg-opacity-10">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-primary" style="font-size: 13px;">TOTAL ITEMS:</span>
                        <span class="fw-bold fs-5 text-primary">${uniqueItemCount} <span class="fs-6 fw-normal">Item</span></span>
                    </div>
                </div>
            `);
        }
    }

    if (!isViewOnly) {
        // --- A. FUNGSI KERANJANG UTAMA ---
        $(document).on('click', '.btn-plus', function() {
            let index = $(this).data('index');
            cart[index].qty += 1;
            renderCart();
        });

        $(document).on('click', '.btn-minus', function() {
            let index = $(this).data('index');
            let removedName = cart[index].nama; 
            if (cart[index].qty > 1) {
                cart[index].qty -= 1;
            } else {
                cart.splice(index, 1);
                if (typeof showNotification !== 'undefined') showNotification(`Dihapus: ${removedName}`, 'warning');
            }
            renderCart();
        });

        $(document).on('change', '.qty-input', function() {
            let index = $(this).data('index');
            let val = parseFloat($(this).val());
            
            if (isNaN(val) || val <= 0) {
                cart[index].qty = 1;
                if (typeof showNotification !== 'undefined') showNotification('Kuantitas tidak boleh 0 atau kosong!', 'warning');
            } else {
                cart[index].qty = val;
            }
            renderCart();
        });

        $(document).on('click', '.btn-remove', function() {
            let index = $(this).data('index');
            let removedName = cart[index].nama;
            cart.splice(index, 1);
            renderCart();
            if (typeof showNotification !== 'undefined') showNotification(`Dihapus: ${removedName}`, 'warning');
        });

        $('#btnClearCart').click(function() {
            resetToNewReceivement();
        });

        // --- B. FUNGSI MODAL PENCARIAN BARANG ---
        let modalDraft = [];

        function renderModalSummary() {
            let summaryContainer = $('#modalSummaryList');
            summaryContainer.empty();
            $('#selectedCountBadge').text(modalDraft.length);

            if (modalDraft.length === 0) {
                summaryContainer.html(`
                    <div class="text-center text-muted mt-5 opacity-50 empty-summary">
                        <i class="fa-solid fa-cart-arrow-down fs-1 mb-2"></i><br>
                        <small>Belum ada barang dipilih</small>
                    </div>
                `);
                return;
            }

            modalDraft.forEach((item) => {
                let html = `
                    <div class="card shadow-sm border-0 mb-2">
                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                            <div class="me-2" style="font-size: 12px;">
                                <div class="fw-bold text-dark text-truncate" style="max-width: 170px;">${item.nama}</div>
                                <span class="text-muted" style="font-size: 11px;">${item.kode}</span>
                            </div>
                            <button class="btn btn-sm btn-light text-danger border btn-remove-draft" data-id="${item.id}">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                `;
                summaryContainer.append(html);
            });
        }

        function setModalEmptyState() {
            $('#modalItemTableBody').html(`
                <tr>
                    <td colspan="3" class="text-center text-muted py-5">
                        <i class="fa-solid fa-magnifying-glass fs-3 mb-3 d-block opacity-25"></i>
                        Silakan ketik nama atau kode barang...
                    </td>
                </tr>
            `);
        }

        function loadModalProducts(keyword = '') {
            let tbodyModal = $('#modalItemTableBody');
            let currentWarehouse = $('#warehouseSelect').val();
            tbodyModal.html('<tr><td colspan="3" class="text-center text-muted py-5"><i class="fa-solid fa-spinner fa-spin me-2 fs-3 mb-2 d-block opacity-50"></i> Mencari produk...</td></tr>');

            $.ajax({
                url: 'index.php?page=receive',
                type: 'POST',
                dataType: 'json',
                data: { action: 'get_products', keyword: keyword, warehouse_id: currentWarehouse },
                success: function(response) {
                    tbodyModal.empty();
                    const productsList = response.data || [];

                    if (productsList.length === 0) {
                        tbodyModal.append('<tr><td colspan="3" class="text-center text-muted py-5">Produk tidak ditemukan.</td></tr>');
                        return;
                    }

                    productsList.forEach(function(item) {
                        let existingDraft = modalDraft.find(draft => draft.id == item.id);
                        let isChecked = existingDraft ? 'checked' : '';
                        let safeUOM = item.item_uom || 'Pcs';

                        let tr = `
                            <tr class="modal-item-row">
                                <td class="text-muted fw-mono align-middle ps-3">${item.item_code}</td>
                                <td class="align-middle">
                                    <span class="fw-bold text-dark d-block">${item.item_name}</span>
                                    <small class="badge bg-light text-secondary border mt-1">${safeUOM}</small>
                                </td>
                                <td class="text-center align-middle pe-3">
                                    <input type="checkbox" class="form-check-input item-chk border-secondary shadow-sm m-0" 
                                           style="width: 24px; height: 24px; cursor: pointer;" 
                                           data-id="${item.id}" data-kode="${item.item_code}" 
                                           data-nama="${item.item_name}" data-uom="${safeUOM}" ${isChecked}>
                                </td>
                            </tr>
                        `;
                        tbodyModal.append(tr);
                    });
                }
            });
        }

        $('#itemModal').on('show.bs.modal', function () {
            $('#modalSearchItem').val('');
            $('#btnClearModalSearch').hide();
            modalDraft = [];
            setModalEmptyState();
            renderModalSummary();
        });

        let searchTimeout = null;
        $('#modalSearchItem').on('input', function() {
            clearTimeout(searchTimeout);
            let keyword = $(this).val().trim();
            
            if (keyword.length > 0) $('#btnClearModalSearch').show();
            else $('#btnClearModalSearch').hide();
            
            if (keyword === '') {
                setModalEmptyState();
                return;
            }
            searchTimeout = setTimeout(function() { loadModalProducts(keyword); }, 500);
        });

        $('#btnClearModalSearch').click(function() {
            let searchInput = $('#modalSearchItem');
            searchInput.val('');
            $(this).hide();
            setModalEmptyState();
            searchInput.focus();
        });

        $(document).on('change', '.item-chk', function() {
            let $chk = $(this);
            let id = $chk.data('id');

            if ($chk.is(':checked')) {
                modalDraft.push({
                    id: id, kode: $chk.data('kode'), nama: $chk.data('nama'),
                    uom: $chk.data('uom'), qty: 1
                });
            } else {
                modalDraft = modalDraft.filter(item => item.id != id);
            }
            renderModalSummary();
        });

        $(document).on('click', '.btn-remove-draft', function() {
            let id = $(this).data('id');
            modalDraft = modalDraft.filter(item => item.id != id);
            $(`.item-chk[data-id="${id}"]`).prop('checked', false);
            renderModalSummary();
        });

        $('#btnSubmitModalItems').click(function() {
            if (modalDraft.length === 0) {
                if (typeof showNotification !== 'undefined') showNotification('Pilih minimal satu barang terlebih dahulu!', 'warning');
                return;
            }

            let addedCount = 0;

            modalDraft.forEach(function(draftItem) {
                // 1. Cek apakah barang sudah ada di keranjang (cart) utama
                let existingItemInCart = cart.find(c => c.id == draftItem.id);

                // 2. Jika SUDAH ADA, blokir dan berikan notifikasi error
                if (existingItemInCart) {
                    if (typeof showNotification !== 'undefined') {
                        showNotification(`Barang ${draftItem.nama} sudah ada di keranjang utama!`, 'danger');
                    }
                    return; // Lewati item ini, lanjut ke item berikutnya di loop draft
                }

                // 3. Jika BELUM ADA, masukkan ke dalam keranjang utama
                cart.push({ ...draftItem, stok: 999999 }); 
                addedCount++;
            });

            // Tutup modal dan render ulang tabel keranjang
            $('#itemModal').modal('hide');
            renderCart();

            // Tampilkan notifikasi sukses hanya jika ada barang baru yang berhasil masuk
            if (addedCount > 0) {
                if (typeof showNotification !== 'undefined') {
                    showNotification(`${addedCount} baris barang berhasil ditambahkan.`, 'success');
                }
            }
        });

        // --- C. PROSES CHECKOUT / SIMPAN TRANSAKSI ---
        $('#btnCheckout').click(function() {
            if (cart.length === 0) {
                if (typeof showNotification !== 'undefined') showNotification('Keranjang masih kosong!', 'danger');
                return;
            }

            let receivedBy = $('#received_by').val().trim();            
            if (receivedBy === "" || receivedBy === "0") {
                if (typeof showNotification !== 'undefined') showNotification('Nama Penerima tidak boleh kosong!', 'danger');
                $('#received_by').focus();
                return;
            }

            let btn = $(this);
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: 'index.php?page=receive',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'checkout',
                    received_by: receivedBy,
                    warehouse: $('#warehouseSelect').val(),
                    date_receive: $('#date_receive').val(),
                    notes: $('#notes').val(),
                    cart: JSON.stringify(cart),
                    // Parameter Edit Mode
                    is_edit_mode: isEditMode ? 1 : 0,
                    receive_id: editingReceiveId,
                    last_updated_at: lastUpdatedAt
                },
                success: function(response) {
                    if (response.status === 'success') {
                        if (typeof showNotification !== 'undefined') showNotification(response.message || 'Transaksi berhasil disimpan!', 'success');
                        resetToNewReceivement();
                    } else {
                        if (typeof showNotification !== 'undefined') showNotification(response.message || 'Gagal menyimpan transaksi', 'danger');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Terjadi kesalahan sistem pada server.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (typeof showNotification !== 'undefined') showNotification(errorMessage, 'danger');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-check-double me-2"></i> Save Transaksi');
                }
            });
        });
    }    
});