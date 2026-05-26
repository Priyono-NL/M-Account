$(document).ready(function() {	
	
	let tbody = $("#historyTable tbody");

    function loadFilteredHistory() {
        $.ajax({
            url: "index.php?page=history",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                start_date: $("#startDate").val(),
                end_date: $("#endDate").val()
            },
            success: function(res) {
                if (res.status === "success") {                    
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center py-5 text-muted italic">Tidak ada riwayat transaksi ditemukan.</td></tr>');
                        return;
                    }

                    res.data.forEach(function(t) {
                        let warehouseName = t.warehouse == '1' ? 'Gudang BS' : (t.warehouse == '2' ? 'Gudang Sampah' : t.warehouse);
                        
                        let typeBadge = t.type === 'IN' 
                            ? '<span class="badge bg-success bg-opacity-10 text-success border-0 px-2 py-1">MASUK</span>' 
                            : '<span class="badge bg-danger bg-opacity-10 text-danger border-0 px-2 py-1">KELUAR</span>';
                        
                        let qtySymbol = t.type === 'IN' ? '+' : '-';
                        
                        let dateObj = new Date(t.transaction_date);
                        let formattedDate = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

                        let tr = `
                            <tr>
                                <td class="ps-4 text-muted">
                                    ${formattedDate}
                                </td>
                                <td class="fw-medium text-dark">${t.reference_no}</td>
                                <td>
                                    <div class="fw-bold text-primary">${t.item_name}</div>
                                    <small class="text-muted" style="font-size: 11px;">${t.item_code}</small>
                                </td>
                                <td class="text-center fw-medium">${warehouseName}</td>
                                <td class="text-center">${typeBadge}</td>
                                <td class="text-center fw-bold fs-6">${qtySymbol}${t.qty}</td>
                                <td class="pe-4 text-muted small">${t.notes || '-'}</td>
                            </tr>
                        `;
                        tbody.append(tr);
                    });
                }
            },
            error: function() {
                console.error("Gagal menarik data riwayat.");
            }
        });
    }
	
	function clearTable() {
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

    $("#search").on("keyup", clearTable);
    $("#filterWarehouse, #startDate, #endDate").on("change", clearTable);
	
	$("#btnFilter").click(function() {
		loadFilteredHistory();
	});
	
    $("#btnClearSearch").click(function() {
        $("#search").val("");
		clearTable();
    });

    $("#btnResetAll").click(function() {
		let before = new Date();
		let now = new Date();
		before.setDate(before.getDate() - 14);
		now.setDate(now.getDate());
		let beforeLokal = before.getFullYear() + '-' + String(before.getMonth() + 1).padStart(2, '0') + '-' + String(before.getDate()).padStart(2, '0');
		let nowLokal = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0'); 
		
        $("#search").val("");
        $("#filterWarehouse").val("");
        $("#startDate").val(beforeLokal);
        $("#endDate").val(nowLokal);
		clearTable();
    });

    $("#btnExportExcel").click(function() {
        let payload = {
            action: 'export_xls',
            search: $("#search").val() || "",
            start_date: $("#startDate").val() || "",
            end_date: $("#endDate").val() || "",
            warehouse: $("#filterWarehouse").val() || ""
        };

        downloadExcelAjax(this, window.location.href, payload, 'Laporan_Transaksi');
    });

});