$(document).ready(function() {

    function loadFilteredBuyers() {
        $.ajax({
            url: "index.php?page=buyers",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                status: $("#filterStatus").val()
            },
            success: function(res) {
                if (res.status === "success") {
                    let tbody = $("#buyerTable tbody");
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="3" class="text-center py-5 text-muted italic">Tidak ada data pelanggan yang ditemukan.</td></tr>');
                        return;
                    }

                    res.data.forEach(function(b) {
                        let buyerJson = JSON.stringify(b).replace(/'/g, "&#39;");

                        let tr = `
                            <tr>
                                <td class="ps-4 fw-medium text-primary">${b.buyer_code}</td>
                                <td class="fw-bold">${b.buyer_name}</td>
                                <td class="fw-bold">${b.buyer_status}</td>
                                <td class="fw-bold">${b.buyer_address}</td>
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
                }
            }
        });
    }

    loadFilteredBuyers();

    $("#search").on("keyup", loadFilteredBuyers);
    
    $("#btnClearSearch").click(function() {
        $("#search").val("");
        loadFilteredBuyers();
    });

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
                    loadFilteredBuyers(); 
                    showNotification("Data berhasil disimpan!", "success");
                } else {
                    showNotification(res.message || "Gagal menyimpan data", "danger");
                }
            },
            error: function() {
                showNotification("Terjadi kesalahan pada server", "danger");
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
                        loadFilteredBuyers();
                        showNotification("Data berhasil dihapus!", "success");
                    } else {
                        showNotification(res.message || "Gagal menghapus data", "danger");
                    }
                },
                error: function() {
                    showNotification("Terjadi kesalahan sistem", "danger");
                }
            });
        }
    });

    $("#btnTemplate").click(function() {
        let payload = { action: 'download_template' };
        downloadExcelAjax(this, '/m-account/buyers', payload, 'Format Buyer');
    });

    $("#btnUpload").click(function() {
        $("#fileCari").click();
    });

    $("#fileCari").change(function() {
        addBulk("#btnUpload", window.location.href, "fileCari", { action: 'upload' }, loadFilteredBuyers);
    });
});