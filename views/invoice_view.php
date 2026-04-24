<?php
class InvoiceView {
    public static function render($header, $items) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cetak Struk - <?= $header['invoice_no'] ?? 'INV' ?></title>
            <style>
                body { font-family: 'Courier New', Courier, monospace; font-size: 12px; line-height: 1.2; width: 80mm; margin: 0 auto; padding: 10px; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .line { border-bottom: 1px dashed #000; margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; }
                .footer { margin-top: 15px; font-size: 10px; }

                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body onload="window.print();">
            <div class="no-print" style="background: #fff3cd; padding: 10px; margin-bottom: 20px; text-align: center;">
                <button onclick="window.print()">Klik Cetak</button>
                <button onclick="window.close()">Tutup Halaman</button>
            </div>

            <div class="text-center">
                <strong style="font-size: 16px;">TEST INVOICE</strong><br>
            </div>

            <div class="line"></div>
            <table>
                <tr><td>No: <?= $header['invoice_no'] ?? $header['id'] ?></td><td class="text-right"><?= date('d M Y', strtotime($header['sales_date'])) ?></td></tr>
                <tr><td colspan="2">Kasir: <?= $_SESSION['user_name'] ?? 'Admin' ?></td></tr>
                <tr><td colspan="2">Pelanggan: <?= $header['buyer_name'] ?></td></tr>
            </table>
            <div class="line"></div>

            <table>
                <?php 
                $totalQty = 0;
                foreach ($items as $item): 
                    $subtotal = $item['unit_price'] * $item['item_qty'];
                    $totalQty += $item['item_qty'];
                ?>
                <tr>
                    <td colspan="2"><?= $item['item_name'] ?></td>
                </tr>
                <tr>
                    <td><?= $item['item_qty'] ?> x Rp <?= number_format($item['unit_price'], 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="line"></div>
            <table>
                <tr>
                    <td><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>Rp <?= number_format($header['total'], 0, ',', '.') ?></strong></td>
                </tr>
            </table>

            <div class="line"></div>
        </body>
        </html>
        <?php
    }
}