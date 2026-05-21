<?php

function loadEnv($filename) {
    $path = __DIR__ . '/' . $filename;

    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);

        if (empty($line) || strpos($line, '#') === 0) continue;

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            
            $name = trim($name);
            $value = trim($value);
            
            if (preg_match('/^"([^"]*)"$/', $value, $matches) || preg_match('/^\'([^\']*)\'$/', $value, $matches)) $value = $matches[1];

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            
            if (!defined($name)) define($name, $value);
        }
    }
    return true;
}

loadEnv('.env');