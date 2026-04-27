<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= $title ?? 'M-Account' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="/m-account/vendors/bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="/m-account/vendors/fontawesome-free-6.4.2-web/css/all.min.css">
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="/m-account/vendors/select2-4.1.0-rc.0/css/select2.min.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="/m-account/vendors/toastify-js-1.12.0/toastify.css">
    
    <!-- Custom CSS (Universal) -->
    <link rel="stylesheet" href="/m-account/assets/css/style.css">
</head>
<body>

<div class="wrapper">
    
    <!-- Memanggil komponen Sidebar -->
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="content-wrapper">
        
        <!-- Memanggil komponen Navbar -->
        <?php include __DIR__ . '/../partials/navbar.php'; ?>

        <!-- Main Content (Area Dinamis yang berubah per halaman) -->
        <div class="main-content">
            <?= $content ?? '' ?>
        </div>

    </div>
</div>

<!-- jQuery -->
<script src="/m-account/vendors/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS Bundle -->
<script src="/m-account/vendors/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<!-- Select2 JS -->
<script src="/m-account/vendors/select2-4.1.0-rc.0/js/select2.min.js"></script>
<!-- Toastify JS -->
<script src="/m-account/vendors/toastify-js-1.12.0/toastify.js"></script>

<!-- Universal JS -->
<script src="/m-account/assets/js/main.js"></script>
<!-- Custom JS Spesifik (Dipanggil dari masing-masing view) -->
 
<script>
    //cek isi session
    const sessionData = <?= json_encode($_SESSION); ?>;
    console.log("Session Saat Ini:", sessionData);
</script>

<?= $extra_js ?? '' ?>

</body>
</html>