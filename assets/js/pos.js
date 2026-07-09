// ==========================================
// INISIALISASI & GLOBAL VARIABLE
// ==========================================
let cart = [];

function printReceipt(id) {
    if (!id) {
        alert("ID Transaksi tidak ditemukan.");
        return;
    }
    // const printUrl = 'index.php?page=pos&action=print_invoice&id=' + id;
    const printUrl = 'index.php?page=pos&action=print_invoice_pdf&id=' + id;
    window.open(printUrl, '_blank');
}

$(document).ready(function() {
    let dateTimeout = null;
    let invoiceTimeout = null;
    const jedaMengetik = 500;
    
    const isViewOnly = (typeof IS_VIEW_MODE !== 'undefined' && IS_VIEW_MODE === true);

    let isEditMode = false;
    let editingSaleId = null;
    let lastUpdatedAt = '';

    function getQtyInCart(itemId, excludeIndex = -1) {
        let total = 0;
        cart.forEach((item, index) => {
            if (item.id === itemId && index !== excludeIndex) {
                total += item.qty;
            }
        });
        return total;
    }

    function resetToNewTransaction() {
        isEditMode = false;
        editingSaleId = null;
        lastUpdatedAt = '';
        cart = [];
        
        $('#buyerId').val('');
        $('#buyerNameDisplay').prop('disabled', false).val('');
        $('#salesDate').prop('disabled', false).val(new Date().toISOString().split('T')[0]);
        $('#warehouseSelect').prop('disabled', false).val('1');
        $('#salesType').prop('disabled', false).val('SLS');
        $('#invoiceNo').prop('disabled', false).val('');
		$('#btnFindInvoice').show();
		$('#btnFindBuyer').show();
		$('#info-state').empty();
        
        $('#btnCancelEditInvoice').hide();
        $('#btnClearBuyer').hide();
        
        renderCart();
    }

    // ==========================================
    // 0. FITUR CARI & EDIT INVOICE (MODAL)
    // ==========================================
    const allowedRoles = ['all'];
    const isUserAllowed = typeof USER_ROLE !== 'undefined' && allowedRoles.includes(USER_ROLE.toLowerCase());

    if (!isUserAllowed) $('#salesType option[value="EXP"]').prop('disabled', true).hide();

    if (isUserAllowed && !isViewOnly) {
        
        function setInvoiceEmpty() {
            $('#invoiceTableBody').html(`<tr><td colspan="4" class="text-center text-muted py-4"><i class="fa-solid fa-magnifying-glass fs-3 mb-2 d-block opacity-25"></i>Ketik nomor invoice atau nama pelanggan...</td></tr>`);
        }

        function loadModalInvoices(keyword = '') {
            let tbody = $('#invoiceTableBody');
            tbody.html('<tr><td colspan="4" class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i> Mencari Invoice...</td></tr>');
            
            $.ajax({
                url: 'index.php?page=pos',
                type: "POST",
                dataType: 'json',
                data: { action: 'get_invoice_list', keyword: keyword }, 
                success: function(response) {
                    tbody.empty();
                    let data = response.data || [];
                    if (data.length === 0) {
                        tbody.append('<tr><td colspan="4" class="text-center text-muted py-4">Invoice tidak ditemukan atau tidak dapat diedit.</td></tr>');
                        return;
                    }
                    data.forEach(item => {
                        let total = formatRupiah ? formatRupiah(parseFloat(item.grand_total || 0)) : item.grand_total;
                        tbody.append(`
                            <tr style="font-size: 13px;">
                                <td class="ps-3">
                                    <span class="fw-bold d-block text-dark">${item.invoice_no}</span>
                                    <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i>${item.sales_date}</small>
                                </td>
                                <td class="align-middle">
                                    <span class="fw-semibold text-dark d-block">${item.buyer_name}</span>
                                    <small class="text-muted">${item.buyer_code}</small>
                                </td>
                                <td class="text-end align-middle fw-bold text-primary">${total}</td>
                                <td class="text-center align-middle">
                                    <button class="btn btn-sm btn-outline-warning btn-pilih-invoice" data-id="${item.id}">
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

        $('#invoiceModal').on('show.bs.modal', function () {
            $('#modalSearchInvoice').val(''); 
            $('#btnClearInvoiceSearch').hide(); 
            setInvoiceEmpty();
        });

        $('#modalSearchInvoice').on('input', function() {
            clearTimeout(invoiceTimeout);
            let keyword = $(this).val().trim();
            if (keyword.length > 0) $('#btnClearInvoiceSearch').show(); else $('#btnClearInvoiceSearch').hide();
            if (keyword === '') { setInvoiceEmpty(); return; }
            
            invoiceTimeout = setTimeout(() => loadModalInvoices(keyword), jedaMengetik);
        });

        $('#btnClearInvoiceSearch').click(function() {
            $('#modalSearchInvoice').val('').focus(); 
            $(this).hide(); 
            setInvoiceEmpty();
        });

        $(document).on('click', '.btn-pilih-invoice', function() {
            let saleId = $(this).data('id');

            $.ajax({
                url: 'index.php?page=pos',
                type: 'POST',
                dataType: 'json',
                data: { action: 'search_invoice_detail', id: saleId },
                success: function(res) {
                    if (res.status === 'success' && res.data) {                        
                        const header = res.data.header;
                        const items = res.data.items;
                        
                        isEditMode = true;
                        editingSaleId = header.id;
                        lastUpdatedAt = header.updated_at || '';
                        
                        // Set header (Disabled)
                        $('#invoiceNo').val(header.invoice_no).prop('disabled', true);
                        $('#btnCancelEditInvoice').show(); 
                        
                        $('#buyerId').val(header.buyer);
                        $('#buyerNameDisplay').val(header.buyer_name + ' - ' + header.buyer_code).prop('disabled', true);
                        $('#salesDate').val(header.sales_date).prop('disabled', true);
                        $('#warehouseSelect').val(header.warehouse).prop('disabled', true);
                        $('#salesType').val(header.sale_type).prop('disabled', true);
                        $('#btnClearBuyer').hide();
                        $('#btnFindBuyer').hide();

                        // Setup Cart (Dengan penyesuaian stok lama)
                        cart = items.map(function(item) {
                            let hargaAsli = parseFloat(item.unit_price || 0);
                            let qtyLama = parseFloat(item.item_qty || 0);
                            let stokGudangSaatIni = parseFloat(item.current_stock || 0);

                            return {
                                id: item.item_id,
                                kode: item.item_code,
                                nama: item.item_name,
                                uom: item.item_uom,
                                harga_asli: hargaAsli,
                                harga: (header.sales_type === 'EXP') ? 0 : hargaAsli,
                                qty: qtyLama, 
                                stok: stokGudangSaatIni + qtyLama
                            };
                        });

                        renderCart();
                        $('#invoiceModal').modal('hide');
						$('#info-state').html(`Mode Edit Aktif untuk Invoice: ${header.invoice_no}. Informasi header dikunci.`);
						$('#btnFindInvoice').hide();
                        if(typeof showNotification !== 'undefined') showNotification(`Mode Edit Aktif untuk Invoice: ${header.invoice_no}. Informasi header dikunci.`, 'info');
                    } else {
                        if(typeof showNotification !== 'undefined') showNotification('Gagal memuat detail data invoice.', 'danger');
                    }
                }
            });
        });

        $('#btnCancelEditInvoice').click(function() {
            if (confirm("Apakah Anda ingin membatalkan pengeditan invoice ini dan kembali ke transaksi baru?")) {
                resetToNewTransaction();
            }
        });
    }

    // ==========================================
    // 1. VALIDASI TANGGAL & GUDANG
    // ==========================================
    $('#salesDate').on('input change', function() {
        if (isEditMode) return; // Jika edit, abaikan validasi karena field disabled
        clearTimeout(dateTimeout);
        const selectedDateStr = $(this).val();
        if (!selectedDateStr) return; 

        dateTimeout = setTimeout(function() {            
            let parts = selectedDateStr.split('-');
            if (parts.length !== 3) return;
            let year = parseInt(parts[0], 10), month = parseInt(parts[1], 10) - 1, day = parseInt(parts[2], 10);
            if (year < 2000) return;

            let selectedDate = new Date(year, month, day); selectedDate.setHours(0, 0, 0, 0);
            let minDate = new Date(); minDate.setDate(minDate.getDate()); minDate.setHours(0, 0, 0, 0);

            if (selectedDate.getTime() < minDate.getTime()) {
                if(typeof showNotification !== 'undefined') showNotification('Tanggal tidak valid! Maksimal 14 hari ke belakang.', 'danger');
            }            
        }, jedaMengetik);
    });

    $('#warehouseSelect').on('change', function() {
        if (cart.length > 0) {
            if(typeof showNotification !== 'undefined') showNotification('Gudang diubah! Keranjang dikosongkan untuk menyesuaikan stok gudang baru.', 'warning');
            cart = [];
            renderCart();
        }
    });

    // ==========================================
    // 2. RENDER KERANJANG UTAMA
    // ==========================================
    if (isViewOnly && typeof VIEW_DATA_ITEMS !== 'undefined') {
        const currentSalesType = document.getElementById('salesType').value;
        cart = VIEW_DATA_ITEMS.map(function(item) {
            return {
                id: item.item_id || item.id,
                kode: item.item_code,
                nama: item.item_name,
                uom: item.item_uom,
                harga_asli: parseFloat(item.unit_price || 0),
                harga: (currentSalesType === 'EXP') ? 0 : parseFloat(item.unit_price || 0),
                qty: parseFloat(item.item_qty || 1), 
                stok: 999999
            };
        });
        renderCart();
    }

    function renderCart() {
        let tbody = $('#cartTableBody');
        let summaryCards = $('#mainCartSummaryCards');
        
        tbody.empty();
        summaryCards.empty();
        
        let subtotal = 0;

        if (cart.length === 0) {
            tbody.append(`
                <tr id="emptyCartRow">
                    <td colspan="${isViewOnly ? 4 : 5}" class="text-center text-muted py-5 border-bottom-0">
                        <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                        ${isViewOnly ? 'Memuat data transaksi...' : 'Belum ada barang di keranjang.<br><small>Silakan klik tombol tambah di atas.</small>'}
                    </td>
                </tr>
            `);
            summaryCards.html('<small class="text-muted text-center py-2">Belum ada item terpilih</small>');
        } else {
            let groupedSummary = {};

            cart.forEach((item, index) => {
                let totalHarga = item.harga * item.qty;
                subtotal += totalHarga;

                if (!groupedSummary[item.id]) {
                    groupedSummary[item.id] = { 
                        nama: item.nama, 
                        uom: item.uom, 
                        totalQty: 0, 
                        totalHargaGroup: 0 
                    };
                }
                groupedSummary[item.id].totalQty += item.qty;
                groupedSummary[item.id].totalHargaGroup += totalHarga;

                let qtyHTML = isViewOnly 
                    ? `<span class="fw-bold fs-6 text-dark">${item.qty} <small class="fw-normal text-muted">${item.uom}</small></span>`
                    : `
                        <div class="d-flex justify-content-center align-items-center gap-1">
                            <button class="btn btn-sm btn-light border btn-action btn-minus" data-index="${index}"><i class="fa-solid fa-minus" style="font-size: 10px;"></i></button>
                            <input type="number" class="form-control form-control-sm text-center qty-input fw-bold" style="width: 70px;" value="${item.qty}" min="1" data-index="${index}">
                            <button class="btn btn-sm btn-light border btn-action btn-plus" data-index="${index}"><i class="fa-solid fa-plus" style="font-size: 10px;"></i></button>
                            <span class="text-muted small ms-1 fw-semibold text-start" style="min-width: 30px;">${item.uom}</span>
                        </div>
                    `;

                let actionHTML = isViewOnly ? '' : `
                        <td class="text-center align-middle pe-3">
                            <button class="btn btn-sm btn-outline-danger btn-action btn-remove" data-index="${index}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    `;

                let formattedHarga = typeof formatRupiah !== 'undefined' ? formatRupiah(item.harga) : item.harga;
                let formattedTotal = typeof formatRupiah !== 'undefined' ? formatRupiah(totalHarga) : totalHarga;

                tbody.append(`
                    <tr style="font-size: 13px;">
                        <td class="ps-4">
                            <div class="fw-bold text-dark">${item.nama}</div>
                            <small class="text-muted" style="font-size: 11px;">${item.kode}</small>
                        </td>
                        <td class="text-center align-middle text-muted">${formattedHarga}</td>
                        <td class="text-center align-middle">${qtyHTML}</td>
                        <td class="text-end align-middle fw-bold text-dark pe-4">${formattedTotal}</td>
                        ${actionHTML}
                    </tr>
                `);
            });

            Object.values(groupedSummary).forEach(group => {
                let formattedGroupTotal = typeof formatRupiah !== 'undefined' ? formatRupiah(group.totalHargaGroup) : group.totalHargaGroup;
                let cardHTML = `
                    <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center" style="font-size: 12px;">
                        <div class="text-truncate" style="max-width: 60%;">
                            <span class="fw-bold text-dark d-block text-truncate" title="${group.nama}">${group.nama}</span>
                            <small class="text-muted fw-semibold">${group.totalQty} ${group.uom}</small>
                        </div>
                        <span class="fw-bold text-primary">${formattedGroupTotal}</span>
                    </div>
                `;
                summaryCards.append(cardHTML);
            });
        }

        let formattedSubtotal = typeof formatRupiah !== 'undefined' ? formatRupiah(subtotal) : subtotal;
        $('#summarySubtotal').text(formattedSubtotal);
        $('#summaryTotal').text(formattedSubtotal);
    }

    if (!isViewOnly) {

        // ==========================================
        // 3. MODAL PELANGGAN (BUYER)
        // ==========================================
        function setBuyerEmpty() {
            $('#buyerTableBody').html(`<tr><td colspan="2" class="text-center text-muted py-4"><i class="fa-solid fa-magnifying-glass fs-3 mb-2 d-block opacity-25"></i>Ketik nama pelanggan...</td></tr>`);
        }

        function loadModalBuyers(keyword = '') {
            let tbody = $('#buyerTableBody');
            tbody.html('<tr><td colspan="2" class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-2"></i> Mencari...</td></tr>');
            
            $.ajax({
                url: 'index.php?page=pos',
                type: "POST", dataType: 'json',
                data: { action: 'get_buyers', keyword: keyword },
                success: function(response) {
                    tbody.empty();
                    let data = response.data || [];
                    if (data.length === 0) {
                        tbody.append('<tr><td colspan="2" class="text-center text-muted py-4">Pelanggan tidak ditemukan.</td></tr>');
                        return;
                    }
                    data.forEach(item => {
                        let isExpHTML = item.buyer_status === 'EXP' ? '<span class="badge bg-warning ms-2">Bisa EXP</span>' : '';
                        tbody.append(`
                            <tr>
                                <td class="ps-3 align-middle">
                                    <span class="fw-bold d-block text-dark">${item.buyer_name} ${isExpHTML}</span>
                                    <small class="text-muted">${item.buyer_code}</small>
                                </td>
                                <td class="text-center align-middle pe-3">
                                    <button class="btn btn-sm btn-outline-primary btn-pilih-buyer" 
                                            data-id="${item.id}" 
                                            data-nama="${item.buyer_name}" 
                                            data-kode="${item.buyer_code}" 
                                            data-isexp="${item.buyer_status}">
                                        Pilih
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }
            });
        }

        $('#buyerModal').on('show.bs.modal', function () {
            $('#modalSearchBuyer').val(''); $('#btnClearBuyerSearch').hide(); setBuyerEmpty();
        });

        let buyerTimeout = null;
        $('#modalSearchBuyer').on('input', function() {
            clearTimeout(buyerTimeout);
            let keyword = $(this).val().trim();
            if (keyword.length > 0) $('#btnClearBuyerSearch').show(); else $('#btnClearBuyerSearch').hide();
            if (keyword === '') { setBuyerEmpty(); return; }
            buyerTimeout = setTimeout(() => loadModalBuyers(keyword), 500);
        });

        $('#btnClearBuyerSearch').click(function() {
            $('#modalSearchBuyer').val('').focus(); $(this).hide(); setBuyerEmpty();
        });

        $(document).on('click', '.btn-pilih-buyer', function() {
            let id = $(this).data('id');
            let nama = $(this).data('nama');
            let kode = $(this).data('kode');
            let isExp = $(this).data('isexp');

            $('#buyerId').val(id);
            $('#buyerNameDisplay').val(nama + ' - ' + kode); 
            $('#btnClearBuyer').show();
            
            if (isExp === 'EXP' && isUserAllowed) {
                $('#salesType option[value="EXP"]').prop('disabled', false);
            } else {
                if ($('#salesType').val() === 'EXP') {
                    $('#salesType').val('SLS').trigger('change');
                    if(typeof showNotification !== 'undefined') showNotification('Expense tidak tersedia untuk Pelanggan ini. Kembali ke Normal Sales.', 'warning');
                }
                $('#salesType option[value="EXP"]').prop('disabled', true);
            }
            $('#buyerModal').modal('hide');
        });

        $('#btnClearBuyer').click(function() {
            $('#buyerId').val('');
            $('#buyerNameDisplay').val('');
            $(this).hide();

            $('#salesType').val('SLS').trigger('change');
            $('#salesType option[value="EXP"]').prop('disabled', true);
        });
        
        $('#buyerModal').on('hide.bs.modal', function () {
            $('#buyerNameDisplay').focus(); 
        });

        // ==========================================
        // 4. MODAL BARANG (ITEM)
        // ==========================================
        let itemDraft = [];

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
                    <div class="card shadow-sm border-0 mb-2">
                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                            <div class="me-2 text-truncate" style="font-size: 12px; max-width: 170px;">
                                <div class="fw-bold text-dark">${item.nama}</div>
                                <span class="text-muted" style="font-size: 11px;">${item.kode}</span>
                            </div>
                            <button class="btn btn-sm btn-light text-danger border btn-remove-item-draft" data-id="${item.id}">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                `);
            });
        }

        function setItemEmpty() {
            $('#itemTableBody').html(`<tr><td colspan="4" class="text-center text-muted py-5"><i class="fa-solid fa-magnifying-glass fs-3 mb-3 d-block opacity-25"></i>Silakan ketik nama atau kode barang...</td></tr>`);
        }

        function loadModalItems(keyword = '') {
            let tbody = $('#itemTableBody');
            tbody.html('<tr><td colspan="4" class="text-center text-muted py-5"><i class="fa-solid fa-spinner fa-spin me-2 fs-3 mb-2 d-block opacity-50"></i> Mencari...</td></tr>');

            $.ajax({
                url: 'index.php?page=pos',
                type: 'POST', dataType: 'json',
                data: { action: 'get_products', keyword: keyword, warehouse: $('#warehouseSelect').val() },
                success: function(response) {
                    tbody.empty();
                    const data = response.data || [];
                    let filtered = data.filter(item => parseFloat(item.current_stock) > 0);

                    if (filtered.length === 0) {
                        tbody.append('<tr><td colspan="4" class="text-center text-muted py-5">Barang dengan stok tersedia tidak ditemukan.</td></tr>');
                        return;
                    }

                    filtered.forEach(item => {
                        let isChecked = itemDraft.find(d => d.id == item.id) ? 'checked' : '';
                        let stok = parseFloat(item.current_stock);
                        let hargaAsli = parseFloat(item.unit_price || 0);
                        let formattedHarga = typeof formatRupiah !== 'undefined' ? formatRupiah(hargaAsli) : hargaAsli;

                        tbody.append(`
                            <tr>
                                <td class="text-muted fw-mono align-middle ps-3">${item.item_code}</td>
                                <td class="align-middle">
                                    <span class="fw-bold text-dark d-block">${item.item_name}</span>
                                    <small class="badge bg-light text-secondary border mt-1">${formattedHarga}</small>
                                </td>
                                <td class="text-center align-middle fw-bold text-primary">${stok} ${item.item_uom || 'Pcs'}</td>
                                <td class="text-center align-middle pe-3">
                                    <input type="checkbox" class="form-check-input item-chk border-secondary shadow-sm m-0" 
                                           style="width: 24px; height: 24px; cursor: pointer;" 
                                           data-id="${item.id}" data-kode="${item.item_code}" data-nama="${item.item_name}" 
                                           data-uom="${item.item_uom || 'Pcs'}" data-harga="${hargaAsli}" data-stok="${stok}" ${isChecked}>
                                </td>
                            </tr>
                        `);
                    });
                }
            });
        }

        $('#itemModal').on('show.bs.modal', function () {
            $('#modalSearchItem').val(''); $('#btnClearItemSearch').hide();
            itemDraft = []; setItemEmpty(); renderItemSummary();
        });

        let itemTimeout = null;
        $('#modalSearchItem').on('input', function() {
            clearTimeout(itemTimeout);
            let keyword = $(this).val().trim();
            if (keyword.length > 0) $('#btnClearItemSearch').show(); else $('#btnClearItemSearch').hide();
            if (keyword === '') { setItemEmpty(); return; }
            itemTimeout = setTimeout(() => loadModalItems(keyword), 500);
        });

        $('#btnClearItemSearch').click(function() {
            $('#modalSearchItem').val('').focus(); $(this).hide(); setItemEmpty();
        });

        $(document).on('change', '.item-chk', function() {
            let $chk = $(this); let id = $chk.data('id');
            if ($chk.is(':checked')) {
                itemDraft.push({
                    id: id, kode: $chk.data('kode'), nama: $chk.data('nama'), 
                    uom: $chk.data('uom'), harga_asli: parseFloat($chk.data('harga')), stok: parseFloat($chk.data('stok')), qty: 1
                });
            } else itemDraft = itemDraft.filter(i => i.id != id);
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
                if(typeof showNotification !== 'undefined') showNotification('Pilih minimal satu barang!', 'warning'); 
                return; 
            }
            
            let currentSalesType = $('#salesType').val();
            let addedCount = 0;

            itemDraft.forEach(draft => {
                // 1. Cek apakah barang sudah ada di keranjang (cart) utama
                let existingItemInCart = cart.find(c => c.id == draft.id);
                
                // 2. Jika SUDAH ADA, blokir dan berikan notifikasi ke user
                if (existingItemInCart) {
                    if (typeof showNotification !== 'undefined') {
                        showNotification(`Barang ${draft.nama} sudah ada di keranjang utama!`, 'danger');
                    }
                    return; // Skip item ini, lanjut ke item berikutnya di dalam draft loop
                }

                // 3. Jika BELUM ADA, jalankan pengecekan stok normal
                let maxStok = draft.stok;

                if (draft.qty > maxStok) {
                    if(typeof showNotification !== 'undefined') {
                        showNotification(`Gagal: ${draft.nama} melebihi batas stok maksimal (${maxStok}).`, 'warning');
                    }
                } else {
                    // Masukkan sebagai item baru di cart
                    cart.push({
                        ...draft,
                        harga: (currentSalesType === 'EXP') ? 0 : draft.harga_asli
                    });
                    addedCount++;
                }
            });

            $('#itemModal').modal('hide');
            renderCart();
            if (addedCount > 0) {
                if(typeof showNotification !== 'undefined') showNotification(`${addedCount} macam barang disesuaikan.`, 'success');
            }
        });

        $('#itemModal').on('hide.bs.modal', function () {
            $('#search').focus(); 
        });

        // ==========================================
        // 5. INTERAKSI KERANJANG UTAMA & CHECKOUT
        // ==========================================
        $('#salesType').on('change', function() {
            let newType = $(this).val();
            cart.forEach(item => { item.harga = (newType === 'EXP') ? 0 : item.harga_asli; });
            renderCart();    
            if (newType === 'EXP' && typeof showNotification !== 'undefined') showNotification('Tipe EXP dipilih: Harga barang diatur ke Rp 0', 'info');
        });

        $(document).on('click', '.btn-plus', function() {
            let index = $(this).data('index');
            let selectedItem = cart[index];            
            let totalQtyAllRows = getQtyInCart(selectedItem.id);
            
            if (totalQtyAllRows + 1 > selectedItem.stok) {
                if(typeof showNotification !== 'undefined') showNotification(`Stok maksimal di gudang hanya ${selectedItem.stok}!`, 'warning');
            } else { 
                cart[index].qty += 1; 
                renderCart(); 
            }
        });

        $(document).on('click', '.btn-minus', function() {
            let index = $(this).data('index');
            if (cart[index].qty > 1) { cart[index].qty -= 1; renderCart(); }
            else { cart.splice(index, 1); renderCart(); }
        });

        $(document).on('change', '.qty-input', function() {
            let index = $(this).data('index'); 
            let val = parseFloat($(this).val());
            let selectedItem = cart[index];

            if (isNaN(val) || val <= 0) { 
                cart[index].qty = 1; 
                if(typeof showNotification !== 'undefined') showNotification('Kuantitas tidak valid!', 'warning'); 
                val = 1;
            } 
            
            let otherRowsQty = getQtyInCart(selectedItem.id, index);
            let sisaStokTersedia = selectedItem.stok - otherRowsQty;

            if (val > sisaStokTersedia) {
                cart[index].qty = sisaStokTersedia > 0 ? sisaStokTersedia : 1; 
                if(typeof showNotification !== 'undefined') showNotification(`Sisa stok yang bisa Anda input di baris ini hanya ${sisaStokTersedia} (Total Stok: ${selectedItem.stok})!`, 'warning');
            } else {
                cart[index].qty = val;
            }
            
            renderCart();
        });

        $(document).on('click', '.btn-remove', function() {
            cart.splice($(this).data('index'), 1); renderCart();
        });

        $('#btnClearCart').click(function() {
            if (cart.length > 0 && !confirm("Batalkan transaksi dan bersihkan form?")) return;
            resetToNewTransaction();
        });
        
        let modalCheckoutEl = document.getElementById('modalCheckoutSuccess');
        const modalCheckoutSuccess = modalCheckoutEl ? new bootstrap.Modal(modalCheckoutEl) : null;
        let currentSaleId = null;

        $('#btnCheckout').click(function() {
            if (cart.length === 0) { if(typeof showNotification !== 'undefined') showNotification('Keranjang kosong!', 'danger'); return; }

            let buyerId = $('#buyerId').val();
            if (!buyerId) { if(typeof showNotification !== 'undefined') showNotification('Harap pilih Pelanggan (Buyer)!', 'danger'); return; }

            let currentSalesType = $('#salesType').val(); 
            if (currentSalesType === 'EXP' && !isUserAllowed) {
                if (typeof showNotification !== 'undefined') showNotification('Akses ditolak! Tipe penjualan EXP khusus untuk hak akses ALL.', 'danger');else alert('Akses ditolak! Tipe penjualan EXP khusus untuk hak akses ALL.');
                return; // Menghentikan AJAX
            }

            let btn = $(this);
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: 'index.php?page=pos', type: 'POST', dataType: 'json',
                data: {
                    action: 'checkout', 
                    buyer_id: buyerId,
                    warehouse: $('#warehouseSelect').val(), 
                    sales_date: $('#salesDate').val(),
                    sales_type: $('#salesType').val(), 
                    cart: JSON.stringify(cart),
                    is_edit_mode: isEditMode ? 1 : 0,
                    sale_id: editingSaleId,
                    last_updated_at: lastUpdatedAt
                },
                success: function(res) {
                    if (res.status === 'success') {
                        if(typeof showNotification !== 'undefined') showNotification(res.message || 'Transaksi berhasil!', 'success');
                        resetToNewTransaction();
                        currentSaleId = res.data.sale_id;
                        if(modalCheckoutSuccess) modalCheckoutSuccess.show();
                    } else {
                        if(typeof showNotification !== 'undefined') showNotification(res.message || 'Gagal menyimpan', 'danger');
                    }
                },
                error: function(xhr) {
                    let err = 'Terjadi kesalahan sistem.';
                    if (xhr.responseJSON && xhr.responseJSON.message) err = xhr.responseJSON.message;
                    if(typeof showNotification !== 'undefined') showNotification(err, 'danger');
                },
                complete: function() { btn.prop('disabled', false).html('<i class="fa-solid fa-check-double me-2"></i> Save Transaksi'); }
            });
        });
        
        $('#btnPrintInvoice').click(function() {
            if (currentSaleId) {
                // const printUrl = 'index.php?page=pos&action=print_invoice&id=' + currentSaleId;
                const printUrl = 'index.php?page=pos&action=print_invoice_pdf&id=' + currentSaleId;
                window.open(printUrl, '_blank');
                if(modalCheckoutSuccess) modalCheckoutSuccess.hide();
                currentSaleId = null;
            }
        });

        if(modalCheckoutEl) {
            $(modalCheckoutEl).on('hidden.bs.modal', function () {
                currentSaleId = null;
            });
        }

        // ========================================================
        // 6. FITUR KEYBOARD SHORTCUTS & MANAGEMENT FOCUS (KASIR MODE)
        // ========================================================
        $(document).on('keydown', function(e) {
            // F2 - Buka Modal Tambah Barang
            if (e.key === "F2") {
                e.preventDefault();
                if (!$('#itemModal').is(':visible')) $('#itemModal').modal('show');
            }
            // F3 - Tambah Barang ke Cart Utama
            if (e.key === "F3") {
                e.preventDefault();
                if ($('#itemModal').is(':visible')) {
                    $('#btnSubmitItems, #btnSubmitModalItems').filter(':visible').click();
                }
            }
            // F1 - Buka Modal Cari Pelanggan
            if (e.key === "F1") {
                e.preventDefault();
                if (!$('#buyerModal').is(':visible') && !isEditMode) $('#buyerModal').modal('show');
            }
            // F8 - Buka Modal Cari & Edit Invoice
            if (e.key === "F8") {
                e.preventDefault();
                if (isUserAllowed && !$('#invoiceModal').is(':visible')) $('#invoiceModal').modal('show');
            }
            // F4 - Simpan Transaksi (Save)
            if (e.key === "F4") {
                e.preventDefault();
                // Jalankan checkout hanya jika tidak ada modal yang sedang terbuka
                if ($('.modal.show').length === 0) $('#btnCheckout').click();
            }
            // ESCAPE - Tutup Modal atau Clear Form
            if (e.key === "Escape") {
                if ($('.modal.show').length > 0) {
                    $('.modal').modal('hide');
                } else {
                    $('#btnClearCart').click();
                }
            }
        });

        // Otomatis Pindahkan Kursor (Fokus) ke Input Pencarian saat Modal Terbuka
        $('#itemModal').on('shown.bs.modal', function () { $('#modalSearchItem').focus(); });

        $('#buyerModal').on('shown.bs.modal', function () { $('#modalSearchBuyer').focus(); });

        $('#invoiceModal').on('shown.bs.modal', function () { $('#modalSearchInvoice').focus(); });

        // ========================================================
        // HOTKEYS: NAVIGASI PANAH & ENTER DI DALAM MODAL (DENGAN AUTO-SCROLL)
        // ========================================================
        $('#modalSearchBuyer, #modalSearchItem, #modalSearchInvoice').on('keydown', function(e) {
            let $modal = $(this).closest('.modal');
            let $tbody = $modal.find('tbody');
            
            // Ambil hanya baris data asli (abaikan baris loading / text-center)
            let $rows = $tbody.find('tr').filter(function() {
                return !$(this).attr('id') && !$(this).hasClass('text-center') && $(this).find('td').length > 1;
            });

            if ($rows.length === 0) return;

            // Cari baris yang saat ini sedang aktif di-highlight biru
            let $activeRow = $tbody.find('tr.table-primary');
            let activeIndex = $rows.index($activeRow);

            // 1. TOMBOL PANAH BAWAH (↓)
            if (e.key === "ArrowDown") {
                e.preventDefault(); 
                $rows.removeClass('table-primary'); // Bersihkan warna biru lama
                
                if (activeIndex === -1 || activeIndex === $rows.length - 1) {
                    $rows.first().addClass('table-primary'); // Jika belum ada atau di paling akhir, balik ke baris pertama
                } else {
                    $rows.eq(activeIndex + 1).addClass('table-primary'); // Turun 1 baris
                }
                
                // Jalankan auto-scroll ke baris baru yang aktif
                scrollToRow($tbody.find('tr.table-primary'));
            } 
            
            // 2. TOMBOL PANAH ATAS (↑)
            else if (e.key === "ArrowUp") {
                e.preventDefault();
                $rows.removeClass('table-primary');
                
                if (activeIndex === -1 || activeIndex === 0) {
                    $rows.last().addClass('table-primary'); // Jika belum ada atau di paling atas, lompat ke baris paling bawah
                } else {
                    $rows.eq(activeIndex - 1).addClass('table-primary'); // Naik 1 baris
                }
                
                // Jalankan auto-scroll ke baris baru yang aktif
                scrollToRow($tbody.find('tr.table-primary'));
            } 
            
            // 3. TOMBOL ENTER (↵)
            else if (e.key === "Enter") {
                e.preventDefault();
                if ($activeRow.length > 0) {
                    let $selectBtn = $activeRow.find('.btn-pilih-buyer, .btn-pilih-invoice, .item-chk');
                    
                    if ($selectBtn.length > 0) {
                        if ($selectBtn.is(':checkbox')) {
                            // Khusus modal barang: Enter untuk Centang/Uncentang checkbox
                            $selectBtn.prop('checked', !$selectBtn.prop('checked')).trigger('change');
                        } else {
                            // Khusus modal Buyer & Invoice: Langsung pilih/eksekusi
                            $selectBtn.click();
                        }
                    }
                }
            }
        });

        // RESET HIGHLIGHT SAAT KASIR MENGETIK ULANG KEYWORD BARU
        $('#modalSearchBuyer, #modalSearchItem, #modalSearchInvoice').on('input', function() {
            $(this).closest('.modal').find('tbody tr').removeClass('table-primary');
        });

        // FUNGSI UTAMA AUTO-SCROLL (BERDASARKAN KOORDINAT OFFSET)
        function scrollToRow($row) {
            if ($row.length === 0) return;

            // Deteksi container mana yang memicu scrollbar (biasanya .table-responsive atau .modal-body)
            let $container = $row.closest('.table-responsive').length ? $row.closest('.table-responsive') : $row.closest('.modal-body');
            if (!$container.length) return;

            // Ambil batas koordinat container penampung scroll
            let containerTop = $container.offset().top;
            let containerBottom = containerTop + $container.innerHeight();

            // Ambil batas koordinat baris tabel (.table-primary) yang dituju
            let rowTop = $row.offset().top;
            let rowBottom = rowTop + $row.outerHeight();

            // JIKA BARIS BERADA DI BAWAH BATAS VIEWPORT (Scroll Turun)
            if (rowBottom > containerBottom) {
                $container.scrollTop($container.scrollTop() + (rowBottom - containerBottom));
            }
            // JIKA BARIS BERADA DI ATAS BATAS VIEWPORT (Scroll Naik)
            else if (rowTop < containerTop) {
                $container.scrollTop($container.scrollTop() - (containerTop - rowTop));
            }
        }
    }
});