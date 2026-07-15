// 1. Fungsi Loader Script Berbasis Promise
function loadPivotScript(url) {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${url}"]`)) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = url;
        script.defer = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Gagal memuat: ${url}`));
        document.head.appendChild(script);
    });
}

// Global flag untuk menandakan status kesiapan library
window.pivotLibrariesLoaded = false;

window.addEventListener('load', () => {
    Promise.all([
        loadPivotScript("https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.js"),
        loadPivotScript("https://cdnjs.cloudflare.com/ajax/libs/plotly.js/2.32.0/plotly-basic.min.js"),
        loadPivotScript("https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.js"),
    ]).then(() => {
        console.log("Semua dependensi PivotTable & Plotly berhasil dimuat.");
        window.pivotLibrariesLoaded = true;
    }).catch(err => {
        console.error("Gagal memuat library pivot di latar belakang:", err);
    });
});

// 3. Logika Utama Aplikasi (Di dalam jQuery Ready)
$(document).ready(function() {

    let debounceTimer;
    function debounce(func, delay) {
        return function(...args) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function loadFilteredHistory() {
        if (!window.pivotLibrariesLoaded) {
            $("#pivot_loading").show();
            $("#pivot_loading span").text("Menyiapkan komponen visualisasi (Mohon tunggu)...");
            $("#pivot_output").hide();
            
            setTimeout(loadFilteredHistory, 300);
            return;
        }

        $("#pivot_loading span").text("Loading Data...");
        $("#pivot_loading").show();
        $("#pivot_output").hide();

        $.ajax({
            url: "index.php?page=receive",
            type: "POST",
            dataType: "json",
            data: {
                action: "pivot_api", 
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                start_date: $("#startDate").val(),
                end_date: $("#endDate").val()
            },
            success: function(res) {
                $("#pivot_loading").hide();

                if (res.status === "success") {
                    if (!res.data || res.data.length === 0) {
                        $("#pivot_output").show().html('<div class="text-center py-5 text-muted">Data tidak ditemukan untuk periode ini.</div>');
                        return;
                    }

                    let totalUniqueValues = {};
                    let sets = {}; 

                    // Memetakan kolom detail barang sesuai Query getDetailedFiltered Penerimaan
                    let mappedData = res.data.map(function(row) {
                        let qtyVal = parseFloat(row.item_qty || 0);
                        
                        let item = {                             
                            "Tgl Penerimaan": row.date_receive,
                            "No. Dokumen": row.doc_number,
                            "Nama Barang": row.item_name || "",
                            "Kode Barang": row.item_code || "",
                            "Satuan": row.item_uom || "Pcs",
                            "Qty Masuk": qtyVal,
                            "Penerima": row.received_by
                        };

                        for (let key in item) {
                            if (!sets[key]) sets[key] = new Set();
                            sets[key].add(item[key]);
                        }

                        return item;
                    });

                    for (let key in sets) {
                        totalUniqueValues[key] = sets[key].size;
                    }
                    sets = null; 
                    
                    // KUNCI UTAMA: Formatter bilangan bulat bersih tanpa desimal ,00
                    const pivotIntFormatter = (number) => {
                        if (number === 0 || isNaN(number)) return "-";
                        return number.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                    };
                    
                    const tpl = $.pivotUtilities.aggregatorTemplates;
                    let $pivotOutput = $("#pivot_output");

                    $pivotOutput.removeData("pivotUIOptions").empty().show().pivotUI(mappedData, {
                        // Susunan rincian berjenjang di sebelah kiri (Rows)
                        rows: ["Tgl Penerimaan", "No. Dokumen", "Penerima", "Kode Barang", "Nama Barang"], 
                        cols: [], 
                        vals: ["Qty Masuk"],
                        aggregators: {
                            // Menyuntikkan formatter bilangan bulat ke jumlahan qty
                            "Total Qty Masuk": function() { 
                                return tpl.sum(pivotIntFormatter)(["Qty Masuk"]) 
                            }
                        },
                        aggregatorName: "Total Qty Masuk",
                        renderers: $.pivotUtilities.renderers,
                        rendererName: "Table", 
                        
                        onRefresh: function(config) {
                            let $table = $(".pvtTable");
                            $table.addClass("table table-sm table-bordered mt-3");
                            $(".pvtCols, .pvtVals").hide();
                            $(".pvtTotal, .pvtTotalLabel, .pvtGrandTotal").show();
                            
                            $(".pvtAttr").each(function() {
                                let $this = $(this);
                                let attrName = $this.data("attrName");
                                
                                if (attrName && totalUniqueValues[attrName] !== undefined) {
                                    let totalCount = totalUniqueValues[attrName];
                                    let selectedCount = totalCount;
                                    
                                    if (config.exclusions && config.exclusions[attrName]) {
                                        selectedCount = totalCount - Object.keys(config.exclusions[attrName]).length;
                                    } else if (config.inclusions && config.inclusions[attrName]) {
                                        selectedCount = Object.keys(config.inclusions[attrName]).length;
                                    }
                                    
                                    let newLabel = attrName + " (" + selectedCount + "/" + totalCount + ") ";
                                    let textNode = $this.contents().filter(function() { 
                                        return this.nodeType === 3;
                                    })[0];
                                    
                                    if (textNode) {
                                        textNode.nodeValue = newLabel;
                                    }
                                }
                            });
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

    function clearPivot() {
        $("#pivot_loading").show();
        $("#pivot_output").hide();
    }

    const debouncedClearPivot = debounce(clearPivot, 250);

    $("#search").on("keyup", debouncedClearPivot);
    $("#filterWarehouse, #startDate, #endDate").on("change", debouncedClearPivot);
    
    $("#btnFilter").click(function() {
        loadFilteredHistory();
    });
    
    $("#btnClearSearch").click(function() {
        $("#search").val("");
        clearPivot();
    });

    $("#btnResetAll").click(function() {
        let before = new Date();
        let now = new Date();
        before.setDate(before.getDate() - 14);
        
        let beforeLokal = before.getFullYear() + '-' + String(before.getMonth() + 1).padStart(2, '0') + '-' + String(before.getDate()).padStart(2, '0');
        let nowLokal = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0'); 
        let firstWarehouseVal = $("#filterWarehouse option:first").val();
        
        $("#search").val("");        
        $("#filterWarehouse").val(firstWarehouseVal);
        $("#startDate").val(beforeLokal);
        $("#endDate").val(nowLokal);
        
        clearPivot();
    });

    // VERSI ASLI: Fungsi tombol ekspor murni mengambil outerHTML sesuai permintaan
    $("#btnExportExcel").on("click", function() {
        let pivotTable = document.querySelector(".pvtTable");        

        if (!pivotTable) {
            showNotification("Tabel pivot kosong! Silakan atur pivot terlebih dahulu.", "warning");
            return;
        }
        let requestData = {
            action: 'export_xls',
            tabel_html: pivotTable.outerHTML
        };

        let sDateVal = $("#startDate").val() || "";
        let eDateVal = $("#endDate").val() || "";
        let fStart = sDateVal.replace(/-/g, "");
        let fEnd = eDateVal.replace(/-/g, "");
        let dynamicFileName = 'Laporan_Penerimaan_Pivot_' + fStart + '_sd_' + fEnd;

        downloadExcelAjax(this, window.location.href, requestData, dynamicFileName);
    });

});