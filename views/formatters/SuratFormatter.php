<?php
namespace Views\Formatters;

class SuratFormatter {
    public static function buildInvoiceRaw($header, $items) {
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        
        $ESC = "\x1B";
        $LN  = "\n";
        
        $rawText = $ESC . "@";
        
        // --- Setting Karakter PC850 ---
        $rawText .= $ESC . "(t" . chr(3) . chr(0) . chr(1) . chr(3) . chr(0);       
        $rawText .= $ESC . "t" . chr(1);
        
        // --- Setting Ukuran Kertas & Mode ---
        $rawText .= $ESC . "k" . chr(0);
        $rawText .= "\x12";
        $rawText .= $ESC . "g";
        $rawText .= $ESC . "C" . chr(33);  // Panjang
        $rawText .= $ESC . "Q" . chr(113); // Lebar
        
        // Judul
        $rawText .= $ESC . "R" . chr(5);
        $simbol = "$$"; 
        $judul = $simbol . " FAKTUR PENJUALAN " . $simbol;
        $rawText .= str_pad($judul, 113, " ", STR_PAD_BOTH) . $LN . $LN;
        
        // Header Info
        $tgl    = "Tanggal : " . (isset($header['sales_date']) ? strtoupper(date('d-M-Y', strtotime($header['sales_date']))) : '-');
        $kepada = "Kepada  : " . ($header['buyer_code'] ?? '') . " " . ($header['buyer_name'] ?? '-');
        $print  = "Print#" . (($header['is_reprint'] == false) ? '' : $header['reprint']);
        $no_inv = "Nomor Faktur : " . ($header['invoice_no'] ?? '-');
        $gudang = "Kode Gudang  : " . ($header['warehouse'] ?? '-');                

        $col1 = 60; $col2 = 40; $col3 = 13; 

        $rawText .= str_pad($tgl, $col1) . str_pad($no_inv, $col2) . str_pad($print, $col3, " ", STR_PAD_LEFT) . $LN;
        $rawText .= str_pad($kepada, $col1) . $gudang . $LN;
        
        // Tabel Header
        $rawText .= str_repeat("=", 113) . $LN;
        $rawText .= str_pad("Nama Barang", 67) . str_pad("Jumlah", 14) . str_pad("Harga", 16, " ", STR_PAD_LEFT) . str_pad("Total", 16, " ", STR_PAD_LEFT) . $LN;
        $rawText .= str_repeat("-", 113) . $LN;
        
        // Looping Barang
        if (!empty($items)) {
            foreach ($items as $item) {
                $subtotal = ($item['unit_price'] ?? 0) * ($item['item_qty'] ?? 0);
                
                $nama  = str_pad(substr($item['item_name'], 0, 65), 67);
                $qty   = str_pad(($item['item_qty'] ?? 0) . " " . ($item['item_uom'] ?? ''), 14);
                $harga = str_pad($isExp ? '-' : number_format($item['unit_price'], 0, ',', '.'), 16, " ", STR_PAD_LEFT);
                $total = str_pad($isExp ? '-' : number_format($subtotal, 0, ',', '.'), 16, " ", STR_PAD_LEFT);
                
                $rawText .= $nama . $qty . $harga . $total . $LN;
            }
        }
        
        $rawText .= "$$$$$" . str_repeat("-", 108) . $LN;
        
        $grandTotal = $isExp ? '-' : number_format($header['total'] ?? 0, 0, ',', '.');
        $rawText .= str_pad("Grand Total:", 97, " ", STR_PAD_LEFT) . str_pad($grandTotal, 16, " ", STR_PAD_LEFT) . $LN . $LN;
        
        // Tanda Tangan
        $rawText .= str_pad("Yang Menyerahkan", 56, " ", STR_PAD_BOTH) . str_pad("Penerima", 57, " ", STR_PAD_BOTH) . $LN . $LN . $LN . $LN;
        $rawText .= str_pad("________________", 56, " ", STR_PAD_BOTH) . str_pad("________________", 57, " ", STR_PAD_BOTH) . $LN;
        
        // Form Feed
        $rawText .= "\x0C";

        return $rawText;
    }
}