<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= $title ?? 'M-Account' ?></title>
    
    <link rel="stylesheet" href="/maccount/vendors/bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/maccount/vendors/fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="/maccount/vendors/select2-4.1.0-rc.0/css/select2.min.css">
    <link rel="stylesheet" href="/maccount/vendors/toastify-js-1.12.0/toastify.css">
    
    <link rel="stylesheet" href="/maccount/assets/css/style.css">
    <?= $extra_css ?? '' ?>
</head>
<body>

<div class="wrapper">
    
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div id="content-wrapper">
        
        <?php include __DIR__ . '/../partials/navbar.php'; ?>

        <div class="main-content">
            <?= $content ?? '' ?>
        </div>

    </div>
</div>

<script src="/maccount/vendors/jquery-3.7.1.min.js"></script>
<script src="/maccount/vendors/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script src="/maccount/vendors/select2-4.1.0-rc.0/js/select2.min.js"></script>
<script src="/maccount/vendors/toastify-js-1.12.0/toastify.js"></script>

<script src="/maccount/assets/js/main.js"></script>

<script>
    var sessionActive = <?php echo json_encode($_SESSION); ?>;
    
    console.log("sessionActive:", sessionActive);
</script>

<?= $extra_js ?? '' ?>

</body>
</html>