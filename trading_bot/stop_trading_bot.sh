#!/bin/bash

# Log dosyaları
error_log="/var/www/html/bot_error.log"
pid_file="/var/www/html/bot.pid"

# Hata logunu başlat
echo "$(date '+%Y-%m-%d %H:%M:%S') - Durdurma betiği çalıştırıldı" >> $error_log

if [ -f $pid_file ]; then
    PID=$(cat $pid_file)
    
    if [ -n "$PID" ] && [ "$PID" -gt 0 ]; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') - PID: $PID sonlandırılıyor" >> $error_log
        
        # Kill komutunu tradingbot kullanıcısı ile çalıştırmaya çalış
        su tradingbot -c "kill $PID" >> $error_log 2>&1
        
        # Biraz bekle
        sleep 3
        
        # Hala çalışıyor mu kontrol et
        if ps -p $PID > /dev/null 2>&1; then
            echo "$(date '+%Y-%m-%d %H:%M:%S') - Normal sonlandırma başarısız, zorunlu sonlandırma" >> $error_log
            # Su komutuyla kill -9 çalıştır
            su tradingbot -c "kill -9 $PID" >> $error_log 2>&1
            
            # Hala çalışıyor mu?
            sleep 1
            if ps -p $PID > /dev/null 2>&1; then
                # En son çare olarak pkill kullan
                pkill -f "python3.8 trading_bot.py"
            fi
        fi
        
        # PID dosyasını sil
        rm $pid_file
        echo "$(date '+%Y-%m-%d %H:%M:%S') - Bot durduruldu" >> $error_log
        echo "OK"
        exit 0
    else
        echo "$(date '+%Y-%m-%d %H:%M:%S') - Geçersiz PID: $PID" >> $error_log
        rm $pid_file
        exit 1
    fi
else
    echo "$(date '+%Y-%m-%d %H:%M:%S') - PID dosyası bulunamadı" >> $error_log
    exit 1
fi