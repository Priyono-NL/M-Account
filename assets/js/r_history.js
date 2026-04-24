function viewDetail(id) {
    let form = document.createElement("form");
    form.setAttribute("method", "post");
    form.setAttribute("action", "/m-account/receive");

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

    function loadFilteredHistory() {
        $.ajax({
            url: "index.php?page=receive",
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
                    let tbody = $("#historyTable tbody");
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append(`
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="fa-solid fa-folder-open fs-3"></i></div>
                                    <span class="fst-italic">Belum ada riwayat transaksi yang ditemukan.</span>
                                </td>
                            </tr>
                        `);
                        return;
                    }

                    res.data.forEach(function(t) {
                        let warehouseName = t.warehouse == '1' ? 'Gudang BS' : (t.warehouse == '2' ? 'Gudang Sampah' : t.warehouse);
                                                
                        let dateObj = new Date(t.date_receive);
                        let formattedDate = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

                        let tr = `
                            <tr>
                                <td class="ps-4 text-center">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-2 fw-normal">
                                        ${warehouseName}
                                    </span>
                                </td>                              
                                <td class="fw-bold text-primary">${t.doc_number}</td>                                
                                <td class="fw-medium text-dark">${t.received_by}</td>                                
                                <td class="text-center text-muted">${formattedDate}</td>                               
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

    loadFilteredHistory();

    $("#search").on("keyup", loadFilteredHistory);
    $("#filterWarehouse, #startDate, #endDate").on("change", loadFilteredHistory);

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        loadFilteredHistory();
    });

    $("#btnResetAll").click(function() {
        $("#search").val("");
        $("#filterWarehouse").val("");
        $("#startDate").val("");
        $("#endDate").val("");
        
        loadFilteredHistory();
    });

    $("#btnExportExcel").click(function() {
        let payload = {
            action: 'export_xls',
            search: $("#search").val() || "",
            start_date: $("#startDate").val() || "",
            end_date: $("#endDate").val() || "",
            warehouse: $("#filterWarehouse").val() || "",
        };

        downloadExcelAjax(this, '/m-account/receive', payload, 'Laporan_Penerimaan');
    });

});