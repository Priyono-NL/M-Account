let cart = [];

function printReceipt(id) {
    if (!id) {
        alert("ID Transaksi tidak ditemukan.");
        return;
    }
    const printUrl = 'index.php?page=pos&action=print_invoice&id=' + id;
    // const printUrl = 'index.php?page=pos&action=print_invoice_pdf&id=' + id;
    window.open(printUrl, '_blank');
}

$(document).ready(function() {

    const isViewOnly = (typeof IS_VIEW_MODE !== 'undefined' && IS_VIEW_MODE === true);

    // MODE VIEW ONLY (Melihat Detail Transaksi Riwayat Lama)
    if (isViewOnly) {
        if (typeof VIEW_DATA_ITEMS !== 'undefined') {
            const currentSalesType = document.getElementById('salesType').value;
            
            cart = VIEW_DATA_ITEMS.map(function(item) {
                let hargaDb = parseFloat(item.unit_price || 0);
                return {
                    id: item.item_id || item.id,
                    kode: item.item_code,
                    nama: item.item_name,
                    harga_asli: hargaDb,
                    harga: (currentSalesType === 'EXP') ? 0 : parseFloat(item.unit_price || 0),
                    qty: parseFloat(item.item_qty || 1), 
                    stok: 0
                };
            });
        }
        renderCart();
    }

    // MODE TRANSAKSI AKTIF (Kasir Bisa Berbelanja)
    if (!isViewOnly) {
        // Select2 Pencarian Data Pelanggan/Buyer
        $('#buyerSelect').select2({
            placeholder: "-- Cari Pelanggan --",
            allowClear: true,
            ajax: {
                url: 'index.php?page=pos',
                type: "POST",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { action: 'get_buyers', keyword: params.term || '' };
                },
                processResults: function (response) {
                    // Mengakses data dari standarisasi helper jsonSuccess BaseController
                    const buyersData = response.data || [];
                    return {
                        results: $.map(buyersData, function (item) {
                            return { 
                                id: item.id, 
                                text: item.buyer_code + ' - ' + item.buyer_name,
                                is_exp: item.buyer_status
                            };
                        })
                    };
                },
                cache: true
            }
        });

        $('#buyerSelect').on('select2:select', function (e) {
            let data = e.params.data;
            let canExp = (data.is_exp == 'EXP'); 

            if (canExp) $('#salesType option[value="EXP"]').prop('disabled', false);
            else {
                if ($('#salesType').val() === 'EXP') {
                    $('#salesType').val('SLS').trigger('change');
                    showNotification('Expense tidak tersedia untuk Pelanggan ini. Kembali ke Normal Sales.', 'warning');
                }
                $('#salesType option[value="EXP"]').prop('disabled', true);
            }
        });

        $('#buyerSelect').on('select2:clear', function () {
            $('#salesType option[value="EXP"]').prop('disabled', true);
            if ($('#salesType').val() === 'EXP') $('#salesType').val('SLS').trigger('change');
        });

        // Select2 Pencarian Produk Barang Berdasarkan Gudang Terpilih
        $('#productSearch').select2({
            placeholder: "Ketik nama atau kode produk...",
            allowClear: true,
            ajax: {
                url: 'index.php?page=pos',
                type: "POST",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        action: 'get_products',
                        keyword: params.term || '',
                        warehouse: $('#warehouseSelect').val()
                    };
                },
                processResults: function (response) {
                    // Mengakses data dari standarisasi helper jsonSuccess BaseController
                    const productsData = response.data || [];
                    let filteredData = productsData.filter(function(item) {
                        return parseFloat(item.qty_close) > 0;
                    });
                    return {
                        results: $.map(filteredData, function (item) {
                            return {
                                id: item.id,
                                text: item.item_code + ' | ' + item.item_name + ' | ' + parseFloat(item.qty_close),
                                nama: item.item_name,
                                kode: item.item_code,
                                harga_asli: parseFloat(item.unit_price || 0),
                                harga: ($('#salesType').val() === 'EXP') ? 0 : parseFloat(item.unit_price || 0),
                                stok: parseFloat(item.qty_close)
                            }
                        })
                    };
                },
                cache: true
            }
        });

        $('#productSearch').on('select2:select', function (e) {
            let data = e.params.data;
            let liveSalesType = $('#salesType').val();
            let id = data.id;
            if (!id) return; 

            let nama = data.nama;
            let stok = parseFloat(data.stok) || 0;

            if (stok <= 0) {
                showNotification(`Stok ${nama} di gudang ini kosong/habis!`, 'danger');
                $('#productSearch').val(null).trigger('change');
                return;
            }

            let existingItem = cart.find(item => item.id == id);

            if (existingItem) {
                if (existingItem.qty + 1 > stok) {
                    showNotification(`Maksimal Qty untuk ${nama} adalah ${stok}!`, 'warning');
                } else {
                    existingItem.qty += 1;
                    showNotification(`Qty ${nama} ditambahkan`, 'success');
                }
            } else {
                cart.push({ 
                    id: data.id, 
                    kode: data.kode, 
                    nama: data.nama, 
                    harga_asli: data.harga_asli,
                    harga: (liveSalesType === 'EXP') ? 0 : data.harga_asli, 
                    qty: 1, 
                    stok: data.stok 
                });
                showNotification(`Ditambahkan: ${nama}`, 'success');
            }

            $('#productSearch').val(null).trigger('change');
            renderCart();
        });
    }

    // Fungsi Render Tampilan Item Keranjang Belanja HTML
    function renderCart() {
        let tbody = $('#cartTableBody');
        tbody.empty();
        
        let subtotal = 0;

        if (cart.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="${isViewOnly ? 4 : 5}" class="text-center text-muted py-5">
                        <i class="fa-solid fa-box-open fs-2 mb-3 d-block opacity-25"></i>
                        ${isViewOnly ? 'Memuat data transaksi...' : 'Belum ada barang di keranjang.<br><small>Silakan cari dan pilih produk di atas.</small>'}
                    </td>
                </tr>
            `);
        } else {
            cart.forEach((item, index) => {
                let totalHarga = item.harga * item.qty;
                subtotal += totalHarga;

                let qtyHTML = isViewOnly 
                    ? `<span class="fw-bold fs-6 text-dark">${item.qty}</span>`
                    : `
                        <div class="d-flex justify-content-center align-items-center gap-1">
                            <button class="btn btn-sm btn-light border btn-action btn-minus" data-index="${index}"><i class="fa-solid fa-minus" style="font-size: 10px;"></i></button>
                            <input type="number" class="form-control form-control-sm text-center qty-input fw-bold" style="width: 75px;" value="${item.qty}" min="1" data-index="${index}">
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
                        <td class="text-center align-middle text-muted">${formatRupiah(item.harga)}</td>
                        <td class="text-center align-middle">
                            ${qtyHTML}
                        </td>
                        <td class="text-end align-middle fw-bold text-dark">${formatRupiah(totalHarga)}</td>
                        ${actionHTML}
                    </tr>
                `;
                tbody.append(tr);
            });
        }

        let grandTotal = subtotal;
        $('#summarySubtotal').text(formatRupiah(subtotal));
        $('#summaryTotal').text(formatRupiah(grandTotal));
    }

    // Trigger Perubahan Tipe Sales (Normal vs Expense)
    $('#salesType').on('change', function() {
        let newType = $(this).val();

        cart.forEach(item => {
            if (newType === 'EXP') item.harga = 0;
            else item.harga = item.harga_asli;
        });

        renderCart();    
        if (newType === 'EXP') showNotification('Tipe EXP dipilih: Semua harga diatur ke 0', 'info');
    });

    if (!isViewOnly) {
        // Tombol Plus (+) Qty
        $(document).on('click', '.btn-plus', function() {
            let index = $(this).data('index');
            if (cart[index].qty + 1 > cart[index].stok) {
                showNotification(`Stok maksimal hanya ${cart[index].stok}!`, 'warning');
            } else {
                cart[index].qty += 1;
                renderCart();
            }
        });

        // Tombol Minus (-) Qty
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

        // Event Input Manual Kolom Qty
        $(document).on('change', '.qty-input', function() {
            let index = $(this).data('index');
            let val = parseInt($(this).val());
            let removedName = cart[index].nama;
            
            if (isNaN(val) || val <= 0) {
                cart.splice(index, 1);
                showNotification(`Dihapus: ${removedName}`, 'warning');
            } else if (val > cart[index].stok) {
                showNotification(`Stok maksimal hanya ${cart[index].stok}!`, 'warning');
                cart[index].qty = cart[index].stok;
            } else cart[index].qty = val;
            renderCart();
        });

        // Tombol Hapus Barang Tunggal dari Keranjang
        $(document).on('click', '.btn-remove', function() {
            let index = $(this).data('index');
            let removedName = cart[index].nama;
            cart.splice(index, 1);
            renderCart();
            showNotification(`Dihapus: ${removedName}`, 'warning');
        });

        // Tombol Batalkan / Clear Form Transaksi
        $('#btnClearCart').click(function() {
            if (cart.length > 0) {
                if (!confirm("Apakah Anda yakin ingin membatalkan transaksi dan membersihkan form?")) {
                    return;
                }
            }
            cart = [];
            $('#buyerSelect').val(null).trigger('change');
            $('#productSearch').val(null).trigger('change');
            $('#salesType').val('SLS');
            $('#warehouseSelect').val('1');
            $('#salesDate').val(new Date().toISOString().split('T')[0]);
            renderCart();
        });

        // Prosedur Simpan Transaksi / Checkout Kasir
        $('#btnCheckout').click(function() {
            if (cart.length === 0) {
                showNotification('Keranjang masih kosong!', 'danger');
                return;
            }

            let buyerId = $('#buyerSelect').val();
            let warehouse = $('#warehouseSelect').val();
            let salesDate = $('#salesDate').val();
            let salesType = $('#salesType').val();

            if (!buyerId) {
                showNotification('Harap pilih Pembeli (Buyer)!', 'danger');
                return;
            }

            let btn = $(this);
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: 'index.php?page=pos',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'checkout',
                    buyer_id: buyerId,
                    warehouse: warehouse,
                    sales_date: salesDate,
                    sales_type: salesType,
                    cart: JSON.stringify(cart)
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showNotification(response.message || 'Transaksi berhasil disimpan!', 'success');
                        
                        cart = [];
                        $('#buyerSelect').val(null).trigger('change');
                        $('#productSearch').val(null).trigger('change');
                        renderCart();

                        const completedPrintUrl = 'index.php?page=pos&action=print_invoice&id=' + response.data.sale_id;
                        window.open(completedPrintUrl, '_blank');
                    } else {
                        showNotification(response.message || 'Gagal menyimpan transaksi', 'danger');
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    let errorMessage = 'Terjadi kesalahan sistem pada server.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) errorMessage = xhr.responseJSON.message;
                    else if (xhr.responseText) {
                        try {
                            let res = JSON.parse(xhr.responseText);
                            if (res.message) errorMessage = res.message;
                        } catch (e) {
                            console.error("Respons dari server bukan JSON yang valid:", xhr.responseText);
                        }
                    }
                    showNotification(errorMessage, 'danger');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa-double-check me-2"></i> SIMPAN TRANSAKSI');
                }
            });
        });
    }

});