<?php
// Basit hata yakalama
ini_set('display_errors', 1);
error_reporting(E_ALL);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$response = ['success' => false, 'message' => '', 'debug' => []];

// Debug çıktısı için
$debug = [];

// DOĞRU Python yolu - sizin sisteminizde olduğu gibi
$python_path = '/usr/local/bin/python3.8';

try {
    switch($action) {
        case 'start':
            // Doğrudan komutu çalıştır - doğru Python yoluyla
            $command = "cd /var/www/html/bot && $python_path trading_bot.py > /var/www/html/bot.log 2>> /var/www/html/bot_error.log & echo $!";
            $debug[] = "Çalıştırılan komut: " . $command;
            
            exec($command, $output, $return_var);
            $debug[] = "Komut çıktısı: " . print_r($output, true);
            $debug[] = "Dönüş kodu: " . $return_var;
            
            if ($return_var === 0 && !empty($output)) {
                $pid = end($output);
                file_put_contents('/var/www/html/bot/bot.pid', $pid);
                $response = [
                    'success' => true,
                    'message' => "Bot başlatıldı (PID: $pid)",
                    'debug' => $debug
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => "Bot başlatılamadı: " . $return_var,
                    'debug' => $debug
                ];
            }
            break;
            
        case 'stop':
            // Doğrudan komutu çalıştır
            if (file_exists('/var/www/html/bot/bot.pid')) {
                $pid = file_get_contents('/var/www/html/bot/bot.pid');
                $command = "kill $pid 2>> /var/www/html/bot_error.log";
                $debug[] = "Çalıştırılan komut: " . $command;
                
                exec($command, $output, $return_var);
                $debug[] = "Komut çıktısı: " . print_r($output, true);
                $debug[] = "Dönüş kodu: " . $return_var;
                
                unlink('/var/www/html/bot/bot.pid');
                $response = [
                    'success' => true,
                    'message' => "Bot durduruldu (PID: $pid)",
                    'debug' => $debug
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => "Bot PID dosyası bulunamadı",
                    'debug' => $debug
                ];
            }
            break;
            
        case 'status':
            if (file_exists('/var/www/html/bot/bot.pid')) {
                $pid = trim(file_get_contents('/var/www/html/bot/bot.pid'));
                if (!empty($pid)) {
                    $command = "ps -p $pid";
                    $debug[] = "Çalıştırılan komut: " . $command;
                    
                    exec($command, $output, $return_var);
                    $debug[] = "Komut çıktısı: " . print_r($output, true);
                    $debug[] = "Dönüş kodu: " . $return_var;
                    
                    $running = ($return_var === 0);
                    $response = [
                        'success' => true,
                        'message' => $running ? "Bot çalışıyor (PID: $pid)" : "Bot çalışmıyor",
                        'running' => $running,
                        'debug' => $debug
                    ];
                } else {
                    $response = [
                        'success' => true,
                        'message' => "Bot çalışmıyor (PID dosyası boş)",
                        'running' => false,
                        'debug' => $debug
                    ];
                }
            } else {
                $response = [
                    'success' => true,
                    'message' => "Bot çalışmıyor",
                    'running' => false,
                    'debug' => $debug
                ];
            }
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => "Geçersiz işlem: $action",
                'debug' => $debug
            ];
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => "Hata: " . $e->getMessage(),
        'debug' => $debug
    ];
}

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
?>