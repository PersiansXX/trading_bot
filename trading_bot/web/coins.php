<?php
session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanı ve API bağlantısı
require_once 'api/bot_api.php';
$bot_api = new BotAPI();

// Aktif coinleri al
$coins = $bot_api->getActiveCoins();

// Sayfa başlığı
$page_title = 'Aktif Coinler';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sol Menü -->
            <div class="col-md-2">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Ana İçerik -->
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-coins"></i> <?php echo $page_title; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Sembol</th>
                                        <th>Fiyat</th>
                                        <th>24s Değişim</th>
                                        <th>Son Sinyal</th>
                                        <th>İndikatörler</th>
                                        <th>Son Güncelleme</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($coins)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Aktif coin bulunamadı</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($coins as $coin): ?>
                                            <tr>
                                                <td><strong><?php echo $coin['symbol']; ?></strong></td>
                                                <td><?php echo number_format($coin['price'], 8); ?></td>
                                                <td class="<?php echo $coin['change_24h'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo number_format($coin['change_24h'], 2); ?>%
                                                </td>
                                                <td>
                                                    <?php if ($coin['signal'] == 'BUY'): ?>
                                                        <span class="badge badge-success">ALIŞ</span>
                                                    <?php elseif ($coin['signal'] == 'SELL'): ?>
                                                        <span class="badge badge-danger">SATIŞ</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">BEKLİYOR</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (isset($coin['indicators']['rsi'])) {
                                                        echo 'RSI: ' . number_format($coin['indicators']['rsi'], 2);
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($coin['last_updated'])); ?></td>
                                                <td>
                                                    <a href="coin_detail.php?symbol=<?php echo $coin['symbol']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-chart-line"></i> Detay
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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