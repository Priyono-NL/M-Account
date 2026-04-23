<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= $title ?? 'M-Account' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <!-- Custom CSS (Universal) -->
    <link rel="stylesheet" href="assets/css/style.css">
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Toastify JS -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<!-- Universal JS -->
<script src="assets/js/main.js"></script>
<!-- Custom JS Spesifik (Dipanggil dari masing-masing view) -->
<?= $extra_js ?? '' ?>

</body>
</html>