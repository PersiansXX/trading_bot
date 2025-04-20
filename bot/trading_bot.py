import time
import json
import logging
import ccxt
import pandas as pd
import numpy as np
import threading
from datetime import datetime
import telegram
import mysql.connector
from indicators import bollinger_bands, macd, rsi, moving_average
from strategies.short_term_strategy import analyze as short_term_strategy
from strategies.trend_following import analyze as trend_following
from strategies.breakout_detection import analyze as breakout_detection

# Loglama konfigürasyonu
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("bot.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("trading_bot")

class TradingBot:
    def __init__(self, config_file="../config/bot_config.json"):
        """
        Trading bot sınıfını başlat
        """
        self.load_config(config_file)
        self.initialize_exchange()
        self.initialize_db()
        self.initialize_telegram()
        self.running = False
        self.active_coins = []
        self.indicators_data = {}
        self.trades_history = []
    
    def load_config(self, config_file):
        """
        Ayarları yapılandırma dosyasından yükle
        """
        try:
            with open(config_file, 'r') as f:
                self.config = json.load(f)
            
            # API anahtarlarını yükle
            with open('config/api_keys.json', 'r') as f:
                self.api_keys = json.load(f)
            
            # Telegram ayarlarını yükle
            with open('config/telegram_config.json', 'r') as f:
                self.telegram_config = json.load(f)
                
            logger.info("Konfigürasyon başarıyla yüklendi")
        except Exception as e:
            logger.error(f"Konfigürasyon yükleme hatası: {str(e)}")
            raise
    
    def initialize_exchange(self):
        """
        Kripto para borsası bağlantısını başlat
        """
        try:
            exchange_id = self.config["exchange"]
            exchange_class = getattr(ccxt, exchange_id)
            self.exchange = exchange_class({
                'apiKey': self.api_keys[exchange_id]['api_key'],
                'secret': self.api_keys[exchange_id]['secret'],
                'timeout': 30000,
                'enableRateLimit': True,
            })
            logger.info(f"{exchange_id} borsasına bağlantı kuruldu")
        except Exception as e:
            logger.error(f"Borsa bağlantı hatası: {str(e)}")
            raise
    
    def initialize_db(self):
        """
        Veritabanı bağlantısını başlat
        """
        try:
            self.db = mysql.connector.connect(
                host=self.config["database"]["host"],
                user=self.config["database"]["user"],
                password=self.config["database"]["password"],
                database=self.config["database"]["dbname"]
            )
            self.cursor = self.db.cursor()
            logger.info("Veritabanı bağlantısı kuruldu")
        except Exception as e:
            logger.error(f"Veritabanı bağlantı hatası: {str(e)}")
            self.db = None
            self.cursor = None
    
    def initialize_telegram(self):
        """
        Telegram bot bağlantısını başlat
        """
        try:
            self.telegram_bot = telegram.Bot(token=self.telegram_config["token"])
            logger.info("Telegram bot bağlantısı kuruldu")
        except Exception as e:
            logger.error(f"Telegram bağlantı hatası: {str(e)}")
            self.telegram_bot = None
    
    def send_telegram_message(self, message):
        """
        Telegram üzerinden mesaj gönder
        """
        if self.telegram_bot and self.telegram_config.get("enabled", False):
            try:
                self.telegram_bot.send_message(
                    chat_id=self.telegram_config["chat_id"],
                    text=message
                )
            except Exception as e:
                logger.error(f"Telegram mesaj gönderme hatası: {str(e)}")
    
    def fetch_markets(self):
        """
        Borsadan mevcut piyasaları getir
        """
        try:
            markets = self.exchange.load_markets()
            base_currency = self.config["base_currency"]  # USDT, BTC, ETH vs.
            
            coins = []
            for symbol in markets:
                if "/" + base_currency in symbol:
                    coins.append(symbol)
            
            # Minimum işlem hacmine göre filtrele
            active_coins = []
            for coin in coins[:50]:  # En iyi 50 coin ile başla
                try:
                    ticker = self.exchange.fetch_ticker(coin)
                    if ticker['quoteVolume'] > self.config["min_volume"]:
                        active_coins.append(coin)
                except:
                    continue
            
            self.active_coins = active_coins[:self.config["max_coins"]]
            logger.info(f"{len(self.active_coins)} aktif coin bulundu")
            self.save_active_coins_to_db()
            
            return self.active_coins
        except Exception as e:
            logger.error(f"Piyasa verileri getirilirken hata: {str(e)}")
            return []
    
    def save_active_coins_to_db(self):
        """
        Aktif coinleri veritabanına kaydet
        """
        if not self.db:
            return
        
        try:
            now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            
            # Önce eski verileri temizle
            self.cursor.execute("DELETE FROM active_coins")
            
            # Yeni verileri ekle
            for coin in self.active_coins:
                self.cursor.execute(
                    "INSERT INTO active_coins (symbol, last_updated) VALUES (%s, %s)",
                    (coin, now)
                )
            
            self.db.commit()
        except Exception as e:
            logger.error(f"Veritabanına coin kaydı hatası: {str(e)}")
    
    def fetch_ohlcv(self, symbol, timeframe='1h', limit=100):
        """
        Belirli bir coin için OHLCV verileri getir
        """
        try:
            ohlcv = self.exchange.fetch_ohlcv(symbol, timeframe, limit=limit)
            df = pd.DataFrame(ohlcv, columns=['timestamp', 'open', 'high', 'low', 'close', 'volume'])
            df['timestamp'] = pd.to_datetime(df['timestamp'], unit='ms')
            return df
        except Exception as e:
            logger.error(f"{symbol} için OHLCV verileri alınamadı: {str(e)}")
            return None
    
    def calculate_indicators(self, df):
        """
        Teknik göstergeleri hesapla
        """
        indicators = {}
        
        # Bollinger Bands
        if self.config["indicators"]["bollinger_bands"]["enabled"]:
            window = self.config["indicators"]["bollinger_bands"]["window"]
            num_std = self.config["indicators"]["bollinger_bands"]["num_std"]
            df_bb = bollinger_bands.calculate(df, window, num_std)
            indicators["bollinger_bands"] = {
                "upper": df_bb["upper_band"].iloc[-1],
                "middle": df_bb["middle_band"].iloc[-1],
                "lower": df_bb["lower_band"].iloc[-1]
            }
        
        # RSI
        if self.config["indicators"]["rsi"]["enabled"]:
            window = self.config["indicators"]["rsi"]["window"]
            rsi_value = rsi.calculate(df, window)
            indicators["rsi"] = rsi_value.iloc[-1]
        
        # MACD
        if self.config["indicators"]["macd"]["enabled"]:
            fast = self.config["indicators"]["macd"]["fast_period"]
            slow = self.config["indicators"]["macd"]["slow_period"]
            signal = self.config["indicators"]["macd"]["signal_period"]
            macd_data = macd.calculate(df, fast, slow, signal)
            indicators["macd"] = {
                "macd": macd_data["macd"].iloc[-1],
                "signal": macd_data["signal"].iloc[-1],
                "histogram": macd_data["histogram"].iloc[-1]
            }
        
        # Hareketli Ortalama
        if self.config["indicators"]["moving_average"]["enabled"]:
            short_window = self.config["indicators"]["moving_average"]["short_window"]
            long_window = self.config["indicators"]["moving_average"]["long_window"]
            ma_data = moving_average.calculate(df, short_window, long_window)
            indicators["moving_average"] = {
                "short_ma": ma_data["short_ma"].iloc[-1],
                "long_ma": ma_data["long_ma"].iloc[-1]
            }
        
        return indicators
    
    def analyze_market(self, symbol):
        """
        Belirli bir coin için piyasa analizi yap
        """
        df = self.fetch_ohlcv(symbol)
        if df is None:
            return None
        
        indicators = self.calculate_indicators(df)
        self.indicators_data[symbol] = indicators
        
        # Strateji analizi
        signal = None
        reason = ""

        # Kısa vadeli strateji
        if self.config["strategies"]["short_term"]["enabled"]:
            # Önceki (hatalı): signal, reason = short_term_strategy.analyze(df, indicators)
            # Yeni (düzeltilmiş):
            signal, reason = short_term_strategy(df, indicators)

        # Trend takip stratejisi
        if signal is None and self.config["strategies"]["trend_following"]["enabled"]:
            # Önceki (hatalı): signal, reason = trend_following.analyze(df, indicators)
            # Yeni (düzeltilmiş):
            signal, reason = trend_following(df, indicators)

        # Kırılma stratejisi  
        if signal is None and self.config["strategies"]["breakout"]["enabled"]:
            # Önceki (hatalı): signal, reason = breakout_detection.analyze(df, indicators)
            # Yeni (düzeltilmiş):
            signal, reason = breakout_detection(df, indicators)
        
        return {
            "symbol": symbol,
            "indicators": indicators,
            "signal": signal,
            "reason": reason,
            "price": df["close"].iloc[-1],
            "timestamp": datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
    
    def execute_trade(self, symbol, signal, price):
        """
        Alım-satım işlemini gerçekleştir
        """
        if signal == "BUY":
            # Bakiye kontrolü
            balance = self.get_balance()
            if balance < self.config["min_trade_amount"]:
                logger.warning(f"Yetersiz bakiye: {balance}")
                return False
            
            # Alım miktarı hesapla
            amount = min(balance * self.config["position_size"], self.config["max_trade_amount"]) / price
            
            try:
                # Alım emri ver
                order = self.exchange.create_market_buy_order(symbol, amount)
                
                # İşlemi kaydet
                trade = {
                    "symbol": symbol,
                    "type": "BUY",
                    "price": price,
                    "amount": amount,
                    "timestamp": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                    "order_id": order["id"]
                }
                self.trades_history.append(trade)
                self.save_trade_to_db(trade)
                
                # Telegram bildirimi
                message = f"🟢 ALIM: {symbol}\nFiyat: {price}\nMiktar: {amount}\nSebep: {signal}"
                self.send_telegram_message(message)
                
                logger.info(f"Alım emri gerçekleştirildi: {symbol} - {amount}")
                return True
            except Exception as e:
                logger.error(f"Alım emri hatası: {str(e)}")
                return False
                
        elif signal == "SELL":
            # Coin bakiyesi kontrolü
            coin_symbol = symbol.split('/')[0]
            coin_balance = self.get_coin_balance(coin_symbol)
            
            if coin_balance <= 0:
                logger.warning(f"Satılacak {coin_symbol} yok")
                return False
            
            try:
                # Satış emri ver
                order = self.exchange.create_market_sell_order(symbol, coin_balance)
                
                # İşlemi kaydet
                trade = {
                    "symbol": symbol,
                    "type": "SELL",
                    "price": price,
                    "amount": coin_balance,
                    "timestamp": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                    "order_id": order["id"]
                }
                self.trades_history.append(trade)
                self.save_trade_to_db(trade)
                
                # Telegram bildirimi
                message = f"🔴 SATIŞ: {symbol}\nFiyat: {price}\nMiktar: {coin_balance}\nSebep: {signal}"
                self.send_telegram_message(message)
                
                logger.info(f"Satış emri gerçekleştirildi: {symbol} - {coin_balance}")
                return True
            except Exception as e:
                logger.error(f"Satış emri hatası: {str(e)}")
                return False
        
        return False
    
    def save_trade_to_db(self, trade):
        """
        Alım-satım işlemini veritabanına kaydet
        """
        if not self.db:
            return
        
        try:
            query = """
                INSERT INTO trades (symbol, type, price, amount, timestamp, order_id)
                VALUES (%s, %s, %s, %s, %s, %s)
            """
            values = (
                trade["symbol"],
                trade["type"],
                trade["price"],
                trade["amount"],
                trade["timestamp"],
                trade["order_id"]
            )
            self.cursor.execute(query, values)
            self.db.commit()
        except Exception as e:
            logger.error(f"İşlem veritabanı kaydı hatası: {str(e)}")
    
    def get_balance(self):
        """
        Baz para biriminin bakiyesini getir
        """
        try:
            balance = self.exchange.fetch_balance()
            return balance["total"][self.config["base_currency"]]
        except Exception as e:
            logger.error(f"Bakiye sorgusu hatası: {str(e)}")
            return 0
    
    def get_coin_balance(self, coin):
        """
        Belirli bir coin'in bakiyesini getir
        """
        try:
            balance = self.exchange.fetch_balance()
            return balance["total"].get(coin, 0)
        except Exception as e:
            logger.error(f"{coin} bakiye sorgusu hatası: {str(e)}")
            return 0
    
    def save_analysis_to_db(self, analysis):
        """
        Coin analizini veritabanına kaydet
        """
        if not self.db or not analysis:
            return
        
        try:
            query = """
                INSERT INTO coin_analysis (symbol, price, signal, reason, timestamp, indicators_json)
                VALUES (%s, %s, %s, %s, %s, %s)
            """
            values = (
                analysis["symbol"],
                analysis["price"],
                analysis["signal"] if analysis["signal"] else "NEUTRAL",
                analysis["reason"],
                analysis["timestamp"],
                json.dumps(analysis["indicators"])
            )
            self.cursor.execute(query, values)
            self.db.commit()
        except Exception as e:
            logger.error(f"Analiz veritabanı kaydı hatası: {str(e)}")
    
    def monitor_coins(self):
        """
        Tüm coinleri sürekli olarak izle ve analiz et
        """
        while self.running:
            for symbol in self.active_coins:
                try:
                    analysis = self.analyze_market(symbol)
                    if analysis:
                        self.save_analysis_to_db(analysis)
                        
                        # İşlem sinyali varsa alım-satım yap
                        if analysis["signal"] in ["BUY", "SELL"]:
                            self.execute_trade(symbol, analysis["signal"], analysis["price"])
                except Exception as e:
                    logger.error(f"{symbol} analiz edilirken hata: {str(e)}")
                
                # Rate limit aşımını önlemek için bekle
                time.sleep(self.config["api_delay"])
            
            # Tüm coinler kontrol edildikten sonra kısa bir süre bekle
            time.sleep(self.config["scan_interval"])
    
    def start(self):
        """
        Botu başlat
        """
        if self.running:
            logger.warning("Bot zaten çalışıyor")
            return False
        
        logger.info("Bot başlatılıyor...")
        self.running = True
        
        # Aktif coinleri yükle
        self.fetch_markets()
        
        # Takip thread'ini başlat
        self.monitor_thread = threading.Thread(target=self.monitor_coins)
        self.monitor_thread.daemon = True
        self.monitor_thread.start()
        
        # Telegram bildirimi
        self.send_telegram_message("🤖 Trading bot başlatıldı!")
        
        return True
    
    def stop(self):
        """
        Botu durdur
        """
        if not self.running:
            logger.warning("Bot zaten durmuş durumda")
            return False
        
        logger.info("Bot durduruluyor...")
        self.running = False
        
        # Thread'in durmasını bekle
        if hasattr(self, 'monitor_thread'):
            self.monitor_thread.join(timeout=5)
        
        # Veritabanı bağlantısını kapat
        if self.db:
            self.cursor.close()
            self.db.close()
        
        # Telegram bildirimi
        self.send_telegram_message("⛔ Trading bot durduruldu!")
        
        return True
    
    def get_status(self):
        """
        Botun mevcut durumunu getir
        """
        return {
            "running": self.running,
            "active_coins_count": len(self.active_coins),
            "last_update": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            "base_currency_balance": self.get_balance()
        }

if __name__ == "__main__":
    bot = TradingBot()
    bot.start()
    
    # Ctrl+C ile durdurma
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        bot.stop()
        print("Bot durduruldu")