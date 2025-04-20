#!/bin/bash

# Log dosyalarının konumları
log_file="/var/www/html/bot.log"
error_log="/var/www/html/bot_error.log"
pid_file="/var/www/html/bot.pid"

# Hata logunu başlat
echo "$(date '+%Y-%m-%d %H:%M:%S') - Başlatma betiği çalıştırıldı" >> $error_log

# Trading bot dizinine git
cd /var/www/html/bot

# Doğrudan tradingbot kullanıcısına geçip komutu çalıştır
# sudo -u yerine su -c kullanarak tty hatasını aşalım
su tradingbot -c "cd /var/www/html/bot && python3.8 trading_bot.py >> $log_file 2>> $error_log &"

# Başlayan bot'un PID'ini al - ps ile sorgulama yaparak
sleep 1
PID=$(ps -ef | grep "python3.8 trading_bot.py" | grep -v grep | awk '{print $2}')

if [ -n "$PID" ] && [ $PID -gt 0 ]; then
    echo $PID > $pid_file
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Bot başlatıldı (PID: $PID)" >> $error_log
    echo $PID
    exit 0
else
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Bot başlatılamadı veya PID alınamadı" >> $error_log
    exit 1
fi