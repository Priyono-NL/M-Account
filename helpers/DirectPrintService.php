<?php

class DirectPrintService {
    /**
     * Mengirim data RAW ke printer dengan jalur super cepat (LPT1 -> Stream -> CMD)
     */
    public static function sendToPrinter($rawText, $printerShareName = 'EPSON_LQ310', $serverIp = '127.0.0.1') {
        $printerPath = "\\\\{$serverIp}\\{$printerShareName}";
        // ⚡ OPTIMASI 1: Coba Tembak Langsung ke Port Virtual LPT1 (Instan Tanpa Delay SMB)
        exec("net use LPT1: " . escapeshellarg($printerPath) . " /persistent:yes 2>nul");
        $lptPorts = ["LPT1", "\\\\.\\LPT1"]; 
        
        foreach ($lptPorts as $port) {
            $fp = @fopen($port, "wb");
            if ($fp) {
                fwrite($fp, $rawText);
                fclose($fp);
                return ['status' => true, 'message' => "Berhasil dicetak instan via port {$port}."];
            }
        }

        // ⚡ OPTIMASI 2: Jika LPT1 tidak terikat, gunakan Stream UNC Path        
        $fp = @fopen($printerPath, "wb");
        if ($fp) {
            fwrite($fp, $rawText);
            fclose($fp);
            return ['status' => true, 'message' => 'Berhasil dicetak via Network Stream.'];
        }

        // ⚡ OPTIMASI 3: Fallback ke CMD Copy jika kedua cara di atas diblokir izin Windows
        $tempFile = sys_get_temp_dir() . "\\p_" . microtime(true) . ".tmp";
        file_put_contents($tempFile, $rawText);

        $cmd = "cmd /c copy /b " . escapeshellarg($tempFile) . " " . escapeshellarg($printerPath);
        exec($cmd, $output, $returnCode);

        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }

        if ($returnCode === 0) {
            return ['status' => true, 'message' => 'Berhasil dicetak via CMD Spooler.'];
        } else {
            return ['status' => false, 'message' => 'Gagal terhubung ke printer. Pastikan printer sudah di-share.'];
        }
    }
}