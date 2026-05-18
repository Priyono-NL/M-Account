$(document).ready(function() {
    
    const ROLE_BADGE = {
        'superadmin': { bg: '#ef444418', text: '#ef4444' }, 
        'admin':      { bg: '#f59e0b18', text: '#f59e0b' }, 
        'manager':    { bg: '#22c55e18', text: '#22c55e' }, 
        'user':       { bg: '#94a3b818', text: '#64748b' }  
    };

    let allUsers = [];

    function renderTable(users) {
        let tbody = $('#usersTableBody');
        tbody.empty();

        if (users.length === 0) {
            tbody.append('<tr><td colspan="4" class="text-center py-5 text-muted"><i class="fa-solid fa-users fs-2 mb-2"></i><br>Tidak ada user ditemukan</td></tr>');
            return;
        }

        users.forEach(function(user) {
            let charCode = user.username.charCodeAt(0) || 65;
            let hue = (charCode * 15) % 360;
            let avatarBg = `hsl(${hue}, 55%, 50%)`;
            let initial = user.username.charAt(0).toUpperCase();

            let roleStyle = ROLE_BADGE[user.role] || ROLE_BADGE['user'];
            let badgeHtml = `<span style="background:${roleStyle.bg}; color:${roleStyle.text}; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: capitalize;">${user.role}</span>`;

            let statusHtml = (user.is_active == 1) 
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Aktif</span>' 
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill">Nonaktif</span>';

            let actionBtn = '';
            if (user.role === 'superadmin') {
                actionBtn = `<span class="text-muted small"><i class="fa-solid fa-lock me-1"></i> Tidak bisa</span>`;
            } else {
                let disabledAttr = (user.is_active != 1) ? 'disabled' : '';
                actionBtn = `<button class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-login-as" data-id="${user.id}" data-name="${user.username}" data-role="${user.role}" ${disabledAttr}><i class="fa-solid fa-right-to-bracket me-1"></i> Login As</button>`;
            }

            let tr = `
                <tr>
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 35px; height: 35px; border-radius: 50%; background: ${avatarBg}; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">
                                ${initial}
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size: 14px;">${user.username}</div>
                            </div>
                        </div>
                    </td>
                    <td>${badgeHtml}</td>
                    <td>${statusHtml}</td>
                    <td class="text-end pe-4">${actionBtn}</td>
                </tr>
            `;
            tbody.append(tr);
        });
    }

    function fetchUsers() {
        $.ajax({
            url: 'index.php?page=changeLogin', 
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_all_users' },
            success: function(res) {
                if (res.status === 'success') {
                    allUsers = res.data;
                    renderTable(allUsers);
                } else {
                    showNotification('Gagal memuat pengguna: ' + res.message, 'danger');
                }
            },
            error: function() {
                showNotification('Terjadi kesalahan sistem saat memuat data.', 'danger');
            }
        });
    }

    $('#searchUser').on('keyup', function() {
        let keyword = $(this).val().toLowerCase();
        let filtered = allUsers.filter(u => u.username.toLowerCase().includes(keyword));
        renderTable(filtered);
    });

    $(document).on('click', '.btn-login-as', function() {
        let userId = $(this).data('id');
        let userName = $(this).data('name');
        let userRole = $(this).data('role');

        let konfirmasi = confirm(`Login sebagai ${userName}?\n\nAnda akan bertindak dengan otoritas sebagai ${userRole.toUpperCase()}.`);
        
        if (!konfirmasi) {
            return;
        }

        let $btn = $(this);
        let originalHtml = $btn.html();
        $btn.html('<i class="fa-solid fa-spinner fa-spin me-1"></i>').prop('disabled', true);

        $.ajax({
            url: 'index.php?page=changeLogin',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'switchAccount',
                target_user_id: userId
            },
            success: function(res) {
                if (res.status === 'success') {
                    showNotification(`Berhasil login sebagai ${userName}`, 'success');
                    
                    setTimeout(function() {
                        window.location.href = '/m-account/dashboard';
                    }, 1000); 

                } else {
                    showNotification(res.message, 'danger');
                    $btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function() {
                showNotification('Kesalahan pada server saat memproses login.', 'danger');
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });

    fetchUsers();
});