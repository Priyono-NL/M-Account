// =========================================================================
// 1. FUNGSI GLOBAL: GENERIC TEXT PRINT (DRIVER GENERIC / TEXT ONLY VIA BROWSER)
// =========================================================================
function genericPrintSales(saleId, $btnInstance = null) {
    if (!saleId) {
        if (typeof showNotification === "function") showNotification("ID Transaksi tidak valid.", 'danger');
        else alert("ID Transaksi tidak valid.");
        return;
    }

    let originalText = "";
    if ($btnInstance && $btnInstance.length) {
        originalText = $btnInstance.html();
        $btnInstance.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...');
    }

    $.ajax({
        url: 'index.php?page=pos',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getPrintTextApi',
            id: saleId
        },
        success: function(res) {
            if (res.status === true && res.raw_text) {
                // Sediakan container print otomatis jika belum ada di DOM HTML
                if ($('#print-area').length === 0) {
                    $('body').append(`
                        <div id="print-area" class="d-none">
                            <pre id="print-text-content" style="font-family: 'Courier New', Courier, monospace; font-size: 10pt; white-space: pre;"></pre>
                        </div>
                    `);
                }

                // Render teks polos ASCII hasil dari Formatter
                $('#print-text-content').text(res.raw_text);

                // Panggil Dialog Cetak Browser
                window.print();

                if (typeof showNotification === "function") {
                    showNotification("Perintah cetak dikirim ke printer.", 'success');
                }
            } else {
                let msg = res.message || "Gagal memuat data cetakan.";
                if (typeof showNotification === "function") showNotification(msg, 'danger');
                else alert(msg);
            }
        },
        error: function(xhr) {
            let err = 'Terjadi kesalahan saat terhubung ke server.';
            if (xhr.responseJSON && xhr.responseJSON.message) err = xhr.responseJSON.message;

            if (typeof showNotification === "function") showNotification(err, 'danger');
            else alert(err);
        },
        complete: function() {
            if ($btnInstance && $btnInstance.length) {
                $btnInstance.prop('disabled', false).html(originalText);
            }
        }
    });
}

// =========================================================================
// 3. FUNGSI GLOBAL: MENAMPILKAN DETAIL TRANSAKSI & REPRINT VIA MODAL
// =========================================================================
function viewDetail(id) {
    let modalTbody = $("#mdTableItems tbody");
    modalTbody.html('<tr><td colspan="7" class="text-center py-3 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Mengambil detail nota...</td></tr>');
    $("#mdInvNo, #mdBuyer, #mdDate, #mdWarehouse, #mdGrandTotal").text("-");

    $.ajax({
        url: 'index.php?page=pos',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'search_invoice_detail',
            id: id
        },
        success: function(res) {
            if (res.status === 'success') {
                modalTbody.empty();
                let header = res.data.header;
                let items  = res.data.items || [];
                let grandTotal = 0;
                
                let dateObj = new Date(header.sales_date);
                let formattedDate = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                let buyer = header.buyer_name + ' (' + header.buyer_code + ')';
                
                // Isi Profil Atas Nota pada Modal
                $("#mdInvNo").text(header.invoice_no);
                $("#mdSaleType").text(header.sale_type);
                $("#mdBuyer").text(buyer);
                $("#mdDate").text(formattedDate);
                $("#mdWarehouse").text(header.warehouse_name);
                $("#mdRemark").text(header.remark || '');

                // Urai Baris Pecahan Item Barang Terjual
                items.forEach(function(item, idx) {
                    let sub = parseFloat(item.item_qty) * parseFloat(item.unit_price);
                    
                    // Aturan Bisnis: Tipe EXP (Internal Expense) subtotal dihitung 0
                    let displaySub = header.sale_type === 'EXP' ? 0 : sub;
                    grandTotal += displaySub;

                    modalTbody.append(`
                        <tr>
                            <td class="text-center">${idx + 1}</td>
                            <td class="fw-bold text-secondary">${item.item_code}</td>
                            <td>${item.item_name}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">${item.item_uom}</span></td>
                            <td class="text-center fw-bold">${item.item_qty}</td>
                            <td class="text-end">${parseFloat(item.unit_price).toLocaleString('id-ID')}</td>
                            <td class="text-end fw-bold text-dark">${displaySub.toLocaleString('id-ID')}</td>
                        </tr>
                    `);
                });

                // Tampilkan Akumulasi Grand Total
                $("#mdGrandTotal").text('Rp ' + grandTotal.toLocaleString('id-ID'));

                // Set data-id untuk Reprint Modal
                $("#mdBtnReprint").data('id', header.id).attr('data-id', header.id).removeAttr('href');

                // Tampilkan Modal
                $("#modalDetailSales").modal('show');
            } else {
                if (typeof showNotification === "function") showNotification(res.message, 'danger');
            }
        },
        error: function() {
            if (typeof showNotification === "function") showNotification('Gagal menghubungi server database.', 'danger');
        }
    });
}

// =========================================================================
// LOGIKA UTAMA HALAMAN RIWAYAT (JQUERY DOCUMENT READY)
// =========================================================================
$(document).ready(function() {
    
    let tbody = $("#historyTable tbody");
    let currentPage = 1;
    let limit = 10; 

    // HANDLER EVENT REPRINT MODAL
    $(document).on('click', '#mdBtnReprint', function(e) {
        e.preventDefault();
        let saleId = $(this).data('id');
        
        // Pilih metode cetak (Default: Generic Text-Only via window.print)
        genericPrintSales(saleId, $(this));
        
    });

    function loadFilteredHistory(page = 1) {
        currentPage = page;
        
        tbody.html('<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Memuat data riwayat ...</td></tr>');

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
                end_date: $("#endDate").val(),
                page: currentPage,
                limit: limit
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
                        renderPagination({ total: 0, totalPages: 0, page: 1 });
                        return;
                    }

                    res.data.forEach(function(t) {
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
                                        ${t.warehouse_name}
                                    </span>
                                </td>                                
                                <td class="text-center">${typeBadge}</td>                                
                                <td class="fw-bold text-primary">${t.invoice_no}</td>                                
                                <td class="fw-medium text-dark">${t.buyer_name || '-'}</td>                                
                                <td class="text-center text-muted">${formattedDate}</td>                                
                                <td class="text-end fw-bold text-dark">${formattedTotal}</td>                                
                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm">
                                        <button type="button" class="btn btn-light btn-sm border text-primary" title="Lihat Detail Transaksi" onclick="viewDetail('${t.id}')">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm border text-secondary" title="Cetak Langsung" onclick="genericPrintSales('${t.id}', $(this))">
                                            <i class="fa-solid fa-print"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(tr);
                    });

                    if (res.pagination) {
                        renderPagination(res.pagination);
                    }
                }
            },
            error: function() {
                tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Terjadi kesalahan saat memuat data.</td></tr>');
            }
        });
    }

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        let $parent = $(this).parent();
        if ($parent.hasClass('disabled') || $parent.hasClass('active')) return;
        
        let targetPage = $(this).data('page');
        loadFilteredHistory(targetPage);
    });

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
        renderPagination({ total: 0, totalPages: 0, page: 1, limit: limit });
    }

    $("#search").on("keyup", clearTable);
    $("#filterWarehouse, #startDate, #endDate, #filterType").on("change", clearTable);
    
    $("#btnFilter").click(function() {
        loadFilteredHistory(1);
    });

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        clearTable();
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
        
        clearTable();
    });

    $("#btnExportExcel").click(function() {
        let sDateVal = $("#startDate").val() || "";
        let eDateVal = $("#endDate").val() || "";

        let payload = {
            action: 'export_xls',
            search: $("#search").val() || "",
            start_date: sDateVal,
            end_date: eDateVal,
            warehouse: $("#filterWarehouse").val() || "",
            type: $("#filterType").val() || "" 
        };

        let fStart = sDateVal.replace(/-/g, "");
        let fEnd = eDateVal.replace(/-/g, "");
        let dynamicFileName = 'Laporan_Penjualan_Detail_' + fStart + '_sd_' + fEnd;

        if (typeof downloadExcelAjax === "function") {
            downloadExcelAjax(this, window.location.href, payload, dynamicFileName);
        }
    });
    
});