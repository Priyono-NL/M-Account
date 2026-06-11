$(document).ready(function() {
    let $selectRole = $("#selectRole");
    let $formRoleId = $("#formRoleId");
    let $checkboxes = $(".menu-checkbox");
    let $btnSave = $("#btnSave");
    let $btnSelectAll = $("#btnSelectAll");

    $selectRole.on("change", function() {
        let role_id = $(this).val();
        $formRoleId.val(role_id);
        
        $checkboxes.prop("checked", false);

        if (role_id === "") {
            $checkboxes.prop("disabled", true);
            $btnSave.prop("disabled", true);
            $btnSelectAll.prop("disabled", true);
            return;
        }

        $checkboxes.prop("disabled", false);
        $btnSave.prop("disabled", false);
        $btnSelectAll.prop("disabled", false);

        $("#loadingIndicator").removeClass("d-none");
        $selectRole.prop("disabled", true);

        $.ajax({
            url: "index.php?page=permission&action=get_role_permission",
            type: "POST",
            data: { role_id: role_id },
            dataType: "json",
            success: function(res) {
                if (res.status === "success" && res.data.paths) {
                    let paths = res.data.paths;
                    // Centang checkbox yang sesuai dengan path di database
                    $checkboxes.each(function() {
                        if (paths.includes($(this).val())) {
                            $(this).prop("checked", true);
                        }
                    });
                }
            },
            complete: function() {
                $("#loadingIndicator").addClass("d-none");
                $selectRole.prop("disabled", false);
            }
        });
    });

    // Tombol Pilih Semua / Batal Pilih Semua
    $btnSelectAll.on("click", function() {
        let allChecked = $checkboxes.length === $checkboxes.filter(":checked").length;
        $checkboxes.prop("checked", !allChecked);
        $(this).text(allChecked ? "Pilih Semua" : "Batal Pilih Semua");
    });

    // Submit Form
    $("#formPermission").on("submit", function(e) {
        e.preventDefault();
        let $btn = $("#btnSave");
        $btn.prop("disabled", true).html("<i class='fa-solid fa-spinner fa-spin'></i> Menyimpan...");

        $.ajax({
            url: "index.php?page=permission&action=save",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    Toastify({text: res.message, style: {background: "green"}}).showToast();
                } else {
                    Toastify({text: res.message, style: {background: "red"}}).showToast();
                }
            },
            error: function() {
                Toastify({text: "Terjadi kesalahan server.", style: {background: "red"}}).showToast();
            },
            complete: function() {
                $btn.prop("disabled", false).html("<i class='fa-solid fa-save me-1'></i> Simpan");
            }
        });
    });
});