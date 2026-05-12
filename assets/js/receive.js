let cart = [];

$(document).ready(function() {

    const isViewOnly = (typeof IS_VIEW_MODE !== 'undefined' && IS_VIEW_MODE === true);

    if (isViewOnly) {
        if (typeof VIEW_DATA_ITEMS !== 'undefined') {
            cart = VIEW_DATA_ITEMS.map(function(item) {
                return {
                    id: item.item_id || item.id,
                    kode: item.item_code,
                    nama: item.item_name,
                    qty: parseFloat(item.item_qty || item.qty || 1),
                    stok: 999999
                };
            });
        }
        renderCart();
    }

    if (!isViewOnly) {
        $('#productSearch').select2({
            placeholder: "Ketik nama atau kode produk...",
            allowClear: true,
            ajax: {
                url: window.location.href,
                type: "POST",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        action: 'get_products',
                        keyword: params.term || '',
                    };
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                id: item.id,
                                text: item.item_code + ' | ' + item.item_name,
                                nama: item.item_name,
                                kode: item.item_code,
                            }
                        })
                    };
                },
                cache: true
            }
        });

        $('#productSearch').on('select2:select', function (e) {
            let data = e.params.data;
            let id = data.id;
            if (!id) return; 

            let nama = data.nama;
            let kode = data.kode;

            let existingItem = cart.find(item => item.id == id);

            if (existingItem) {
                existingItem.qty += 1;
                showNotification(`Qty ${nama} ditambahkan`, 'success');
            } else {
                cart.push({ id: id, kode: kode, nama: nama, qty: 1, stok: 999999 });
                showNotification(`Ditambahkan: ${nama}`, 'success');
            }

            $('#productSearch').val(null).trigger('change');
            renderCart();
        });
    }

    function renderCart() {
        let tbody = $('#cartTableBody');
        tbody.empty();
        
        if (cart.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="${isViewOnly ? 2 : 3}" class="text-center text-muted py-5">
                        <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                        ${isViewOnly ? 'Memuat rincian barang...' : 'Belum ada barang di keranjang.<br><small>Silakan cari dan pilih produk di atas.</small>'}
                    </td>
                </tr>
            `);
        } else {
            cart.forEach((item, index) => {
                
                let qtyHTML = isViewOnly 
                    ? `<span class="fw-bold fs-6 text-dark">${item.qty}</span>`
                    : `
                        <div class="d-flex justify-content-center align-items-center gap-1">
                            <button class="btn btn-sm btn-light border btn-action btn-minus" data-index="${index}"><i class="fa-solid fa-minus" style="font-size: 10px;"></i></button>
                            <input type="number" class="form-control form-control-sm text-center qty-input fw-bold" style="width: 100px;" value="${item.qty}" min="1" data-index="${index}">
                            <button class="btn btn-sm btn-light border btn-action btn-plus" data-index="${index}"><i class="fa-solid fa-plus" style="font-size: 10px;"></i></button>
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
                        <td class="ps-3">
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
        }
    }

    if (!isViewOnly) {
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
            let val = parseInt($(this).val());
            let removedName = cart[index].nama; 

            if (isNaN(val) || val <= 0) {
                cart.splice(index, 1);
                showNotification(`Dihapus: ${removedName}`, 'warning');
            } else cart[index].qty = val;
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
                if (!confirm("Apakah Anda yakin ingin membatalkan transaksi dan membersihkan form?")) {
                    return;
                }
            }
            cart = [];
            $('#productSearch').val(null).trigger('change');
            $('#docNumber').val('');
            $('#received_by').val('');
            $('#warehouseSelect').val('1');
            $('#date_receive').val(new Date().toISOString().split('T')[0]);
            renderCart();
        });

        $('#btnCheckout').click(function() {
            if (cart.length === 0) {
                showNotification('Keranjang masih kosong!', 'danger');
                return;
            }

            let docNumber = $('#docNumber').val().trim();
            let receivedBy = $('#received_by').val().trim();

            if (docNumber === "" || docNumber === "0") {
                showNotification('Nomor Dokumen tidak boleh kosong!', 'danger');
                return;
            }

            if (receivedBy === "" || receivedBy === "0") {
                showNotification('Nama Penerima tidak boleh kosong!', 'danger');
                return;
            }

            let doc_number = docNumber;
            let received_by = receivedBy;
            let warehouse = $('#warehouseSelect').val();
            let transDate = $('#date_receive').val();

            let btn = $(this);
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'checkout',
                    doc_number: doc_number,
                    received_by: received_by,
                    warehouse: warehouse,
                    date_receive: transDate,
                    cart: JSON.stringify(cart)
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showNotification(response.message || 'Transaksi berhasil disimpan!', 'success');
                        cart = [];
                        $('#docNumber').val('');
                        $('#received_by').val('');
                        renderCart();
                    } else {
                        showNotification(response.message || 'Gagal menyimpan transaksi', 'danger');
                    }
                },
                error: function() {
                    showNotification('Terjadi kesalahan sistem pada server.', 'danger');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Simpan Transaksi');
                }
            });
        });
    }

});