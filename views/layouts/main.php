<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= $title ?? 'M-Account' ?></title>
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendors/bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/vendors/select2-4.1.0-rc.0/css/select2.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendors/toastify-js-1.12.0/toastify.css" media="print" onload="this.media='all'">
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendors/fontawesome-free-6.4.2-web/css/all.min.css" media="print" onload="this.media='all'">
    
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

<script src="<?= BASE_URL ?>/vendors/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/vendors/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= BASE_URL ?>/vendors/select2-4.1.0-rc.0/js/select2.min.js" defer></script>
<script src="<?= BASE_URL ?>/vendors/toastify-js-1.12.0/toastify.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/main-min.js" defer></script>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?= $extra_js ?? '' ?>

</body>
</html>