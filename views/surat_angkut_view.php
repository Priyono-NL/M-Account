<?php
class SuratView {
    public static function render($header, $items) {
        // die(var_dump($header, $items));
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cetak Surat Angkut - <?= $header['invoice_no'] ?? 'INV' ?></title>
            <style>
                /* Reset Dasar */
                * { box-sizing: border-box; -webkit-print-color-adjust: exact; }
                
                body { 
                    font-family: 'Courier New', Courier, monospace; 
                    font-size: 8pt;
                    line-height: 1.2; 
                    width: 26cm;
                    height: 17cm;
                    margin: 0 auto; 
                    padding: 0.3cm;
                    color: #000;
                }

                .text-left { text-align: left; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-bold { font-weight: bold; }

                .line { border-bottom: 1px dashed #000; margin: 5px 0; }
                
                table { width: 100%; border-collapse: collapse; border:1px; }
                table td, table th { vertical-align: top; padding: 2px 0; font-weight: normal; }

                .table-header { border-top: 1px dashed #000; border-bottom: 1px dashed #000; }

                @media print { 
                    .no-print { display: none !important; } 
                    @page { 
                        size: 26cm 17cm;
                        margin: 0; 
                    }
                    body {
                        margin: 0;
                        border: none;
                    }
                }
            </style>
        </head>

        <body onload="window.print();">
            <div class="no-print" style="background: #fff3cd; padding: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #ffeeba;">
                <button onclick="window.print()" style="padding: 5px 10px; cursor: pointer;">CETAK (Ctrl+P)</button>
                <button onclick="window.close()" style="padding: 5px 10px; cursor: pointer;">TUTUP</button>
                <p style="font-size: 11px; margin-top: 5px;">Pastikan Margins diatur ke "None" pada setelan print browser.</p>
            </div>

            <table>
                <tr>
                    <td>
                        <strong style="font-size: 10pt;"><center>¤¤ FAKTUR PENJUALAN ¤¤</center></strong><br>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td>Tanggal : <?= $header['sales_date'] ?></td>
                    <td>Nomor Faktur : <?= $header['invoice_no'] ?></td>
                    <td class="text-right">Print#</td>
                </tr>
                <tr>
                    <td>Kepada : <?= $header['buyer_code'] ?>  <?= $header['buyer_name'] ?></td>
                    <td>Kode Gudang : <?= $header['warehouse'] ?></td>
                </tr>
            </table>

            <table style="margin-top: 5px;">
                <thead>
                    <tr class="table-header">
                        <th class="text-center" style="width: 50%;">Nama Barang</th>
                        <th class="text-center" style="width: 10%;">Jumlah</th>
                        <th class="text-center" style="width: 20%;">Harga</th>
                        <th class="text-center" style="width: 20%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalQty = 0;
                    foreach ($items as $item): 
                        $subtotal = $item['unit_price'] * $item['item_qty'];
                        $totalQty += $item['item_qty'];
                    ?>
                    <tr>
                        <td><?= $item['item_name'] ?></td>
                        <td><?= $item['item_qty'] ?> <?= $item['item_uom'] ?></td>
                        <td class="text-right">
                            <?= $isExp ? '-' : number_format($item['unit_price'], 0, ',', '.') ?>
                        </td>
                        <td class="text-right">
                            <?= $isExp ? '-' : number_format($subtotal, 0, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="line"></div>

            <table style="width: 100%;">
                <tr class="text-bold    ">
                    <td class="text-right" style="width: 80%;">GRAND TOTAL:</td>
                    <td class="text-right" style="width: 20%;">
                        <?= $isExp ? '-' : number_format($header['total'], 0, ',', '.') ?>
                    </td>
                </tr>
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