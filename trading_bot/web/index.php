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

// Aktif coinleri al
$active_coins = $bot_api->getActiveCoins();

// Son işlemleri al
$recent_trades = $bot_api->getRecentTrades();

// Sayfa başlığı
$page_title = 'Trading Bot Dashboard';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <!-- Bot Durum Kartı -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Bot Durumu</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span>Durum:</span>
                                    <span class="<?php echo $bot_status['running'] ? 'text-success' : 'text-danger'; ?>">
                                        <i class="fas <?php echo $bot_status['running'] ? 'fa-play-circle' : 'fa-stop-circle'; ?>"></i>
                                        <?php echo $bot_status['running'] ? 'Çalışıyor' : 'Durdu'; ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Aktif Coinler:</span>
                                    <span class="text-primary"><?php echo count($active_coins); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Bakiye:</span>
                                    <span class="text-success"><?php echo number_format($bot_status['base_currency_balance'], 2); ?> USDT</span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Son Güncelleme:</span>
                                    <span class="text-muted"><?php echo $bot_status['last_update']; ?></span>
                                </div>
                                <div class="mt-3">
                                    <?php if ($bot_status['running']): ?>
                                        <button class="btn btn-danger btn-sm btn-block stop-bot">
                                            <i class="fas fa-stop-circle"></i> Botu Durdur
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-success btn-sm btn-block start-bot">
                                            <i class="fas fa-play-circle"></i> Botu Başlat
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- İşlem Özeti Kartı -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">İşlem Özeti (Bugün)</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $today_stats = $bot_api->getTodayStats();
                                ?>
                                <div class="d-flex justify-content-between">
                                    <span>Toplam İşlem:</span>
                                    <span><?php echo $today_stats['total_trades']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Alımlar:</span>
                                    <span class="text-success"><?php echo $today_stats['buy_trades']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Satışlar:</span>
                                    <span class="text-danger"><?php echo $today_stats['sell_trades']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Kar/Zarar:</span>
                                    <span class="<?php echo $today_stats['profit_loss'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo number_format($today_stats['profit_loss'], 2); ?> USDT
                                    </span>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="reports.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-chart-line"></i> Tüm Raporları Gör
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Piyasa Özeti Kartı -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Piyasa Özeti</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $market_overview = $bot_api->getMarketOverview();
                                ?>
                                <div class="d-flex justify-content-between">
                                    <span>BTC Dominans:</span>
                                    <span><?php echo number_format($market_overview['btc_dominance'], 2); ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Toplam Hacim (24s):</span>
                                    <span>$<?php echo number_format($market_overview['total_volume'], 0); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>En Yüksek Getiri:</span>
                                    <span class="text-success">
                                        <?php echo $market_overview['best_performer']['symbol']; ?> 
                                        (<?php echo number_format($market_overview['best_performer']['change'], 2); ?>%)
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>En Düşük Getiri:</span>
                                    <span class="text-danger">
                                        <?php echo $market_overview['worst_performer']['symbol']; ?> 
                                        (<?php echo number_format($market_overview['worst_performer']['change'], 2); ?>%)
                                    </span>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="market.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-globe"></i> Piyasa Detayları
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aktif Coinler ve Grafikler -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0">Aktif Takip Edilen Coinler</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Symbol</th>
                                                <th>Fiyat</th>
                                                <th>24s Değişim</th>
                                                <th>RSI</th>
                                                <th>Sinyal</th>
                                                <th>Son Analiz</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($active_coins as $coin): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $coin['symbol']; ?></strong>
                                                    </td>
                                                    <td><?php echo number_format($coin['price'], 8); ?></td>
                                                    <td class="<?php echo $coin['change_24h'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo number_format($coin['change_24h'], 2); ?>%
                                                    </td>
                                                    <td>
                                                        <?php echo number_format($coin['indicators']['rsi'], 2); ?>
                                                        <div class="progress" style="height: 4px;">
                                                            <div class="progress-bar 
                                                                <?php 
                                                                    if ($coin['indicators']['rsi'] < 30) echo 'bg-danger';
                                                                    else if ($coin['indicators']['rsi'] > 70) echo 'bg-success';
                                                                    else echo 'bg-info';
                                                                ?>" 
                                                                style="width: <?php echo $coin['indicators']['rsi']; ?>%">
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($coin['signal'] == 'BUY'): ?>
                                                            <span class="badge badge-success">ALIM</span>
                                                        <?php elseif ($coin['signal'] == 'SELL'): ?>
                                                            <span class="badge badge-danger">SATIM</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">BEKLE</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $coin['last_updated']; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-info view-chart" data-symbol="<?php echo $coin['symbol']; ?>">
                                                            <i class="fas fa-chart-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Coin Grafikleri -->
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0" id="chart-title">Coin Grafikleri</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-light timeframe-btn" data-timeframe="1h">1s</button>
                                    <button class="btn btn-sm btn-outline-light timeframe-btn" data-timeframe="4h">4s</button>
                                    <button class="btn btn-sm btn-outline-light timeframe-btn active" data-timeframe="1d">1g</button>
                                    <button class="btn btn-sm btn-outline-light timeframe-btn" data-timeframe="1w">1h</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height:400px;">
                                    <canvas id="coinChart"></canvas>
                                </div>
                                <div class="text-center mt-3 chart-message">
                                    <p class="text-muted">Grafik görmek için yukarıdaki tabloda bir coin seçin</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Son İşlemler -->
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">Son İşlemler</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Coin</th>
                                                <th>İşlem</th>
                                                <th>Fiyat</th>
                                                <th>Miktar</th>
                                                <th>Toplam</th>
                                                <th>Sebep</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_trades as $trade): ?>
                                                <tr>
                                                    <td><?php echo $trade['timestamp']; ?></td>
                                                    <td><strong><?php echo $trade['symbol']; ?></strong></td>
                                                    <td>
                                                        <?php if ($trade['type'] == 'BUY'): ?>
                                                            <span class="badge badge-success">ALIM</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">SATIM</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo number_format($trade['price'], 8); ?></td>
                                                    <td><?php echo number_format($trade['amount'], 8); ?></td>
                                                    <td><?php echo number_format($trade['price'] * $trade['amount'], 2); ?> USDT</td>
                                                    <td><?php echo $trade['reason']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer text-center">
                                    <a href="trades.php" class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-history"></i> Tüm İşlemleri Gör
                                    </a>
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
    
    <script>
        // Grafik oluşturma
        let coinChart;
        const ctx = document.getElementById('coinChart').getContext('2d');
        
        // Bot başlatma/durdurma
        $('.start-bot').click(function() {
            $.post('api/control.php', {action: 'start'}, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Bot başlatılamadı: ' + response.message);
                }
            }, 'json');
        });
        
        $('.stop-bot').click(function() {
            $.post('api/control.php', {action: 'stop'}, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Bot durdurulamadı: ' + response.message);
                }
            }, 'json');
        });
        
        // Coin grafiği gösterme
        $('.view-chart').click(function() {
            const symbol = $(this).data('symbol');
            const timeframe = $('.timeframe-btn.active').data('timeframe') || '1d';
            
            $('#chart-title').text(symbol + ' Grafiği');
            $('.chart-message').hide();
            
            // API'den veri al
            $.get('api/chart_data.php', {symbol: symbol, timeframe: timeframe}, function(data) {
                updateChart(data, symbol);
            }, 'json');
        });
        
        // Zaman dilimi değiştirme
        $('.timeframe-btn').click(function() {
            $('.timeframe-btn').removeClass('active');
            $(this).addClass('active');
            
            const symbol = $('#chart-title').text().replace(' Grafiği', '');
            const timeframe = $(this).data('timeframe');
            
            if (symbol && symbol !== 'Coin Grafikleri') {
                $.get('api/chart_data.php', {symbol: symbol, timeframe: timeframe}, function(data) {
                    updateChart(data, symbol);
                }, 'json');
            }
        });
        
        function updateChart(data, symbol) {
            // Eğer zaten bir grafik varsa yok et
            if (coinChart) {
                coinChart.destroy();
            }
            
            // Yeni grafik oluştur
            coinChart = new Chart(ctx, {
                type: 'candlestick',
                data: {
                    datasets: [{
                        label: symbol + ' Fiyat',
                        data: data.candles,
                        color: {
                            up: 'rgba(75, 192, 192, 1)',
                            down: 'rgba(255, 99, 132, 1)',
                            unchanged: 'rgba(90, 90, 90, 1)',
                        }
                    }]
                },
                options: {
                    responsive: