<?php
session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanı bağlantısı
require_once 'includes/db_connect.php';

// Bot API'ye bağlan
require_once 'api/bot_api.php';
$bot_api = new BotAPI();

// Bot durumunu al
$bot_status = $bot_api->getStatus();

// Sayfa başlığı
$page_title = 'Bot Kontrol Paneli';
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
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> Bot Durumu</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h3 class="<?php echo $bot_status['running'] ? 'text-success' : 'text-danger'; ?>">
                                                    <i class="fas <?php echo $bot_status['running'] ? 'fa-play-circle' : 'fa-stop-circle'; ?>"></i>
                                                </h3>
                                                <h6>Durum: <?php echo $bot_status['running'] ? 'Çalışıyor' : 'Durdu'; ?></h6>
                                                <?php if ($bot_status['running']): ?>
                                                    <button class="btn btn-sm btn-danger stop-bot">Durdur</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-success start-bot">Başlat</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h3 class="text-info"><?php echo count($bot_api->getActiveCoins()); ?></h3>
                                                <h6>Aktif Coinler</h6>
                                                <a href="coins.php" class="btn btn-sm btn-info">Detaylar</a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h3 class="text-success"><?php echo $bot_api->getTodayStats()['total_trades']; ?></h3>
                                                <h6>Bugünkü İşlemler</h6>
                                                <a href="trades.php" class="btn btn-sm btn-success">Detaylar</a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <?php $pnl = $bot_api->getTodayStats()['profit_loss']; ?>
                                                <h3 class="<?php echo $pnl >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo number_format($pnl, 2); ?>
                                                </h3>
                                                <h6>Günlük Kar/Zarar</h6>
                                                <a href="reports.php" class="btn btn-sm btn-warning">Raporlar</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bot Log Bölümü -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-list"></i> Bot Logları</h5>
                            </div>
                            <div class="card-body">
                                <div class="log-container p-2 bg-dark text-light" style="height: 300px; overflow-y: auto; font-family: monospace;">
                                    <?php
                                    // Bot log dosyasını oku ve göster
                                    $log_file = "../bot.log";
                                    if (file_exists($log_file)) {
                                        $logs = file($log_file);
                                        $logs = array_slice($logs, max(0, count($logs) - 100)); // Son 100 log
                                        foreach ($logs as $log) {
                                            // Log tipine göre renklendir
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
                                    ?>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <button class="btn btn-sm btn-secondary refresh-logs">
                                    <i class="fas fa-sync-alt"></i> Logları Yenile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Çalışan Stratejiler -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Aktif Stratejiler</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    // Bot ayarlarından stratejileri al
                                    $strategies = $bot_api->getActiveStrategies();
                                    foreach ($strategies as $strategy => $details):
                                    ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-header">
                                                <div class="custom-control custom-switch float-right">
                                                    <input type="checkbox" class="custom-control-input strategy-toggle" 
                                                           id="strategy-<?php echo $strategy; ?>" 
                                                           data-strategy="<?php echo $strategy; ?>" 
                                                           <?php echo $details['enabled'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="strategy-<?php echo $strategy; ?>"></label>
                                                </div>
                                                <h6 class="mb-0"><?php echo $details['name']; ?></h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text small"><?php echo $details['description']; ?></p>
                                                <div class="text-center">
                                                    <a href="strategy_settings.php?name=<?php echo $strategy; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-cog"></i> Ayarlar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>