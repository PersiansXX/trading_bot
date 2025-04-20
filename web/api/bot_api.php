<?php
/**
 * Bot API sınıfı
 * Web arayüzü ile Python botu arasındaki iletişimi sağlar
 */
class BotAPI {
    private $config_file;
    private $db;
    
    public function __construct() {
        $this->config_file = __DIR__.'/../../config/bot_config.json';
        $this->connectDB();
    }
    
    private function connectDB() {
        // Veritabanı bağlantısı kurulumu
        $db_host = "localhost";
        $db_user = "root";  // Veritabanı kullanıcınızı buraya yazın
        $db_pass = "Efsane44.";      // Veritabanı şifrenizi buraya yazın
        $db_name = "trading_bot_db";
        
        // MySQLi bağlantısı oluşturma
        $this->db = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Bağlantı kontrolü
        if ($this->db->connect_error) {
            die("Veritabanı bağlantısı başarısız: " . $this->db->connect_error);
        }
        
        // UTF-8 karakter seti
        $this->db->set_charset("utf8mb4");
    }
    
    /**
     * Bot durumunu kontrol eder
     */
    public function getStatus() {
        // Bot çalışıyor mu kontrolü (Linux/Unix sistemlerde)
        $pid_file = __DIR__.'/../../bot.pid';
        $running = false;
        
        if (file_exists($pid_file)) {
            $pid = trim(file_get_contents($pid_file));
            $running = $this->isProcessRunning($pid);
        }
        
        // Bot durumunu veritabanından da kontrol edebiliriz
        // Ya da doğrudan bir status JSON dosyası oluşturup kontrol edebiliriz
        
        // Örnek yanıt (gerçek duruma göre güncellenmeli)
        return [
            'running' => $running,
            'active_coins_count' => $this->getActiveCoinsCount(),
            'last_update' => date('Y-m-d H:i:s'),
            'base_currency_balance' => $this->getBalance()
        ];
    }
    
    /**
     * Aktif coinleri listeler
     */
    public function getActiveCoins() {
        $coins = [];
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM active_coins ORDER BY symbol");
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                // Her coin için son analiz bilgisini de al
                $analysis = $this->getCoinLastAnalysis($row['symbol']);
                
                $coins[] = [
                    'symbol' => $row['symbol'],
                    'price' => $analysis ? $analysis['price'] : 0,
                    'change_24h' => $this->calculate24hChange($row['symbol']),
                    'indicators' => $analysis ? json_decode($analysis['indicators_json'], true) : [],
                    'signal' => $analysis ? $analysis['trade_signal'] : null,
                    'reason' => $analysis ? $analysis['reason'] : null,
                    'last_updated' => $row['last_updated']
                ];
            }
        } catch (Exception $e) {
            // Hata durumunda boş dizi döndür
        }
        
        return $coins;
    }
    
    /**
     * Aktif coin sayısını döndürür
     */
    private function getActiveCoinsCount() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM active_coins");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Son işlemleri listeler
     */
    public function getRecentTrades($limit = 10) {
        $trades = [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM trades 
                ORDER BY timestamp DESC 
                LIMIT ?
            ");
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $trades[] = $row;
            }
        } catch (Exception $e) {
            // Hata durumunda boş dizi döndür
        }
        
        return $trades;
    }
    
    /**
     * Bugünkü işlem istatistiklerini döndürür
     */
    public function getTodayStats() {
        $today = date('Y-m-d');
        $stats = [
            'total_trades' => 0,
            'buy_trades' => 0,
            'sell_trades' => 0,
            'profit_loss' => 0
        ];
        
        try {
            // Toplam işlem sayısı
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN type = 'BUY' THEN 1 ELSE 0 END) as buys,
                    SUM(CASE WHEN type = 'SELL' THEN 1 ELSE 0 END) as sells
                FROM trades 
                WHERE DATE(timestamp) = ?
            ");
            $stmt->bind_param("s", $today);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $stats['total_trades'] = $row['total'] ?? 0;
            $stats['buy_trades'] = $row['buys'] ?? 0;
            $stats['sell_trades'] = $row['sells'] ?? 0;
            
            // Kar/Zarar hesaplama
            // Not: Bu basit bir örnektir, gerçek kar-zarar hesabı için daha detaylı bir algoritma gerekir
            $stmt = $this->db->prepare("
                SELECT profit_loss FROM trades 
                WHERE DATE(timestamp) = ?
            ");
            $stmt->bind_param("s", $today);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $stats['profit_loss'] += ($row['profit_loss'] ?? 0);
            }
        } catch (Exception $e) {
            // Hata durumunda varsayılan değerleri döndür
        }
        
        return $stats;
    }
    
    /**
     * Piyasa genel bakış bilgilerini döndürür
     */
    public function getMarketOverview() {
        // Bu fonksiyon normalde bir API'den piyasa verilerini çekmelidir
        // Şimdilik örnek veri döndürüyoruz
        return [
            'btc_dominance' => 48.5,
            'total_volume' => 98500000000,
            'best_performer' => [
                'symbol' => 'SOL/USDT',
                'change' => 12.5
            ],
            'worst_performer' => [
                'symbol' => 'DOGE/USDT',
                'change' => -8.7
            ]
        ];
    }
    
    /**
     * Belirli bir coin için son analiz bilgisini döndürür
     */
    private function getCoinLastAnalysis($symbol) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM coin_analysis 
                WHERE symbol = ? 
                ORDER BY timestamp DESC 
                LIMIT 1
            ");
            $stmt->bind_param("s", $symbol);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        } catch (Exception $e) {
            // Hata durumunda null döndür
        }
        
        return null;
    }
    
    /**
     * Belirli bir coin için 24 saatlik değişimi hesaplar
     */
    private function calculate24hChange($symbol) {
        try {
            $stmt = $this->db->prepare("
                SELECT price FROM coin_analysis 
                WHERE symbol = ? 
                ORDER BY timestamp DESC 
                LIMIT 1
            ");
            $stmt->bind_param("s", $symbol);
            $stmt->execute();
            $result = $stmt->get_result();
            $current = $result->fetch_assoc();
            
            // 24 saat önceki fiyatı bul
            $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $stmt = $this->db->prepare("
                SELECT price FROM coin_analysis 
                WHERE symbol = ? AND timestamp <= ? 
                ORDER BY timestamp DESC 
                LIMIT 1
            ");
            $stmt->bind_param("ss", $symbol, $yesterday);
            $stmt->execute();
            $result = $stmt->get_result();
            $old = $result->fetch_assoc();
            
            if ($current && $old && $old['price'] > 0) {
                return (($current['price'] - $old['price']) / $old['price']) * 100;
            }
        } catch (Exception $e) {
            // Hata durumunda 0 döndür
        }
        
        return 0;
    }
    
    /**
     * Bot ayarlarını döndürür
     */
    public function getSettings() {
        if (file_exists($this->config_file)) {
            return json_decode(file_get_contents($this->config_file), true);
        }
        
        // Dosya yoksa varsayılan ayarları döndür
        return [
            'exchange' => 'binance',
            'base_currency' => 'USDT',
            'min_volume' => 1000000,
            'max_coins' => 20,
            'min_trade_amount' => 10,
            'max_trade_amount' => 100,
            'position_size' => 0.05,
            'api_delay' => 0.5,
            'scan_interval' => 60,
            'indicators' => [
                'bollinger_bands' => [
                    'enabled' => true,
                    'window' => 20,
                    'num_std' => 2
                ],
                'rsi' => [
                    'enabled' => true,
                    'window' => 14
                ],
                'macd' => [
                    'enabled' => true,
                    'fast_period' => 12,
                    'slow_period' => 26,
                    'signal_period' => 9
                ],
                'moving_average' => [
                    'enabled' => true,
                    'short_window' => 50,
                    'long_window' => 200
                ]
            ],
            'strategies' => [
                'short_term' => [
                    'enabled' => true
                ],
                'trend_following' => [
                    'enabled' => true
                ],
                'breakout' => [
                    'enabled' => true
                ]
            ]
        ];
    }
    
    /**
     * Bot ayarlarını günceller
     */
    public function updateSettings($settings) {
        try {
            // Config klasörünün varlığını kontrol et
            $config_dir = dirname($this->config_file);
            if (!is_dir($config_dir)) {
                mkdir($config_dir, 0755, true);
            }
            
            file_put_contents($this->config_file, json_encode($settings, JSON_PRETTY_PRINT));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Aktif stratejileri döndürür
     */
    public function getActiveStrategies() {
        $settings = $this->getSettings();
        
        return [
            'short_term' => [
                'name' => 'Kısa Vadeli Strateji',
                'description' => 'RSI ve Bollinger Bands kullanarak kısa vadeli alım-satım sinyalleri üretir.',
                'enabled' => $settings['strategies']['short_term']['enabled'] ?? false
            ],
            'trend_following' => [
                'name' => 'Trend Takip Stratejisi',
                'description' => 'Hareketli ortalamaların kesişimlerini kullanarak trend yönünde işlem yapar.',
                'enabled' => $settings['strategies']['trend_following']['enabled'] ?? false
            ],
            'breakout' => [
                'name' => 'Kırılma Stratejisi',
                'description' => 'Fiyat destek ve direnç seviyelerini kırdığında işlem sinyalleri üretir.',
                'enabled' => $settings['strategies']['breakout']['enabled'] ?? false
            ]
        ];
    }
    
    /**
     * Bakiye bilgisini döndürür (örnek)
     */
    public function getBalance() {
        // Gerçek uygulamada bu bilgi bot tarafından bir dosyaya yazılmalı veya veritabanında saklanmalıdır
        return 1000.00; // Örnek değer
    }
    
    /**
     * Bir prosesin çalışıp çalışmadığını kontrol eder (Linux/Unix)
     */
    private function isProcessRunning($pid) {
        if (empty($pid)) return false;
        
        // Windows kontrolü
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("tasklist /FI \"PID eq $pid\" 2>&1", $output);
            return count($output) > 1;
        }
        
        // Linux/Unix kontrolü
        return file_exists("/proc/$pid");
    }
}