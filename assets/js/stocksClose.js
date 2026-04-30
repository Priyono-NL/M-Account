$(document).ready(function() {

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
                        
                        let dateObj = new Date(t.date || t.transaction_date);
                        let formattedDate = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

                        let qtyInStr = t.qty_in > 0 ? `<span class="text-success">+${t.qty_in}</span>` : '0';
                        let qtyOutStr = t.qty_out > 0 ? `<span class="text-danger">-${t.qty_out}</span>` : '0';

                        let selisihStr = t.selisih != 0 ? `<span class="text-danger fw-bold">${t.selisih}</span>` : t.selisih;

                        let tr = `
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">${t.item_name}</div>
                                    <small class="text-muted" style="font-size: 11px;">${t.item_code}</small>
                                </td>
                                <td class="text-center fw-medium text-muted">${t.qty_open}</td>
                                <td class="text-center fw-bold">${qtyInStr}</td>
                                <td class="text-center fw-bold">${qtyOutStr}</td>
                                <td class="text-center fw-bold">${t.qty_close}</td>
                                <td class="text-center fw-bold">${t.qty_onhand}</td>
                                <td class="text-center fw-bold">${selisihStr}</td>
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