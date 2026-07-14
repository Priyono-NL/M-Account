<?php
class InvoiceView {
    public static function render($header, $items) {
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        
        // --- 1. PHP MENYUSUN TEKS RAW (UKURAN KECIL / STRUK POS) ---
        $ESC = "\x1B";
        $LN = "\n";
        
        $rawText = $ESC . "@";
        
        // --- AKTIFKAN MODUL 15 CPI (WAJIB AGAR LEBAR 9,5 CM MUAT 51 KOLOM) ---
        $rawText .= $ESC . "k" . chr(0);
        $rawText .= "\x12";
        $rawText .= $ESC . "g"; // ESC g = Mode 15 CPI (Karakter rapat, muat hingga 56 karakter di 9,5 cm)
        $rawText .= $ESC . "C" . chr(33); // Panjang halaman
        $rawText .= $ESC . "Q" . chr(51); // Batasi margin kanan ketat di 51 kolom
        
        // --- 2. ISI KONTEN ---
        
        // Judul (Trik Swedia untuk Simbol ¤)
        $rawText .= $ESC . "R" . chr(5);
        
        $simbol = "$$"; 
        $judul = $simbol . " PASS KELUAR " . $simbol;
        $rawText .= str_pad($judul, 51, " ", STR_PAD_BOTH) . $LN . $LN;
        
        // Header Info
        $valDate  = isset($header['sales_date']) ? strtoupper(date('d-M-Y', strtotime($header['sales_date']))) : '-';
        $valDoc   = $header['invoice_no'] ?? '-';
        $valPrint = ($header['is_reprint'] == false) ? '' : $header['reprint'];
        $valBuyer = trim(($header['buyer_code'] ?? '') . " " . ($header['buyer_name'] ?? '-'));
        $valRemark = $header['remark'] ?? '';
        
        $kiriDate = "Date      " . $valDate;
        $kananPrint = "Print#" . $valPrint;
        // Total kolom baris tanggal: 36 + 15 = 51
        $dateLine   = str_pad($kiriDate, 36) . str_pad($kananPrint, 15, " ", STR_PAD_LEFT);
        
        $rawText .= $dateLine . $LN;
        $rawText .= "Doc. No   " . $valDoc . $LN;
        $rawText .= "Buyer     " . $valBuyer . $LN;
        $rawText .= "Remark    " . $valRemark . $LN . $LN;
        
        // Tabel Header (Total Pas 51 Kolom)
        $th_nama = str_pad("Deskripsi", 26);
        $th_hrg  = str_pad("Harga", 9, " ", STR_PAD_LEFT);
        $th_qty  = str_pad("Qty", 4, " ", STR_PAD_LEFT);        
        $th_tot  = str_pad("Subtotal", 12, " ", STR_PAD_LEFT);
        $rawText .= $th_nama . $th_hrg . $th_qty . $th_tot . $LN;
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $subtotal = ($item['unit_price'] ?? 0) * ($item['item_qty'] ?? 0);
                
                // Potong nama barang di 25 karakter agar kolom sebelahnya tidak tergeser
                $nama  = str_pad(substr($item['item_name'], 0, 25), 26); 
                $harga = str_pad($isExp ? '-' : number_format($item['unit_price'], 0, ',', '.'), 9, " ", STR_PAD_LEFT);
                $qty   = str_pad($item['item_qty'], 4, " ", STR_PAD_LEFT);                
                $total = str_pad($isExp ? '-' : number_format($subtotal, 0, ',', '.'), 12, " ", STR_PAD_LEFT);
                
                $rawText .= $nama . $harga . $qty . $total . $LN;
            }
        }       
        $simbolTotal = "$$$$$";
        $teksTotal = "TOTAL:";
        $grandTotal = $isExp ? '-' : number_format($header['total'] ?? 0, 0, ',', '.');
        
        // Blok Kiri (39) + Blok Kanan (12) = 51 Kolom
        $blokKiri = $simbolTotal . str_pad($teksTotal, 39 - strlen($simbolTotal), " ", STR_PAD_LEFT);
        $blokKanan = str_pad($grandTotal, 12, " ", STR_PAD_LEFT);       
        $rawText .= $blokKiri . $blokKanan . $LN;

        for ($i = 0; $i < 14; $i++) {
            $rawText .= $LN;
        }

        $notice1 = "HANYA UNTUK KONSUMSI SENDIRI TIDAK UNTUK";
        $notice2 = "DIPERJUALBELIKAN";
        $notice3 = "-------------------------------------------";
        $notice4 = "DILARANG MENGGUNAKAN MERK DAGANG PERUSAHAAN";

        $rawText .= str_pad($notice1, 51, " ", STR_PAD_BOTH) . $LN;
        $rawText .= str_pad($notice2, 51, " ", STR_PAD_BOTH) . $LN;
        $rawText .= str_pad($notice3, 51, " ", STR_PAD_BOTH) . $LN;
        $rawText .= str_pad($notice4, 51, " ", STR_PAD_BOTH) . $LN;
        
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
                        <p style="color: #6c757d; font-size: 14px;">Izin ke printer diperlukan untuk mencetak dokumen.</p>
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
                            
                            // --- PERBAIKAN 1: Cari Endpoint OUT secara otomatis ---
                            const interfaceObj = device.configuration.interfaces[0];
                            const alternate = interfaceObj.alternates[0];
                            const endpoint = alternate.endpoints.find(e => e.direction === 'out');
                            
                            if (!endpoint) {
                                throw new Error("Endpoint OUT tidak ditemukan pada printer ini.");
                            }
                            const endpointNumber = endpoint.endpointNumber;

                            // Konversi data hex dari PHP
                            const dataBytes = hexToBytes(dataHex);
                            
                            // --- PERBAIKAN 2: Kirim data per 64 byte (Chunking) agar buffer tidak meluap ---
                            const chunkSize = 64;
                            for (let i = 0; i < dataBytes.length; i += chunkSize) {
                                const chunk = dataBytes.subarray(i, i + chunkSize);
                                await device.transferOut(endpointNumber, chunk);
                            }
                            
                            // Lepas interface secara bersih
                            await device.releaseInterface(0);
                            await device.close();
                            
                            // Beri jeda sebentar sebelum menutup window agar proses transfer selesai sempurna
                            setTimeout(() => {
                                window.close();
                            }, 500);

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