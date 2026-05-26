$(document).ready(function() {
	let tbody = $("#stockTable tbody");

    function loadFilteredHistory() {
		let periodMonth = $("#periodMonth").val();        
        if (!periodMonth) {
            showNotification("Harap tentukan Periode Bulan terlebih dahulu!", "warning");
            return;
        }
		
        $.ajax({
            url: "index.php?page=stockClosing",
            type: "POST",
            dataType: "json",
            data: {
                action: "filter_api",
                search: $("#search").val(),
                warehouse: $("#filterWarehouse").val(),
                periodMonth: $("#periodMonth").val(), 
            },
            success: function(res) {
                if (res.status === "success") {                    
                    tbody.empty();

                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center py-5 text-muted italic">Tidak ada riwayat transaksi ditemukan.</td></tr>');
                        return;
                    }

                    res.data.forEach(function(t) {
                        let warehouseName = t.warehouse == '1' ? 'Gudang BS' : (t.warehouse == '2' ? 'Gudang Sampah' : t.warehouse);
                        let dateObj = new Date(t.date || t.transaction_date); 
                        let formattedDate = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                        let qtyInStr = t.qty_in > 0 ? `<span class="text-success">+${t.qty_in}</span>` : '0';
                        let qtyOutStr = t.qty_out > 0 ? `<span class="text-danger">-${t.qty_out}</span>` : '0';

                        let tr = `
                            <tr>                                
                                <td class="text-center ps-4"><span class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-2 fw-normal">${warehouseName}</span></td>
                                <td><div class="fw-bold text-dark">${t.item_name}</div><small class="text-muted" style="font-size: 11px;">${t.item_code}</small></td>
                                <td class="text-center fw-medium text-muted">${t.qty_open}</td>
                                <td class="text-center fw-bold">${qtyInStr}</td>
                                <td class="text-center fw-bold">${qtyOutStr}</td>
                                <td class="text-center fw-bold fs-6 text-primary">${t.qty_close}</td>
                                <td class="pe-4 text-muted">${formattedDate}</td>
                            </tr>`;
                        tbody.append(tr);
                    });
					$("#btnMulaiClosing").prop('disabled', false);
                }
            }
        });
    }

    function clearTable() {
		$("#btnMulaiClosing").prop('disabled', true);
		tbody.empty();
		tbody.append(`
			<tr>
				<td colspan="7" class="text-center py-5 text-muted">
					<i class="fa-solid fa-magnifying-glass fs-2 mb-3 d-block opacity-25"></i>
					Pencarian dibersihkan
				</td>
			</tr>
		`);
	}
	
	$("#btnFilter").click(function() {
		loadFilteredHistory();
	})

    $("#search").on("keyup", clearTable);
	
    $("#filterWarehouse, #periodMonth").on("change", clearTable);

    $("#btnClearSearch").click(function() {
        $("#search").val("");
        clearTable();
    });
	
	$("#btnResetAll").click(function() {
		let d = new Date();
		let tahun = d.getFullYear();
		let bulan = String(d.getMonth() + 1).padStart(2, '0');
		let nowMonth = tahun + '-' + bulan;
	
        $("#search").val("");
        $("#filterWarehouse").val("");
        $("#periodMonth").val(nowMonth);
        
        clearTable();
    });
	
	// ==========================================
    // PREVENT EMPTY PERIOD
    // ==========================================
	$("#periodMonth").on("keydown", function(e) {
		if (e.key === "Backspace" || e.key === "Delete") {
			e.preventDefault();
		}
	});

	$("#periodMonth").on("blur", function() {
		if ($(this).val() === "") {
			let today = new Date();
			let year = today.getFullYear();
			let month = String(today.getMonth() + 1).padStart(2, '0');
			
			$(this).val(`${year}-${month}`);
			showNotification("Periode tidak boleh kosong. Dikembalikan ke bulan berjalan.", "warning");
		}
	});

    // ==========================================
    // LOGIKA MODAL CLOSING YANG AMAN
    // ==========================================
    const modalClosing = new bootstrap.Modal(document.getElementById('modalClosing'));

    $("#btnMulaiClosing").click(function() {
        let monthPeriod = $("#periodMonth").val();
        let warehouseName = $("#filterWarehouse option:selected").text();

        if (!monthPeriod) {
            showNotification("Harap pilih Bulan Periode terlebih dahulu pada filter pencarian!", "warning");
			return;
        }

        let parts = monthPeriod.split("-");
        let dateObj = new Date(parts[0], parts[1] - 1);
        let namaBulanIndo = dateObj.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });

        $("#lblPeriode").text(namaBulanIndo.toUpperCase());
        $("#lblGudang").text(warehouseName);
        
        $("#txtKonfirmasi").val('');
        $("#btnEksekusiClosing").prop('disabled', true);

        modalClosing.show();
    });

    // Validasi ketikan user "TUTUP" (Case Sensitive / Insensitive terserah, disini kita buat uppercase otomatis)
    $("#txtKonfirmasi").on("keyup", function() {
        let val = $(this).val().toUpperCase();
        $(this).val(val);
        
        if (val === "TUTUP") {
            $("#btnEksekusiClosing").prop('disabled', false).removeClass('btn-secondary').addClass('btn-danger');
        } else {
            $("#btnEksekusiClosing").prop('disabled', true);
        }
    });

    // Eksekusi AJAX sesungguhnya
    $("#btnEksekusiClosing").click(function() {
        let monthPeriod = $("#periodMonth").val();
        let $btn = $(this);
        let originalHtml = $btn.html();

        $btn.html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Memproses...').prop('disabled', true);
        $("#txtKonfirmasi").prop('disabled', true);

        $.ajax({
            url: "index.php?page=stockClosing",
            type: "POST",
            dataType: "json",
            data: {
                action: "do_closing",
                periodMonth: monthPeriod,
                warehouse: $("#filterWarehouse").val()
            },
            success: function(res) {
                if (res.status === "success") {
                    showNotification(res.message, "success");
					modalClosing.hide();
                    loadFilteredHistory(); 
                } else {
                    showNotification(res.message || "Gagal memproses closing.", "danger");
                }
            },
            error: function() {
                showNotification("Terjadi kesalahan sistem saat memproses closing ke database.", "danger");
            },
            complete: function() {
                $btn.html(originalHtml).prop('disabled', false);
                $("#txtKonfirmasi").prop('disabled', false);
            }
        });
    });
	
	// ==========================================
    // SELEKTOR OTOMATIS LOAD TAB RIWAYAT
    // ==========================================
    $('#riwayat-tab').on('shown.bs.tab', function (e) {
        let hBody = $("#historyTable tbody");
        
        // Tampilkan loading spinner
        hBody.html('<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2 fs-4 d-block mb-2"></i>Memuat riwayat log...</td></tr>');

        $.ajax({
            url: "index.php?page=stockClosing",
            type: "POST",
            dataType: "json",
            data: { action: "get_history" }, 
            success: function(res) {
                if (res.status === "success") {
                    hBody.empty();
                    
                    if (res.data.length === 0) {
                        hBody.append('<tr><td colspan="5" class="text-center py-5 text-muted">Belum ada riwayat closing stok yang tercatat di sistem.</td></tr>');
                        return;
                    }

                    res.data.forEach(function(h) {
                        let whName = h.warehouse == '1' ? 'Gudang BS' : (h.warehouse == '2' ? 'Gudang Sampah' : h.warehouse);

                        let pParts = h.periode.split("-");
                        let pDate = new Date(pParts[0], pParts[1] - 1);
                        let labelBulan = pDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }).toUpperCase();
                        
                        let execDate = new Date(h.executed_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' });

                        let rowHtml = `
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><i class="fa-solid fa-calendar-check text-success me-2"></i>${labelBulan}</td>
                                <td class="text-center"><span class="badge bg-light text-dark border px-2 fw-normal">${whName}</span></td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success border-0 px-2 fw-bold"><i class="fa-solid fa-lock me-1"></i> LOCKED</span></td>
                                <td class="text-center text-muted fw-medium">${h.executed_by}</td>
                                <td class="pe-4 text-end text-muted small">${execDate} WIB</td>
                            </tr>`;
                        hBody.append(rowHtml);
                    });
                } else {
                    hBody.html(`<tr><td colspan="5" class="text-center py-5 text-danger">${res.message || 'Gagal memuat data'}</td></tr>`);
                }
            },
            error: function() {
                hBody.html('<tr><td colspan="5" class="text-center py-5 text-danger">Gagal terhubung ke server saat memuat riwayat.</td></tr>');
            }
        });
    });
	
});