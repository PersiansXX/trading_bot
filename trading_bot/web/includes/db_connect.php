<?php
// Veritabanı bağlantısı
$db_host = "localhost";
$db_user = "root";
$db_pass = "Efsane44."; // Veritabanı şifrenizi buraya girin
$db_name = "trading_bot_db";

// MySQLi bağlantısı oluşturma
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Bağlantı kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// UTF-8 karakter seti
$conn->set_charset("utf8mb4");