<?php
class InvoiceView {
    public static function render($header, $items) {
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        ?>
        <html>
        <head>
            <style>
                @page { margin: 0; }
                
                body { 
                    font-family: 'Courier', monospace;
                    font-size: 8pt;
                    line-height: 1.1; 
                    margin: 0.3cm; 
                    color: #000;
                }

                .text-left { text-align: left; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-bold { font-weight: bold; }

                .line { border-bottom: 1px dashed #000; margin: 4px 0; }
                
                table { width: 100%; border-collapse: collapse; }
                table td { vertical-align: top; padding: 1px 0; }

                .table-header { border-top: 1px dashed #000; border-bottom: 1px dashed #000; }

                .no-print { display: none; }
            </style>
        </head>
        <body>
            <div class="text-center">
                <strong style="font-size: 10pt;">** PASS KELUAR **</strong>
            </div>

            <table style="margin-top: 10px;">
                <tr>
                    <td>Date : <?= $header['sales_date'] ?></td>
                    <td class="text-right">Print#</td>
                </tr>
                <tr>
                    <td colspan="2">Doc. No : <?= $header['invoice_no'] ?></td>
                </tr>
                <tr>
                    <td colspan="2">Buyer: <?= $header['buyer_code'] ?> - <?= $header['buyer_name'] ?></td>
                </tr>
            </table>

            <table style="margin-top: 10px;">
                <thead>
                    <tr class="table-header">
                        <th class="text-left" style="width: 45%;">Barang</th>
                        <th class="text-right" style="width: 10%;">Qty</th>
                        <th class="text-right" style="width: 20%;">Harga</th>
                        <th class="text-right" style="width: 25%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $subtotal = $item['unit_price'] * $item['item_qty'];
                    ?>
                    <tr>
                        <td><?= $item['item_name'] ?></td>
                        <td class="text-right"><?= $item['item_qty'] ?></td>
                        <td class="text-right"><?= $isExp ? '-' : number_format($item['unit_price'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= $isExp ? '-' : number_format($subtotal, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="height:2cm;"></div>
            <div style="margin-top: 10px;" class="line"></div>

            <table>
                <tr class="text-bold">
                    <td style="width: 60%;">TOTAL:</td>
                    <td class="text-right" style="width: 40%;">
                        <?= $isExp ? '-' : number_format($header['total'], 0, ',', '.') ?>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
    }
}