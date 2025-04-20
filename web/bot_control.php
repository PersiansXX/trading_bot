<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$result = ['success' => false, 'message' => ''];

switch ($action) {
    case 'start':
        // Apache kullanıcısıyla doğrudan Python komutunu çalıştır
        $cmd = "cd /var/www/html/bot && sudo -u apache /usr/bin/python3.8 trading_bot.py > /var/www/html/bot.log 2> /var/www/html/bot_error.log & echo $!";
        $pid = exec($cmd, $output, $return_var);
        
        // Çıktı ve hata logla
        file_put_contents("/var/www/html/bot_error.log", date('Y-m-d H:i:s') . " - Bot başlatma komutu çalıştırıldı. PID: $pid\n", FILE_APPEND);
        
        if ($pid > 0) {
            // PID'i dosyaya kaydet
            file_put_contents("/var/www/html/bot/bot.pid", $pid);
            $result = [
                'success' => true, 
                'message' => 'Bot başlatıldı (PID: ' . $pid . ')', 
                'running' => true,
                'pid' => $pid
            ];
        } else {
            $result = [
                'success' => false, 
                'message' => 'Bot başlatılamadı! Hata kodu: ' . $return_var,
                'running' => false
            ];
        }
        break;

    case 'stop':
        // PID dosyasını kontrol et
        if (file_exists("/var/www/html/bot/bot.pid")) {
            $pid = file_get_contents("/var/www/html/bot/bot.pid");
            if ($pid > 0) {
                // Botu durdur (Apache kullanıcısı olarak)
                exec("sudo -u apache kill $pid 2>> /var/www/html/bot_error.log", $output, $return_var);
                unlink("/var/www/html/bot/bot.pid"); // PID dosyasını sil
                
                $result = [
                    'success' => true,
                    'message' => 'Bot durduruldu',
                    'running' => false
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Geçerli PID bulunamadı',
                    'running' => false
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => 'Bot zaten çalışmıyor',
                'running' => false
            ];
        }
        break;

    case 'status':
        // Bot durumunu kontrol et
        $running = false;
        $pid = 0;
        
        if (file_exists("/var/www/html/bot/bot.pid")) {
            $pid = trim(file_get_contents("/var/www/html/bot/bot.pid"));
            if ($pid > 0) {
                // PID aktif mi kontrol et
                exec("ps -p $pid", $output, $return_var);
                $running = ($return_var === 0);
            }
        }
        
        $result = [
            'success' => true,
            'message' => $running ? 'Bot çalışıyor (PID: ' . $pid . ')' : 'Bot çalışmıyor',
            'running' => $running,
            'pid' => $pid
        ];
        break;

    default:
        $result = [
            'success' => false,
            'message' => 'Geçersiz işlem',
        ];
}

// JSON olarak yanıt döndür
header('Content-Type: application/json');
echo json_encode($result);
exit;
?>