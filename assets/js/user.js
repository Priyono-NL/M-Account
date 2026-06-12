$(document).ready(function() {
    let tbody = $("#UserTable tbody");

    let currentPage = 1;
    let limit = 10;

    function loadFiltered(page = 1) {
        currentPage = page;

        tbody.html('<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-2"></i><br>Memuat data pelanggan...</td></tr>');

        $.ajax({
            url: "index.php?page=users",
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
                        tbody.append('<tr><td colspan="4" class="text-center py-5 text-muted fst-italic">Tidak ada data pelanggan yang ditemukan.</td></tr>');
                        renderPagination({ total: 0, totalPages: 0, page: 1 });
                        return;
                    }

                    res.data.forEach(function(b) {
                        let userJSON = JSON.stringify(b).replace(/'/g, "&#39;");

                        let tr = `
                            <tr>
                                <td class="ps-4 fw-medium text-primary">${b.username}</td>
                                <td class="fw-bold">${b.buyer_name} (${b.buyer_code})</td>
                                <td>${b.rolename}</td>
                                <td class="text-center pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-light border btn-action edit-btn" data-item='${userJSON}'>
                                            <i class="fa-solid fa-pen-to-square text-primary"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border btn-action delete-btn" data-id="${b.id}" data-name="${b.username}">
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
        loadFiltered(targetPage);
    });

    let searchTimer;
    $("#search").on("keyup", function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { loadFiltered(1); }, 300);
    });

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        $("#filterStatus").val("");
        loadFiltered(1);
    });

    loadFiltered(1);

    // ==========================================
    // ACTION HANDLERS (ADD, EDIT, DELETE)
    // ==========================================

    $("#btnAddUser").click(function() {
        $("#formUser")[0].reset();
        $("#userId").val("");
        $("#password").attr("required", true);
        $("#person_id").val(null).trigger('change');
        $("#modalTitle").text("Tambah User Baru");
        $("#modalUser").modal("show");
    });

    $(document).on("click", ".edit-btn", function() {
        const data = $(this).data("item");
        console.log(data);
        $("#userId").val(data.id);
        $("#username").val(data.username);
        $("#role_id").val(data.role_id);
        $("#company").val(data.company);
        $("#person_id").empty();
        if (data.person_id) {
            let optionText = (data.buyer_code ? data.buyer_code + " | " : "") + data.buyer_name;
            var newOption = new Option(optionText, data.person_id, true, true);            
            $("#person_id").append(newOption).trigger('change');
        } else {
            $("#person_id").val(null).trigger('change');
        }
        
        $("#password").val("").removeAttr("required");
        $("#modalTitle").text("Edit Data User");
        $("#modalUser").modal("show");
    });

    $("#formUser").submit(function(e) {
        e.preventDefault();

        const action = $("#userId").val() ? "update" : "add";
        const btn = $("#btnSave");
        const originalText = btn.text();

        btn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: "index.php?page=users",
            type: "POST",
            dataType: "json",
            data: $(this).serialize() + "&action=" + action,
            success: function(res) {
                if(res.status === "success") {
                    $("#modalUser").modal("hide");
                    loadFiltered(currentPage);
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
        
        if(confirm(`Apakah Anda yakin ingin menghapus user login "${name}"?`)) {
            $.ajax({
                url: "index.php?page=users",
                type: "POST",
                dataType: "json",
                data: { action: "delete", id: id },
                success: function(res) {
                    if(res.status === "success") {
                        loadFiltered(currentPage);
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

    // ==========================================
    // MODAL PERSON
    // ==========================================
    $('#person_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalUser'),
        placeholder: '-- Ketik nama person --',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: 'index.php?page=users',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'get_person',
                    keyword: params.term
                };
            },
            processResults: function (response) {
                return {
                    results: response.data 
                };
            },
            cache: true
        }
    });

    $('#modalUser').on('hidden.bs.modal', function () {
        $('#person_id').val(null).trigger('change');
    });

});