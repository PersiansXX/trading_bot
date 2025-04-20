<?php
// Veritabanı bağlantısı
$db_host = "localhost";
$db_user = "root";  // Veritabanı kullanıcı adınız
$db_pass = "Efsane44.";      // Veritabanı şifreniz
$db_name = "trading_bot_db";

// Yeni şifre
$new_password = "abuzer";
$username = "admin";

// MySQLi bağlantısı oluşturma
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Bağlantı kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Şifreyi hashleme
$password_hash = password_hash($new_password, PASSWORD_BCRYPT);

// Hash değerini ekrana yazdır
echo "Oluşturulan Hash: " . $password_hash . "<br>";

// Kullanıcının şifresini güncelle
$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
$stmt->bind_param("ss", $password_hash, $username);

if ($stmt->execute()) {
    echo "Şifre başarıyla güncellendi! Artık 'admin' kullanıcısı için 'abuzer' şifresi ile giriş yapabilirsiniz.";
} else {
    echo "Hata: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>