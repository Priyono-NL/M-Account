<?php
namespace Views;

use Views\Formatters\InvoiceFormatter;

class InvoiceView {

    /**
     * Render Tampilan HTML untuk Browser / Generic Text Print
     */
    public static function render($header, $items) {
        // Ambil string teks ASCII yang sudah diformat rapi (51 kolom)
        $textContent = InvoiceFormatter::buildPassKeluarText($header, $items);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Pass Keluar - <?= htmlspecialchars($header['invoice_no'] ?? '') ?></title>
            <link rel="stylesheet" href="assets/css/bootstrap.min.css">
            <style>
                /* Styling khusus cetak browser */
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #f8f9fa;
                }
                .print-container {
                    background: #fff;
                    padding: 15px;
                    margin: 20px auto;
                    max-width: 500px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                pre.text-receipt {
                    font-family: 'Courier New', Courier, monospace !important;
                    font-size: 10pt !important;
                    line-height: 1.2 !important;
                    white-space: pre !important;
                    margin: 0;
                }
                @media print {
                    body { background: transparent; }
                    .no-print { display: none !important; }
                    .print-container {
                        box-shadow: none;
                        margin: 0;
                        padding: 0;
                        max-width: 100%;
                    }
                    @page { margin: 0; size: auto; }
                }
            </style>
        </head>
        <body>

            <!-- Tombol Aksi di Layar Normal -->
            <div class="text-center my-3 no-print">
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-print me-1"></i> Cetak Faktur
                </button>
                <button onclick="window.close()" class="btn btn-secondary btn-sm ms-2">
                    Tutup
                </button>
            </div>

            <!-- Container Teks Cetak -->
            <div class="print-container">
                <pre class="text-receipt"><?= htmlspecialchars($textContent) ?></pre>
            </div>

            <!-- Otomatis Panggil Dialog Print saat Halaman Dimuat -->
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    // window.print();
                });
            </script>
        </body>
        </html>
        <?php
    }
}