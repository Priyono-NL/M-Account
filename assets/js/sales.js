function viewDetail(id) {
    let form = document.createElement("form");
    form.setAttribute("method", "post");
    form.setAttribute("action", "index.php?page=pos");

    let actionInput = document.createElement("input");
    actionInput.setAttribute("type", "hidden");
    actionInput.setAttribute("name", "action");
    actionInput.setAttribute("value", "index");
    form.appendChild(actionInput);

    let invoiceInput = document.createElement("input");
    invoiceInput.setAttribute("type", "hidden");
    invoiceInput.setAttribute("name", "id");
    invoiceInput.setAttribute("value", id);
    form.appendChild(invoiceInput);

    let modeInput = document.createElement("input");
    modeInput.setAttribute("type", "hidden");
    modeInput.setAttribute("name", "mode");
    modeInput.setAttribute("value", "view");
    form.appendChild(modeInput);

    document.body.appendChild(form);
    form.submit();
}

$(document).ready(function() {
	
	let tbody = $("#historyTable tbody");

    function loadFilteredHistory() {
        $.ajax({
            url: "index.php?page=pos",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                type: $("#filterType").val(),
                start_date: $("#startDate").val(),
                end_date: $("#endDate").val()
            },
            success: function(res) {
                if (res.status === "success") {
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append(`
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="fa-solid fa-folder-open fs-3"></i></div>
                                    <span class="fst-italic">Belum ada riwayat transaksi yang ditemukan.</span>
                                </td>
                            </tr>
                        `);
                        return;
                    }

                    res.data.forEach(function(t) {
                        let warehouseName = t.warehouse == '1' ? 'Gudang BS' : (t.warehouse == '2' ? 'Gudang Sampah' : t.warehouse);
                        
                        let typeBadge = t.sale_type === 'SLS' 
                            ? '<span class="badge bg-success bg-opacity-10 text-success border-0 px-2 py-1"><i class="fa-solid fa-arrow-trend-up me-1"></i> Normal</span>' 
                            : '<span class="badge bg-primary bg-opacity-10 text-primary border-0 px-2 py-1"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Expense</span>';
                        
                        let dateObj = new Date(t.sales_date);
                        let formattedDate = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

                        let formattedTotal = t.sale_type === 'EXP' ? '<span class="text-muted">-</span>' : formatRupiah(t.total);

                        let tr = `
                            <tr>
                                <td class="ps-4 text-center">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-2 fw-normal">
                                        ${warehouseName}
                                    </span>
                                </td>                                
                                <td class="text-center">${typeBadge}</td>                                
                                <td class="fw-bold text-primary">${t.invoice_no}</td>                                
                                <td class="fw-medium text-dark">${t.buyer_name}</td>                                
                                <td class="text-center text-muted">${formattedDate}</td>                                
                                <td class="text-end fw-bold text-dark">${formattedTotal}</td>                                
                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm">
                                        <button type="button" class="btn btn-light btn-sm border text-primary" title="Lihat Detail Transaksi" onclick="viewDetail('${t.id}')">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
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
    $("#filterWarehouse, #startDate, #endDate, #filterType").on("change", clearTable);
	
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
        $("#filterType").val("");
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
            warehouse: $("#filterWarehouse").val() || "",
            type: $("#filterType").val() || "",
        };

        downloadExcelAjax(this, '/m-account/pos', payload, 'Laporan_Penjualan');
    });

});