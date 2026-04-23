// =========================================================================
// POS PAGE LOGIC (assets/js/pos.js)
// =========================================================================

let cart = [];

$(document).ready(function() {
    
    $('#buyerSelect').select2({
        placeholder: "-- Cari Pelanggan --",
        allowClear: true,
        ajax: {
            url: window.location.href,
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'search_buyer',
                    keyword: params.term || ''
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            id: item.id,
                            text: item.buyer_code + ' - ' + item.buyer_name
                        }
                    })
                };
            },
            cache: true
        }
    });

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
                    action: 'search_product',
                    keyword: params.term || ''
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            id: item.id,
                            text: item.item_code + ' - ' + item.item_name + ' (Rp ' + formatRupiah(item.unit_price) + ')',
                            nama: item.item_name,
                            kode: item.item_code,
                            harga: item.unit_price
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
        let harga = parseFloat(data.harga) || 0;

        let existingItem = cart.find(item => item.id == id);

        if (existingItem) {
            existingItem.qty += 1;
        } else {
            cart.push({ id: id, kode: kode, nama: nama, harga: harga, qty: 1 });
        }

        $('#productSearch').val(null).trigger('change');
        renderCart();
        showNotification(`Ditambahkan: ${nama}`, 'success');
    });

    function renderCart() {
        let tbody = $('#cartTableBody');
        tbody.empty();
        let subtotal = 0;

        if (cart.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="fa-solid fa-cart-arrow-down fs-3 mb-2 text-light"></i><br>
                        Belum ada produk di keranjang.
                    </td>
                </tr>
            `);
        } else {
            cart.forEach((item, index) => {
                let totalHarga = item.harga * item.qty;
                subtotal += totalHarga;

                let tr = `
                    <tr style="font-size: 13px;">
                        <td>
                            <div class="fw-bold text-dark">${item.nama}</div>
                            <small class="text-muted" style="font-size: 11px;">${item.kode}</small>
                        </td>
                        <td class="text-center align-middle">${formatRupiah(item.harga)}</td>
                        <td class="text-center align-middle">
                            <div class="d-flex justify-content-center align-items-center gap-1">
                                <button class="btn btn-sm btn-light btn-action btn-minus" data-index="${index}"><i class="fa-solid fa-minus"></i></button>
                                <input type="number" class="qty-input" value="${item.qty}" min="1" data-index="${index}">
                                <button class="btn btn-sm btn-light btn-action btn-plus" data-index="${index}"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </td>
                        <td class="text-end align-middle fw-medium">${formatRupiah(totalHarga)}</td>
                        <td class="text-center align-middle">
                            <button class="btn btn-sm btn-outline-danger btn-action btn-remove" data-index="${index}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(tr);
            });
        }

        let grandTotal = subtotal;

        $('#summarySubtotal').text(formatRupiah(subtotal));
        $('#summaryTotal').text(formatRupiah(grandTotal));
    }

    $(document).on('click', '.btn-plus', function() {
        let index = $(this).data('index');
        cart[index].qty += 1;
        renderCart();
    });

    $(document).on('click', '.btn-minus', function() {
        let index = $(this).data('index');
        if (cart[index].qty > 1) {
            cart[index].qty -= 1;
        } else {
            cart.splice(index, 1);
        }
        renderCart();
    });

     $(document).on('change', '.qty-input', function() {
        let index = $(this).data('index');
        let val = parseInt($(this).val());
        
        if (isNaN(val) || val < 1) {
            val = 1;
        }
        
        cart[index].qty = val;
        renderCart();
    });

    $(document).on('click', '.btn-remove', function() {
        let index = $(this).data('index');
        let removedName = cart[index].nama;
        cart.splice(index, 1);
        renderCart();
        showNotification(`Dihapus: ${removedName}`, 'danger');
    });

    $('#btnCheckout').click(function() {
        if (cart.length === 0) {
            showNotification('Pilih produk terlebih dahulu!', 'danger');
            return;
        }

        let buyerId = $('#buyerSelect').val();
        let warehouse = $('#warehouseSelect').val();

        if (!buyerId) {
            showNotification('Harap pilih Pelanggan (Buyer)!', 'warning');
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).html('Processing...');

        $.ajax({
            url: window.location.href,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'checkout',
                buyer_id: buyerId,
                warehouse: warehouse,
                cart: JSON.stringify(cart)
            },
            success: function(response) {
                if (response.status === 'success') {
                    showNotification(response.message, 'success');
                    cart = [];
                    $('#buyerSelect').val(null).trigger('change');
                    renderCart();
                } else {
                    showNotification(response.message, 'danger');
                }
            },
            error: function() {
                showNotification('Terjadi kesalahan sistem.', 'danger');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa-solid fa-money-bill-wave me-1"></i> Bayar Sekarang');
            }
        });
    });

});