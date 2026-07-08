$(document).ready(function() {
    let tbody = $("#stockTable tbody");

    let currentPage = 1;
    let limit = 10;

    function loadFilteredHistory(page = 1) {
        currentPage = page;
        let periodMonth = $("#periodMonth").val();
        // Validasi jika Periode Bulan Kosong
        if (!periodMonth) {
            if (typeof showNotification === "function") showNotification("Gagal memuat data! Periode Bulan tidak boleh kosong.", "danger");
            clearTable("Periode bulan wajib diisi.", true);
            return;
        }
        
        // Validasi jika Periode Melebihi Bulan Berjalan
        let parts = periodMonth.split("-");
        let inputYear = parseInt(parts[0], 10);
        let inputMonth = parseInt(parts[1], 10);

        let today = new Date();
        let currentYear = today.getFullYear();
        let currentMonth = today.getMonth() + 1;
        let inputTotalMonths = (inputYear * 12) + inputMonth;
        let currentTotalMonths = (currentYear * 12) + currentMonth;

        if (inputTotalMonths > currentTotalMonths) {
            if (typeof showNotification === "function")showNotification("Gagal memuat data! Periode tidak boleh melewati bulan berjalan saat ini.", "danger");
            clearTable("Periode tidak valid (Melebihi bulan berjalan).", true);
            return;
        }
        tbody.html('<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Memuat data mutasi stok ...</td></tr>');

        $.ajax({
            url: "index.php?page=stockClosing",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                periodMonth: periodMonth, 
                page: currentPage,
                limit: limit
            },
            success: function(res) {
                if (res.status === "success") {                    
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center py-5 text-muted fst-italic">Tidak ada riwayat transaksi ditemukan.</td></tr>');
                        renderPagination({ total: 0, totalPages: 0, page: 1 });
                        return;
                    }

                    res.data.forEach(function(t) {
                        let qtyInStr = t.qty_in > 0 ? `<span class="text-success">+${t.qty_in}</span>` : '0';
                        let qtyOutStr = t.qty_out > 0 ? `<span class="text-danger">-${t.qty_out}</span>` : '0';
                        let rawDate = t.date || t.transaction_date; 
                        let dateObj = rawDate ? new Date(rawDate) : null;
                        let formattedDate = (dateObj && !isNaN(dateObj.getTime())) 
                            ? dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) 
                            : '-';

                        let tr = `
                            <tr>                                
                                <td class="text-center ps-4"><span class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-2 fw-normal">${t.warehouse_name}</span></td>
                                <td><div class="fw-bold text-dark">${t.item_name}</div><small class="text-muted" style="font-size: 11px;">${t.item_code}</small></td>
                                <td class="text-center fw-medium text-muted">${t.qty_open}</td>
                                <td class="text-center fw-bold">${qtyInStr}</td>
                                <td class="text-center fw-bold">${qtyOutStr}</td>
                                <td class="text-center fw-bold fs-6 text-primary">${t.qty_close}</td>
                                <td class="pe-4 text-muted">${formattedDate}</td>
                            </tr>`;
                        tbody.append(tr);
                    });
                    
					if (res.is_closed) $("#btnMulaiClosing").prop('disabled', true);
					else $("#btnMulaiClosing").prop('disabled', false);

                    if (res.pagination) renderPagination(res.pagination);
                }
            },
            error: function() {
                tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Terjadi kesalahan saat memuat data stok.</td></tr>');
            }
        });
    }

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        let $parent = $(this).parent();
        if ($parent.hasClass('disabled') || $parent.hasClass('active')) return;
        
        let targetPage = $(this).data('page');
        loadFilteredHistory(targetPage);
    });

    function clearTable(pesan = "Pencarian dibersihkan", isValidationError = false) {
        $('#statusBannerContainer').empty();
        tbody.empty();
        
        let iconHtml = isValidationError 
            ? `<i class="fa-solid fa-triangle-exclamation fs-2 mb-3 d-block text-danger opacity-50"></i>`
            : `<i class="fa-solid fa-magnifying-glass fs-2 mb-3 d-block opacity-25"></i>`;
            
        let subPesanHtml = isValidationError
            ? `<br><small class="text-secondary">Silakan perbaiki filter Anda lalu klik Apply Filter kembali.</small>`
            : ``;

        tbody.append(`
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    ${iconHtml}
                    ${pesan}
                    ${subPesanHtml}
                </td>
            </tr>
        `);
        $("#btnMulaiClosing").prop('disabled', true);
        renderPagination({ total: 0, totalPages: 0, page: 1, limit: limit });
    }
	
	$("#btnFilter").click(function() {
		loadFilteredHistory(1);
	});

    $("#search").on("keyup", clearTable);
    $("#filterWarehouse, #periodMonth").on("change", clearTable);

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        clearTable();
    });
	
	$("#btnResetAll").click(function() {
		let d = new Date();
		let tahun = d.getFullYear();
		let bulan = String(d.getMonth() + 1).padStart(2, '0');
		let nowMonth = tahun + '-' + bulan;
        let firstWarehouseVal = $("#filterWarehouse option:first").val();
	
        $("#search").val("");
        $("#filterWarehouse").val(firstWarehouseVal);
        $("#periodMonth").val(nowMonth);
        
        clearTable();
    });
	
	// ==========================================
    // PREVENT EMPTY PERIOD
    // ==========================================
	$("#periodMonth").on("keydown", function(e) {
		if (e.key === "Backspace" || e.key === "Delete") {
			e.preventDefault();
		}
	});

    // ==========================================
    // LOGIKA MODAL CLOSING YANG AMAN
    // ==========================================
    const modalClosing = new bootstrap.Modal(document.getElementById('modalClosing'));

    $("#btnMulaiClosing").click(function() {
        let monthPeriod = $("#periodMonth").val();
        let warehouseName = $("#filterWarehouse option:selected").text();

        if (!monthPeriod) {
            showNotification("Harap pilih Bulan Periode terlebih dahulu pada filter pencarian!", "warning");
			return;
        }

        let parts = monthPeriod.split("-");
        let dateObj = new Date(parts[0], parts[1] - 1);
        let namaBulanIndo = dateObj.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });

        $("#lblPeriode").text(namaBulanIndo.toUpperCase());
        $("#lblGudang").text(warehouseName);
        
        $("#txtKonfirmasi").val('');
        $("#btnEksekusiClosing").prop('disabled', true);

        modalClosing.show();
    });

    $("#txtKonfirmasi").on("keyup", function() {
        let val = $(this).val().toUpperCase();
        $(this).val(val);
        
        if (val === "TUTUP") {
            $("#btnEksekusiClosing").prop('disabled', false).removeClass('btn-secondary').addClass('btn-danger');
        } else {
            $("#btnEksekusiClosing").prop('disabled', true);
        }
    });

    $("#btnEksekusiClosing").click(function() {
        let monthPeriod = $("#periodMonth").val();
        let $btn = $(this);
        let originalHtml = $btn.html();

        $btn.html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Memproses...').prop('disabled', true);
        $("#txtKonfirmasi").prop('disabled', true);

        $.ajax({
            url: "index.php?page=stockClosing",
            type: "POST",
            dataType: "json",
            data: {
                action: "do_closing",
                periodMonth: monthPeriod,
                warehouse: $("#filterWarehouse").val()
            },
            success: function(res) {
                if (res.status === "success") {
                    showNotification(res.message, "success");
					modalClosing.hide();
                    loadFilteredHistory(currentPage); // Muat ulang posisi halaman aktif
                } else {
                    showNotification(res.message || "Gagal memproses closing.", "danger");
                }
            },
            error: function() {
                showNotification("Terjadi kesalahan sistem saat memproses closing ke database.", "danger");
            },
            complete: function() {
                $btn.html(originalHtml).prop('disabled', false);
                $("#txtKonfirmasi").prop('disabled', false);
            }
        });
    });
	
	// ==========================================
    // SELEKTOR OTOMATIS LOAD TAB RIWAYAT (LOG)
    // ==========================================
    $('#riwayat-tab').on('shown.bs.tab', function (e) {
        let hBody = $("#historyTable tbody");
        
        hBody.html('<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2 fs-4 d-block mb-2"></i>Memuat riwayat log...</td></tr>');

        $.ajax({
            url: "index.php?page=stockClosing",
            type: "POST",
            dataType: "json",
            data: { action: "get_history" }, 
            success: function(res) {
                if (res.status === "success") {
                    hBody.empty();
                    
                    if (res.data.length === 0) {
                        hBody.append('<tr><td colspan="5" class="text-center py-5 text-muted">Belum ada riwayat closing stok yang tercatat di sistem.</td></tr>');
                        return;
                    }

                    res.data.forEach(function(h) {
                        let whName = h.warehouse == '1' ? 'Gudang BS' : (h.warehouse == '2' ? 'Gudang Sampah' : h.warehouse);

                        let pParts = h.periode.split("-");
                        let pDate = new Date(pParts[0], pParts[1] - 1);
                        let labelBulan = pDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }).toUpperCase();
                        
                        let execDate = new Date(h.executed_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' });

                        let rowHtml = `
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><i class="fa-solid fa-calendar-check text-success me-2"></i>${labelBulan}</td>
                                <td class="text-center"><span class="badge bg-light text-dark border px-2 fw-normal">${whName}</span></td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success border-0 px-2 fw-bold"><i class="fa-solid fa-lock me-1"></i> LOCKED</span></td>
                                <td class="text-center text-muted fw-medium">${h.executed_by}</td>
                                <td class="pe-4 text-end text-muted small">${execDate} WIB</td>
                            </tr>`;
                        hBody.append(rowHtml);
                    });
                } else {
                    hBody.html(`<tr><td colspan="5" class="text-center py-5 text-danger">${res.message || 'Gagal memuat data'}</td></tr>`);
                }
            },
            error: function() {
                hBody.html('<tr><td colspan="5" class="text-center py-5 text-danger">Gagal terhubung ke server saat memuat riwayat.</td></tr>');
            }
        });
    });
	
});