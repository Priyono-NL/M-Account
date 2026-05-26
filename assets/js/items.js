$(document).ready(function() {
	let tbody = $("#itemTable tbody");

    function loadFilteredItems() {
        $.ajax({
            url: "index.php?page=items",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                category: $("#filterCategory").val(),
            },
            success: function(res) {
                if (res.status === "success") {                    
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center py-5 text-muted italic">Tidak ada data yang ditemukan.</td></tr>');
                        return;
                    }

                    res.data.forEach(function(item) {
                        let catBadge = item.category == '1' ? 'ByProduct' : 'Sampah';
                        let price = parseInt(item.unit_price).toLocaleString('id-ID');
                        let itemJson = JSON.stringify(item).replace(/'/g, "&#39;");

                        let tr = `
                            <tr>
                                <td class="ps-4 fw-medium text-primary">${item.item_code}</td>
                                <td class="fw-bold">${item.item_name}</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border-0 fw-normal px-2">${catBadge}</span></td>
                                <td class="text-center">${item.item_uom}</td>
                                <td class="text-end fw-bold text-dark">Rp ${price}</td>
                                <td class="text-center pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-light border btn-action edit-btn" data-item='${itemJson}'>
                                            <i class="fa-solid fa-pen-to-square text-primary"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border btn-action delete-btn" data-id="${item.id}" data-name="${item.item_name}">
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
	
	function clearTable() {
		tbody.empty();
		tbody.append(`
			<tr>
				<td colspan="7" class="text-center py-5 text-muted">
					<i class="fa-solid fa-magnifying-glass fs-2 mb-3 d-block opacity-25"></i>
					Ketik di Pencarian untuk memuat data...
				</td>
			</tr>
		`);
	}

    loadFilteredItems();

    $("#search").on("keyup", loadFilteredItems);
    $("#filterCategory, #filterStatus").on("change", loadFilteredItems);

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        clearTable();
    });

    $("#btnAddItem").click(function() {
        $("#formItem")[0].reset();
        $("#itemId").val("");
        $("#modalTitle").text("Tambah Barang Baru");
        $("#modalItem").modal("show");
    });

    $(document).on("click", ".edit-btn", function() {
        const data = $(this).data("item");
        $("#itemId").val(data.id);
        $("#itemCode").val(data.item_code).prop("readonly", true);
        $("#itemName").val(data.item_name);
        $("#itemCategory").val(data.category);
        $("#itemUom").val(data.item_uom);
        $("#itemPrice").val(formatAngka(data.unit_price));
        $("#itemCost").val(formatAngka(data.unit_cost));
        $("#modalTitle").text("Edit Barang");
        $("#modalItem").modal("show");
    });

    $("#formItem").submit(function(e) {
        e.preventDefault();
        const action = $("#itemId").val() ? "update" : "add";
        const btn = $("#btnSave");
        const originalText = btn.text();
        const inputHarga = $(".input-harga");
        let originalValues = [];
        inputHarga.each(function() {
            originalValues.push({ el: $(this), val: $(this).val() });
            $(this).val($(this).val().replace(/\./g, ""));
        });
        btn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: "index.php?page=items",
            type: "POST",
            dataType: "json",
            data: $(this).serialize() + "&action=" + action,
            success: function(res) {
                if(res.status === "success") {
                    $("#modalItem").modal("hide");
                    loadFilteredItems(); 
                    showNotification("Data berhasil disimpan!", "success");
                } else {
                    showNotification(res.message || "Gagal menyimpan data", "danger");
                    originalValues.forEach(item => item.el.val(item.val));  
                }
            },
            complete: function() { 
                btn.prop('disabled', false).text(originalText); 
                if ($("#modalItem").is(":visible")) originalValues.forEach(item => item.el.val(item.val));
            }
        });
    });

    $(document).on("click", ".delete-btn", function() {
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
                        loadFilteredItems();
                    } else {
                        showNotification(res.message, "danger");
                    }
                }
            });
        }
    });

    $("#btnTemplate").click(function() {
        let payload = { action: 'download_template' };
        downloadExcelAjax(this, window.location.href, payload, 'Format Items');
    });

    $("#btnUpload").click(function() {
        $("#fileCari").click();
    });

    $("#fileCari").change(function() {
        addBulk("#btnUpload", window.location.href, "fileCari", { action: 'upload' }, loadFilteredItems);
    });

    document.querySelectorAll('.input-harga').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, "");
            this.value = new Intl.NumberFormat('id-ID').format(value);
            if (this.value === '0' && value === '') this.value = '';
        });
    });
    
});