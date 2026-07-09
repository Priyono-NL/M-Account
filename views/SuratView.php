<?php
class SuratView {
    public static function render($header, $items) {
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        
        // --- 1. PHP MENYUSUN TEKS RAW ---
        $ESC = "\x1B";
        $LN = "\n";
        
        $rawText = $ESC . "@";
		
		// --- Tambah karakter tabel PC850 ---
		$rawText .= $ESC . "(t" . chr(3) . chr(0) . chr(1) . chr(3) . chr(0);		
		$rawText .= $ESC . "t" . chr(1);
        
        // --- SETTING MODUL ---
        $rawText .= $ESC . "k" . chr(0);
        $rawText .= "\x12";
        $rawText .= $ESC . "g";
        $rawText .= $ESC . "C" . chr(33);  // PANJANG
        $rawText .= $ESC . "Q" . chr(113); // LEBAR
        
        // --- 2. ISI KONTEN ---
        
        // Judul otomatis rata tengah - ganti region
		$rawText .= $ESC . "R" . chr(5);
		$simbol = "$$"; 
		$judul = $simbol . " FAKTUR PENJUALAN " . $simbol;
		$rawText .= str_pad($judul, 113, " ", STR_PAD_BOTH) . $LN . $LN;
        
        // Header Info
        $tgl    = "Tanggal : " . ($header['sales_date'] ?? '-');
        $kepada = "Kepada  : " . ($header['buyer_code'] ?? '') . " " . ($header['buyer_name'] ?? '-');
		$print  = "Print#" . (($header['is_reprint'] == false) ? '' : $header['reprint']);
		$no_inv = "Nomor Faktur : " . ($header['invoice_no'] ?? '-');
        $gudang = "Kode Gudang  : " . ($header['warehouse'] ?? '-');                

        $col1 = 60; 
        $col2 = 40;
        $col3 = 13; 

        $baris1 = str_pad($tgl, $col1) . str_pad($no_inv, $col2) . str_pad($print, $col3, " ", STR_PAD_LEFT);
        $baris2 = str_pad($kepada, $col1) . $gudang;

        $rawText .= $baris1 . $LN;
        $rawText .= $baris2 . $LN;
        
        // Tabel Header
        $rawText .= str_repeat("=", 113) . $LN;
        $th_nama   = str_pad("Nama Barang", 67);
        $th_jumlah = str_pad("Jumlah", 14);
        $th_harga  = str_pad("Harga", 16, " ", STR_PAD_LEFT);
        $th_total  = str_pad("Total", 16, " ", STR_PAD_LEFT);
        $rawText .= $th_nama . $th_jumlah . $th_harga . $th_total . $LN;
        $rawText .= str_repeat("-", 113) . $LN;
        
        // Looping Isi Barang
        if (!empty($items)) {
            foreach ($items as $item) {
                $subtotal = ($item['unit_price'] ?? 0) * ($item['item_qty'] ?? 0);
                
                $nama = str_pad(substr($item['item_name'], 0, 65), 67);
                $qty = str_pad(($item['item_qty'] ?? 0) . " " . ($item['item_uom'] ?? ''), 14);
                $harga = str_pad($isExp ? '-' : number_format($item['unit_price'], 0, ',', '.'), 16, " ", STR_PAD_LEFT);
                $total = str_pad($isExp ? '-' : number_format($subtotal, 0, ',', '.'), 16, " ", STR_PAD_LEFT);
                
                $rawText .= $nama . $qty . $harga . $total . $LN;
            }
        }
		$simbol = "$$$$$";
		$garis  = str_repeat("-", 108);
        $rawText .= $simbol . $garis . $LN;
        
        $grandTotal = $isExp ? '-' : number_format($header['total'] ?? 0, 0, ',', '.');
        $rawText .= str_pad("Grand Total:", 97, " ", STR_PAD_LEFT) . str_pad($grandTotal, 16, " ", STR_PAD_LEFT) . $LN . $LN;
        
        $ttd_kiri  = "Yang Menyerahkan";
        $ttd_kanan = "Penerima";
        
        $garis_kiri  = "________________"; 
        $garis_kanan = "________________";
        
        // Baris Teks Nama
        $baris_nama_ttd = str_pad($ttd_kiri, 56, " ", STR_PAD_BOTH) . str_pad($ttd_kanan, 57, " ", STR_PAD_BOTH);
        $rawText .= $baris_nama_ttd . $LN . $LN . $LN . $LN;
        
        // Baris Garis Tanda Tangan
        $baris_garis_ttd = str_pad($garis_kiri, 56, " ", STR_PAD_BOTH) . str_pad($garis_kanan, 57, " ", STR_PAD_BOTH);
        $rawText .= $baris_garis_ttd . $LN;
        
        // --- 3. PELATUK FORM FEED ---
        $rawText .= "\x0C";
        
        // Encode ke Hex
        $hexData = bin2hex($rawText);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Mencetak <?= $header['invoice_no'] ?? 'INV' ?>...</title>
            <style>
                body { font-family: sans-serif; padding: 40px; text-align: center; background: #eef2f7; }
                .box { background: white; padding: 40px; border-radius: 8px; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.05); max-width: 500px;}
                button { padding: 12px 30px; font-size: 16px; font-weight: bold; cursor: pointer; border: none; border-radius: 4px; background: #198754; color: white; margin-top: 20px;}
                button:hover { background: #157347; }
                #status { font-size: 18px; color: #333; margin-bottom: 10px; font-weight: bold;}
                #manual-print-area { display: none; } /* Disembunyikan secara default */
            </style>
        </head>
        <body>
            <div class="box">
                <div id="status">Memeriksa koneksi printer...</div>
                
                <div id="manual-print-area">
                    <p style="color: #6c757d; font-size: 14px;">Izin ke printer diperlukan untuk mencetak dokumen.</p>
                    <button onclick="printManual()">IZINKAN & CETAK SEKARANG</button>
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

                // Fungsi inti eksekusi printer
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

                // Fungsi saat tab baru dibuka (Auto-Print)
                async function autoPrint() {
                    if (!("usb" in navigator)) {
                        document.getElementById('status').innerText = "Browser tidak mendukung WebUSB.";
                        return;
                    }

                    try {
                        const devices = await navigator.usb.getDevices();
                        const epsonDevice = devices.find(d => d.vendorId === 0x04b8);

                        if (epsonDevice) {
                            // Izin sudah ada, langsung cetak dan tutup!
                            await eksekusiCetak(epsonDevice);
                        } else {
                            // Belum ada izin, tampilkan tombol untuk klik manual
                            document.getElementById('status').innerText = "Menunggu Izin Printer";
                            document.getElementById('manual-print-area').style.display = 'block';
                        }
                    } catch (error) {
                        console.error("Auto-print error:", error);
                    }
                }

                // Fungsi jika user harus klik (Manual Print)
                async function printManual() {
                    try {
                        const device = await navigator.usb.requestDevice({ filters: [{ vendorId: 0x04b8 }] });
                        await eksekusiCetak(device);
                    } catch (error) {
                        console.error(error);
                        alert("Gagal mendapatkan izin printer.");
                    }
                }

                // Jalankan otomatis saat tab selesai di-load
                window.onload = autoPrint;
            </script>
        </body>
        </html>
        <?php
    }
}