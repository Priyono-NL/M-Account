$(document).ready(function() {
    let tbody = $("#adjustmentTable tbody");
    let mBody = $("#modalDetailTableBody");
    
    let currentPage = 1;
    let limit = 10;
    let activeOpnameId = null;

    function loadPendingOpnames(page = 1) {
        currentPage = page;
        tbody.html('<tr><td colspan="6" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Memuat antrean opname pending...</td></tr>');

        $.ajax({
            url: "index.php?page=stockAdjustment",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                page: currentPage,
                limit: limit
            },
            success: function(res) {
                if (res.status === "success") {
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="6" class="text-center py-5 text-muted fst-italic"><i class="fa-solid fa-folder-open fs-3 d-block mb-2 opacity-50"></i>Tidak ada antrean dokumen opname saat ini.</td></tr>');
                        renderPagination({ total: 0, totalPages: 0, page: 1, limit: limit });
                        return;
                    }

                    res.data.forEach(function(o) {
                        let whName = o.warehouse == '1' ? 'Gudang BS' : (o.warehouse == '2' ? 'Gudang Sampah' : o.warehouse);
                        let dateFormatted = new Date(o.opname_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

                        let tr = `
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><i class="fa-solid fa-file-invoice text-muted me-2"></i>${o.opname_no}</td>
                                <td class="text-center text-muted">${dateFormatted}</td>
                                <td class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-2fw-normal">${whName}</span></td>
                                <td class="text-center text-dark fw-medium">${o.created_by}</td>
                                <td class="text-center"><span class="badge bg-warning text-dark border-0 px-2 fw-bold"><i class="fa-solid fa-clock me-1"></i> PENDING</span></td>
                                <td class="text-center pe-4">
                                    <button type="button" class="btn btn-sm btn-primary rounded-3 px-3 btn-periksa" data-id="${o.id}">
                                        Periksa Selisih
                                    </button>
                                </td>
                            </tr>`;
                        tbody.append(tr);
                    });

                    if (res.pagination) renderPagination(res.pagination);
                }
            }
        });
    }

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        if ($(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) return;
        loadPendingOpnames($(this).data('page'));
    });

    $(document).on('click', '.btn-periksa', function() {
        activeOpnameId = $(this).data('id');
        mBody.html('<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Menarik rincian barang...</td></tr>');
        
        const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
        detailModal.show();

        $.ajax({
            url: "index.php?page=stockAdjustment",
            type: "POST",
            dataType: "json",
            data: { action: "get_opname_detail", opname_id: activeOpnameId },
            success: function(res) {
                if (res.status === "success") {
                    let h = res.data.header;
                    let items = res.data.items || [];

                    $("#lblDetailNo").text(h.opname_no);
                    $("#lblDetailGudang").text(h.warehouse == '1' ? 'GUDANG BS' : 'GUDANG SAMPAH');
                    $("#lblDetailTanggal").text(new Date(h.opname_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }));

                    mBody.empty();
                    items.forEach(function(i) {
                        let sys = parseFloat(i.qty_sistem || 0);
                        let phy = parseFloat(i.qty_fisik || 0);
                        let selisih = phy - sys; // Konsep matematika dasar

                        let selisihHtml = '0';
                        let inputHtml = '<span class="text-muted small italic">Klop (Tidak butuh alasan)</span>';

                        if (selisih > 0) {
                            selisihHtml = `<span class="text-success fw-bold">+${selisih}</span>`;
                            inputHtml = `<input type="text" class="form-control form-control-sm border-danger reason-input" placeholder="Wajib isi alasan kelebihan barang..." data-itemid="${i.item_id}">`;
                        } else if (selisih < 0) {
                            selisihHtml = `<span class="text-danger fw-bold">${selisih}</span>`;
                            inputHtml = `<input type="text" class="form-control form-control-sm border-danger reason-input" placeholder="Wajib isi alasan kehilangan/kerusakan..." data-itemid="${i.item_id}">`;
                        }

                        let row = `
                            <tr>
                                <td><div class="fw-bold">${i.item_name}</div><small class="text-muted fw-mono">${i.item_code}</small></td>
                                <td class="text-center text-secondary">${sys} ${i.item_uom}</td>
                                <td class="text-center text-dark fw-bold">${phy} ${i.item_uom}</td>
                                <td class="text-center fs-6">${selisihHtml}</td>
                                <td>${inputHtml}</td>
                            </tr>`;
                        mBody.append(row);
                    });
                }
            }
        });
    });

    $('#detailModal').on('hide.bs.modal', function () {
        $(`.btn-periksa[data-id="${activeOpnameId}"]`).focus();
    });

    $("#btnExecuteAdjustment").click(function() {
        let payloadItems = [];
        let validationPass = true;

        $(".reason-input").each(function() {
            let reasonVal = $(this).val().trim();
            let itemId = $(this).data('itemid');

            if (reasonVal === "") {
                validationPass = false;
                $(this).addClass("is-invalid").focus();
                showNotification("Gagal! Semua kolom alasan barang yang selisih wajib diisi sebagai bukti audit.", "danger");
                return false;
            }

            payloadItems.push({ item_id: itemId, reason: reasonVal });
        });

        if (!validationPass) return;

        if (!confirm("Peringatan! Menyetujui adjustment ini akan langsung merubah saldo stok komputer mengikuti jumlah fisik gudang saat ini. Lanjutkan?")) return;

        let $btn = $(this);
        let originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Memproses Mutasi Stok...');

        $.ajax({
            url: "index.php?page=stockAdjustment",
            type: "POST",
            dataType: "json",
            data: {
                action: "submit_adjustment",
                opname_id: activeOpnameId,
                items: JSON.stringify(payloadItems)
            },
            success: function(res) {
                if (res.status === "success") {
                    showNotification(res.message, "success");
                    bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
                    loadPendingOpnames(currentPage); // Refresh list antrean utama
                } else {
                    showNotification(res.message || "Gagal melakukan eksekusi.", "danger");
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    function clearTable() {
        tbody.html('<tr><td colspan="6" class="text-center py-5 text-muted"><i class="fa-solid fa-magnifying-glass fs-2 mb-3 d-block opacity-25"></i>Kriteria pencarian diubah. Silakan klik tombol Cari.</td></tr>');
        renderPagination({ total: 0, totalPages: 0, page: 1, limit: limit });
    }
    $("#search").on("keyup", clearTable);
    $("#filterWarehouse").on("change", clearTable);
    $("#btnClearSearch").click(function() { $("#search").val(""); clearTable(); });
    $("#btnFilter").click(function() { loadPendingOpnames(1); });

    loadPendingOpnames(1);
});