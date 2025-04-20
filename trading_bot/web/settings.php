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

// Mevcut ayarları al
$settings = $bot_api->getSettings();

// POST işlemi kontrolü
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formdan gelen verileri doğrula ve güncelle
    try {
        $updated_settings = [
            'exchange' => $_POST['exchange'],
            'base_currency' => $_POST['base_currency'],
            'min_volume' => (float) $_POST['min_volume'],
            'max_coins' => (int) $_POST['max_coins'],
            'min_trade_amount' => (float) $_POST['min_trade_amount'],
            'max_trade_amount' => (float) $_POST['max_trade_amount'],
            'position_size' => (float) $_POST['position_size'],
            'api_delay' => (float) $_POST['api_delay'],
            'scan_interval' => (int) $_POST['scan_interval'],
            'indicators' => [
                'bollinger_bands' => [
                    'enabled' => isset($_POST['bb_enabled']),
                    'window' => (int) $_POST['bb_window'],
                    'num_std' => (float) $_POST['bb_num_std']
                ],
                'rsi' => [
                    'enabled' => isset($_POST['rsi_enabled']),
                    'window' => (int) $_POST['rsi_window']
                ],
                'macd' => [
                    'enabled' => isset($_POST['macd_enabled']),
                    'fast_period' => (int) $_POST['macd_fast'],
                    'slow_period' => (int) $_POST['macd_slow'],
                    'signal_period' => (int) $_POST['macd_signal']
                ],
                'moving_average' => [
                    'enabled' => isset($_POST['ma_enabled']),
                    'short_window' => (int) $_POST['ma_short'],
                    'long_window' => (int) $_POST['ma_long']
                ]
            ],
            'strategies' => [
                'short_term' => [
                    'enabled' => isset($_POST['short_term_enabled'])
                ],
                'trend_following' => [
                    'enabled' => isset($_POST['trend_following_enabled'])
                ],
                'breakout' => [
                    'enabled' => isset($_POST['breakout_enabled'])
                ]
            ]
        ];
        
        // Ayarları güncelle
        if ($bot_api->updateSettings($updated_settings)) {
            $message = 'Ayarlar başarıyla güncellendi!';
            $message_type = 'success';
            $settings = $bot_api->getSettings(); // Yeniden yükle
        } else {
            $message = 'Ayarlar güncellenirken bir hata oluştu!';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Hata: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Sayfa başlığı
$page_title = 'Bot Ayarları';
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
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Bot Ayarları</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">
                                        Genel Ayarlar
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="indicators-tab" data-toggle="tab" href="#indicators" role="tab">
                                        İndikatör Ayarları
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="strategies-tab" data-toggle="tab" href="#strategies" role="tab">
                                        Strateji Ayarları
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="api-tab" data-toggle="tab" href="#api" role="tab">
                                        API Ayarları
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="settingsTabContent">
                                <!-- Genel Ayarlar -->
                                <div class="tab-pane fade show active" id="general" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="exchange">Borsa</label>
                                                <select name="exchange" id="exchange" class="form-control">
                                                    <option value="binance" <?php echo $settings['exchange'] == 'binance' ? 'selected' : ''; ?>>Binance</option>
                                                    <option value="kucoin" <?php echo $settings['exchange'] == 'kucoin' ? 'selected' : ''; ?>>KuCoin</option>
                                                    <option value="ftx" <?php echo $settings['exchange'] == 'ftx' ? 'selected' : ''; ?>>FTX</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="base_currency">Baz Para Birimi</label>
                                                <select name="base_currency" id="base_currency" class="form-control">
                                                    <option value="USDT" <?php echo $settings['base_currency'] == 'USDT' ? 'selected' : ''; ?>>USDT</option>
                                                    <option value="BTC" <?php echo $settings['base_currency'] == 'BTC' ? 'selected' : ''; ?>>BTC</option>
                                                    <option value="ETH" <?php echo $settings['base_currency'] == 'ETH' ? 'selected' : ''; ?>>ETH</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="min_volume">Minimum Hacim</label>
                                                <input type="number" step="0.01" name="min_volume" id="min_volume" class="form-control" 
                                                       value="<?php echo $settings['min_volume']; ?>">
                                                <small class="form-text text-muted">Coin seçimi için minimum günlük işlem hacmi</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="max_coins">Maksimum Coin Sayısı</label>
                                                <input type="number" name="max_coins" id="max_coins" class="form-control" 
                                                       value="<?php echo $settings['max_coins']; ?>">
                                                <small class="form-text text-muted">Aynı anda takip edilecek maksimum coin sayısı</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="min_trade_amount">Minimum İşlem Tutarı</label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" name="min_trade_amount" id="min_trade_amount" class="form-control" 
                                                           value="<?php echo $settings['min_trade_amount']; ?>">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"><?php echo $settings['base_currency']; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="max_trade_amount">Maksimum İşlem Tutarı</label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" name="max_trade_amount" id="max_trade_amount" class="form-control" 
                                                           value="<?php echo $settings['max_trade_amount']; ?>">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"><?php echo $settings['base_currency']; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="position_size">Pozisyon Büyüklüğü</label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" name="position_size" id="position_size" class="form-control" 
                                                           value="<?php echo $settings['position_size']; ?>" min="0.01" max="1">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Bakiyenin yüzde kaçını kullanacağı (0.01-1)</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="api_delay">API Gecikme Süresi</label>
                                                <div class="input-group">
                                                    <input type="number" step="0.1" name="api_delay" id="api_delay" class="form-control" 
                                                           value="<?php echo $settings['api_delay']; ?>">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">saniye</span>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">API istekleri arasındaki bekleme süresi</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="scan_interval">Tarama Aralığı</label>
                                                <div class="input-group">
                                                    <input type="number" name="scan_interval" id="scan_interval" class="form-control" 
                                                           value="<?php echo $settings['scan_interval']; ?>">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">saniye</span>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Tüm coinleri tarama aralığı</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- İndikatör Ayarları -->
                                <div class="tab-pane fade" id="indicators" role="tabpanel">
                                    <!-- Bollinger Bands -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <div class="custom-control custom-switch float-right">
                                                <input type="checkbox" class="custom-control-input" id="bb_enabled" name="bb_enabled"
                                                       <?php echo $settings['indicators']['bollinger_bands']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="bb_enabled"></label>
                                            </div>
                                            <h5 class="mb-0">Bollinger Bands</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="bb_window">Periyot</label>
                                                        <input type="number" name="bb_window" id="bb_window" class="form-control" 
                                                               value="<?php echo $settings['indicators']['bollinger_bands']['window']; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="bb_num_std">Standart Sapma</label>
                                                        <input type="number" step="0.1" name="bb_num_std" id="bb_num_std" class="form-control" 
                                                               value="<?php echo $settings['indicators']['bollinger_bands']['num_std']; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- RSI -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <div class="custom-control custom-switch float-right">
                                                <input type="checkbox" class="custom-control-input" id="rsi_enabled" name="rsi_enabled"
                                                       <?php echo $settings['indicators']['rsi']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="rsi_enabled"></label>
                                            </div>
                                            <h5 class="mb-0">RSI (Göreceli Güç İndeksi)</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="rsi_window">Periyot</label>
                                                <input type="number" name="rsi_window" id="rsi_window" class="form-control" 
                                                       value="<?php echo $settings['indicators']['rsi']['window']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- MACD -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <div class="custom-control custom-switch float-right">
                                                <input type="checkbox" class="custom-control-input" id="macd_enabled" name="macd_enabled"
                                                       <?php echo $settings['indicators']['macd']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="macd_enabled"></label>
                                            </div>
                                            <h5 class="mb-0">MACD</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="macd_fast">Hızlı EMA</label>
                                                        <input type="number" name="macd_fast" id="macd_fast" class="form-control" 
                                                               value="<?php echo $settings['indicators']['macd']['fast_period']; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="macd_slow">Yavaş EMA</label>
                                                        <input type="number" name="macd_slow" id="macd_slow" class="form-control" 
                                                               value="<?php echo $settings['indicators']['macd']['slow_period']; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="macd_signal">Sinyal Periyodu</label>
                                                        <input type="number" name="macd_signal" id="macd_signal" class="form-control" 
                                                               value="<?php echo $settings['indicators']['macd']['signal_period']; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Hareketli Ortalama -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <div class="custom-control custom-switch float-right">
                                                <input type="checkbox" class="custom-control-input" id="ma_enabled" name="ma_enabled"
                                                       <?php echo $settings['indicators']['moving_average']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="ma_enabled"></label>
                                            </div>
                                            <h5 class="mb-0">Hareketli Ortalamalar</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="ma_short">Kısa Periyot</label>
                                                        <input type="number" name="ma_short" id="ma_short" class="form-control" 
                                                               value="<?php echo $settings['indicators']['moving_average']['short_window']; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="ma_long">Uzun Periyot</label>
                                                        <input type="number" name="ma_long" id="ma_long" class="form-control" 
                                                               value="<?php echo $settings['indicators']['moving_average']['long_window']; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Strateji Ayarları -->
                                <div class="tab-pane fade" id="strategies" role="tabpanel">
                                    <!-- Kısa Vadeli Strateji -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <div class="custom-control custom-switch float-right">
                                                <input type="checkbox" class="custom-control-input" id="short_term_enabled" name="short_term_enabled"
                                                       <?php echo $settings['strategies']['short_term']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="short_term_enabled"></label>
                                            </div>
                                            <h5 class="mb-0">Kısa Vadeli Strateji</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">RSI ve Bollinger Bands'e dayalı kısa vadeli alım-satım stratejisi.</p>
                                            <p class="small text-muted">
                                                Bu strateji aşırı alım/satım bölgelerinde işlem fırsatları arar. RSI 30'un altına düştüğünde veya fiyat 
                                                Bollinger alt bandına yakınsa alım, RSI 70'in üstüne çıktığında veya fiyat üst banda yakınsa satım sinyali üretir.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Trend Takip Stratejisi -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <div class="custom-control custom-switch float-right">
                                                <input type="checkbox" class="custom-control-input" id="trend_following_enabled" name="trend_following_enabled"
                                                       <?php echo $settings['strategies']['trend_following']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="trend_following_enabled"></label>
                                            </div>
                                            <h5 class="mb-0">Trend Takip Stratejisi</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">Hareketli ortalamaların kesişimlerine dayalı trend takip stratejisi.</p>
                                            <p class="small text-muted">
                                                Bu strateji, kısa ve uzun vadeli hareketli ortalamaların kesişimlerini kullanır. Kısa MA uzun MA'yı yukarı kestiğinde 
                                                (Golden Cross) alım, aşağı kestiğinde (Death Cross) satım sinyali üretir.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Kırılma Stratejisi -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <div class="custom-control custom-switch float-right">
                                                <input type="checkbox" class="custom-control-input" id="breakout_enabled" name="breakout_enabled"
                                                       <?php echo $settings['strategies']['breakout']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="breakout_enabled"></label>
                                            </div>
                                            <h5 class="mb-0">Kırılma Stratejisi</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">Direnç ve destek kırılmalarına dayalı strateji.</p>
                                            <p class="small text-muted">
                                                Bu strateji, fiyatın belirli bir süre içindeki en yüksek ve en düşük seviyelere göre kırılmaları tespit eder. 
                                                Direnç kırıldığında alım, destek kırıldığında satım sinyali üretir.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- API Ayarları -->
                                <div class="tab-pane fade" id="api" role="tabpanel">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Bu bölümdeki değişiklikler doğrudan yapılandırma dosyalarında güncellenir. Dikkatli olun!
                                    </div>
                                    
                                    <h5 class="mb-3">Borsa API Ayarları</h5>
                                    
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Binance</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="binance_api_key">API Key</label>
                                                <input type="text" name="binance_api_key" id="binance_api_key" class="form-control" 
                                                       placeholder="Binance API Key">
                                            </div>
                                            <div class="form-group">
                                                <label for="binance_api_secret">API Secret</label>
                                                <input type="password" name="binance_api_secret" id="binance_api_secret" class="form-control" 
                                                       placeholder="Binance API Secret">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">KuCoin</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="kucoin_api_key">API Key</label>
                                                <input type="text" name="kucoin_api_key" id="kucoin_api_key" class="form-control" 
                                                       placeholder="KuCoin API Key">
                                            </div>
                                            <div class="form-group">
                                                <label for="kucoin_api_secret">API Secret</label>
                                                <input type="password" name="kucoin_api_secret" id="kucoin_api_secret" class="form-control" 
                                                       placeholder="KuCoin API Secret">
                                            </div>
                                            <div class="form-group">
                                                <label for="kucoin_api_passphrase">API Passphrase</label>
                                                <input type="password" name="kucoin_api_passphrase" id="kucoin_api_passphrase" class="form-control" 
                                                       placeholder="KuCoin API Passphrase">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h5 class="mb-3">Telegram Ayarları</h5>
                                    
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <div class="custom-control custom-switch float-right">
                                                <input type="checkbox" class="custom-control-input" id="telegram_enabled" name="telegram_enabled">
                                                <label class="custom-control-label" for="telegram_enabled"></label>
                                            </div>
                                            <h5 class="mb-0">Telegram Bot</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="telegram_token">Bot Token</label>
                                                <input type="text" name="telegram_token" id="telegram_token" class="form-control" 
                                                       placeholder="Telegram Bot Token">
                                            </div>
                                            <div class="form-group">
                                                <label for="telegram_chat_id">Chat ID</label>
                                                <input type="text" name="telegram_chat_id" id="telegram_chat_id" class="form-control" 
                                                       placeholder="Telegram Chat ID">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Ayarları Kaydet
                                </button>
                            </div>
                        </form>
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