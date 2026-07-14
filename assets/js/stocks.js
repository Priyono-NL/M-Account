$(document).ready(function() {
    let tbody = $("#stockTable tbody");

    let currentPage = 1;
    let limit = 25;

    function renderStatusBanner(status) {
        let bannerHtml = '';
        if (status === 'ONGOING') {
            bannerHtml = `
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-3">
                    <i class="fa-solid fa-triangle-exclamation fs-3 text-warning me-3"></i>
                    <div>
                        <strong>Status: SEDANG BERJALAN (DRAFT)</strong><br>
                        <span class="small">Angka di bawah adalah akumulasi kalkulasi mutasi stok harian berjalan.</span>
                    </div>
                </div>
            `;
        } else {
            bannerHtml = `
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-3">
                    <i class="fa-solid fa-lock fs-3 text-success me-3"></i>
                    <div>
                        <strong>Status: DIKUNCI (CLOSED)</strong><br>
                        <span class="small">Periode stok ini sudah ditutup secara permanen.</span>
                    </div>
                </div>
            `;
        }
        $('#statusBannerContainer').html(bannerHtml);
    }

    function loadFilteredHistory(page = 1) {
        currentPage = page;
        let sDateVal = $("#startDate").val();
        let eDateVal = $("#endDate").val();

        // 1. Validasi jika Periode/Tanggal Kosong
        if (!sDateVal || !eDateVal) {
            if (typeof showNotification === "function") showNotification("Gagal memuat data! Rentang tanggal filter tidak boleh kosong.", "danger");
            clearTable("Rentang tanggal filter wajib diisi.", true);
            return;
        }

        // Trik Ekstrak Bulan & Tahun untuk Komparasi
        let startMonth = sDateVal.substring(0, 7); // Hasil: YYYY-MM
        let endMonth = eDateVal.substring(0, 7);   // Hasil: YYYY-MM
        if (startMonth !== endMonth) {
            if (typeof showNotification === "function") {
                showNotification("Gagal memuat data! Pencarian lintas bulan tidak diizinkan. Harap filter tanggal dalam bulan yang sama.", "danger");
            }
            clearTable("Filter tidak valid (Pencarian harus dalam 1 periode bulan yang sama).", true);
            return;
        }

        // Dapatkan string tanggal hari ini (Format YYYY-MM-DD)
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;

        // 2. Validasi jika Tanggal Awal Melebihi Tanggal Akhir
        if (sDateVal > eDateVal) {
            if (typeof showNotification === "function") showNotification("Gagal memuat data! Tanggal awal tidak boleh melebihi tanggal akhir.", "danger");
            clearTable("Format tanggal salah (Tanggal awal melampaui tanggal akhir).", true);
            return;
        }

        // 3. Validasi jika Periode Melebihi Hari Ini (Aman untuk hari ini berjalan)
        if (eDateVal > todayStr) {
            if (typeof showNotification === "function") showNotification("Gagal memuat data! Pilihan rentang tanggal tidak boleh melewati hari ini.", "danger");
            clearTable("Periode tidak valid (Melebihi tanggal hari ini berjalan).", true);
            return;
        }

        tbody.html('<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Memuat data mutasi informasi stok harian...</td></tr>');

        $.ajax({
            url: "index.php?page=stocks",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                start_date: sDateVal,
                end_date: eDateVal,
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
                        tbody.append('<tr><td colspan="7" class="text-center py-5 text-muted fst-italic">Tidak ada transaksi ditemukan pada tanggal ini.</td></tr>');
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
                        
                        let onhandDisplay = qtyOnhand;
                        let selisihDisplay = (selisih != 0 ? `<span class="text-danger fw-bold">${selisih}</span>` : selisih);

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

                    if (res.pagination) {
                        renderPagination(res.pagination);
                    }
                }
            },
            error: function() {
                tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Terjadi kesalahan saat memuat data dari server.</td></tr>');
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

        tbody.append(`<tr><td colspan="7" class="text-center py-5 text-muted">${iconHtml}${pesan}${subPesanHtml}</td></tr>`);
        renderPagination({ total: 0, totalPages: 0, page: 1, limit: limit });
    }
    
    // Memicu pencarian berdasarkan tombol filter asli Anda (#btnFilter)
    $("#btnFilter").click(function() {
        loadFilteredHistory(1);
    });

    $("#search").on("keyup", clearTable);
    $("#filterWarehouse, #startDate, #endDate").on("change", clearTable);

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        clearTable();
    });

    // Mengembalikan filter reset harian ke tanggal 1 awal bulan s/d hari ini
    $("#btnResetAll").click(function() {
        let d = new Date();
        let tahun = d.getFullYear();
        let bulan = String(d.getMonth() + 1).padStart(2, '0');
        let tanggal = String(d.getDate()).padStart(2, '0');

        let startOfMonth = tahun + '-' + bulan + '-01';
        let endOfToday = tahun + '-' + bulan + '-' + tanggal;
        let firstWarehouseVal = $("#filterWarehouse option:first").val();
    
        $("#search").val("");
        $("#filterWarehouse").val(firstWarehouseVal);
        $("#startDate").val(startOfMonth);
        $("#endDate").val(endOfToday);
        
        clearTable();
    });

    // Memasang proteksi input keyboard bypass kalender HTML5
    $("#startDate, #endDate").on("keydown", function(e) {
        if (e.key === "Backspace" || e.key === "Delete") {
            e.preventDefault();
        }
    });

    $("#btnExportExcel").click(function() {
        let sDateVal = $("#startDate").val() || "";
        let eDateVal = $("#endDate").val() || "";

        let payload = {
            action: 'export_xls',
            search: $("#search").val() || "",
            start_date: sDateVal,
            end_date: eDateVal,
            warehouse: $("#filterWarehouse").val() || ""
        };

        let fStart = sDateVal.replace(/-/g, "");
        let fEnd = eDateVal.replace(/-/g, "");
        let dynamicFileName = 'Laporan_Stok_' + fStart + '_sd_' + fEnd;

        if (typeof downloadExcelAjax === "function") {
            downloadExcelAjax(this, window.location.href, payload, dynamicFileName);
        }
    });
});