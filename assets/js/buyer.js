// =========================================================================
// BUYER MASTER LOGIC (assets/js/buyer.js)
// =========================================================================

$(document).ready(function() {

    $("#btnAddBuyer").click(function() {
        $("#formBuyer")[0].reset();
        $("#buyerId").val("");
        $("#buyerCode").prop("readonly", false);
        $("#modalTitle").text("Tambah Pelanggan Baru");
        $("#modalBuyer").modal("show");
    });

    $(".edit-btn").click(function() {
        const data = $(this).data("item");
        
        $("#buyerId").val(data.id);
        $("#buyerCode").val(data.buyer_code).prop("readonly", true);
        $("#buyerName").val(data.buyer_name);
        
        $("#modalTitle").text("Edit Data Pelanggan");
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
                    location.reload();
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

    $(".delete-btn").click(function() {
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
                        location.reload();
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
});