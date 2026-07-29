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
            url: "index.php?page=pos",
            type: "POST",
            dataType: "json",
            data: {
                action: "pivot_api", 
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                type: $("#filterType").val(),
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

                    // ==========================================
                    // HELPER UTILITY
                    // ==========================================
                    const formatDateCustom = (dateStr) => {
                        if (!dateStr) return "-";
                        let cleanDate = dateStr.split(" ")[0]; 
                        let parts = cleanDate.split("-");
                        
                        if (parts.length === 3) {
                            let year = parts[0].slice(-2); 
                            let monthIdx = parseInt(parts[1], 10) - 1;
                            let day = String(parseInt(parts[2], 10)).padStart(2, '0');
                            const months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
                            
                            if (monthIdx >= 0 && monthIdx < 12) {
                                return `${day}-${months[monthIdx]}-${year}`;
                            }
                        }
                        return dateStr;
                    };

                    const pivotNumberFormatter = (number) => {
                        if (number === 0 || isNaN(number) || !number) return "-";
                        return new Intl.NumberFormat("id-ID").format(number);
                    };

                    // ==========================================
                    // DATA MAPPING
                    // ==========================================
                    let totalUniqueValues = {};
                    let sets = {}; 

                    let mappedData = res.data.map(function(row) {
                        let isExpense = (row.sale_type === 'EXP' || row.sales_type === 'EXP');
                        let subtotalVal = isExpense ? 0 : parseFloat(row.subtotal || 0);
                        let hargaSatuan = isExpense ? 0 : parseFloat(row.unit_price || 0);
                        let qtyVal = parseFloat(row.item_qty || 0);
                        
                        let item = {                             
                            "Tipe Penjualan": row.sale_type || row.sales_type || "SLS",
                            "No. Invoice": row.invoice_no,
                            "Tgl Penjualan": formatDateCustom(row.sales_date), 
                            "Nama Pembeli": row.buyer_name,
                            "Kode Pembeli": row.buyer_code,
                            "Kode Barang": row.item_code || "",
                            "Nama Barang": row.item_name || "",
                            "Satuan": row.item_uom || "Pcs",
                            "Qty": qtyVal,
                            "Harga Satuan": hargaSatuan,
                            "Total": subtotalVal
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

                    // ==========================================
                    // PIVOT TABLE CONFIGURATION
                    // ==========================================
                    const tpl = $.pivotUtilities.aggregatorTemplates;
                    let $pivotOutput = $("#pivot_output");

                    $pivotOutput.removeData("pivotUIOptions").empty().show().pivotUI(mappedData, {
                        rows: ["Kode Barang", "Nama Barang", "Tgl Penjualan", "No. Invoice", "Qty"], 
                        cols: [], 
                        vals: ["Total"],
                        unusedAttrsVertical: false,
                        aggregators: {
                            "Sum Total Nominal": function() { 
                                return tpl.sum(pivotNumberFormatter)(["Total"]); 
                            }
                        },
                        aggregatorName: "Sum Total Nominal",
                        renderers: $.pivotUtilities.renderers,
                        rendererName: "Table", 
                        
                        onRefresh: function(config) {
                            let $table = $(".pvtTable");
                            
                            $table.addClass("table table-sm table-bordered align-middle mt-2 mb-0")
                                  .css({"font-size": "12px", "width": "100%"});
                            
                            $(".pvtCols, .pvtVals").hide();
                            $(".pvtTotal, .pvtTotalLabel, .pvtGrandTotal").show();

                            let rowsArray = config.rows || [];
                            let rowsCount = rowsArray.length;
                            let qtyIdx = rowsArray.indexOf("Qty");

                            // DYNAMIC TRIGGER DETECTION (BARU: AMBIL PALING LUAR)
                            let colTanggal = rowsArray.indexOf("Tgl Penjualan");
                            
                            let idxKode = rowsArray.indexOf("Kode Barang");
                            let idxNama = rowsArray.indexOf("Nama Barang");
                            let colBarang = -1;
                            
                            if (idxKode !== -1 && idxNama !== -1) {
                                colBarang = Math.min(idxKode, idxNama); // Keduanya ada -> Ambil paling luar (kiri)
                            } else {
                                colBarang = Math.max(idxKode, idxNama); // Cuma 1 yang ada -> Ambil yang ada
                            }

                            // ---------------------------------------------------------
                            // 1. AKUMULASI HIERARKI DATA DENGAN ARRAY FILTER CHECK
                            // ---------------------------------------------------------
                            let grandTotalQty = 0;
                            let barangSubtotals = {}; 
                            let tanggalSubtotals = {}; 

                            mappedData.forEach(item => {
                                let isExcluded = false;

                                if (config.exclusions) {
                                    for (let attr in config.exclusions) {
                                        if (Array.isArray(config.exclusions[attr]) && config.exclusions[attr].includes(String(item[attr]))) {
                                            isExcluded = true; break;
                                        }
                                    }
                                }
                                if (!isExcluded && config.inclusions) {
                                    for (let attr in config.inclusions) {
                                        let inclArr = config.inclusions[attr];
                                        if (Array.isArray(inclArr) && inclArr.length > 0 && !inclArr.includes(String(item[attr]))) {
                                            isExcluded = true; break;
                                        }
                                    }
                                }

                                if (!isExcluded) {
                                    let qty = parseFloat(item["Qty"] || 0);
                                    let total = parseFloat(item["Total"] || 0);

                                    grandTotalQty += qty;

                                    // Bangun Unique Key path untuk Barang
                                    if (colBarang !== -1) {
                                        let parts = [];
                                        for(let i = 0; i <= colBarang; i++) parts.push(item[rowsArray[i]]);
                                        let key = parts.join("|||");
                                        if (!barangSubtotals[key]) barangSubtotals[key] = { qty: 0, total: 0, label: item[rowsArray[colBarang]] };
                                        barangSubtotals[key].qty += qty;
                                        barangSubtotals[key].total += total;
                                    }

                                    // Bangun Unique Key path untuk Tanggal
                                    if (colTanggal !== -1) {
                                        let parts = [];
                                        for(let i = 0; i <= colTanggal; i++) parts.push(item[rowsArray[i]]);
                                        let key = parts.join("|||");
                                        if (!tanggalSubtotals[key]) tanggalSubtotals[key] = { qty: 0, total: 0, label: item[rowsArray[colTanggal]] };
                                        tanggalSubtotals[key].qty += qty;
                                        tanggalSubtotals[key].total += total;
                                    }
                                }
                            });

                            // ---------------------------------------------------------
                            // 2. DETEKSI AKHIR SETIAP KELOMPOK (INJEKSI TARGET)
                            // ---------------------------------------------------------
                            $table.find(".pvtSubtotalRow").remove();

                            let $rows = $table.find("tbody > tr");
                            let insertions = {}; 
                            let currentPath = [];

                            $rows.each(function(rIdx) {
                                let $tr = $(this);
                                let $ths = $tr.children("th.pvtRowLabel");
                                let nTh = $ths.length;
                                if (nTh === 0) return;

                                let startCol = rowsCount - nTh;

                                $ths.each(function(i) {
                                    let actualCol = startCol + i;
                                    let val = $(this).text().trim();
                                    currentPath[actualCol] = val; // Merekam path baris yang aktif

                                    let span = parseInt($(this).attr("rowspan") || "1", 10);
                                    let targetIdx = rIdx + span - 1; // Baris terakhir di mana kelompok ini berakhir

                                    if (actualCol === colBarang) {
                                        let key = currentPath.slice(0, colBarang + 1).join("|||");
                                        if (barangSubtotals[key]) {
                                            if (!insertions[targetIdx]) insertions[targetIdx] = [];
                                            insertions[targetIdx].push({
                                                colIdx: colBarang,
                                                label: `Subtotal Barang (${val})`,
                                                qty: barangSubtotals[key].qty,
                                                total: barangSubtotals[key].total,
                                                bgClass: "table-secondary"
                                            });
                                        }
                                    }

                                    if (actualCol === colTanggal) {
                                        let key = currentPath.slice(0, colTanggal + 1).join("|||");
                                        if (tanggalSubtotals[key]) {
                                            if (!insertions[targetIdx]) insertions[targetIdx] = [];
                                            insertions[targetIdx].push({
                                                colIdx: colTanggal,
                                                label: `Subtotal Tgl (${val})`,
                                                qty: tanggalSubtotals[key].qty,
                                                total: tanggalSubtotals[key].total,
                                                bgClass: "table-light"
                                            });
                                        }
                                    }
                                });
                            });

                            // ---------------------------------------------------------
                            // 3. EKSEKUSI INJEKSI & KALKULASI MATRIKS ROWSPAN
                            // ---------------------------------------------------------
                            let targetIndices = Object.keys(insertions).map(Number).sort((a, b) => b - a);
                            let targetQtyCol = (qtyIdx !== -1) ? qtyIdx : (rowsCount - 1);

                            targetIndices.forEach(targetIdx => {
                                let $currentAnchor = $rows.eq(targetIdx);

                                // Sort descending colIdx: Inner groups close first, then Outer groups
                                insertions[targetIdx].sort((a, b) => b.colIdx - a.colIdx);

                                insertions[targetIdx].forEach(sub => {
                                    
                                    // Ambil semua TH yang saat ini mengunci (span) row targetIdx
                                    let activeThs = [];
                                    $rows.slice(0, targetIdx + 1).each(function(pIdx) {
                                        $(this).children("th.pvtRowLabel").each(function() {
                                            let rSpan = parseInt($(this).attr("rowspan") || "1", 10);
                                            if (pIdx + rSpan - 1 >= targetIdx) {
                                                activeThs.push({ el: this, startRow: pIdx });
                                            }
                                        });
                                    });

                                    let indentCols = sub.colIdx; 
                                    
                                    // Tambah rowspan HANYA untuk kolom Induk (Outer Level)
                                    for (let i = 0; i < indentCols; i++) {
                                        if (activeThs[i]) {
                                            let rSpan = parseInt($(activeThs[i].el).attr("rowspan") || "1", 10);
                                            $(activeThs[i].el).attr("rowspan", rSpan + 1);
                                        }
                                    }

                                    let labelColspan = targetQtyCol - indentCols;
                                    if (labelColspan < 1) labelColspan = 1;

                                    let borderTop = (sub.colIdx === 0) ? '2px solid #adb5bd' : '1px solid #dee2e6';

                                    let subHtml = `<tr class="pvtSubtotalRow ${sub.bgClass} fw-bold" style="border-top: ${borderTop};">`;
                                    subHtml += `<td colspan="${labelColspan}" class="text-end fw-bold align-middle py-2 text-dark">${sub.label}:</td>`;
                                    
                                    if (qtyIdx !== -1) {
                                        subHtml += `<td class="text-end fw-bold text-dark align-middle py-2">${pivotNumberFormatter(sub.qty)}</td>`;
                                    }

                                    subHtml += `<td class="text-end fw-bold text-primary align-middle py-2">${pivotNumberFormatter(sub.total)}</td>`;
                                    subHtml += `</tr>`;

                                    let $newRow = $(subHtml);
                                    $currentAnchor.after($newRow);
                                    $currentAnchor = $newRow; 
                                });
                            });

                            // ---------------------------------------------------------
                            // 4. GRAND TOTAL DI FOOTER TABEL
                            // ---------------------------------------------------------
                            let $totalRow = $table.find("tr:has(.pvtGrandTotal)");
                            let $totalLabel = $totalRow.find(".pvtTotalLabel");

                            if ($totalRow.length && qtyIdx !== -1 && rowsCount > 1) {
                                $totalRow.find(".pvtQtyTotalCell").remove();

                                let labelColspan = qtyIdx;
                                $totalLabel.attr("colspan", labelColspan).text("Grand Total").addClass("text-end align-middle fw-bold py-2 fs-6");

                                $('<th class="pvtTotalLabel text-end fw-bold text-dark pvtQtyTotalCell bg-light align-middle py-2 fs-6">' + pivotNumberFormatter(grandTotalQty) + '</th>')
                                    .insertBefore($totalRow.find(".pvtGrandTotal"));
                            }

                            // ---------------------------------------------------------
                            // 5. UPDATE LABEL FILTER COUNT ATTR (ARRAY CHECK)
                            // ---------------------------------------------------------
                            $(".pvtAttr").each(function() {
                                let $this = $(this);
                                let attrName = $this.data("attrName");
                                
                                if (attrName && totalUniqueValues[attrName] !== undefined) {
                                    let totalCount = totalUniqueValues[attrName];
                                    let selectedCount = totalCount;
                                    
                                    if (config.exclusions && Array.isArray(config.exclusions[attrName])) {
                                        selectedCount = totalCount - config.exclusions[attrName].length;
                                    } else if (config.inclusions && Array.isArray(config.inclusions[attrName])) {
                                        selectedCount = config.inclusions[attrName].length;
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
    $("#filterWarehouse, #startDate, #endDate, #filterType").on("change", debouncedClearPivot);
    
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
        $("#filterType").val("");
        $("#startDate").val(beforeLokal);
        $("#endDate").val(nowLokal);
        
        clearPivot();
    });

    // =========================================================
    // EXPORT EXCEL DENGAN AUTO-FORMATTING ANGKA (ANTI TERPOTONG)
    // =========================================================
    $("#btnExportExcel").on("click", function() {
        let pivotTable = document.querySelector(".pvtTable");        

        if (!pivotTable) {
            if (typeof showNotification !== 'undefined') {
                showNotification("Tabel pivot kosong! Silakan atur pivot terlebih dahulu.", "warning");
            } else {
                alert("Tabel pivot kosong! Silakan atur pivot terlebih dahulu.");
            }
            return;
        }

        let $cloneTable = $(pivotTable).clone();

        $cloneTable.find("th, td").each(function() {
            let cellText = $(this).text().trim();
            if (/^-?\d{1,3}(?:\.\d{3})*$/.test(cellText)) {
                let rawNumber = cellText.replace(/\./g, "");
                $(this).text(rawNumber);
                $(this).attr("style", "mso-number-format:'\\#\\,\\#\\#0';");
            }
        });

        let requestData = {
            action: 'export_xls',
            tabel_html: $cloneTable.prop('outerHTML')
        };

        let sDateVal = $("#startDate").val() || "";
        let eDateVal = $("#endDate").val() || "";
        let fStart = sDateVal.replace(/-/g, "");
        let fEnd = eDateVal.replace(/-/g, "");
        let dynamicFileName = 'Laporan_Penjualan_' + fStart + '_sd_' + fEnd;

        downloadExcelAjax(this, window.location.href, requestData, dynamicFileName);
    });

});