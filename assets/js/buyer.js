$(document).ready(function() {
    let tbody = $("#buyerTable tbody");

    let currentPage = 1;
    let limit = 10;

    function loadFilteredBuyers(page = 1) {
        currentPage = page;

        tbody.html('<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Memuat data pelanggan...</td></tr>');

        $.ajax({
            url: "index.php?page=buyers",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                status: $("#filterStatus").val(),
                page: currentPage,
                limit: limit
            },
            success: function(res) {
                if (res.status === "success") {
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="5" class="text-center py-5 text-muted fst-italic">Tidak ada data pelanggan yang ditemukan.</td></tr>');
                        renderPagination({ total: 0, totalPages: 0, page: 1 });
                        return;
                    }

                    res.data.forEach(function(b) {
                        let buyerJson = JSON.stringify(b).replace(/'/g, "&#39;");

                        let tr = `
                            <tr>
                                <td class="ps-4 fw-medium text-primary">${b.buyer_code}</td>
                                <td class="fw-bold">${b.buyer_name}</td>
                                <td>${b.buyer_status}</td>
                                <td>${b.buyer_address}</td>
                                <td class="text-center pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-light border btn-action edit-btn" data-item='${buyerJson}'>
                                            <i class="fa-solid fa-pen-to-square text-primary"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border btn-action delete-btn" data-id="${b.id}" data-name="${b.buyer_name}">
                                            <i class="fa-solid fa-trash text-danger"></i>
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
                tbody.html('<tr><td colspan="5" class="text-center text-danger py-4">Terjadi kesalahan saat memuat data.</td></tr>');
            }
        });
    }
    
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        let $parent = $(this).parent();
        if ($parent.hasClass('disabled') || $parent.hasClass('active')) return;
        
        let targetPage = $(this).data('page');
        loadFilteredBuyers(targetPage);
    });

    let searchTimer;
    $("#search").on("keyup", function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { loadFilteredBuyers(1); }, 300);
    });
    
    $("#filterStatus").on("change", function() { 
        loadFilteredBuyers(1); 
    });

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        $("#filterStatus").val("");
        loadFilteredBuyers(1);
    });

    loadFilteredBuyers(1);

    // ==========================================
    // ACTION HANDLERS (ADD, EDIT, DELETE, EXCEL)
    // ==========================================

    $("#btnAddBuyer").click(function() {
        $("#formBuyer")[0].reset();
        $("#buyerId").val("");
        $("#buyerCode").val("").prop("readonly", false);
        $("#modalTitle").text("Tambah Buyer Baru");
        $("#modalBuyer").modal("show");
    });

    $(document).on("click", ".edit-btn", function() {
        const data = $(this).data("item");
        
        $("#buyerId").val(data.id);
        $("#buyerCode").val(data.buyer_code).prop("readonly", true);
        $("#buyerName").val(data.buyer_name);
        $("#buyerStatus").val(data.buyer_status);
        $("#buyerAddress").val(data.buyer_address);
        
        $("#modalTitle").text("Edit Data Buyer");
        $("#modalBuyer").modal("show");
    });

    $("#formBuyer").submit(function(e) {
        e.preventDefault();

        const action = $("#buyerId").val() ? "update" : "add";
        const btn = $("#btnSave");
        const originalText = btn.text();

        btn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: "index.php?page=buyers",
            type: "POST",
            dataType: "json",
            data: $(this).serialize() + "&action=" + action,
            success: function(res) {
                if(res.status === "success") {
                    $("#modalBuyer").modal("hide");
                    loadFilteredBuyers(currentPage);
                    if(typeof showNotification === "function") showNotification("Data berhasil disimpan!", "success");
                } else {
                    if(typeof showNotification === "function") showNotification(res.message || "Gagal menyimpan data", "danger");
                }
            },
            error: function() {
                if(typeof showNotification === "function") showNotification("Terjadi kesalahan pada server", "danger");
            },
            complete: function() {
                btn.prop('disabled', false).text(originalText);
            }
        });
    });

    $(document).on("click", ".delete-btn", function() {
        const id = $(this).data("id");
        const name = $(this).data("name");
        
        if(confirm(`Apakah Anda yakin ingin menghapus pelanggan "${name}"?`)) {
            $.ajax({
                url: "index.php?page=buyers",
                type: "POST",
                dataType: "json",
                data: { action: "delete", id: id },
                success: function(res) {
                    if(res.status === "success") {
                        loadFilteredBuyers(currentPage); // Positional reload setelah hapus
                        if(typeof showNotification === "function") showNotification("Data berhasil dihapus!", "success");
                    } else {
                        if(typeof showNotification === "function") showNotification(res.message || "Gagal menghapus data", "danger");
                    }
                },
                error: function() {
                    if(typeof showNotification === "function") showNotification("Terjadi kesalahan sistem", "danger");
                }
            });
        }
    });

    $("#btnTemplate").click(function() {
        let payload = { action: 'download_template' };
        if(typeof downloadExcelAjax === "function") {
            downloadExcelAjax(this, window.location.href, payload, 'Format Buyer');
        }
    });

    $("#btnUpload").click(function() {
        $("#fileCari").click();
    });

    $("#fileCari").change(function() {
        if(typeof addBulk === "function") {
            addBulk("#btnUpload", window.location.href, "fileCari", { action: 'upload' }, function() {
                loadFilteredBuyers(1);
            });
        }
    });
});