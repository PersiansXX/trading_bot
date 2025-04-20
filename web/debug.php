<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Versiyon: " . phpversion() . "<br>";
echo "Sunucu Bilgileri: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Veritabanı bağlantısını deneyelim
echo "<h3>Veritabanı Bağlantısı Test Ediliyor...</h3>";
try {
    require_once 'includes/db_connect.php';
    echo "Veritabanı bağlantısı başarılı!<br>";
    
    $result = $conn->query("SHOW TABLES");
    echo "<b>Tablolar:</b><br>";
    while($row = $result->fetch_array()) {
        echo $row[0] . "<br>";
    }
} catch (Exception $e) {
    echo "Veritabanı hatası: " . $e->getMessage();
}

// API ve diğer sınıfları deneyelim
echo "<h3>Bot API Test Ediliyor...</h3>";
try {
    if (file_exists('api/bot_api.php')) {
        echo "Bot API dosyası mevcut.<br>";
        require_once 'api/bot_api.php';
        
        echo "Bot API sınıfı yükleniyor...<br>";
        $bot_api = new BotAPI();
        echo "Bot API sınıfı başarıyla oluşturuldu.<br>";
        
        echo "<b>Bot Durumu:</b><br>";
        print_r($bot_api->getStatus());
    } else {
        echo "Bot API dosyası bulunamadı!";
    }
} catch (Exception $e) {
    echo "Bot API hatası: " . $e->getMessage();
}
?>