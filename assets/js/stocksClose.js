$(document).ready(function() {
	let tbody = $("#stockTable tbody");

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
                    tbody.empty();

                    let currentStatus = res.data.status;
					console.log(res.data.status);
                    renderStatusBanner(currentStatus);

                    let itemsArray = res.data.stocks || [];

                    if (itemsArray.length === 0) {
						$('#statusBannerContainer').empty();
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

    function clearTable() {
		$('#statusBannerContainer').empty();
		tbody.empty();
		tbody.append(`
			<tr>
				<td colspan="7" class="text-center py-5 text-muted">
					<i class="fa-solid fa-magnifying-glass fs-2 mb-3 d-block opacity-25"></i>
					Pencarian dibersihkan
				</td>
			</tr>
		`);
	}
	
	$("#btnFilter").click(function() {
		loadFilteredHistory();
	})

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
	
        $("#search").val("");
        $("#filterWarehouse").val("");
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

        downloadExcelAjax(this, window.location.href, payload, 'Laporan_Stok');
    });

});