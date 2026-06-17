<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= $title ?? 'M-Account' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" media="print" onload="this.media='all'">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-min.css">
    
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js" defer></script>

<script src="<?= BASE_URL ?>/assets/js/main-min.js" defer></script>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<?= $extra_js ?? '' ?>

</body>
</html>