<?php
session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    echo '<div class="text-danger">Oturum zaman aşımına uğradı!</div>';
    exit;
}

// Bot log dosyası
$log_file = __DIR__ . '/../../bot.log';
$lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;

if (file_exists($log_file)) {
    // Son N satırı oku
    $logs = tailCustom($log_file, $lines);
    
    // Log tipine göre renklendir
    foreach ($logs as $log) {
        if (strpos($log, 'ERROR') !== false) {
            echo '<div class="text-danger">' . htmlspecialchars($log) . '</div>';
        } elseif (strpos($log, 'WARNING') !== false) {
            echo '<div class="text-warning">' . htmlspecialchars($log) . '</div>';
        } else {
            echo '<div>' . htmlspecialchars($log) . '</div>';
        }
    }
} else {
    echo '<div class="text-muted">Log dosyası bulunamadı.</div>';
}

/**
 * Dosyadan son N satırı okur
 */
function tailCustom($filepath, $lines = 100, $adaptive = true) {
    // Dosya açılamıyorsa boş dizi döndür
    if (!$f = @fopen($filepath, "rb")) {
        return [];
    }
    
    // $buffer için maksimum boyut tanımla
    $buffer = ($adaptive) ? 4096 : 1024;
    
    // Dosya sonuna git
    fseek($f, -1, SEEK_END);
    
    // Sondan başa doğru oku
    $output = [];
    $chunk = "";
    
    while (ftell($f) > 0 && count($output) < $lines) {
        $seek = min(ftell($f), $buffer);
        fseek($f, -$seek, SEEK_CUR);
        $chunk = fread($f, $seek);
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        
        // Satırları ayır
        $lines_in_chunk = preg_split('/\n|\r\n?/', $chunk);
        
        // İlk parça önceki iterasyondan kalanla birleştir
        if (isset($output[0])) {
            $output[0] = $lines_in_chunk[count($lines_in_chunk) - 1] . $output[0];
            $lines_in_chunk = array_slice($lines_in_chunk, 0, -1);
        }
        
        // Parçaları çıkış dizisinin başına ekle
        $output = array_merge($lines_in_chunk, $output);
        
        // Gereken satır sayısını aştıysak kırp
        if (count($output) > $lines) {
            $output = array_slice($output, -$lines);
        }
    }
    
    fclose($f);
    
    // Boş satırları kaldır ve array_filter döndür
    return array_filter($output);
}