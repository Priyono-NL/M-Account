<?php
class InvoiceViewPdf {
    public static function render($header, $items) {
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        ?>
        <html>
        <head>
            <style>
                @page { 
                    /* Margin: Atas Kanan Bawah Kiri */
                    /* Kita beri margin bawah 2.2cm khusus sebagai "ruang pelindung" untuk notice */
                    margin: 0.3cm 0.3cm 2.2cm 0.3cm; 
                }

                body { 
                    font-family: 'Courier', monospace;
                    font-size: 8pt;
                    line-height: 1.2; 
                    color: #000;
                }

                .text-left { text-align: left; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-bold { font-weight: bold; }

                table { width: 100%; border-collapse: collapse; }
                table td, table th { vertical-align: top; padding: 2px 0; }

                /* --- KUNCI POSISI DI BAWAH --- */
                .notice-box {
                    position: fixed; /* Mengunci elemen relatif terhadap halaman */
                    bottom: -2cm; /* Menarik elemen ke bawah agar masuk ke area margin-bottom @page tadi */
                    left: 0;
                    right: 0;
                    height: 2cm;
                    text-align: center;
                    font-size: 7.5pt;
                    line-height: 1.3;
                }
                .notice-divider {
                    margin: 3px 0;
                    letter-spacing: -0.5px;
                }

                .no-print { display: none; }
            </style>
        </head>
        <body>
            <div class="text-center">
                <strong style="font-size: 10pt;">¤¤ PASS KELUAR ¤¤</strong>
            </div>

            <table style="margin-top: 10px;">
                <tr>
                    <td>Date <?= isset($header['sales_date']) ? htmlspecialchars(strtoupper(date('d-M-Y', strtotime($header['sales_date']))), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                    <td class="text-right">Print#<?= htmlspecialchars(($header['is_reprint'] == false) ? '' : $header['reprint'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td colspan="2">Doc. No <?= htmlspecialchars($header['invoice_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td colspan="2">Buyer <?= htmlspecialchars($header['buyer_code'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($header['buyer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td colspan="2">Remark <?= htmlspecialchars($header['remark'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            </table>

            <table style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th class="text-left" style="width: 45%;">Item Description</th>                        
                        <th class="text-right" style="width: 20%;">Price</th>
                        <th class="text-right" style="width: 10%;">Qty</th>
                        <th class="text-right" style="width: 25%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $subtotal = $item['unit_price'] * $item['item_qty'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>                        
                        <td class="text-right"><?= $isExp ? '-' : number_format($item['unit_price'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= htmlspecialchars($item['item_qty'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-right"><?= $isExp ? '-' : number_format($subtotal, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <table style="margin-top: 5px;">
                <tr>
                    <td style="width: 65%">¤¤¤¤¤</td>
                    <td style="width: 10%;">TOTAL:</td>
                    <td class="text-right" style="width: 25%;">
                        <?= $isExp ? '-' : number_format($header['total'], 0, ',', '.') ?>
                    </td>
                </tr>
            </table>

            <div class="notice-box">
                HANYA UNTUK KONSUMSI SENDIRI TIDAK UNTUK
                <br>
                DIPERJUALBELIKAN
                <div class="notice-divider">-------------------------------------------</div>
                DILARANG MENGGUNAKAN MERK DAGANG PERUSAHAAN
            </div>
        </body>
        </html>
        <?php
    }
}
?>