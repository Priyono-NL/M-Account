<?php
namespace Views\Formatters;

class InvoiceFormatter {
    public static function buildPassKeluarRaw($header, $items) {
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        
        $ESC = "\x1B";
        $LN  = "\n";
        
        // --- 1. INISIALISASI PRINTER ---
        $rawText = $ESC . "@";
        
        // Mode 15 CPI (Karakter Rapat, Muat 51-56 Kolom di Kertas 9,5 cm)
        $rawText .= $ESC . "k" . chr(0);
        $rawText .= "\x12";
        $rawText .= $ESC . "g"; 
        $rawText .= $ESC . "C" . chr(33); // Panjang Halaman
        $rawText .= $ESC . "Q" . chr(51); // Margin Kanan 51 Kolom
        
        // --- 2. JUDUL PASS KELUAR ---
        $rawText .= $ESC . "R" . chr(5);
        $simbol = "$$"; 
        $judul  = $simbol . " PASS KELUAR " . $simbol;

        $rawText .= $ESC . "E" . $ESC . "G";
        $rawText .= str_pad($judul, 51, " ", STR_PAD_BOTH) . $LN . $LN;
        $rawText .= $ESC . "H" . $ESC . "F";
        
        // --- 3. HEADER INFO ---
        $valDate   = isset($header['sales_date']) ? strtoupper(date('d-M-Y', strtotime($header['sales_date']))) : '-';
        $valDoc    = $header['invoice_no'] ?? '-';
        $valPrint  = ($header['is_reprint'] == false) ? '' : $header['reprint'];
        $valBuyer  = trim(($header['buyer_code'] ?? '') . " " . ($header['buyer_name'] ?? '-'));
        $valRemark = $header['remark'] ?? '';
        
        $kiriDate   = "Date      " . $valDate;
        $kananPrint = "Print#" . $valPrint;
        $dateLine   = str_pad($kiriDate, 36) . str_pad($kananPrint, 15, " ", STR_PAD_LEFT);
        
        $rawText .= $dateLine . $LN;
        $rawText .= "Doc. No   " . $valDoc . $LN;
        $rawText .= "Buyer     " . $valBuyer . $LN;
        $rawText .= "Remark    " . $valRemark . $LN . $LN;
        
        // --- 4. TABEL HEADER (TOTAL 51 KOLOM) ---
        $th_nama = str_pad("Deskripsi", 26);
        $th_hrg  = str_pad("Harga", 9, " ", STR_PAD_BOTH);
        $th_qty  = str_pad("Qty", 4, " ", STR_PAD_LEFT);        
        $th_tot  = str_pad("Subtotal", 12, " ", STR_PAD_LEFT);
        $rawText .= $th_nama . $th_hrg . $th_qty . $th_tot . $LN;
        
        // Loop Item Barang
        if (!empty($items)) {
            foreach ($items as $item) {
                $subtotal = ($item['unit_price'] ?? 0) * ($item['item_qty'] ?? 0);
                
                $nama  = str_pad(substr($item['item_name'], 0, 25), 26); 
                $harga = str_pad($isExp ? '-' : number_format($item['unit_price'], 0, ',', '.'), 9, " ", STR_PAD_LEFT);
                $qty   = str_pad($item['item_qty'], 4, " ", STR_PAD_LEFT);                
                $total = str_pad($isExp ? '-' : number_format($subtotal, 0, ',', '.'), 12, " ", STR_PAD_LEFT);
                
                $rawText .= $nama . $harga . $qty . $total . $LN;
            }
        }
        
        // Total Omset
        $simbolTotal = "$$$$$";
        $teksTotal   = "TOTAL:";
        $grandTotal  = $isExp ? '-' : number_format($header['total'] ?? 0, 0, ',', '.');
        
        $blokKiri  = $simbolTotal . str_pad($teksTotal, 39 - strlen($simbolTotal), " ", STR_PAD_LEFT);
        $blokKanan = str_pad($grandTotal, 12, " ", STR_PAD_LEFT);       
        $rawText .= $blokKiri . $blokKanan . $LN;

        // Spasi
        for ($i = 0; $i < 14; $i++) {
            $rawText .= $LN;
        }

        // --- 5. FOOTER NOTICE ---
        $notice1 = "HANYA UNTUK KONSUMSI SENDIRI TIDAK UNTUK";
        $notice2 = "DIPERJUALBELIKAN";
        $notice3 = "-------------------------------------------";
        $notice4 = "DILARANG MENGGUNAKAN MERK DAGANG PERUSAHAAN";

        $rawText .= $ESC . "E" . $ESC . "G";
        $rawText .= str_pad($notice1, 51, " ", STR_PAD_BOTH) . $LN;
        $rawText .= str_pad($notice2, 51, " ", STR_PAD_BOTH) . $LN;
        $rawText .= str_pad($notice3, 51, " ", STR_PAD_BOTH) . $LN;
        $rawText .= str_pad($notice4, 51, " ", STR_PAD_BOTH) . $LN;
        $rawText .= $ESC . "H" . $ESC . "F";
        
        // --- 6. PELATUK FORM FEED ---
        $rawText .= "\x0C";

        return $rawText;
    }
}