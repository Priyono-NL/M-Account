$(document).ready(function() {
    // Inisialisasi plugin search dropdown Select2 Bootstrap 5
    $('#filterItem').select2({ theme: 'bootstrap-5' });

    // Fungsi Pengait Dinamis: Mengambil barang spesifik berdasarkan Gudang terpilih
    function loadItemsByWarehouse() {
        let whId = $('#filterWarehouse').val();
        let itemSelect = $('#filterItem');
        
        itemSelect.prop('disabled', true).html('<option value="">-- Memuat Barang... --</option>');
        
        $.ajax({
            url: 'index.php?page=stocks',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_items_by_warehouse_api',
                warehouse: whId
            },
            success: function(res) {
                itemSelect.empty().append('<option value="">-- Pilih Barang --</option>');
                if (res.status === 'success' && res.data) {
                    res.data.forEach(function(item) {
                        itemSelect.append(`<option value="${item.id}" data-uom="${item.item_uom}">${item.item_code} - ${item.item_name}</option>`);
                    });
                }
            },
            error: function() {
                itemSelect.empty().append('<option value="">-- Gagal memuat barang --</option>');
            },
            complete: function() {
                itemSelect.prop('disabled', false).trigger('change');
            }
        });
    }

    // Ketika dropdown Gudang Asal diganti oleh pengguna
    $('#filterWarehouse').change(function() {
        loadItemsByWarehouse();
        // Kembalikan isi tabel ke kondisi kosong awal agar tidak rancu dengan data gudang lama
        $('#stockCardTable tbody').html(`
            <tr id="initialRow">
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-arrow-pointer fs-3 mb-2 d-block opacity-25"></i>
                    Silakan tentukan nama barang dan rentang tanggal, lalu klik <b>Tarik Data</b>.
                </td>
            </tr>
        `);
    });

    // Jalankan otomatis sekali di awal saat halaman pertama kali dibuka
    loadItemsByWarehouse();

    $('#btnCariCard').click(function() {
        let itemId = $('#filterItem').val();
        let whId = $('#filterWarehouse').val();
        let sDate = $('#startDate').val();
        let eDate = $('#endDate').val();
        let uom = $('#filterItem option:selected').data('uom') || '-';

        if(!itemId) {
            showNotification('Harap pilih barang terlebih dahulu!', 'warning');
            return;
        }

        let tbody = $('#stockCardTable tbody');
        tbody.html('<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Menghitung aliran kronologis barang...</td></tr>');

        $.ajax({
            url: 'index.php?page=stocks',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_card_api',
                item_id: itemId,
                warehouse: whId,
                start_date: sDate,
                end_date: eDate
            },
            success: function(res) {
                if(res.status === 'success') {
                    tbody.empty();
                    let mutations = res.data.mutations || [];

                    mutations.forEach(function(row) {
                        let labelIn = row.in > 0 ? '<span class="text-success fw-bold">+' + row.in + '</span>' : '0';
                        let labelOut = row.out > 0 ? '<span class="text-danger fw-bold">-' + row.out + '</span>' : '0';
                        
                        let formattedDate = row.date;
                        if(row.date !== '-' && row.code !== '-') {
                            let dObj = new Date(row.date);
                            if(!isNaN(dObj.getTime())) {
                                // REVISI: Cukup memuat tanggal saja (Tanpa opsi hour & minute)
                                formattedDate = dObj.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'});
                            }
                        }

                        tbody.append('<tr>' +
                            '<td class="ps-4 text-muted">' + formattedDate + '</td>' +
                            '<td class="fw-bold text-primary">' + row.code + '</td>' +
                            '<td><span class="fw-semibold text-dark">' + row.notes + '</span></td>' +
                            '<td class="text-center"><span class="badge bg-light text-secondary border">' + uom + '</span></td>' +
                            '<td class="text-center">' + labelIn + '</td>' +
                            '<td class="text-center">' + labelOut + '</td>' +
                            '<td class="text-center fw-bold text-primary fs-6 pe-4">' + row.balance + '</td>' +
                        '</tr>');
                    });
                } else {
                    showNotification(res.message, 'danger');
                }
            },
            error: function() {
                tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Gagal memproses kartu stok ke server.</td></tr>');
            }
        });
    });
});