<?php
require_once __DIR__ . '/formatters/InvoiceFormatter.php';
use Views\Formatters\InvoiceFormatter;

class InvoiceView {
    public static function render($header, $items) {
        
        // Build RAW ESC/POS string via Formatter
        $rawText = InvoiceFormatter::buildPassKeluarRaw($header, $items);
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
                        
                        const interfaceObj = device.configuration.interfaces[0];
                        const alternate = interfaceObj.alternates[0];
                        const endpoint = alternate.endpoints.find(e => e.direction === 'out');
                        
                        if (!endpoint) {
                            throw new Error("Endpoint OUT tidak ditemukan pada printer ini.");
                        }
                        const endpointNumber = endpoint.endpointNumber;

                        const dataBytes = hexToBytes(dataHex);
                        
                        // Chunking per 64 byte
                        const chunkSize = 64;
                        for (let i = 0; i < dataBytes.length; i += chunkSize) {
                            const chunk = dataBytes.subarray(i, i + chunkSize);
                            await device.transferOut(endpointNumber, chunk);
                        }
                        
                        await device.releaseInterface(0);
                        await device.close();
                        
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