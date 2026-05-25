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
        
        // --- AKTIFKAN MODUL ELITE (12 CPI) ---
        $rawText .= $ESC . "k" . chr(0);
        $rawText .= "\x12";
        $rawText .= $ESC . "M";
        $rawText .= $ESC . "C" . chr(33);  // PANJANG
        $rawText .= $ESC . "Q" . chr(94); // LEBAR
        
        // --- 2. ISI KONTEN ---
        
        // Judul otomatis rata tengah - ganti region
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
        
        // Baris Teks Nama
        $baris_nama_ttd = str_pad($ttd_kiri, 47, " ", STR_PAD_BOTH) . str_pad($ttd_kanan, 47, " ", STR_PAD_BOTH);
        $rawText .= $baris_nama_ttd . $LN . $LN . $LN . $LN;
        
        // Baris Garis Tanda Tangan
        $baris_garis_ttd = str_pad($garis_kiri, 47, " ", STR_PAD_BOTH) . str_pad($garis_kanan, 47, " ", STR_PAD_BOTH);
        $rawText .= $baris_garis_ttd . $LN;
        
        // --- 3. PELATUK FORM FEED ---
        $rawText .= "\x0C";
        
        // Encode ke Hex agar aman dikirim ke JavaScript browser
        $hexData = bin2hex($rawText);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Chromebook USB Print - <?= $header['invoice_no'] ?? 'INV' ?></title>
            <style>
                body { font-family: sans-serif; padding: 40px; text-align: center; background: #eef2f7; }
                .box { background: white; padding: 30px; border-radius: 8px; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
                button { padding: 12px 30px; font-size: 16px; font-weight: bold; cursor: pointer; border: none; border-radius: 4px; background: #007bff; color: white; }
                button:hover { background: #0056b3; }
                .note { margin-top: 15px; font-size: 12px; color: #6c757d; }
            </style>
        </head>
        <body>

            <div class="box">
                <h2>Cetak Dot Matrix via Chromebook USB</h2>
                <p>Tekan tombol di bawah untuk mengirim perintah RAW langsung ke printer.</p>
				<div style="text-align: left; margin-bottom: 20px;">
					<p style="font-size: 14px; font-weight: bold; margin-bottom: 5px; color: #333;">Verifikasi Kode Hex (Trik Swedia):</p>
					<textarea readonly style="width: 100%; height: 100px; font-family: monospace; font-size: 12px; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; background-color: #f8f9fa; resize: vertical; box-sizing: border-box;"><?= $hexData ?></textarea>
				</div>
                
                <button onclick="printDariChromebook()">MULAI CETAK</button>
                <button onclick="window.close()" style="background:#6c757d;">TUTUP</button>
                
                <p class="note">Kertas akan otomatis berhenti pas di ukuran 17 cm.</p>
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

                async function printDariChromebook() {
					if (!("usb" in navigator)) {
						alert("Browser Chrome di Chromebook Anda tidak mendukung WebUSB API!");
						return;
					}

					try {
						const device = await navigator.usb.requestDevice({ filters: [{ vendorId: 0x04b8 }] });
						await device.open();
						await device.selectConfiguration(1);
						await device.claimInterface(0); 
						const dataBytes = hexToBytes(dataHex);
						await device.transferOut(1, dataBytes);
						await device.releaseInterface(0);
						await device.close();
						alert("Faktur berhasil dikirim langsung ke Epson LQ-310!");
					} catch (error) {
						console.error(error);
						alert("Gagal koneksi ke LQ-310: " + error.message);
					}
				}
            </script>
        </body>
        </html>
        <?php
    }
}