$(document).ready(function() {

    function loadFilteredHistory() {
        $.ajax({
            url: "index.php?page=stocks",
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
                    let tbody = $("#stockTable tbody");
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center py-5 text-muted italic">Tidak ada riwayat transaksi ditemukan.</td></tr>');
                        return;
                    }

                    res.data.forEach(function(t) {
                        let warehouseName = t.warehouse == '1' ? 'Gudang BS' : (t.warehouse == '2' ? 'Gudang Sampah' : t.warehouse);
                        
                        let dateObj = new Date(t.date || t.transaction_date); // Antisipasi nama kolom date/transaction_date
                        let formattedDate = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

                        let qtyInStr = t.qty_in > 0 ? `<span class="text-success">+${t.qty_in}</span>` : '0';
                        let qtyOutStr = t.qty_out > 0 ? `<span class="text-danger">-${t.qty_out}</span>` : '0';

                        let tr = `
                            <tr>
                                <td class="ps-4 text-muted">
                                    ${formattedDate}<br>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-2 fw-normal">
                                        ${warehouseName}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">${t.item_name}</div>
                                    <small class="text-muted" style="font-size: 11px;">${t.item_code}</small>
                                </td>
                                <td class="text-center fw-medium text-muted">${t.qty_open}</td>
                                <td class="text-center fw-bold">${qtyInStr}</td>
                                <td class="text-center fw-bold">${qtyOutStr}</td>
                                <td class="text-center fw-bold fs-6 text-primary pe-4">${t.qty_close}</td>
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
            warehouse: $("#filterWarehouse").val() || ""
        };

        downloadExcelAjax(this, '/m-account/stocks', payload, 'Laporan_Stok');
    });

});