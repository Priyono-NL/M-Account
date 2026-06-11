<?php
class LoginView {
    public static function render($data = []) {
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login - M-Account</title>
            
            <link rel="stylesheet" href="/maccount/vendors/bootstrap-5.3.8-dist/css/bootstrap.min.css">
            <link rel="stylesheet" href="/maccount/vendors/fontawesome-free-6.4.2-web/css/all.min.css">
            
            <style>
                body {
                    background-color: #f4f6f9;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .login-card {
                    width: 100%;
                    max-width: 420px;
                    border-radius: 12px;
                }
            </style>
        </head>
        <body>

        <div class="card shadow-sm border-0 login-card p-4 bg-white">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-right-to-bracket me-2"></i>Login</h4>
                <p class="text-muted small">Silakan masukkan username dan password Anda</p>
            </div>

            <div id="loginAlert" class="alert d-none small text-center fw-bold py-2" role="alert"></div>

            <form id="formLogin">
                <div class="mb-3">
                    <label for="username" class="form-label text-muted small fw-bold">USERNAME</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 py-2 shadow-none" name="username" id="username" placeholder="Ketik username Anda" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label text-muted small fw-bold">PASSWORD</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                        <input type="password" class="form-control border-start-0 py-2 shadow-none" name="password" id="password" placeholder="Ketik password Anda" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm" id="btnLogin">
                    Masuk
                </button>
            </form>
            
            <div class="text-center mt-4">
                <p class="text-muted" style="font-size: 11px;">&copy; <?php echo date('Y'); ?> M-Account</p>
            </div>
        </div>

        <script src="/maccount/vendors/jquery-3.7.1.min.js"></script>
        <script src="/maccount/vendors/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
        
        <script>
        $(document).ready(function() {
            $('#formLogin').on('submit', function(e) {
                e.preventDefault();
                
                let $btn = $('#btnLogin');
                let $alert = $('#loginAlert');

                $btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin me-2"></i>Memproses...');
                $alert.addClass('d-none').removeClass('alert-success alert-danger');

                $.ajax({
                    url: 'index.php?page=auth&action=process_login', 
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if(res.status === 'success') {
                            
                            $alert.addClass('alert-success').removeClass('d-none alert-danger').text(res.message);
                            setTimeout(function() {
                                window.location.href = 'index.php?page=dashboard';  
                            }, 500);
                        } else {
                            $alert.addClass('alert-danger').removeClass('d-none alert-success').text(res.message);
                            $btn.prop('disabled', false).text('Masuk');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Terjadi kesalahan koneksi ke server.';
                        if (xhr.responseJSON && xhr.responseJSON.message) errorMsg = xhr.responseJSON.message;

                        $alert.addClass('alert-danger').removeClass('d-none alert-success').text(errorMsg);
                        $btn.prop('disabled', false).text('Masuk');
                    }
                });
            });
        });
        </script>

        </body>
        </html>
        <?php
    }
}
?>