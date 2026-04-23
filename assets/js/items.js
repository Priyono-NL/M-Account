// =========================================================================
// ITEMS MASTER LOGIC (assets/js/items.js)
// =========================================================================

$(document).ready(function() {
    
    $("#btnAddItem").click(function() {
        $("#formItem")[0].reset();
        $("#itemId").val("");
        $("#modalTitle").text("Tambah Barang Baru");
        $("#modalItem").modal("show");
    });

    $(".edit-btn").click(function() {
        const data = $(this).data("item");
        
        $("#itemId").val(data.id);
        $("#itemCode").val(data.item_code);
        $("#itemName").val(data.item_name);
        $("#itemCategory").val(data.category);
        $("#itemUom").val(data.item_uom);
        $("#itemPrice").val(data.unit_price);
        $("#itemCost").val(data.unit_cost);
        
        $("#modalTitle").text("Edit Barang");
        $("#modalItem").modal("show");
    });

    $("#formItem").submit(function(e) {
        e.preventDefault();
        
        const action = $("#itemId").val() ? "update" : "add";
        const btn = $("#btnSave");
        const originalText = btn.text();

        btn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: "index.php?page=items",
            type: "POST",
            dataType: "json",
            data: $(this).serialize() + "&action=" + action,
            success: function(res) {
                if(res.status === "success") {
                    // Jika sukses, reload halaman untuk memperbarui tabel
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
        
        if(confirm(`Apakah Anda yakin ingin menghapus "${name}"?`)) {
            $.ajax({
                url: "index.php?page=items",
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