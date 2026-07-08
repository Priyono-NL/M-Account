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
    
<?php if (defined('APP_ENV') && APP_ENV === 'development') : ?>
    
    <div style="
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 3px; 
        background: linear-gradient(90deg, #ff9800, #f44336); 
        z-index: 999999; 
        pointer-events: none;
    "></div>

    <div style="
        position: fixed;
        top: 3px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(20, 20, 20, 0.85);
        color: #ff9800;
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        padding: 2px 14px;
        font-family: 'SF Mono', Monaco, Consolas, 'Liberation Mono', monospace;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 1.5px;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        z-index: 999999;
        text-transform: uppercase;
        border: 1px solid rgba(255,255,255,0.08);
        border-top: none;
        pointer-events: none;
        display: flex;
        align-items: center;
        gap: 6px;
    ">
        <span style="
            width: 5px; 
            height: 5px; 
            background-color: #ff9800; 
            border-radius: 50%; 
            display: inline-block;
            box-shadow: 0 0 6px #ff9800;
            animation: envPulse 2s infinite;
        "></span>
        
        DEVELOPMENT STATE
    </div>

    <style>
        @keyframes envPulse {
            0%, 100% { opacity: 0.4; transform: scale(0.9); }
            50% { opacity: 1; transform: scale(1.1); }
        }
    </style>
<?php endif; ?>

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