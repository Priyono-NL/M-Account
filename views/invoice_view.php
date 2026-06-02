<?php
class InvoiceView {
    public static function render($header, $items) {
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        
        // --- 1. PHP MENYUSUN TEKS RAW (UKURAN KECIL / STRUK POS) ---
        $ESC = "\x1B";
        $LN = "\n";
        
        $rawText = $ESC . "@";
        
        // --- AKTIFKAN MODUL ELITE (12 CPI) ---
        $rawText .= $ESC . "k" . chr(0);
        $rawText .= "\x12";
        $rawText .= $ESC . "g";        
        $rawText .= $ESC . "C" . chr(33); //panjang
        $rawText .= $ESC . "Q" . chr(53); //lebar 
        
        // --- 2. ISI KONTEN ---
        
        // Judul (Trik Swedia untuk Simbol ¤)
        $rawText .= $ESC . "R" . chr(5);
		
		$simbol = "$$"; 
        $judul = $simbol . " PASS KELUAR " . $simbol;
        $rawText .= str_pad($judul, 53, " ", STR_PAD_BOTH) . $LN . $LN;
        
        // Header Info
        $valDate  = $header['sales_date'] ?? '-';
        $valDoc   = $header['invoice_no'] ?? '-';
        $valPrint = $header['print_count'] ?? '';
        $valBuyer = trim(($header['buyer_code'] ?? '') . " " . ($header['buyer_name'] ?? '-'));
        
		$kiriDate = "Date      " . $valDate;
        $kananPrint = "Print#" . $valPrint;
        $dateLine   = str_pad($kiriDate, 38) . str_pad($kananPrint, 15, " ", STR_PAD_LEFT);
        
        $rawText .= $dateLine . $LN;
        $rawText .= "Doc. No   " . $valDoc . $LN;
        $rawText .= "Buyer     " . $valBuyer . $LN;
        $rawText .= "Remark    " . $LN . $LN;
        
        // Tabel Header
        $th_nama = str_pad("Deskripsi", 28);
		$th_hrg  = str_pad("Harga", 9, " ", STR_PAD_LEFT);
        $th_qty  = str_pad("Qty", 4, " ", STR_PAD_LEFT);        
        $th_tot  = str_pad("Subtotal", 12, " ", STR_PAD_LEFT);
        $rawText .= $th_nama . $th_hrg . $th_qty . $th_tot . $LN;
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $subtotal = ($item['unit_price'] ?? 0) * ($item['item_qty'] ?? 0);
                
                // Potong nama barang jika lebih dari 14 karakter agar tidak merusak layout
                $nama  = str_pad(substr($item['item_name'], 0, 27), 28); 
				$harga = str_pad($isExp ? '-' : number_format($item['unit_price'], 0, ',', '.'), 9, " ", STR_PAD_LEFT);
                $qty   = str_pad($item['item_qty'], 4, " ", STR_PAD_LEFT);                
                $total = str_pad($isExp ? '-' : number_format($subtotal, 0, ',', '.'), 12, " ", STR_PAD_LEFT);
                
                $rawText .= $nama . $harga . $qty . $total . $LN;
            }
        }		
        $simbolTotal = "$$$$$";
        $teksTotal = "TOTAL:";
        $grandTotal = $isExp ? '-' : number_format($header['total'] ?? 0, 0, ',', '.');
		
		$blokKiri = $simbolTotal . str_pad($teksTotal, 41 - strlen($simbolTotal), " ", STR_PAD_LEFT);
        $blokKanan = str_pad($grandTotal, 12, " ", STR_PAD_LEFT);		
        $rawText .= $blokKiri . $blokKanan . $LN;
        
        // --- 3. PELATUK FORM FEED ---
        $rawText .= "\x0C";
        
        // Encode ke Hex
        $hexData = bin2hex($rawText);
        ?>
        
        <!DOCTYPE html>
        <html>
        <head>
            <title>Mencetak PASS KELUAR...</title>
            <style>
                body { font-family: sans-serif; padding: 40px; text-align: center; background: #eef2f7; }
                .box { background: white; padding: 40px; border-radius: 8px; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.05); max-width: 500px;}
                button { padding: 12px 30px; font-size: 16px; font-weight: bold; cursor: pointer; border: none; border-radius: 4px; background: #198754; color: white; margin-top: 20px;}
                button:hover { background: #157347; }
                #status { font-size: 18px; color: #333; margin-bottom: 10px; font-weight: bold;}
                #manual-print-area { display: none; }
            </style>
        </head>
        <body>
            <div class="box">
                <div id="status">Memeriksa koneksi printer Epson...</div>
                
                <div id="manual-print-area">
                    <p style="color: #6c757d; font-size: 14px;">Izin ke printer diperlukan untuk mencetak dokumen kecil ini.</p>
                    <button onclick="printManual()">IZINKAN & CETAK (40 Kolom)</button>
                </div>
            </div>

            <script>
                const dataHex = "<?= $hexData ?>";
                
                function hexToBytes(hex) {
                    let bytes = new Uint8Array(hex.length / 2);
                    for (let i = 0; i < hex.length; i += 2) {
                        bytes[i / 2] = parseInt(hex.substr(i, 2), 16);
                    }
                    return bytes;
                }

                async function eksekusiCetak(device) {
                    try {
                        document.getElementById('status').innerText = "Mencetak struk Pass Keluar...";
                        document.getElementById('manual-print-area').style.display = 'none';

                        await device.open();
                        await device.selectConfiguration(1);
                        await device.claimInterface(0); 
                        
                        const dataBytes = hexToBytes(dataHex);
                        await device.transferOut(1, dataBytes);
                        
                        await device.releaseInterface(0);
                        await device.close();
                        
                        window.close(); // Otomatis tutup tab
                    } catch (error) {
                        console.error(error);
                        document.getElementById('status').innerText = "Gagal mencetak: " + error.message;
                    }
                }

                async function autoPrint() {
                    if (!("usb" in navigator)) {
                        document.getElementById('status').innerText = "Browser tidak mendukung WebUSB.";
                        return;
                    }
                    try {
                        const devices = await navigator.usb.getDevices();
                        const epsonDevice = devices.find(d => d.vendorId === 0x04b8);

                        if (epsonDevice) {
                            await eksekusiCetak(epsonDevice);
                        } else {
                            document.getElementById('status').innerText = "Menunggu Izin Printer";
                            document.getElementById('manual-print-area').style.display = 'block';
                        }
                    } catch (error) {
                        console.error("Auto-print error:", error);
                    }
                }

                async function printManual() {
                    try {
                        const device = await navigator.usb.requestDevice({ filters: [{ vendorId: 0x04b8 }] });
                        await eksekusiCetak(device);
                    } catch (error) {
                        console.error(error);
                        alert("Gagal mendapatkan izin printer.");
                    }
                }

                window.onload = autoPrint;
            </script>
        </body>
        </html>
        <?php
    }
}
?>