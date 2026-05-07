$(document).ready(function() {

    function loadFilteredHistory() {
        $("#pivot_loading").show();
        $("#pivot_output").hide();

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
                $("#pivot_loading").hide();

                if (res.status === "success") {
                    if (res.data.length === 0) {
                        $("#pivot_output").show().html('<div class="text-center py-5 text-muted">Data tidak ditemukan untuk periode ini.</div>');
                        return;
                    }

                    let mappedData = res.data.map(function(row) {
                        return {                            
                            "Tipe Penjualan": row.sale_type,
                            "No. Invoice": row.invoice_no,
                            "Gudang": row.warehouse == '1' ? 'Gudang BS' : 'Gudang Sampah',
                            "Tgl Penjualan": row.sales_date,
                            "Nama Pembeli": row.buyer_name,
                            "Kode Pembeli": row.buyer_code,
                            "Total": row.sale_type === 'EXP' ? 0 : parseFloat(row.total)
                        };
                    });

                    const pivotRupiahFormatter = (number) => {
                        if (number === 0 || isNaN(number)) return "-";
                        return formatRupiah(number);
                    };

                    const tpl = $.pivotUtilities.aggregatorTemplates;

                    $("#pivot_output").show().pivotUI(mappedData, {
                        rows: ["Tgl Penjualan", "No. Invoice", "Nama Pembeli"], 
                        cols: [], 
                        vals: ["Total"],

                        aggregators: {
                            "Sum Total": function() { 
                                return tpl.sum(pivotRupiahFormatter)(["Total"]) 
                            }
                        },
                        aggregatorName: "Sum Total",
                        
                        renderers: $.extend(
                            $.pivotUtilities.renderers
                        ),
                        
                        rendererName: "Table", 

                        onRefresh: function(config) {
                            $(".pvtTable").addClass("table table-sm table-bordered mt-3");
                            $(".pvtCols, .pvtVals").hide();
                            $(".pvtTotal, .pvtTotalLabel, .pvtGrandTotal").show();
                        }
                    });
                }
            },
            error: function(xhr, status, error) {
                $("#pivot_loading").hide();
                console.error("AJAX Error: " + error);
                alert("Gagal mengambil data dari server.");
            }
        });
    }

    loadFilteredHistory();

    $("#search").on("keyup", loadFilteredHistory);
    $("#filterWarehouse, #startDate, #endDate, #filterType").on("change", loadFilteredHistory);

    $("#btnResetAll").click(function() {
        $("#search").val("");
        $("#filterWarehouse").val("");
        $("#filterType").val("");
        $("#startDate").val("");
        $("#endDate").val("");
        
        loadFilteredHistory();
    });

    $("#btnExportExcel").on("click", function() {
    let pivotTable = document.querySelector(".pvtTable");

    if (!pivotTable) {
        showNotification("Tabel pivot kosong! Silakan atur pivot terlebih dahulu.", "warning");
        return;
    }

    let tableHTML = pivotTable.outerHTML;

    let requestData = {
        action: 'export_xls',
        tabel_html: tableHTML
    };

    downloadExcelAjax(this, '/m-account/test', requestData, 'hasil_pivot');
});

});