$(document).ready(function() {
    let tbody = $("#stockTable tbody");

    let currentPage = 1;
    let limit = 10;

    function renderStatusBanner(status) {
        let bannerHtml = '';
        if (status === 'ONGOING') {
            bannerHtml = `
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-3">
                    <i class="fa-solid fa-triangle-exclamation fs-3 text-warning me-3"></i>
                    <div>
                        <strong>Status: SEDANG BERJALAN (DRAFT)</strong><br>
                        <span class="small">Bulan ini belum di-closing. Angka di bawah adalah mutasi stok berjalan.</span>
                    </div>
                </div>
            `;
            $('#btnClosing').prop('disabled', false);
        } else {
            bannerHtml = `
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-3">
                    <i class="fa-solid fa-lock fs-3 text-success me-3"></i>
                    <div>
                        <strong>Status: DIKUNCI (CLOSED)</strong><br>
                        <span class="small">Bulan ini sudah ditutup secara permanen.</span>
                    </div>
                </div>
            `;
            $('#btnClosing').prop('disabled', true);
        }
        $('#statusBannerContainer').html(bannerHtml);
    }

    function loadFilteredHistory(page = 1) {
        currentPage = page;
        let closeMonthInput = $("#closeMonth").val();
        let warehouseInput = $("#filterWarehouse").val();

        // Validasi jika Periode/Bulan Kosong
        if (!closeMonthInput) {
            if (typeof showNotification === "function") showNotification("Gagal memuat data! Periode bulan tidak boleh kosong.", "danger");
            clearTable("Periode bulan wajib diisi.", true);
            return;
        }

        // Validasi jika Periode Melebihi Bulan Berjalan (Juni 2026)
        let parts = closeMonthInput.split('-');
        let inputYear = parseInt(parts[0], 10);
        let inputMonth = parseInt(parts[1], 10);
        let today = new Date();
        let currentYear = today.getFullYear();
        let currentMonth = today.getMonth() + 1;

        let inputTotalMonths = (inputYear * 12) + inputMonth;
        let currentTotalMonths = (currentYear * 12) + currentMonth;

        if (inputTotalMonths > currentTotalMonths) {
            if (typeof showNotification === "function") showNotification("Gagal memuat data! Periode tidak boleh melewati bulan berjalan saat ini.", "danger");
            clearTable("Periode tidak valid (Melebihi bulan berjalan).", true);
            return;
        }

        tbody.html('<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Memuat data informasi stok...</td></tr>');

        $.ajax({
            url: "index.php?page=stockClose",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                closeMonth: $("#closeMonth").val(),
                page: currentPage,
                limit: limit
            },
            success: function(res) {
                if (res.status === "success") {                    
                    tbody.empty();

                    let currentStatus = res.data.status;
                    renderStatusBanner(currentStatus);

                    let itemsArray = res.data.stocks || [];

                    if (itemsArray.length === 0) {
                        $('#statusBannerContainer').empty();
                        tbody.append('<tr><td colspan="7" class="text-center py-5 text-muted fst-italic">Tidak ada riwayat transaksi ditemukan.</td></tr>');
                        renderPagination({ total: 0, totalPages: 0, page: 1 });
                        return;
                    }

                    itemsArray.forEach(function(t) {
                        let qtyOpen = t.qty_open || 0;
                        let qtyIn = t.qty_in || 0;
                        let qtyOut = t.qty_out || 0;
                        let qtyClose = t.qty_close || 0;
                        let qtyOnhand = t.qty_onhand || 0;
                        let selisih = t.selisih || 0;

                        let qtyInStr = qtyIn > 0 ? `<span class="text-success">+${qtyIn}</span>` : '0';
                        let qtyOutStr = qtyOut > 0 ? `<span class="text-danger">-${qtyOut}</span>` : '0';
                        
                        let onhandDisplay = currentStatus === 'ONGOING' ? '-' : qtyOnhand;
                        let selisihDisplay = currentStatus === 'ONGOING' ? '-' : (selisih != 0 ? `<span class="text-danger fw-bold">${selisih}</span>` : selisih);

                        let tr = `
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">${t.item_name}</div>
                                    <small class="text-muted" style="font-size: 11px;">${t.item_code}</small>
                                </td>
                                <td class="text-center fw-medium text-muted">${qtyOpen}</td>
                                <td class="text-center fw-bold">${qtyInStr}</td>
                                <td class="text-center fw-bold">${qtyOutStr}</td>
                                <td class="text-center fw-bold text-primary">${qtyClose}</td>
                                <td class="text-center fw-bold text-success">${onhandDisplay}</td>
                                <td class="text-center fw-bold">${selisihDisplay}</td>
                            </tr>
                        `;
                        tbody.append(tr);
                    });

                    $("#btnMulaiClosing").prop('disabled', false);

                    if (res.pagination) {
                        renderPagination(res.pagination);
                    }
                }
            },
            error: function() {
                tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Terjadi kesalahan saat memuat data.</td></tr>');
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
        
        renderPagination({ total: 0, totalPages: 0, page: 1, limit: limit });
    }
	
	$("#btnFilter").click(function() {
		loadFilteredHistory(1);
	});

    $("#search").on("keyup", clearTable);
    $("#filterWarehouse, #closeMonth").on("change", clearTable);

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
        $("#closeMonth").val(nowMonth);
        
        clearTable();
    });

    $("#btnExportExcel").click(function() {
        let payload = {
            action: 'export_xls',
            search: $("#search").val() || "",
            closeMonth: $("#closeMonth").val() || "",
            warehouse: $("#filterWarehouse").val() || ""
        };

        if (typeof downloadExcelAjax === "function") {
            downloadExcelAjax(this, window.location.href, payload, 'Laporan_Stok');
        }
    });
	
    $("#closeMonth").on("keydown", function(e) {
        if (e.key === "Backspace" || e.key === "Delete") {
            e.preventDefault();
        }
    });

});