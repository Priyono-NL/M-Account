const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
};

const formatAngka = (number) => {
    if (!number && number !== 0) return "";
    
    let cleanNumber = parseFloat(number);
    if (isNaN(cleanNumber)) return "";
    
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2
    }).format(cleanNumber);
}

// Notifikasi Universal Menggunakan Toastify
const showNotification = (message, type) => {
    let bgColor = "#0d6efd";
    let textColor = "#ffffff";
    if (type === 'success') bgColor = "#198754";
    if (type === 'danger') bgColor = "#dc3545";
    if (type === 'warning') {
        bgColor = "#ffc107";
        textColor = "#000000";
    }

    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        stopOnFocus: true,
        style: {
            background: bgColor,
            color: textColor,
            borderRadius: "8px",
            boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
            fontFamily: "'Inter', sans-serif",
            fontSize: "14px"
        }
    }).showToast();
};

// Download File Excel Secara Async/AJAX
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
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(err => {
                throw new Error(err.message || "Gagal export dari server");
            });
        }
        return response.blob();
    })
    .then(blob => {
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

// Import Excel / Upload Bulk Universal
const addBulk = (btnElement, url, fileInputId, additionalData = {}, onSuccess = null) => {
    let btn = $(btnElement);
    let originalText = btn.html();
    let fileInput = document.getElementById(fileInputId);
    
    if (!fileInput) return;
    let file = fileInput.files[0];

    if (!file) {
        showNotification("Harap pilih file terlebih dahulu!", "warning");
        return;
    }

    btn.html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Mengunggah...');
    btn.prop('disabled', true);

    let formData = new FormData();
    formData.append('file_excel', file);
    
    Object.keys(additionalData).forEach(key => {
        formData.append(key, additionalData[key]);
    });

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error("Gagal koneksi ke server");
        return response.json();
    })
    .then(res => {
        if (res.status === 'success') {
            showNotification(res.message || "Data berhasil diperbarui!", "success");
            $(fileInput).val('');
            if (typeof onSuccess === "function") onSuccess(res); 
        } else throw new Error(res.message || "Terjadi kesalahan saat memproses file");
    })
    .catch(error => {
        showNotification(error.message || "Terjadi kesalahan sistem.", "danger");
        console.error(error);
    })
    .finally(() => {
        btn.html(originalText);
        btn.prop('disabled', false);
    });
};

const renderPagination = (paging, controlsId = '#paginationControls', infoId = '#paginationInfo') => {
    let total = parseInt(paging.total) || 0;
    let totalPages = parseInt(paging.totalPages) || 0;
    let page = parseInt(paging.page) || 1;
    let limit = parseInt(paging.limit) || 25; // Otomatis membaca limit dari server response

    // 1. Render Teks Info
    let start = total === 0 ? 0 : ((page - 1) * limit) + 1;
    let end = Math.min(page * limit, total);
    $(infoId).text(`Menampilkan ${start} - ${end} dari total ${total} data`);

    let pgHtml = '';

    // 2. Tombol Prev («)
    pgHtml += `<li class="page-item ${page === 1 || totalPages === 0 ? 'disabled' : ''}">
                  <a class="page-link px-3" href="#" data-page="${page - 1}">&laquo;</a>
               </li>`;

    // 3. Logika Titik-titik (Smart Elipsis)
    if (totalPages > 0) {
        let pages = [];
        if (totalPages <= 5) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            if (page <= 3) {
                pages = [1, 2, 3, '...', totalPages];
            } else if (page >= totalPages - 2) {
                pages = [1, '...', totalPages - 2, totalPages - 1, totalPages];
            } else {
                pages = [1, '...', page - 1, page, page + 1, '...', totalPages];
            }
        }

        pages.forEach(p => {
            if (p === '...') {
                pgHtml += `<li class="page-item disabled">
                              <span class="page-link bg-light text-muted border-0">...</span>
                           </li>`;
            } else {
                pgHtml += `<li class="page-item ${p === page ? 'active' : ''}">
                              <a class="page-link px-3" href="#" data-page="${p}">${p}</a>
                           </li>`;
            }
        });
    }

    // 4. Tombol Next (»)
    pgHtml += `<li class="page-item ${page === totalPages || totalPages === 0 ? 'disabled' : ''}">
                  <a class="page-link px-3" href="#" data-page="${page + 1}">&raquo;</a>
               </li>`;

    $(controlsId).html(pgHtml);
};

$(document).ready(function() {
    
    $('#sidebarToggle').click(function() {
        $('#sidebar').toggleClass('expanded');
    });

    const checkSsoSession = () => {
        $.ajax({
            url: 'index.php?page=auth',
            method: 'POST',
            dataType: 'json',
            data: { 
                action: 'checkSession'
            },
            success: function(response) {
                if (response.status === 'expired') {
                    showNotification("Sesi SSO Anda telah berakhir. Mengalihkan...", "danger");                    
                    setTimeout(function() {
                        window.location.href = response.redirect_url;
                    }, 2000);
                }
            },
            error: function() {
                console.warn("Pengecekan sesi SSO gagal berjalan.");
            }
        });
    };
    
    checkSsoSession();
    setInterval(checkSsoSession, 600000);

    $(document).on('click', '#btnStopImpersonate', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...');

        $.ajax({
            url: 'index.php?page=auth',
            type: 'POST',
            data: { action: 'stopImpersonate' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showNotification(response.message, "success");
                    setTimeout(function() {
                        window.location.href = 'index.php?page=dashboard';
                    }, 1500);
                } else {
                    alert(response.message);
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-right-from-bracket me-1"></i> Kembali ke Admin');
                }
            },
            error: function() {
                alert('Terjadi kesalahan sistem saat mencoba keluar dari mode penyamaran.');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-right-from-bracket me-1"></i> Kembali ke Admin');
            }
        });
    });
});