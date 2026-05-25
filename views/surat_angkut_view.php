<?php
class SuratView {
    public static function render($header, $items) {
        $isExp = (isset($header['sale_type']) && $header['sale_type'] === 'EXP');
        
        // --- 1. PHP MENYUSUN TEKS RAW ---
        $ESC = "\x1B";
        $LN = "\n";
        
        $rawText = $ESC . "@";
        
        // --- AKTIFKAN MODUL ELITE (12 CPI) ---
        $rawText .= $ESC . "k" . chr(0);
        $rawText .= "\x12";
        $rawText .= $ESC . "M";
        $rawText .= $ESC . "C" . chr(33);  // PANJANG
        $rawText .= $ESC . "Q" . chr(94); // LEBAR
        
        // --- 2. ISI KONTEN ---
        // Judul otomatis rata tengah - Trik Swedia untuk ¤
        $rawText .= $ESC . "R" . chr(5);
        $simbol = "$$"; 
        $judul = $simbol . " FAKTUR PENJUALAN " . $simbol;
        $rawText .= str_pad($judul, 94, " ", STR_PAD_BOTH) . $LN . $LN;
        $rawText .= $ESC . "R" . chr(0);
        
        // Header Info
        $tgl = "Tanggal      : " . ($header['sales_date'] ?? '-');
        $no_inv = "Nomor Faktur : " . ($header['invoice_no'] ?? '-');
        $gudang = "Gudang : " . ($header['warehouse'] ?? '-');
        
        $rawText .= str_pad($tgl, 44) . str_pad($no_inv, 25) . str_pad($gudang, 25, " ", STR_PAD_LEFT) . $LN;
        $rawText .= "Kepada       : " . ($header['buyer_code'] ?? '') . " " . ($header['buyer_name'] ?? '-') . $LN;
        
        // Tabel Header
        $rawText .= str_repeat("-", 94) . $LN;
        $th_nama   = str_pad("Nama Barang", 52);
        $th_jumlah = str_pad("Jumlah", 14);
        $th_harga  = str_pad("Harga", 14, " ", STR_PAD_LEFT);
        $th_total  = str_pad("Total", 14, " ", STR_PAD_LEFT);
        $rawText .= $th_nama . $th_jumlah . $th_harga . $th_total . $LN;
        $rawText .= str_repeat("-", 94) . $LN;
        
        // Looping Isi Barang
        if (!empty($items)) {
            foreach ($items as $item) {
                $subtotal = ($item['unit_price'] ?? 0) * ($item['item_qty'] ?? 0);
                
                $nama = str_pad(substr($item['item_name'], 0, 50), 52);
                $qty = str_pad(($item['item_qty'] ?? 0) . " " . ($item['item_uom'] ?? ''), 14);
                $harga = str_pad($isExp ? '-' : number_format($item['unit_price'], 0, ',', '.'), 14, " ", STR_PAD_LEFT);
                $total = str_pad($isExp ? '-' : number_format($subtotal, 0, ',', '.'), 14, " ", STR_PAD_LEFT);
                
                $rawText .= $nama . $qty . $harga . $total . $LN;
            }
        }
        
        $rawText .= str_repeat("-", 94) . $LN;
        
        $grandTotal = $isExp ? '-' : number_format($header['total'] ?? 0, 0, ',', '.');
        $rawText .= str_pad("GRAND TOTAL:", 80, " ", STR_PAD_LEFT) . str_pad($grandTotal, 14, " ", STR_PAD_LEFT) . $LN . $LN;
        
        $ttd_kiri  = "Yang Menyerahkan";
        $ttd_kanan = "Penerima";
        
        $garis_kiri  = "____________________"; 
        $garis_kanan = "____________________";
        
        // Baris Teks Nama & Tanda Tangan
        $baris_nama_ttd = str_pad($ttd_kiri, 47, " ", STR_PAD_BOTH) . str_pad($ttd_kanan, 47, " ", STR_PAD_BOTH);
        $rawText .= $baris_nama_ttd . $LN . $LN . $LN . $LN;
        $baris_garis_ttd = str_pad($garis_kiri, 47, " ", STR_PAD_BOTH) . str_pad($garis_kanan, 47, " ", STR_PAD_BOTH);
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
                    <p style="color: #6c757d; font-size: 14px;">Browser membutuhkan izin Anda untuk terhubung ke printer Epson LQ-310 pada sesi ini.</p>
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
                        document.getElementById('status').innerText = "Sedang mengirim data ke printer...";
                        document.getElementById('manual-print-area').style.display = 'none';

                        await device.open();
                        await device.selectConfiguration(1);
                        await device.claimInterface(0); 
                        
                        const dataBytes = hexToBytes(dataHex);
                        await device.transferOut(1, dataBytes);
                        
                        await device.releaseInterface(0);
                        await device.close();
                        
                        // Sukses! Langsung tutup tab ini
                        window.close();
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