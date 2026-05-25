let cart = [];

$(document).ready(function() {
    
    let dateTimeout = null;
    const jedaMengetik = 500;
    
    // ==========================================
    // 1. VALIDASI TANGGAL TRANSAKSI
    // ==========================================
    $('#date_receive').on('input change', function() {
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

            let today = new Date();
            today.setHours(0, 0, 0, 0);

            let minDate = new Date();
            minDate.setDate(minDate.getDate() - 14);
            minDate.setHours(0, 0, 0, 0);

            if (selectedDate.getTime() < minDate.getTime()) {
                showNotification('Tanggal tidak valid! Maksimal 14 hari ke belakang.', 'danger');
            }            
        }, jedaMengetik);
    });

    const isViewOnly = (typeof IS_VIEW_MODE !== 'undefined' && IS_VIEW_MODE === true);

    // ==========================================
    // 2. MODE VIEW (Melihat histori)
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
    // 3. FUNGSI RENDER KERANJANG & REKAP UTAMA
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

            // A. Gambar Baris Tabel (Kiri)
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

            // B. Gambar Kotak Ringkasan (Kanan)
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
            
            // C. Kotak Grand Total
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


    // ==========================================
    // 4. INTERAKSI INPUT & MODAL (MODE AKTIF)
    // ==========================================
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
                showNotification(`Dihapus: ${removedName}`, 'warning');
            }
            renderCart();
        });

        $(document).on('change', '.qty-input', function() {
            let index = $(this).data('index');
            let val = parseFloat($(this).val());
            
            if (isNaN(val) || val <= 0) {
                cart[index].qty = 1;
                showNotification('Kuantitas tidak boleh 0 atau kosong!', 'warning');
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
            showNotification(`Dihapus: ${removedName}`, 'warning');
        });

        $('#btnClearCart').click(function() {
            if (cart.length > 0) {
                if (!confirm("Apakah Anda yakin ingin membatalkan transaksi dan membersihkan form?")) return;
            }
            cart = [];
            $('#docNumber').val('');
            $('#received_by').val('');
            $('#notes').val('');
            $('#warehouseSelect').val('1');
            $('#date_receive').val(new Date().toISOString().split('T')[0]);
            renderCart();
        });


        // --- B. FUNGSI MODAL PENCARIAN BARANG (SEDERHANA) ---
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
            tbodyModal.html('<tr><td colspan="3" class="text-center text-muted py-5"><i class="fa-solid fa-spinner fa-spin me-2 fs-3 mb-2 d-block opacity-50"></i> Mencari produk...</td></tr>');

            $.ajax({
                url: 'index.php?page=receive',
                type: 'POST',
                dataType: 'json',
                data: { action: 'get_products', keyword: keyword },
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

                        // Kolom Qty dihapus, HTML lebih bersih
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

        // Event Centang Checkbox (Langsung Set Qty = 1)
        $(document).on('change', '.item-chk', function() {
            let $chk = $(this);
            let id = $chk.data('id');

            if ($chk.is(':checked')) {
                modalDraft.push({
                    id: id,
                    kode: $chk.data('kode'),
                    nama: $chk.data('nama'),
                    uom: $chk.data('uom'),
                    qty: 1 // Default otomatis 1
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
                showNotification('Pilih minimal satu barang terlebih dahulu!', 'warning');
                return;
            }

            modalDraft.forEach(function(draftItem) {
                cart.push({ ...draftItem, stok: 999999 }); 
            });

            $('#itemModal').modal('hide');
            renderCart();
            showNotification(`${modalDraft.length} baris barang ditambahkan.`, 'success');
        });


        // --- C. PROSES CHECKOUT / SIMPAN TRANSAKSI ---
        $('#btnCheckout').click(function() {
            if (cart.length === 0) {
                showNotification('Keranjang masih kosong!', 'danger');
                return;
            }

            let docNumber = $('#docNumber').val().trim();
            let receivedBy = $('#received_by').val().trim();

            if (docNumber === "" || docNumber === "0") {
                showNotification('Nomor Dokumen tidak boleh kosong!', 'danger');
                $('#docNumber').focus();
                return;
            }

            if (receivedBy === "" || receivedBy === "0") {
                showNotification('Nama Penerima tidak boleh kosong!', 'danger');
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
                    doc_number: docNumber,
                    received_by: receivedBy,
                    warehouse: $('#warehouseSelect').val(),
                    date_receive: $('#date_receive').val(),
                    notes: $('#notes').val(),
                    cart: JSON.stringify(cart)
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showNotification(response.message || 'Transaksi berhasil disimpan!', 'success');
                        cart = [];
                        $('#docNumber').val('');
                        $('#received_by').val('');
                        $('#notes').val('');
                        renderCart();
                    } else {
                        showNotification(response.message || 'Gagal menyimpan transaksi', 'danger');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Terjadi kesalahan sistem pada server.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showNotification(errorMessage, 'danger');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-check-double me-2"></i> Save Transaksi');
                }
            });
        });
    }

});