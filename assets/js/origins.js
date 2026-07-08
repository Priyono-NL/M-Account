$(document).ready(function() {
    let tbody = $("#originTable tbody");
    
    let currentPage = 1;
    let limit = 10;

    function loadFilterdOrigins(page = 1) {
        currentPage = page;
        
        tbody.html('<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Memuat data ...</td></tr>');

        $.ajax({
            url: "index.php?page=origins",
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

                    if (!res.data || res.data.length === 0) {
                        tbody.append('<tr><td colspan="5" class="text-center py-5 text-muted fst-italic">Tidak ada data yang ditemukan.</td></tr>');
                        renderPagination({ total: 0, totalPages: 0, page: 1 });
                        return;
                    }

                    res.data.forEach(function(item, index) {
                        let no = (currentPage - 1) * limit + (index + 1);
                        let itemJson = JSON.stringify(item).replace(/'/g, "&#39;");

                        let tr = `
                            <tr>
                                <td class="text-center text-muted">${no}</td>
                                <td class="fw-medium">${item.origin_code}</td>
                                <td>${item.origin_name}</td>
                                <td>${item.origin_type}</td>
                                <td class="text-center">
                                    <button class="btn btn-light btn-sm text-primary border-0 btn-edit" data-item='${itemJson}' title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button class="btn btn-light btn-sm text-danger border-0 btn-delete" data-id="${item.id}" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.append(tr);
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
    loadFilterdOrigins();

    // Event filter dengan setTimeout (Debounce)
    let debounceTimer;
    $("#search").on("keyup", function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadFilterdOrigins(1), 500);
    });

    // EVENT LISTENER KLIK PAGINATION (Menyesuaikan ID dari main.js)
    $(document).on("click", "#paginationControls .page-link", function(e) {
        e.preventDefault();
        let page = $(this).data("page");
        // Pastikan tidak mengeklik tombol yang disabled
        if (page && !$(this).parent().hasClass('disabled')) {
            loadFilterdOrigins(page);
        }
    });

    $("#btnAddCompany").click(function() {
        $("#formAction").val("add");
        $("#originForm")[0].reset();
        $("#modalTitle").text("Tambah Origin Code");
        $("#modalOrigin").modal("show");
    });

    $(document).on("click", ".btn-edit", function() {
        const data = $(this).data("item");
        $("#formAction").val("update");
        $("#originCode").val(data.origin_code);
        $("#originName").val(data.origin_name);
        $("#originType").val(data.origin_type);
        $("#modalTitle").text("Edit Origin Code");

        $("#modalOrigin").modal("show");
    });

    $("#originForm").submit(function(e) {
        e.preventDefault();
        let btn = $("#btnSave");
        btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: "index.php?page=origins",
            type: "POST",
            dataType: "json",
            data: $(this).serialize(),
            success: function(res) {
                if(res.status === "success") {
                    $("#modalOrigin").modal("hide");
                    if(typeof showNotification === "function") showNotification(res.message, "success");
                    loadFilterdOrigins($("#formAction").val() === "add" ? 1 : currentPage);
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
        if(confirm("Apakah Anda yakin ingin menghapus Origin Code ini ?")) {
            $.ajax({
                url: "index.php?page=origins",
                type: "POST",
                dataType: "json",
                data: { action: "delete", id: id },
                success: function(res) {
                    if(res.status === "success") {
                        if(typeof showNotification === "function") showNotification(res.message, "success");
                        loadFilterdOrigins(currentPage); 
                    } else {
                        if(typeof showNotification === "function") showNotification(res.message, "danger");
                    }
                }
            });
        }
    });
});