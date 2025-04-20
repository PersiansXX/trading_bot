<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Eğer kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Veritabanı bağlantısı
require_once 'includes/db_connect.php';

$error = '';
$debug_info = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gerekli!';
    } else {
        // Veritabanı bağlantısını kontrol et
        if (!$conn) {
            $error = 'Veritabanı bağlantısı kurulamadı!';
            $debug_info = "Bağlantı hatası: " . mysqli_connect_error();
        } else {
            // Kullanıcıyı veritabanında ara
            try {
                $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
                if (!$stmt) {
                    $error = 'SQL sorgusu hazırlanamadı!';
                    $debug_info = "Hata: " . $conn->error;
                } else {
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        $debug_info = "Kullanıcı bulundu: " . $username . "<br>Hash: " . substr($user['password_hash'], 0, 15) . "...";
                        
                        // Şifreyi doğrula
                        if (password_verify($password, $user['password_hash'])) {
                            // Giriş başarılı, oturum bilgilerini kaydet
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            
                            // Son giriş tarihini güncelle
                            $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                            $update->bind_param("i", $user['id']);
                            $update->execute();
                            
                            // Ana sayfaya yönlendir
                            header('Location: index.php');
                            exit;
                        } else {
                            $error = 'Geçersiz şifre!';
                            $debug_info .= "<br>Şifre doğrulaması başarısız. Girilen şifre: " . $password;
                        }
                    } else {
                        $error = 'Kullanıcı bulunamadı!';
                        $debug_info = "Kullanıcı adı: " . $username . " veritabanında bulunamadı.";
                    }
                }
            } catch (Exception $e) {
                $error = 'Sorguda hata oluştu!';
                $debug_info = "Hata: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trading Bot - Giriş</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #172a74, #21a9af);
            min-height: 100vh;
        }
        .login-form {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            margin-top: 50px;
            padding: 30px;
        }
        .debug-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-dark">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-form mt-5 p-4 bg-light rounded shadow">
                    <h2 class="text-center mb-4">
                        <i class="fas fa-robot text-primary"></i> Trading Bot
                    </h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Kullanıcı Adı</label>
                            <input type="text" name="username" id="username" class="form-control" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   required autofocus>
                        </div>
                        
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Şifre</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="remember-me">
                                <label class="custom-control-label" for="remember-me">Beni Hatırla</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </button>
                        </div>
                    </form>
                    
                    <?php if (!empty($debug_info) && isset($_GET['debug'])): ?>
                    <div class="debug-box">
                        <h6>Hata Ayıklama Bilgileri:</h6>
                        <p><?php echo $debug_info; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <p class="text-muted">Trading Bot © 2025</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>