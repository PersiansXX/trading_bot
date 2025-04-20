#!/bin/bash

# Bot dizinine git
cd /var/www/html/bot

# Log hazırlığı
log_file="/var/www/html/bot.log"
error_log="/var/www/html/bot_error.log"
pid_file="/var/www/html/bot.pid"

# Başlatma bilgisini logla
echo "--- $(date) - Bot başlatma denemesi ---" >> $error_log

# Gerekli paketleri yükle
su - tradingbot -c "python3.8 -m pip install --upgrade pip >/dev/null 2>&1"
su - tradingbot -c "python3.8 -m pip install ccxt pandas numpy matplotlib websocket-client python-telegram-bot mysql-connector-python >/dev/null 2>&1"

# Bot başlatma
echo "Botu başlatmaya çalışıyorum..." >> $error_log
pid=$(su - tradingbot -c "cd /var/www/html/bot && python3.8 trading_bot.py >> $log_file 2>&1 & echo \$!")

# PID kontrolü ve kayıt
if [[ "$pid" =~ ^[0-9]+$ ]]; then
    echo $pid > $pid_file
    echo "Bot başlatıldı. PID: $pid" >> $error_log
    echo $pid  # PHP'ye PID'i döndür
else
    echo "Bot başlatılamadı!" >> $error_log
    exit 1
fi