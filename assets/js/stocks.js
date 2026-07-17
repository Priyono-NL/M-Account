$(document).ready(function() {
    let tbody = $("#stockTable tbody");

    let currentPage = 1;
    let limit = 25;
    
    // Status urutan aktif (Kosong di awal agar mengikuti penarikan data default database)
    let currentSortColumn = "";
    let currentSortOrder = ""; 

    // Fungsi merender baris data
    function renderStockRows(itemsArray) {
        tbody.empty();

        if (itemsArray.length === 0) {
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
    }

    function loadFilteredHistory(page = 1) {
        currentPage = page;
        let sDateVal = $("#startDate").val();
        let eDateVal = $("#endDate").val();

        if (!sDateVal || !eDateVal) {
            if (typeof showNotification === "function") showNotification("Gagal memuat data! Rentang tanggal filter tidak boleh kosong.", "danger");
            clearTable("Rentang tanggal filter wajib diisi.", true);
            return;
        }

        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;

        if (sDateVal > eDateVal) {
            if (typeof showNotification === "function") showNotification("Gagal memuat data! Tanggal awal tidak boleh melebihi tanggal akhir.", "danger");
            clearTable("Format tanggal salah (Tanggal awal melampaui tanggal akhir).", true);
            return;
        }

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
                limit: limit,
                sort_col: currentSortColumn,
                sort_dir: currentSortOrder
            },
            success: function(res) {
                if (res.status === "success") {                    
                    // KUNCI: Data dari server langsung dioper ke fungsi render tanpa disimpan di memori global
                    renderStockRows(res.data || []);

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

    // Event Listener Klik Header untuk Mengaktifkan Server-Side Sort
    $(document).on("click", "#stockTable th.sortable", function() {
        let th = $(this);
        let column = th.data("column");

        if (currentSortColumn === column) {
            currentSortOrder = (currentSortOrder === "asc") ? "desc" : "asc";
        } else {
            currentSortColumn = column;
            currentSortOrder = "desc"; 
        }

        $("#stockTable th.sortable i").removeClass("fa-sort-up fa-sort-down text-dark").addClass("fa-sort opacity-50");
        let icon = th.find("i");
        if (currentSortOrder === "asc") {
            icon.removeClass("fa-sort opacity-50").addClass("fa-sort-up text-dark");
        } else {
            icon.removeClass("fa-sort opacity-50").addClass("fa-sort-down text-dark");
        }

        loadFilteredHistory(1);
    });

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        let $parent = $(this).parent();
        if ($parent.hasClass('disabled') || $parent.hasClass('active')) return;
        
        let targetPage = $(this).data('page');
        loadFilteredHistory(targetPage);
    });

    function clearTable(pesan = "Pencarian dibersihkan", isValidationError = false) {
        tbody.empty();
        
        // Kembalikan ke penarikan data awal jika filter diubah/direset
        currentSortColumn = "";
        currentSortOrder = "";
        $("#stockTable th.sortable i").removeClass("fa-sort-up fa-sort-down text-dark").addClass("fa-sort opacity-50");
        
        let iconHtml = isValidationError 
            ? `<i class="fa-solid fa-triangle-exclamation fs-2 mb-3 d-block text-danger opacity-50"></i>`
            : `<i class="fa-solid fa-magnifying-glass fs-2 mb-3 d-block opacity-25"></i>`;
            
        let subPesanHtml = isValidationError
            ? `<br><small class="text-secondary">Silakan perbaiki filter Anda lalu klik Apply Filter kembali.</small>`
            : ``;

        tbody.append(`<tr><td colspan="7" class="text-center py-5 text-muted">${iconHtml}${pesan}${subPesanHtml}</td></tr>`);
        renderPagination({ total: 0, totalPages: 0, page: 1, limit: limit });
    }
    
    $("#btnFilter").click(function() {
        loadFilteredHistory(1);
    });

    $("#search").on("keyup", clearTable);
    $("#filterWarehouse, #startDate, #endDate").on("change", clearTable);

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        clearTable();
    });

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