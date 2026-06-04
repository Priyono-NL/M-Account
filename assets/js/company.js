$(document).ready(function() {
    let tbody = $("#companyTable tbody");
    
    let currentPage = 1;
    let limit = 10;

    function loadFilteredCompany(page = 1) {
        currentPage = page;
        
        tbody.html('<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Memuat data ...</td></tr>');

        $.ajax({
            url: "index.php?page=company",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                page: currentPage,
                limit: limit
            },
            success: function(res) {
                if (res.status === "success") {                    
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="5" class="text-center py-5 text-muted fst-italic">Tidak ada data yang ditemukan.</td></tr>');
                        renderPagination({ total: 0, totalPages: 0, page: 1 });
                        return;
                    }

                    res.data.forEach(function(item, index) {
                        let whBadges = '';
                        if (item.warehouses && item.warehouses.length > 0) {
                            item.warehouses.forEach(function(wh) {
                                whBadges += `<span class="badge bg-secondary text-white fw-normal me-1">${wh.warehouse_name}</span>`;
                            });
                        } else {
                            whBadges = '<span class="text-muted small fst-italic">Belum ada gudang</span>';
                        }

                        let itemJson = JSON.stringify(item).replace(/'/g, "&#39;").replace(/"/g, "&quot;");
                        let no = (currentPage - 1) * limit + (index + 1);

                        tbody.append(`
                            <tr>
                                <td class="text-center text-muted">${no}</td>
                                <td class="fw-medium">${item.company_name}</td>
                                <td>${item.company_short}</td>
                                <td>${whBadges}</td>
                                <td class="text-center">
                                    <button class="btn btn-light btn-sm text-primary border-0 btn-edit" data-item='${itemJson}' title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button class="btn btn-light btn-sm text-danger border-0 btn-delete" data-id="${item.id}" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });

                    if(res.pagination) {
                        renderPagination(res.pagination);
                    }
                    
                } else {
                    if(typeof showNotification === "function") showNotification("Gagal mengambil data", "danger");
                }
            },
            error: function() {
                tbody.html('<tr><td colspan="5" class="text-center text-danger py-4">Terjadi kesalahan koneksi.</td></tr>');
            }
        });
    }

    // Panggil load pertama kali
    loadFilteredCompany();

    // Event filter dengan setTimeout (Debounce)
    let debounceTimer;
    $("#search").on("keyup", function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadFilteredCompany(1), 500);
    });

    // EVENT LISTENER KLIK PAGINATION (Menyesuaikan ID dari main.js)
    $(document).on("click", "#paginationControls .page-link", function(e) {
        e.preventDefault();
        let page = $(this).data("page");
        // Pastikan tidak mengeklik tombol yang disabled
        if (page && !$(this).parent().hasClass('disabled')) {
            loadFilteredCompany(page);
        }
    });

    // === Fungsi Form Dinamis & Modal di Bawah Ini Tetap Sama ===
    function createWarehouseRow(id = "", value = ""){
        return `
            <div class="input-group input-group-sm mb-2 wh-row">
                <input type="hidden" name="warehouse_ids[]" value="${id}">
                <input type="text" name="warehouses[]" class="form-control bg-light border-end-0" placeholder="Nama Gudang..." value="${value}" required>
                <button type="button" class="btn border btn-light text-danger btn-remove-wh">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        `;
    }

    $("#btnAddCompany").click(function() {
        $("#formAction").val("add");
        $("#companyId").val("");
        $("#companyForm")[0].reset();
        $("#modalTitle").text("Tambah Company");
        $("#warehouseContainer").html(createWarehouseRow());
        $("#modalCompany").modal("show");
    });

    $("#btnAddWarehouse").click(function() {
        $("#warehouseContainer").append(createWarehouseRow());
    });

    $(document).on("click", ".btn-remove-wh", function() {
        if ($(".wh-row").length > 1) {
            $(this).closest(".wh-row").remove();
        } else {
            $(this).siblings('input').val('');
        }
    });

    $(document).on("click", ".btn-edit", function() {
        let item = JSON.parse($(this).attr("data-item").replace(/&quot;/g, '"').replace(/&#39;/g, "'"));

        $("#formAction").val("update");
        $("#companyId").val(item.id);
        $("#companyName").val(item.company_name);
        $("#companyShort").val(item.company_short);
        $("#modalTitle").text("Edit Company");

        $("#warehouseContainer").empty();
        if (item.warehouses && item.warehouses.length > 0) {
            item.warehouses.forEach(function(wh) {
                $("#warehouseContainer").append(createWarehouseRow(wh.id, wh.warehouse_name));
            });
        } else {
            $("#warehouseContainer").append(createWarehouseRow());
        }

        $("#modalCompany").modal("show");
    });

    $("#companyForm").submit(function(e) {
        e.preventDefault();
        let btn = $("#btnSave");
        btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: "index.php?page=company",
            type: "POST",
            dataType: "json",
            data: $(this).serialize(),
            success: function(res) {
                if(res.status === "success") {
                    $("#modalCompany").modal("hide");
                    if(typeof showNotification === "function") showNotification(res.message, "success");
                    loadFilteredCompany($("#formAction").val() === "add" ? 1 : currentPage);
                } else {
                    if(typeof showNotification === "function") showNotification(res.message, "danger");
                }
            },
            complete: function() {
                btn.prop("disabled", false).text("Simpan");
            }
        });
    });

    $(document).on("click", ".btn-delete", function() {
        let id = $(this).data("id");
        if(confirm("Apakah Anda yakin ingin menghapus Company ini beserta semua Gudangnya?")) {
            $.ajax({
                url: "index.php?page=company",
                type: "POST",
                dataType: "json",
                data: { action: "delete", id: id },
                success: function(res) {
                    if(res.status === "success") {
                        if(typeof showNotification === "function") showNotification(res.message, "success");
                        loadFilteredCompany(currentPage); 
                    } else {
                        if(typeof showNotification === "function") showNotification(res.message, "danger");
                    }
                }
            });
        }
    });
});