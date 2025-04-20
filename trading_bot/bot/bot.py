#!/usr/bin/env python3
"""
Trading Bot - Basit Demo Sürümü
Sadece web panel ile entegrasyon gösterimi için
"""
import os
import sys
import time
import json
import signal
import logging
import random
from datetime import datetime, timedelta

# Log yapılandırması
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler('../bot.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

logger = logging.getLogger('trading_bot')

# Sinyal yönetimi - Temiz şekilde kapatılabilmesi için
def signal_handler(sig, frame):
    logger.info("Bot kapatılıyor...")
    sys.exit(0)

signal.signal(signal.SIGINT, signal_handler)
signal.signal(signal.SIGTERM, signal_handler)

# Bot yapılandırma dosyası
CONFIG_FILE = '../config/bot_config.json'

def load_config():
    """Yapılandırma dosyasını yükler"""
    try:
        if os.path.exists(CONFIG_FILE):
            with open(CONFIG_FILE, 'r') as f:
                return json.load(f)
        else:
            # Varsayılan ayarlar
            config = {
                'exchange': 'binance',
                'base_currency': 'USDT',
                'min_volume': 1000000,
                'max_coins': 20,
                'min_trade_amount': 10,
                'max_trade_amount': 100,
                'position_size': 0.05,
                'api_delay': 0.5,
                'scan_interval': 60,
                'indicators': {
                    'bollinger_bands': {'enabled': True, 'window': 20, 'num_std': 2},
                    'rsi': {'enabled': True, 'window': 14},
                    'macd': {'enabled': True, 'fast_period': 12, 'slow_period': 26, 'signal_period': 9},
                    'moving_average': {'enabled': True, 'short_window': 50, 'long_window': 200}
                },
                'strategies': {
                    'short_term': {'enabled': True},
                    'trend_following': {'enabled': True},
                    'breakout': {'enabled': True}
                }
            }
            # Yapılandırma klasörünü oluştur
            os.makedirs(os.path.dirname(CONFIG_FILE), exist_ok=True)
            with open(CONFIG_FILE, 'w') as f:
                json.dump(config, f, indent=2)
            return config
    except Exception as e:
        logger.error(f"Yapılandırma dosyası yüklenirken hata: {str(e)}")
        return {}

def main():
    """Bot'un ana işlev döngüsü"""
    logger.info("Trading Bot başlatılıyor...")
    
    # PID dosyasını oluştur
    with open('../bot.pid', 'w') as f:
        f.write(str(os.getpid()))
    
    config = load_config()
    
    # Yapılandırma bilgilerini göster
    logger.info(f"Borsa: {config.get('exchange', 'binance')}")
    logger.info(f"Baz Para Birimi: {config.get('base_currency', 'USDT')}")
    logger.info(f"İndikatörler: {', '.join([k for k,v in config.get('indicators', {}).items() if v.get('enabled', False)])}")
    logger.info(f"Stratejiler: {', '.join([k for k,v in config.get('strategies', {}).items() if v.get('enabled', False)])}")
    
    try:
        # Ana döngü
        while True:
            try:
                # Yeniden yapılandırma kontrolü
                config = load_config()
                
                # Simülasyon: Coinleri tara
                logger.info("Coinler taranıyor...")
                time.sleep(2)
                
                # Simülasyon: Bazı alım-satım sinyallerini tetikle
                if random.random() > 0.7:  # %30 olasılıkla
                    coin = random.choice(['BTC', 'ETH', 'ADA', 'SOL', 'DOT', 'XRP', 'BNB'])
                    action = random.choice(['BUY', 'SELL'])
                    price = round(random.uniform(100, 50000), 2)
                    
                    if action == 'BUY':
                        logger.info(f"ALIŞ sinyali: {coin}/USDT @ {price} USDT")
                    else:
                        profit = round(random.uniform(-5, 15), 2)
                        logger.info(f"SATIŞ sinyali: {coin}/USDT @ {price} USDT (Kar/Zarar: {profit}%)")
                
                # Simülasyon: Farklı log seviyeleri
                log_type = random.choices(
                    ['INFO', 'WARNING', 'ERROR'], 
                    weights=[0.8, 0.15, 0.05], 
                    k=1
                )[0]
                
                if log_type == 'WARNING':
                    logger.warning("Piyasa volatilitesi yüksek, işlem boyutları azaltıldı.")
                elif log_type == 'ERROR':
                    logger.error("API'ye bağlanırken hata oluştu. Yeniden deneniyor...")
                
                # Yapılandırılmış tarama aralığı
                sleep_time = config.get('scan_interval', 60)
                logger.info(f"{sleep_time} saniye bekleniyor...")
                time.sleep(sleep_time)
                
            except Exception as e:
                logger.error(f"İşlem döngüsünde hata: {str(e)}")
                time.sleep(10)  # Hata durumunda 10 saniye bekle
                
    except KeyboardInterrupt:
        # Klavye kesintisi (Ctrl+C)
        logger.info("Bot kullanıcı tarafından durduruldu.")
    except Exception as e:
        logger.error(f"Beklenmeyen hata: {str(e)}")
    finally:
        # PID dosyasını temizle
        if os.path.exists('../bot.pid'):
            os.remove('../bot.pid')
        logger.info("Bot kapatıldı.")

if __name__ == "__main__":
    main()