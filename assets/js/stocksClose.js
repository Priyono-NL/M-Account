$(document).ready(function() {

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

    function loadFilteredHistory() {
        $.ajax({
            url: "index.php?page=stockClose",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                closeMonth: $("#closeMonth").val(),
            },
            success: function(res) {
                if (res.status === "success") {
                    let tbody = $("#stockTable tbody");
                    tbody.empty();

                    let currentStatus = res.data.status;
                    renderStatusBanner(currentStatus);

                    let itemsArray = res.data.stocks || [];

                    if (itemsArray.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center py-5 text-muted italic">Tidak ada riwayat transaksi ditemukan.</td></tr>');
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
                }
            },
            error: function() {
                console.error("Gagal menarik data.");
            }
        });
    }

    loadFilteredHistory();

    $("#search").on("keyup", loadFilteredHistory);
    $("#filterWarehouse, #closeMonth").on("change", loadFilteredHistory);

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        loadFilteredHistory();
    });

    $("#btnResetAll").click(function() {
        $("#search").val("");
        $("#filterWarehouse").val("");
        $("#closeMonth").val("");
        
        loadFilteredHistory();
    });

    $("#btnExportExcel").click(function() {
        let payload = {
            action: 'export_xls',
            search: $("#search").val() || "",
            closeMonth: $("#closeMonth").val() || "",
            warehouse: $("#filterWarehouse").val() || ""
        };

        downloadExcelAjax(this, '/m-account/stockClose', payload, 'Laporan_Stok');
    });

    $("#btnClosing").click(function() {
        let monthPeriod = $("#closeMonth").val();

        if (!monthPeriod) {
            alert("Silakan pilih bulan terlebih dahulu pada filter!");
            return;
        }

        let parts = monthPeriod.split("-");
        let dateObj = new Date(parts[0], parts[1] - 1);
        let namaBulanIndo = dateObj.toLocaleDateString('id-ID', { 
            month: 'long', 
            year: 'numeric' 
        });

        let konfirmasi = confirm(`Apakah Anda yakin ingin melakukan Closing Stok untuk bulan ${namaBulanIndo.toUpperCase()}?\n\nData stok terakhir di bulan ini akan dikunci sebagai saldo. Data yang sudah diclose sebelumnya di bulan ini akan ditimpa dengan data terbaru.`);
        
        if (!konfirmasi) {
            return;
        }

        let $btn = $(this);
        let originalHtml = $btn.html();

        $btn.html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Memproses...').prop('disabled', true);

        $.ajax({
            url: "index.php?page=stockClose",
            type: "POST",
            dataType: "json",
            data: {
                action: "do_closing",
                monthPeriod: monthPeriod
            },
            success: function(res) {
                if (res.status === "success") {
                    alert(res.message);
                    loadFilteredHistory(); 
                } else {
                    alert("Gagal: " + res.message);
                }
            },
            error: function() {
                alert("Terjadi kesalahan sistem saat memproses closing.");
            },
            complete: function() {
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });

});