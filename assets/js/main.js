// =========================================================================
// MAIN LOGIC (assets/js/main.js)
// =========================================================================

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
};

const showNotification = (message, type) => {
    let bgColor = "#0d6efd"; // Info default
    if (type === 'success') bgColor = "#198754";
    if (type === 'danger') bgColor = "#dc3545";
    if (type === 'warning') bgColor = "#ffc107";

    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        stopOnFocus: true,
        style: {
            background: bgColor,
            borderRadius: "8px",
            boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
            fontFamily: "'Inter', sans-serif",
            fontSize: "14px"
        }
    }).showToast();
};

const downloadExcelAjax = (btnElement, url, requestData, filePrefix = 'Export_Data') => {
    let btn = $(btnElement);
    let originalText = btn.html();
    
    btn.html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Memproses...');
    btn.prop('disabled', true);

    let formData = new URLSearchParams(requestData);

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error("Gagal koneksi ke server");
        return response.blob();
    })
    .then(blob => {
        if (blob.type === 'application/json') {
            return blob.text().then(text => {
                let err = JSON.parse(text);
                throw new Error(err.message || "Gagal export dari server");
            });
        }

        let fileUrl = window.URL.createObjectURL(blob);
        let a = document.createElement('a');
        a.href = fileUrl;
        
        a.download = `${filePrefix}.xlsx`; 
        
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(fileUrl);
        
        btn.html(originalText);
        btn.prop('disabled', false);
        showNotification("File Excel berhasil diunduh!", "success");
    })
    .catch(error => {
        showNotification(error.message || "Terjadi kesalahan saat mengunduh Excel.", "danger");
        console.error(error);
        btn.html(originalText);
        btn.prop('disabled', false);
    });
};

$(document).ready(function() {
    
    $('#sidebarToggle').click(function() {
        $('#sidebar').toggleClass('expanded');
    });

});