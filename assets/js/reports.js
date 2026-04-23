$(document).ready(function() {

    function loadFilteredHistory() {
        $.ajax({
            url: "index.php?page=history",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#searchHistory").val(),
                warehouse: $("#filterWarehouse").val(),
                start_date: $("#startDate").val(),
                end_date: $("#endDate").val()
            },
            success: function(res) {
                if (res.status === "success") {
                    let tbody = $("#historyTable tbody");
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
                        let formattedTime = dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                        let tr = `
                            <tr>
                                <td class="ps-4 text-muted">
                                    ${formattedDate}<br>
                                    <small style="font-size: 10px;">${formattedTime}</small>
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

    // Event listener: hapus #filterType dari sini
    $("#searchHistory").on("keyup", loadFilteredHistory);
    $("#filterWarehouse, #startDate, #endDate").on("change", loadFilteredHistory);

    $("#btnClearSearch").click(function() {
        $("#searchHistory").val("");
        loadFilteredHistory();
    });

    $("#btnResetAll").click(function() {
        $("#searchHistory").val("");
        $("#filterWarehouse").val("");
        $("#startDate").val("");
        $("#endDate").val("");
        
        loadFilteredHistory();
    });

});