<?php
// Hata ayıklama için PHP hata raporlamasını etkinleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
            'use_tradingview' => isset($_POST['use_tradingview']),
            'tradingview_exchange' => $_POST['tradingview_exchange'],
            
            // Yeni eklenen otomatik coin keşfetme ayarları
            'auto_discovery' => [
                'enabled' => isset($_POST['auto_discovery_enabled']),
                'discovery_interval' => (int) $_POST['discovery_interval'],
                'min_volume_for_discovery' => (float) $_POST['min_volume_for_discovery'],
                'min_price_change' => (float) $_POST['min_price_change'],
                'min_volume_change' => (float) $_POST['min_volume_change'],
                'max_coins_to_discover' => (int) $_POST['max_coins_to_discover'],
                'auto_add_to_watchlist' => isset($_POST['auto_add_to_watchlist'])
            ],
            
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
                ],
                'supertrend' => [
                    'enabled' => isset($_POST['supertrend_enabled']),
                    'period' => (int) $_POST['supertrend_period'],
                    'multiplier' => (float) $_POST['supertrend_multiplier']
                ],
                'vwap' => [
                    'enabled' => isset($_POST['vwap_enabled']),
                    'period' => (int) $_POST['vwap_period']
                ],
                'pivot_points' => [
                    'enabled' => isset($_POST['pivot_points_enabled']),
                    'method' => $_POST['pivot_points_method']
                ],
                'fibonacci' => [
                    'enabled' => isset($_POST['fibonacci_enabled']),
                    'period' => (int) $_POST['fibonacci_period']
                ],
                'stochastic' => [
                    'enabled' => isset($_POST['stochastic_enabled']),
                    'k_period' => (int) $_POST['stochastic_k_period'],
                    'd_period' => (int) $_POST['stochastic_d_period'],
                    'slowing' => (int) $_POST['stochastic_slowing']
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
                ],
                // Yeni volatilite kırılma stratejisi
                'volatility_breakout' => [
                    'enabled' => isset($_POST['volatility_breakout_enabled'])
                ]
            ],
            'risk_management' => [
                'enabled' => isset($_POST['risk_enabled']),
                'stop_loss' => (float) $_POST['stop_loss'],
                'take_profit' => (float) $_POST['take_profit'],
                'trailing_stop' => isset($_POST['trailing_stop']),
                'trailing_stop_distance' => (float) $_POST['trailing_stop_distance'],
                
                // Yeni eklenen trailing stop parametreleri
                'trailing_stop_activation_pct' => (float) $_POST['trailing_stop_activation_pct'],
                'trailing_stop_pct' => (float) $_POST['trailing_stop_pct'],
                
                'max_open_positions' => (int) $_POST['max_open_positions'],
                'max_risk_per_trade' => (float) $_POST['max_risk_per_trade']
            ],
            'backtesting' => [
                'default_start_date' => $_POST['default_start_date'],
                'default_end_date' => $_POST['default_end_date'],
                'initial_capital' => (float) $_POST['initial_capital'],
                'trading_fee' => (float) $_POST['trading_fee'],
                'slippage' => (float) $_POST['slippage'],
                'enable_visualization' => isset($_POST['enable_visualization'])
            ],
            // Yeni eklenen Telegram ayarları
            'telegram' => [
                'enabled' => isset($_POST['telegram_enabled']),
                'trade_signals' => isset($_POST['telegram_trade_signals']),
                'position_updates' => isset($_POST['telegram_position_updates']),
                'performance_updates' => isset($_POST['telegram_performance_updates']),
                'discovered_coins' => isset($_POST['telegram_discovered_coins'])
            ],
            // Yeni eklenen işlem modu ve ayarları
            'trade_mode' => $_POST['trade_mode'],
            'auto_trade' => isset($_POST['auto_trade'])
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <style>
        /* Ayarlar sayfası için özel stiller */
        .settings-wrapper {
            padding: 0.5rem;
        }
        
        .settings-card {
            border-radius: 0.75rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            border: none;
        }
        
        .settings-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1.25rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .settings-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .settings-header i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .nav-tabs {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 1rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--secondary);
            font-weight: 500;
            padding: 1rem 1.25rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary);
            border-bottom-color: rgba(58, 109, 240, 0.3);
            background-color: transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary);
            background-color: transparent;
            border-bottom-color: var(--primary);
            font-weight: 600;
        }

        .nav-tabs .nav-link i {
            margin-right: 0.5rem;
        }

        .tab-content {
            padding: 1.5rem;
        }
        
        .settings-panel {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .settings-panel:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
        
        .settings-panel h5 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
        }
        
        .settings-panel h5 i {
            margin-right: 0.75rem;
            color: var(--primary);
        }
        
        .settings-footer {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 1.25rem;
            text-align: center;
        }
        
        /* Form kontrolleri için stiller */
        .settings-form label {
            font-weight: 500;
            color: var(--secondary);
        }
        
        .settings-form .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(58, 109, 240, 0.25);
        }
        
        .settings-form .custom-switch .custom-control-label::before {
            border-radius: 1rem;
            height: 1.25rem;
            width: 2.25rem;
        }
        
        .settings-form .custom-switch .custom-control-label::after {
            height: calc(1.25rem - 4px);
            width: calc(1.25rem - 4px);
        }
        
        .settings-form .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        /* İndikatör ve strateji kartları için stiller */
        .feature-card {
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .feature-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .feature-card-header {
            background-color: rgba(0, 0, 0, 0.03);
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .feature-card-header h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .feature-card-header h5 i {
            margin-right: 0.75rem;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .feature-card-body {
            padding: 1.25rem;
        }
        
        .feature-card.enabled {
            border-left: 3px solid var(--primary);
        }
        
        .feature-card.disabled {
            opacity: 0.75;
        }
        
        /* Açıklayıcı metin ve ipuçları için stiller */
        .feature-description {
            font-size: 0.9rem;
            color: var(--secondary);
            margin-top: 0.5rem;
        }
        
        .tooltip-icon {
            color: var(--secondary);
            margin-left: 0.5rem;
            font-size: 0.85rem;
            cursor: pointer;
        }
        
        /* Form alanları gruplandırma stili */
        .form-group-container {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            background-color: rgba(0, 0, 0, 0.01);
        }
        
        .form-group-title {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
        }
        
        .form-group-title i {
            margin-right: 0.5rem;
            color: var(--primary);
        }

        /* Dosya içindeki diğer özel stilleri koru */
        .card-header.bg-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light)) !important;
        }

        .alert-success {
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            border-left: 4px solid #ef4444;
        }

        /* Save buton stili */
        .btn-save {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 2.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(58, 109, 240, 0.3);
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(58, 109, 240, 0.4);
        }

        .btn-save i {
            margin-right: 0.5rem;
        }

        /* Açıklayıcı kutucuklar */
        .info-box {
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
            display: flex;
            align-items: flex-start;
            transition: all 0.3s ease;
        }

        .info-box i {
            margin-right: 1rem;
            font-size: 1.5rem;
        }

        .info-box.info {
            background-color: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--info);
        }

        .info-box.warning {
            background-color: rgba(245, 158, 11, 0.1);
            border-left: 4px solid var(--warning);
        }

        .info-box.tip {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 4px solid var(--success);
        }
    </style>
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
            <div class="col-md-10 settings-wrapper">
                <div class="settings-card">
                    <div class="settings-header">
                        <h5><i class="fas fa-cog"></i> Bot Ayarları</h5>
                        <span class="badge badge-light">v2.5.0</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show m-3" role="alert">
                                <div class="d-flex align-items-center">
                                    <?php if($message_type == 'success'): ?>
                                        <i class="fas fa-check-circle mr-3" style="font-size: 1.5rem;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-circle mr-3" style="font-size: 1.5rem;"></i>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo $message_type == 'success' ? 'Başarılı!' : 'Hata!'; ?></strong>
                                        <p class="mb-0"><?php echo $message; ?></p>
                                    </div>
                                </div>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="" class="settings-form">
                            <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">
                                        <i class="fas fa-cogs"></i> Genel Ayarlar
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="auto-discovery-tab" data-toggle="tab" href="#auto-discovery" role="tab">
                                        <i class="fas fa-search-dollar"></i> Otomatik Keşif
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="indicators-tab" data-toggle="tab" href="#indicators" role="tab">
                                        <i class="fas fa-chart-line"></i> İndikatörler
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="strategies-tab" data-toggle="tab" href="#strategies" role="tab">
                                        <i class="fas fa-chess"></i> Stratejiler
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="risk-management-tab" data-toggle="tab" href="#risk-management" role="tab">
                                        <i class="fas fa-shield-alt"></i> Risk Yönetimi
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="backtesting-tab" data-toggle="tab" href="#backtesting" role="tab">
                                        <i class="fas fa-vial"></i> Backtesting
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="api-tab" data-toggle="tab" href="#api" role="tab">
                                        <i class="fas fa-key"></i> API Ayarları
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
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>TradingView Entegrasyonu</label>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="use_tradingview" name="use_tradingview" 
                                                           <?php echo isset($settings['use_tradingview']) && $settings['use_tradingview'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="use_tradingview">TradingView kullan</label>
                                                </div>
                                                <small class="form-text text-muted">CCXT yerine TradingView API kullanarak veri çek</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tradingview_exchange">TradingView Borsa Öneki</label>
                                                <select name="tradingview_exchange" id="tradingview_exchange" class="form-control">
                                                    <option value="BINANCE" <?php echo isset($settings['tradingview_exchange']) && $settings['tradingview_exchange'] == 'BINANCE' ? 'selected' : ''; ?>>Binance</option>
                                                    <option value="KUCOIN" <?php echo isset($settings['tradingview_exchange']) && $settings['tradingview_exchange'] == 'KUCOIN' ? 'selected' : ''; ?>>KuCoin</option>
                                                    <option value="COINBASE" <?php echo isset($settings['tradingview_exchange']) && $settings['tradingview_exchange'] == 'COINBASE' ? 'selected' : ''; ?>>Coinbase</option>
                                                </select>
                                                <small class="form-text text-muted">TradingView'da kullanılacak borsa öneki</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- İşlem Modu Ayarları -->
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="trade_mode">İşlem Modu</label>
                                                <select name="trade_mode" id="trade_mode" class="form-control">
                                                    <option value="paper" <?php echo isset($settings['trade_mode']) && $settings['trade_mode'] == 'paper' ? 'selected' : ''; ?>>Kağıt Üzerinde (Paper Trading)</option>
                                                    <option value="live" <?php echo isset($settings['trade_mode']) && $settings['trade_mode'] == 'live' ? 'selected' : ''; ?>>Gerçek İşlem (Live Trading)</option>
                                                    <option value="backtest" <?php echo isset($settings['trade_mode']) && $settings['trade_mode'] == 'backtest' ? 'selected' : ''; ?>>Backtest</option>
                                                </select>
                                                <small class="form-text text-muted">Botun çalışacağı işlem modu</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Otomatik İşlem</label>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="auto_trade" name="auto_trade" 
                                                           <?php echo isset($settings['auto_trade']) && $settings['auto_trade'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="auto_trade">Otomatik işlem yap</label>
                                                </div>
                                                <small class="form-text text-muted">İşaretlenirse, bot otomatik olarak alım-satım yapar</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Otomatik Keşif Ayarları -->
                                <div class="tab-pane fade" id="auto-discovery" role="tabpanel">
                                    <div class="info-box info mb-4">
                                        <i class="fas fa-info-circle"></i>
                                        <div>
                                            <h6 class="font-weight-bold mb-1">Otomatik Coin Keşfetme Ayarları</h6>
                                            <p class="mb-0">Otomatik coin keşfetme özelliği, belirli kriterlere göre yeni coinleri tarar ve keşfeder. Bu özellik, botun sürekli olarak yeni fırsatları değerlendirmesini sağlar.</p>
                                        </div>
                                    </div>
                                    
                                    <div class="feature-card mb-4 <?php echo isset($settings['auto_discovery']['enabled']) && $settings['auto_discovery']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-search-dollar"></i> Otomatik Keşif</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="auto_discovery_enabled" name="auto_discovery_enabled"
                                                       <?php echo isset($settings['auto_discovery']['enabled']) && $settings['auto_discovery']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="auto_discovery_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                Otomatik coin keşfetme özelliği, belirli kriterlere göre yeni coinleri tarar ve keşfeder. Bu özellik, botun sürekli olarak yeni fırsatları değerlendirmesini sağlar.
                                            </p>
                                            
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="discovery_interval">
                                                            Keşif Aralığı
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Yeni coinleri tarama aralığı (saniye cinsinden)"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                                            </div>
                                                            <input type="number" name="discovery_interval" id="discovery_interval" class="form-control" 
                                                                   value="<?php echo isset($settings['auto_discovery']['discovery_interval']) ? $settings['auto_discovery']['discovery_interval'] : 600; ?>" min="60" max="3600">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">saniye</span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 600 saniye (10 dakika)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="min_volume_for_discovery">
                                                            Minimum Hacim
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Keşfedilecek coinler için minimum günlük işlem hacmi"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-chart-bar"></i></span>
                                                            </div>
                                                            <input type="number" step="0.01" name="min_volume_for_discovery" id="min_volume_for_discovery" class="form-control" 
                                                                   value="<?php echo isset($settings['auto_discovery']['min_volume_for_discovery']) ? $settings['auto_discovery']['min_volume_for_discovery'] : 1000; ?>" min="100" max="10000">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text"><?php echo $settings['base_currency']; ?></span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 1000</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="min_price_change">
                                                            Minimum Fiyat Değişimi
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Keşfedilecek coinler için minimum fiyat değişim yüzdesi"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                            </div>
                                                            <input type="number" step="0.01" name="min_price_change" id="min_price_change" class="form-control" 
                                                                   value="<?php echo isset($settings['auto_discovery']['min_price_change']) ? $settings['auto_discovery']['min_price_change'] : 5; ?>" min="1" max="100">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: %5</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="min_volume_change">
                                                            Minimum Hacim Değişimi
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Keşfedilecek coinler için minimum hacim değişim yüzdesi"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-chart-line"></i></span>
                                                            </div>
                                                            <input type="number" step="0.01" name="min_volume_change" id="min_volume_change" class="form-control" 
                                                                   value="<?php echo isset($settings['auto_discovery']['min_volume_change']) ? $settings['auto_discovery']['min_volume_change'] : 10; ?>" min="1" max="100">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: %10</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="max_coins_to_discover">
                                                            Maksimum Keşfedilecek Coin Sayısı
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Aynı anda keşfedilecek maksimum coin sayısı"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                                            </div>
                                                            <input type="number" name="max_coins_to_discover" id="max_coins_to_discover" class="form-control" 
                                                                   value="<?php echo isset($settings['auto_discovery']['max_coins_to_discover']) ? $settings['auto_discovery']['max_coins_to_discover'] : 10; ?>" min="1" max="50">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 10</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>
                                                            Keşfedilen Coinleri İzleme Listesine Otomatik Ekle
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Keşfedilen coinleri otomatik olarak izleme listesine ekler"></i>
                                                        </label>
                                                        <div class="custom-control custom-switch mt-2">
                                                            <input type="checkbox" class="custom-control-input" id="auto_add_to_watchlist" name="auto_add_to_watchlist"
                                                                   <?php echo isset($settings['auto_discovery']['auto_add_to_watchlist']) && $settings['auto_discovery']['auto_add_to_watchlist'] ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label" for="auto_add_to_watchlist">Otomatik Ekle</label>
                                                        </div>
                                                        <small class="form-text text-muted">Keşfedilen coinler izleme listesine otomatik olarak eklenir</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- İndikatör Ayarları -->
                                <div class="tab-pane fade" id="indicators" role="tabpanel">
                                    <div class="info-box info mb-4">
                                        <i class="fas fa-info-circle"></i>
                                        <div>
                                            <h6 class="font-weight-bold mb-1">İndikatör Ayarları Hakkında</h6>
                                            <p class="mb-0">İndikatörler, fiyat verilerini analiz ederek alım-satım sinyalleri üretir. Etkinleştirdiğiniz indikatörler botun karar verme sürecinde kullanılacaktır. Her indikatör farklı piyasa koşullarında farklı performans gösterebilir.</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Bollinger Bands -->
                                    <div class="feature-card mb-4 <?php echo $settings['indicators']['bollinger_bands']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-chart-area"></i> Bollinger Bands</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="bb_enabled" name="bb_enabled"
                                                       <?php echo $settings['indicators']['bollinger_bands']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="bb_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                Bollinger Bands, fiyat volatilitesini ölçer ve potansiyel aşırı alım veya aşırı satım bölgelerini belirlemenize yardımcı olur. 
                                                Orta bant, bir hareketli ortalamadır; üst ve alt bantlar ise standart sapma çarpanlarıdır.
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="bb_window">
                                                            Periyot
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Hesaplamada kullanılacak veri noktası sayısı. Standart değer 20'dir."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                            </div>
                                                            <input type="number" name="bb_window" id="bb_window" class="form-control" 
                                                                   value="<?php echo $settings['indicators']['bollinger_bands']['window']; ?>" min="5" max="100">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 20</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="bb_num_std">
                                                            Standart Sapma
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Bantların genişliği için standart sapma çarpanı. Daha yüksek değer, daha geniş bantlar demektir."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-arrows-alt-h"></i></span>
                                                            </div>
                                                            <input type="number" step="0.1" name="bb_num_std" id="bb_num_std" class="form-control" 
                                                                   value="<?php echo $settings['indicators']['bollinger_bands']['num_std']; ?>" min="1" max="4">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 2.0</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- RSI -->
                                    <div class="feature-card mb-4 <?php echo $settings['indicators']['rsi']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-chart-line"></i> RSI (Göreceli Güç İndeksi)</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="rsi_enabled" name="rsi_enabled"
                                                       <?php echo $settings['indicators']['rsi']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="rsi_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                RSI (Relative Strength Index), bir varlığın aşırı alım veya aşırı satım durumlarını belirlemek için kullanılan bir momentum osilatörüdür.
                                                RSI değeri 0 ile 100 arasında değişir; genellikle 70'in üzerinde aşırı alım, 30'un altında aşırı satım olarak kabul edilir.
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="rsi_window">
                                                            Periyot
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="RSI hesaplamasında kullanılacak veri noktası sayısı. Standart değer 14'tür."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                            </div>
                                                            <input type="number" name="rsi_window" id="rsi_window" class="form-control" 
                                                                   value="<?php echo $settings['indicators']['rsi']['window']; ?>" min="5" max="50">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 14</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- MACD düzeltmesi -->
                                    <div class="feature-card mb-4 <?php echo $settings['indicators']['macd']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-chart-bar"></i> MACD (Hareketli Ortalama Yakınsama/Iraksama)</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="macd_enabled" name="macd_enabled"
                                                       <?php echo $settings['indicators']['macd']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="macd_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                MACD, trendin yönünü, momentumunu ve süresini belirlemek için kullanılan bir trend takip ve momentum indikatörüdür.
                                                Kısa ve uzun vadeli hareketli ortalamaların farkını ve bunların sinyal çizgisini kullanır.
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="macd_fast">
                                                            Hızlı EMA
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Hızlı Üssel Hareketli Ortalama periyodu. Daha küçük sayı, daha hızlı tepki verir."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-bolt"></i></span>
                                                            </div>
                                                            <input type="number" name="macd_fast" id="macd_fast" class="form-control" 
                                                                   value="<?php echo $settings['indicators']['macd']['fast_period']; ?>" min="5" max="30">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 12</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="macd_slow">
                                                            Yavaş EMA
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Yavaş Üssel Hareketli Ortalama periyodu. Daha büyük sayı, daha yavaş tepki verir."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                                            </div>
                                                            <input type="number" name="macd_slow" id="macd_slow" class="form-control" 
                                                                   value="<?php echo $settings['indicators']['macd']['slow_period']; ?>" min="10" max="50">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 26</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="macd_signal">
                                                            Sinyal Periyodu
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="MACD çizgisinin EMA'sı. Alım-satım sinyalleri için kullanılır."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-signal"></i></span>
                                                            </div>
                                                            <input type="number" name="macd_signal" id="macd_signal" class="form-control" 
                                                                   value="<?php echo $settings['indicators']['macd']['signal_period']; ?>" min="3" max="15">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 9</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Hareketli Ortalama düzeltmesi -->
                                    <div class="feature-card mb-4 <?php echo $settings['indicators']['moving_average']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-wave-square"></i> Hareketli Ortalamalar</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="ma_enabled" name="ma_enabled"
                                                       <?php echo $settings['indicators']['moving_average']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="ma_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                Hareketli ortalamalar, belirli bir süre boyunca fiyat hareketlerinin düzleştirilmiş değerleridir ve trend yönünü belirlemek için kullanılır. 
                                                Kısa ve uzun dönem hareketli ortalamaların kesişimi, trend değişimlerini işaret eder.
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="ma_short">
                                                            Kısa Periyot
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Kısa vadeli hareketli ortalama için periyot. Daha düşük değer, günlük hareketlere daha duyarlıdır."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-bolt"></i></span>
                                                            </div>
                                                            <input type="number" name="ma_short" id="ma_short" class="form-control" 
                                                                   value="<?php echo $settings['indicators']['moving_average']['short_window']; ?>" min="3" max="50">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 7 veya 9</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="ma_long">
                                                            Uzun Periyot
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Uzun vadeli hareketli ortalama için periyot. Daha yüksek değer, uzun vadeli trendi gösterir."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-route"></i></span>
                                                            </div>
                                                            <input type="number" name="ma_long" id="ma_long" class="form-control" 
                                                                   value="<?php echo $settings['indicators']['moving_average']['long_window']; ?>" min="10" max="200">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 20, 50 veya 200</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Supertrend indikatörü -->
                                    <div class="feature-card mb-4 <?php echo isset($settings['indicators']['supertrend']['enabled']) && $settings['indicators']['supertrend']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-route"></i> Supertrend</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="supertrend_enabled" name="supertrend_enabled"
                                                       <?php echo isset($settings['indicators']['supertrend']['enabled']) && $settings['indicators']['supertrend']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="supertrend_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                Supertrend, trend yönünü belirlemek için kullanılan güçlü bir teknik indikatördür. 
                                                ATR (Average True Range) ve fiyat hareketlerini temel alarak alım-satım sinyalleri üretir.
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="supertrend_period">
                                                            ATR Periyodu
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Average True Range hesaplama periyodu. Standart değer 10'dur."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                            </div>
                                                            <input type="number" name="supertrend_period" id="supertrend_period" class="form-control" 
                                                                   value="<?php echo isset($settings['indicators']['supertrend']['period']) ? $settings['indicators']['supertrend']['period'] : 10; ?>" min="5" max="30">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 10</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="supertrend_multiplier">
                                                            Çarpan
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="ATR değerinin çarpanı. Daha yüksek değer, sinyallerin daha az sıklıkta olmasını sağlar."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-times"></i></span>
                                                            </div>
                                                            <input type="number" step="0.1" name="supertrend_multiplier" id="supertrend_multiplier" class="form-control" 
                                                                   value="<?php echo isset($settings['indicators']['supertrend']['multiplier']) ? $settings['indicators']['supertrend']['multiplier'] : 3.0; ?>" min="1" max="5">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 3.0</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- VWAP indikatörü -->
                                    <div class="feature-card mb-4 <?php echo isset($settings['indicators']['vwap']['enabled']) && $settings['indicators']['vwap']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-chart-area"></i> VWAP (Hacim Ağırlıklı Ortalama Fiyat)</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="vwap_enabled" name="vwap_enabled"
                                                       <?php echo isset($settings['indicators']['vwap']['enabled']) && $settings['indicators']['vwap']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="vwap_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                VWAP (Volume Weighted Average Price), belirli bir zaman diliminde gerçekleşen işlemlerin hacimlerini dikkate alarak hesaplanan ortalama fiyattır. 
                                                Kurumsal yatırımcılar tarafından sıklıkla kullanılan bir göstergedir.
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="vwap_period">
                                                            Periyot
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="VWAP hesaplama periyodu. Genellikle günlük (1d) olarak kullanılır."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                            </div>
                                                            <input type="number" name="vwap_period" id="vwap_period" class="form-control" 
                                                                   value="<?php echo isset($settings['indicators']['vwap']['period']) ? $settings['indicators']['vwap']['period'] : 14; ?>" min="1" max="30">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 14</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Pivot Points indikatörü -->
                                    <div class="feature-card mb-4 <?php echo isset($settings['indicators']['pivot_points']['enabled']) && $settings['indicators']['pivot_points']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-crosshairs"></i> Pivot Noktaları</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="pivot_points_enabled" name="pivot_points_enabled"
                                                       <?php echo isset($settings['indicators']['pivot_points']['enabled']) && $settings['indicators']['pivot_points']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="pivot_points_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                Pivot Noktaları, önceki periyodun fiyat verilerini kullanarak destek ve direnç seviyelerini hesaplamak için kullanılır. 
                                                Bu seviyeler, gelecek fiyat hareketleri için potansiyel dönüş noktalarını gösterebilir.
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="pivot_points_method">
                                                            Hesaplama Metodu
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Pivot noktalarının hesaplanma yöntemi."></i>
                                                        </label>
                                                        <select name="pivot_points_method" id="pivot_points_method" class="form-control">
                                                            <option value="standard" <?php echo isset($settings['indicators']['pivot_points']['method']) && $settings['indicators']['pivot_points']['method'] == 'standard' ? 'selected' : ''; ?>>Standart</option>
                                                            <option value="fibonacci" <?php echo isset($settings['indicators']['pivot_points']['method']) && $settings['indicators']['pivot_points']['method'] == 'fibonacci' ? 'selected' : ''; ?>>Fibonacci</option>
                                                            <option value="woodie" <?php echo isset($settings['indicators']['pivot_points']['method']) && $settings['indicators']['pivot_points']['method'] == 'woodie' ? 'selected' : ''; ?>>Woodie</option>
                                                            <option value="camarilla" <?php echo isset($settings['indicators']['pivot_points']['method']) && $settings['indicators']['pivot_points']['method'] == 'camarilla' ? 'selected' : ''; ?>>Camarilla</option>
                                                        </select>
                                                        <small class="form-text text-muted">Önerilen: Standart</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Fibonacci indikatörü -->
                                    <div class="feature-card mb-4 <?php echo isset($settings['indicators']['fibonacci']['enabled']) && $settings['indicators']['fibonacci']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-project-diagram"></i> Fibonacci Geri Çekilme Seviyeleri</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="fibonacci_enabled" name="fibonacci_enabled"
                                                       <?php echo isset($settings['indicators']['fibonacci']['enabled']) && $settings['indicators']['fibonacci']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="fibonacci_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                Fibonacci Geri Çekilme Seviyeleri, fiyattaki önemli geri çekilme noktalarını belirlemek için kullanılır. 
                                                Bu seviyeler, potansiyel destek ve direnç bölgelerini işaret edebilir.
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="fibonacci_period">
                                                            Hesaplama Periyodu
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Fibonacci seviyelerinin hesaplanacağı periyot."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                            </div>
                                                            <input type="number" name="fibonacci_period" id="fibonacci_period" class="form-control" 
                                                                   value="<?php echo isset($settings['indicators']['fibonacci']['period']) ? $settings['indicators']['fibonacci']['period'] : 14; ?>" min="5" max="50">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 14</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Stochastic indikatörü -->
                                    <div class="feature-card mb-4 <?php echo isset($settings['indicators']['stochastic']['enabled']) && $settings['indicators']['stochastic']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-percent"></i> Stochastic Osilatör</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="stochastic_enabled" name="stochastic_enabled"
                                                       <?php echo isset($settings['indicators']['stochastic']['enabled']) && $settings['indicators']['stochastic']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="stochastic_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <p class="feature-description">
                                                Stochastic Osilatör, bir varlığın mevcut fiyatının belirli bir zaman aralığındaki fiyat aralığına göre konumunu gösterir. 
                                                Aşırı alım (80 üzeri) ve aşırı satım (20 altı) durumlarını tespit etmek için kullanılır.
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="stochastic_k_period">
                                                            %K Periyodu
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="%K çizgisi için hesaplama periyodu."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                            </div>
                                                            <input type="number" name="stochastic_k_period" id="stochastic_k_period" class="form-control" 
                                                                   value="<?php echo isset($settings['indicators']['stochastic']['k_period']) ? $settings['indicators']['stochastic']['k_period'] : 14; ?>" min="5" max="30">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 14</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="stochastic_d_period">
                                                            %D Periyodu
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="%D çizgisi için hesaplama periyodu. %K'nın hareketli ortalamasıdır."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                            </div>
                                                            <input type="number" name="stochastic_d_period" id="stochastic_d_period" class="form-control" 
                                                                   value="<?php echo isset($settings['indicators']['stochastic']['d_period']) ? $settings['indicators']['stochastic']['d_period'] : 3; ?>" min="1" max="10">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 3</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="stochastic_slowing">
                                                            Yavaşlatma
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Stochastic osilatörünün sinyallerini yumuşatmak için kullanılır."></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-tachometer-alt"></i></span>
                                                            </div>
                                                            <input type="number" name="stochastic_slowing" id="stochastic_slowing" class="form-control" 
                                                                   value="<?php echo isset($settings['indicators']['stochastic']['slowing']) ? $settings['indicators']['stochastic']['slowing'] : 3; ?>" min="1" max="5">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 3</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Strateji Ayarları -->
                                <div class="tab-pane fade" id="strategies" role="tabpanel">
                                    <div class="info-box tip mb-4">
                                        <i class="fas fa-lightbulb"></i>
                                        <div>
                                            <h6 class="font-weight-bold mb-1">Strateji Seçimi Hakkında</h6>
                                            <p class="mb-0">Stratejiler farklı piyasa koşulları için optimize edilmiştir. En iyi sonucu almak için birden fazla stratejiyi aynı anda etkinleştirebilir ve botun en uygun stratejileri kullanmasını sağlayabilirsiniz.</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Kısa Vadeli Strateji -->
                                    <div class="feature-card mb-4 <?php echo $settings['strategies']['short_term']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-bolt"></i> Kısa Vadeli Strateji</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="short_term_enabled" name="short_term_enabled"
                                                       <?php echo $settings['strategies']['short_term']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="short_term_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="badge badge-primary mr-2">Hızlı</span>
                                                <span class="badge badge-info mr-2">Kısa vadeli</span>
                                                <span class="badge badge-secondary">Aşırı seviyelerde işlem</span>
                                            </div>
                                            <p class="feature-description">
                                                RSI ve Bollinger Bands indikatörlerini kullanarak aşırı alım/satım bölgelerinde işlem fırsatları arar.
                                                Yüksek volatilite dönemlerinde daha iyi performans gösterebilir.
                                            </p>
                                            <div class="mt-3">
                                                <h6 class="font-weight-bold small">Çalışma Prensibi:</h6>
                                                <ul class="small">
                                                    <li>RSI 30'un altına düştüğünde <span class="badge badge-success">ALIM</span> sinyali üretir</li>
                                                    <li>RSI 70'in üzerine çıktığında <span class="badge badge-danger">SATIM</span> sinyali üretir</li>
                                                    <li>Fiyat Bollinger alt bandına yakınsa alım sinyalini güçlendirir</li>
                                                    <li>Fiyat Bollinger üst bandına yakınsa satım sinyalini güçlendirir</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Trend Takip Stratejisi -->
                                    <div class="feature-card mb-4 <?php echo $settings['strategies']['trend_following']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-chart-line"></i> Trend Takip Stratejisi</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="trend_following_enabled" name="trend_following_enabled"
                                                       <?php echo $settings['strategies']['trend_following']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="trend_following_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="badge badge-warning mr-2">Orta</span>
                                                <span class="badge badge-info mr-2">Orta vadeli</span>
                                                <span class="badge badge-secondary">Trend odaklı</span>
                                            </div>
                                            <p class="feature-description">
                                                Hareketli ortalamaların kesişimlerini kullanarak trend değişimlerini tespit eden ve bu yönde işlem yapan bir stratejidir.
                                                Güçlü trend dönemlerinde daha iyi performans gösterir.
                                            </p>
                                            <div class="mt-3">
                                                <h6 class="font-weight-bold small">Çalışma Prensibi:</h6>
                                                <ul class="small">
                                                    <li>Kısa MA uzun MA'yı yukarı kestiğinde (Golden Cross) <span class="badge badge-success">ALIM</span> sinyali üretir</li>
                                                    <li>Kısa MA uzun MA'yı aşağı kestiğinde (Death Cross) <span class="badge badge-danger">SATIM</span> sinyali üretir</li>
                                                    <li>Hacim artışı ile doğrulandığında sinyal güçlenir</li>
                                                    <li>RSI ile birlikte kullanıldığında daha etkili olabilir</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Kırılma Stratejisi -->
                                    <div class="feature-card mb-4 <?php echo $settings['strategies']['breakout']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-expand-arrows-alt"></i> Kırılma Stratejisi</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="breakout_enabled" name="breakout_enabled"
                                                       <?php echo $settings['strategies']['breakout']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="breakout_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="badge badge-danger mr-2">Agresif</span>
                                                <span class="badge badge-info mr-2">Ani hareketler</span>
                                                <span class="badge badge-secondary">Destek/Direnç odaklı</span>
                                            </div>
                                            <p class="feature-description">
                                                Fiyatın belirli bir süre içinde oluşan destek ve direnç seviyelerini kırması durumunda işlem yapan bir stratejidir.
                                                Piyasada güçlü hareketlerin olduğu dönemlerde etkilidir.
                                            </p>
                                            <div class="mt-3">
                                                <h6 class="font-weight-bold small">Çalışma Prensibi:</h6>
                                                <ul class="small">
                                                    <li>Son X periyottaki en yüksek seviyenin kırılması durumunda <span class="badge badge-success">ALIM</span> sinyali üretir</li>
                                                    <li>Son X periyottaki en düşük seviyenin kırılması durumunda <span class="badge badge-danger">SATIM</span> sinyali üretir</li>
                                                    <li>Hacim artışı ile desteklenen kırılmalar daha güvenilirdir</li>
                                                    <li>Volatilite düşük olduğunda yalancı sinyallere dikkat edilmelidir</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Volatilite Kırılma Stratejisi -->
                                    <div class="feature-card mb-4 <?php echo isset($settings['strategies']['volatility_breakout']['enabled']) && $settings['strategies']['volatility_breakout']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-chart-pie"></i> Volatilite Kırılma Stratejisi</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="volatility_breakout_enabled" name="volatility_breakout_enabled"
                                                       <?php echo isset($settings['strategies']['volatility_breakout']['enabled']) && $settings['strategies']['volatility_breakout']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="volatility_breakout_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="badge badge-danger mr-2">Agresif</span>
                                                <span class="badge badge-info mr-2">Volatilite odaklı</span>
                                                <span class="badge badge-secondary">Hızlı hareketler</span>
                                            </div>
                                            <p class="feature-description">
                                                Fiyatın volatiliteye bağlı olarak ani hareketler yapması durumunda işlem yapan bir stratejidir.
                                                Piyasada yüksek volatilite dönemlerinde etkilidir.
                                            </p>
                                            <div class="mt-3">
                                                <h6 class="font-weight-bold small">Çalışma Prensibi:</h6>
                                                <ul class="small">
                                                    <li>Volatilite artışı ile desteklenen fiyat hareketlerinde <span class="badge badge-success">ALIM</span> veya <span class="badge badge-danger">SATIM</span> sinyali üretir</li>
                                                    <li>Hacim ve fiyat değişimlerini birlikte değerlendirir</li>
                                                    <li>Volatilite düşük olduğunda işlem yapmaz</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-box warning mt-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <div>
                                            <h6 class="font-weight-bold mb-1">Dikkat!</h6>
                                            <p class="mb-0">Her strateji farklı piyasa koşullarında farklı performans gösterir. Mevcut piyasa koşullarına göre en uygun stratejiyi seçmek veya birden fazla stratejiyi birlikte kullanmak daha iyi sonuçlar verebilir. Gerçek parayla işlem yapmadan önce stratejilerinizi backtesting ile test etmeniz önerilir.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Risk Yönetimi -->
                                <div class="tab-pane fade" id="risk-management" role="tabpanel">
                                    <div class="info-box warning mb-4">
                                        <i class="fas fa-shield-alt"></i>
                                        <div>
                                            <h6 class="font-weight-bold mb-1">Risk Yönetimi</h6>
                                            <p class="mb-0">Risk yönetimi, yatırımınızı korumak için en önemli faktörlerden biridir. Doğru risk yönetimi stratejisi ile uzun vadede daha istikrarlı sonuçlar elde edebilirsiniz.</p>
                                        </div>
                                    </div>
                                
                                    <div class="feature-card mb-4 <?php echo isset($settings['risk_management']['enabled']) && $settings['risk_management']['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <div class="feature-card-header">
                                            <h5><i class="fas fa-shield-alt"></i> Risk Yönetimi</h5>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="risk_enabled" name="risk_enabled"
                                                       <?php echo isset($settings['risk_management']['enabled']) && $settings['risk_management']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="risk_enabled"></label>
                                            </div>
                                        </div>
                                        <div class="feature-card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="stop_loss">
                                                            Stop Loss (%)
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="İşlem için maksimum kayıp yüzdesi"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-chart-line"></i></span>
                                                            </div>
                                                            <input type="number" step="0.01" name="stop_loss" id="stop_loss" class="form-control" 
                                                                   value="<?php echo isset($settings['risk_management']['stop_loss']) ? $settings['risk_management']['stop_loss'] : 5; ?>" min="0.5" max="20">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: %2-5</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="take_profit">
                                                            Take Profit (%)
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="İşlem için hedef kâr yüzdesi"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-chart-line"></i></span>
                                                            </div>
                                                            <input type="number" step="0.01" name="take_profit" id="take_profit" class="form-control" 
                                                                   value="<?php echo isset($settings['risk_management']['take_profit']) ? $settings['risk_management']['take_profit'] : 10; ?>" min="1" max="50">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: %5-15</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mt-3">
                                                <div class="col-12 mb-3">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="trailing_stop" name="trailing_stop"
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="trailing_stop_distance">
                                                            Trailing Stop Mesafesi (%)
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Fiyat ile trailing stop arasındaki yüzdesel mesafe"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-arrows-alt-v"></i></span>
                                                            </div>
                                                            <input type="number" step="0.01" name="trailing_stop_distance" id="trailing_stop_distance" class="form-control" 
                                                                   value="<?php echo isset($settings['risk_management']['trailing_stop_distance']) ? $settings['risk_management']['trailing_stop_distance'] : 2; ?>" min="0.5" max="10">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="trailing_stop_activation_pct">
                                                            Aktivasyon Yüzdesi
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Trailing stop'un aktif olması için gereken minimum kâr yüzdesi"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                            </div>
                                                            <input type="number" step="0.01" name="trailing_stop_activation_pct" id="trailing_stop_activation_pct" class="form-control" 
                                                                   value="<?php echo isset($settings['risk_management']['trailing_stop_activation_pct']) ? $settings['risk_management']['trailing_stop_activation_pct'] : 3; ?>" min="0.5" max="10">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="trailing_stop_pct">
                                                            Trailing Stop (%)
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Trailing stop yüzdesi"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-chart-line"></i></span>
                                                            </div>
                                                            <input type="number" step="0.01" name="trailing_stop_pct" id="trailing_stop_pct" class="form-control" 
                                                                   value="<?php echo isset($settings['risk_management']['trailing_stop_pct']) ? $settings['risk_management']['trailing_stop_pct'] : 2; ?>" min="0.5" max="10">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="max_open_positions">
                                                            Maksimum Açık Pozisyon
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Aynı anda açık tutulabilecek maksimum pozisyon sayısı"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                                                            </div>
                                                            <input type="number" name="max_open_positions" id="max_open_positions" class="form-control" 
                                                                   value="<?php echo isset($settings['risk_management']['max_open_positions']) ? $settings['risk_management']['max_open_positions'] : 5; ?>" min="1" max="20">
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: 3-5</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="max_risk_per_trade">
                                                            İşlem Başına Maksimum Risk
                                                            <i class="far fa-question-circle tooltip-icon" data-toggle="tooltip" title="Toplam sermayenin işlem başına maksimum risk oranı"></i>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                                            </div>
                                                            <input type="number" step="0.01" name="max_risk_per_trade" id="max_risk_per_trade" class="form-control" 
                                                                   value="<?php echo isset($settings['risk_management']['max_risk_per_trade']) ? $settings['risk_management']['max_risk_per_trade'] : 2; ?>" min="0.1" max="10">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Önerilen: %1-2</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Backtesting -->
                                <div class="tab-pane fade" id="backtesting" role="tabpanel">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <div class="custom-control custom-switch float-right">
                                                <input type="checkbox" class="custom-control-input" id="enable_visualization" name="enable_visualization"
                                                       <?php echo isset($settings['backtesting']['enable_visualization']) && $settings['backtesting']['enable_visualization'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="enable_visualization">Görselleştirme</label>
                                            </div>
                                            <h5 class="mb-0">Backtesting (Geriye Dönük Test)</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="default_start_date">Varsayılan Başlangıç Tarihi</label>
                                                        <input type="date" name="default_start_date" id="default_start_date" class="form-control" 
                                                               value="<?php echo isset($settings['backtesting']['default_start_date']) ? $settings['backtesting']['default_start_date'] : date('Y-m-d', strtotime('-3 months')); ?>">
                                                        <small class="form-text text-muted">Backtesting için varsayılan başlangıç tarihi</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="default_end_date">Varsayılan Bitiş Tarihi</label>
                                                        <input type="date" name="default_end_date" id="default_end_date" class="form-control" 
                                                               value="<?php echo isset($settings['backtesting']['default_end_date']) ? $settings['backtesting']['default_end_date'] : date('Y-m-d'); ?>">
                                                        <small class="form-text text-muted">Backtesting için varsayılan bitiş tarihi</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="initial_capital">Başlangıç Sermayesi</label>
                                                        <div class="input-group">
                                                            <input type="number" step="0.01" name="initial_capital" id="initial_capital" class="form-control" 
                                                                   value="<?php echo isset($settings['backtesting']['initial_capital']) ? $settings['backtesting']['initial_capital'] : 1000; ?>">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text"><?php echo $settings['base_currency']; ?></span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Test başlangıcındaki sanal sermaye miktarı</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="trading_fee">İşlem Ücreti</label>
                                                        <div class="input-group">
                                                            <input type="number" step="0.001" name="trading_fee" id="trading_fee" class="form-control" 
                                                                   value="<?php echo isset($settings['backtesting']['trading_fee']) ? $settings['backtesting']['trading_fee'] : 0.001; ?>" min="0" max="0.01">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Her işlem için alınacak komisyon (0.001 = %0.1)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="slippage">Slippage (Kayma)</label>
                                                        <div class="input-group">
                                                            <input type="number" step="0.001" name="slippage" id="slippage" class="form-control" 
                                                                   value="<?php echo isset($settings['backtesting']['slippage']) ? $settings['backtesting']['slippage'] : 0.001; ?>" min="0" max="0.01">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Beklenilen ve gerçekleşen fiyat arasındaki fark (0.001 = %0.1)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="alert alert-info mt-3">
                                                <i class="fas fa-info-circle"></i> 
                                                <strong>Backtesting Nedir?</strong> Backtesting, bir trading stratejisinin geçmiş verilerde nasıl performans göstereceğini test etmenizi sağlar.
                                                Bu, gerçek para riske atmadan önce stratejilerinizin etkinliğini değerlendirmek için harika bir yöntemdir.
                                            </div>
                                            
                                            <div class="mt-4">
                                                <h5>Backtesting İşlemi Nasıl Yapılır?</h5>
                                                <ol>
                                                    <li>Başlangıç ve bitiş tarihlerini belirleyin</li>
                                                    <li>Test etmek istediğiniz stratejiyi seçin</li>
                                                    <li>Başlangıç sermayesini ayarlayın</li>
                                                    <li>Gerçekçi işlem ücreti ve kayma değerleri girin</li>
                                                    <li>Backtesting sayfasına gidin ve testi başlatın</li>
                                                </ol>
                                                <p>
                                                    <a href="backtesting.php" class="btn btn-primary">
                                                        <i class="fas fa-chart-line"></i> Backtest Çalıştır
                                                    </a>
                                                </p>
                                            </div>
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
                                                <input type="checkbox" class="custom-control-input" id="telegram_enabled" name="telegram_enabled"
                                                       <?php echo isset($settings['telegram']['enabled']) && $settings['telegram']['enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="telegram_enabled"></label>
                                            </div>
                                            <h5 class="mb-0">Telegram Bot</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="telegram_token">Bot Token</label>
                                                <input type="text" name="telegram_token" id="telegram_token" class="form-control" 
                                                       value="<?php echo isset($settings['telegram']['token']) ? $settings['telegram']['token'] : ''; ?>" placeholder="Telegram Bot Token">
                                            </div>
                                            <div class="form-group">
                                                <label for="telegram_chat_id">Chat ID</label>
                                                <input type="text" name="telegram_chat_id" id="telegram_chat_id" class="form-control" 
                                                       value="<?php echo isset($settings['telegram']['chat_id']) ? $settings['telegram']['chat_id'] : ''; ?>" placeholder="Telegram Chat ID">
                                            </div>
                                            <div class="form-group">
                                                <label>Trade Sinyalleri</label>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="telegram_trade_signals" name="telegram_trade_signals"
                                                           <?php echo isset($settings['telegram']['trade_signals']) && $settings['telegram']['trade_signals'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="telegram_trade_signals">Gönder</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Pozisyon Güncellemeleri</label>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="telegram_position_updates" name="telegram_position_updates"
                                                           <?php echo isset($settings['telegram']['position_updates']) && $settings['telegram']['position_updates'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="telegram_position_updates">Gönder</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Performans Güncellemeleri</label>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="telegram_performance_updates" name="telegram_performance_updates"
                                                           <?php echo isset($settings['telegram']['performance_updates']) && $settings['telegram']['performance_updates'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="telegram_performance_updates">Gönder</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Keşfedilen Coinler</label>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="telegram_discovered_coins" name="telegram_discovered_coins"
                                                           <?php echo isset($settings['telegram']['discovered_coins']) && $settings['telegram']['discovered_coins'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="telegram_discovered_coins">Gönder</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-footer">
                                <button type="submit" class="btn btn-save">
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
    <script>
        $(document).ready(function() {
            // İpucu balonlarını etkinleştir
            $('[data-toggle="tooltip"]').tooltip();
            
            // Sekme durumunu tarayıcı hafızasında sakla
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                localStorage.setItem('activeSettingsTab', $(e.target).attr('href'));
            });
            
            // Sayfayı yeniledikten sonra son aktif sekmeye dön
            var activeTab = localStorage.getItem('activeSettingsTab');
            if(activeTab){
                $('#settingsTabs a[href="' + activeTab + '"]').tab('show');
            }
            
            // İndikatörlerin etkinliğine göre kart stilini güncelle
            $('.custom-control-input').change(function() {
                var card = $(this).closest('.feature-card');
                if($(this).prop('checked')) {
                    card.addClass('enabled').removeClass('disabled');
                } else {
                    card.addClass('disabled').removeClass('enabled');
                }
            });
            
            // İndikatör açıklamalarını genişletme/daraltma
            $('.feature-card-header').click(function(e) {
                if (!$(e.target).is('input') && !$(e.target).is('label')) {
                    var body = $(this).next('.feature-card-body');
                    body.slideToggle(300);
                }
            });
            
            // Form gönderildiğinde yükleniyor göstergesi
            $('form').submit(function() {
                $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...').attr('disabled', true);
                return true;
            });
        });
    </script>
</body>
</html>
