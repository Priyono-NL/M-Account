$(document).ready(function() {

    function loadFilteredHistory() {
        $("#pivot_loading").show();
        $("#pivot_output").hide();

        $.ajax({
            url: "index.php?page=stockClose",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                closeMonth: $("#closeMonth").val()
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
                            "Gudang": row.warehouse == '1' ? 'Gudang BS' : 'Gudang Sampah',
                            "Bulan": row.periode,
                            "Kode": row.item_code,
                            "Nama Barang": row.item_name,
                            "Satuan": row.item_uom,
                            "Stok Awal": parseInt(row.qty_open),
                            "Masuk": parseInt(row.qty_in),
                            "Keluar": parseInt(row.qty_out),
                            "Stok Akhir": parseInt(row.qty_onhand),
                            "Selisih": parseInt(row.selisih)
                        };
                    });
                    
                    var agg = $.pivotUtilities.aggregators;
                    $("#pivot_output").show().pivotUI(mappedData, {
                        rows: ["Nama Barang"],
                        cols: ["Gudang"],
                        vals: ["Stok Akhir"],

                        aggregators: { "Total Angka (Sum)": agg["Integer Sum"] },                        
                        aggregatorName: "Total Angka (Sum)",
                        
                        renderers: $.extend(
                            $.pivotUtilities.renderers, 
                            $.pivotUtilities.plotly_renderers,
                            $.pivotUtilities.export_renderers
                        ),
                        
                        rendererName: "Table", 

                        onRefresh: function(config) {
                            $(".pvtTable").addClass("table table-sm table-bordered mt-3");

                            $(".pvt-custom-label").remove();
                            $(".pvtUnused").prepend("<div class='pvt-custom-label text-muted small fw-bold mb-2' style='font-size:10px;'>DAFTAR KOLOM & FILTER</div>");
                            $(".pvtCols").prepend("<div class='pvt-custom-label text-primary small fw-bold mb-2' style='font-size:10px;'>SUMBU KOLOM (HORIZONTAL)</div>");
                            $(".pvtRows").prepend("<div class='pvt-custom-label text-primary small fw-bold mb-2' style='font-size:10px;'>SUMBU BARIS (VERTIKAL)</div>");
                            $(".pvtVals").prepend("<div class='pvt-custom-label text-warning small fw-bold mb-2' style='font-size:10px;'>SETTING NILAI</div>");
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

    // Jalankan pertama kali saat halaman dimuat
    loadFilteredHistory();

    // Event handler untuk tombol filter
    $("#search").on("keyup", loadFilteredHistory);
    $("#filterWarehouse, #closeMonth").on("change", loadFilteredHistory);

    // Reset Filter
    $("#btnResetAll").click(function() {
        $("#search").val("");
        $("#filterWarehouse").val("");
        
        let now = new Date();
        let currentMonth = now.getFullYear() + "-" + ("0" + (now.getMonth() + 1)).slice(-2);
        $("#closeMonth").val(currentMonth);
        
        loadFilteredHistory();
    });

    // Download Excel for pivot table
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