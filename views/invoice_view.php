<?php
class InvoiceView {
    public static function render($header, $items) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cetak Invoice - <?= $header['invoice_no'] ?? 'INV' ?></title>
            <style>
                /* Reset Dasar */
                * { box-sizing: border-box; -webkit-print-color-adjust: exact; }
                
                body { 
                    font-family: 'Courier New', Courier, monospace; 
                    font-size: 10pt;
                    line-height: 1.2; 
                    /* Ukuran Setengah Kuarto (A5 Landscape) */
                    width: 210mm; 
                    height: 140mm;
                    margin: 0 auto; 
                    padding: 5mm; 
                    color: #000;
                }

                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-bold { font-weight: bold; }

                .line { border-bottom: 1px solid #000; margin: 5px 0; }
                
                table { width: 100%; border-collapse: collapse; }
                table td { vertical-align: top; padding: 2px 0; }

                .table-header { border-top: 1px solid #000; border-bottom: 1px solid #000; }

                @media print { 
                    .no-print { display: none; } 
                    @page { size: auto; margin: 0; } 
                }
            </style>
        </head>
        <body onload="window.print();">
            
            <div class="no-print" style="background: #fff3cd; padding: 10px; margin-bottom: 20px; text-align: center; border: 1px solid #ffeeba;">
                <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">CETAK INVOICE (Ctrl+P)</button>
                <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">TUTUP</button>
                <p style="font-size: 11px; margin-top: 5px;">Pastikan Margins diatur ke "None" pada setelan print browser.</p>
            </div>

            <table style="margin-bottom: 10px;">
                <tr>
                    <td style="width: 60%;">
                        <strong style="font-size: 14pt;">M-ACCOUNT TEST</strong><br>
                    </td>
                    <td class="text-right">
                        <strong style="font-size: 12pt;">FAKTUR PENJUALAN</strong><br>
                        No: <?= $header['invoice_no'] ?? $header['id'] ?><br>
                        Tgl: <?= date('d/m/Y', strtotime($header['sales_date'])) ?>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td>Kasir : <?= $_SESSION['user_name'] ?? 'Admin' ?></td>
                    <td class="text-right">Pelanggan : <?= $header['buyer_name'] ?></td>
                </tr>
            </table>

            <table style="margin-top: 5px;">
                <thead>
                    <tr class="table-header">
                        <th class="text-left" style="width: 50%;">Deskripsi Barang</th>
                        <th class="text-center" style="width: 10%;">Qty</th>
                        <th class="text-right" style="width: 20%;">Harga</th>
                        <th class="text-right" style="width: 20%;">Subtotal</th>
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
                        <td class="text-center"><?= $item['item_qty'] ?></td>
                        <td class="text-right"><?= number_format($item['unit_price'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($subtotal, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="line"></div>

            <table style="width: 100%;">
                <tr>
                    <td style="width: 60%; font-style: italic;">
                        
                    </td>
                    <td style="width: 40%;">
                        <table>
                            <tr>
                                <td>TOTAL QTY</td>
                                <td class="text-right"><?= $totalQty ?></td>
                            </tr>
                            <tr class="text-bold">
                                <td>GRAND TOTAL</td>
                                <td class="text-right">Rp <?= number_format($header['total'], 0, ',', '.') ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

        </body>
        </html>
        <?php
    }
}