<?php
class SuratViewPdf {
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
                <strong style="font-size: 10pt;">¤¤ FAKTUR PENJUALAN ¤¤</strong>
            </div>

            <table style="margin-top: 5px;">
                <tr>
                    <td style="width: 55%">Tanggal : <?= isset($header['sales_date']) ? htmlspecialchars(strtoupper(date('d-M-Y', strtotime($header['sales_date']))), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                    <td style="width: 35%">Nomor Faktur : <?= htmlspecialchars($header['invoice_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-right">Print#<?= htmlspecialchars(($header['is_reprint'] == false) ? '' : $header['reprint'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td>Kepada : <?= htmlspecialchars($header['buyer_code'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($header['buyer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>Kode Gudang : <?= htmlspecialchars($header['warehouse'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td></td>
                </tr>
            </table>

            <table style="margin-top: 5px;">
                <thead>
                    <tr class="table-header">
                        <th class="text-left" style="width: 50%;">Nama Barang</th>
                        <th class="text-center" style="width: 10%;">Jumlah</th>
                        <th class="text-center" style="width: 20%;">Harga</th>
                        <th class="text-center" style="width: 20%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $subtotal = $item['unit_price'] * $item['item_qty'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($item['item_qty'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($item['item_uom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-right"><?= $isExp ? '-' : number_format($item['unit_price'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= $isExp ? '-' : number_format($subtotal, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <table style="margin-top: 20px;">
                <tr>
                    <td class="text-center" style="width: 35%;">Yang Menyerahkan</td>
                    <td style="width:30%"></td>
                    <td class="text-center" style="width: 35%;">Penerima</td>
                </tr>
                <tr>
                    <td style="height: 50px;"></td>
                    <td style="height: 50px;"></td>
                    <td style="height: 50px;"></td>
                </tr>
                <tr>
                    <td class="text-center">_________________</td>
                    <td></td>
                    <td class="text-center">_________________</td>
                </tr>
            </table>

        </body>
        </html>
        <?php
    }
}
?>