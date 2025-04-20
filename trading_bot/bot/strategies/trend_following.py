import numpy as np

def analyze(df, indicators):
    """
    Trend takip eden strateji
    """
    signal = None
    reason = ""
    
    # Hareketli ortalama kesişimleri
    if "moving_average" in indicators:
        ma_data = indicators["moving_average"]
        short_ma = ma_data["short_ma"]
        long_ma = ma_data["long_ma"]
        
        # Son iki değeri kontrol etmek için DataFrame'den alalım
        if len(df) >= 2:
            # Kısa MA'nın uzun MA'yı yukarı kesmesi (Golden Cross)
            if df['short_ma'].iloc[-2] < df['long_ma'].iloc[-2] and short_ma > long_ma:
                signal = "BUY"
                reason = "Kısa MA uzun MA'yı yukarı kesiyor (Golden Cross)"
            
            # Kısa MA'nın uzun MA'yı aşağı kesmesi (Death Cross)
            elif df['short_ma'].iloc[-2] > df['long_ma'].iloc[-2] and short_ma < long_ma:
                signal = "SELL"
                reason = "Kısa MA uzun MA'yı aşağı kesiyor (Death Cross)"
    
    # Yükselen/Düşen trend tespiti
    if signal is None and len(df) >= 14:  # En az 14 mum kontrolü
        # Son 10 mumun kapanış fiyatı eğilimini kontrol et
        prices = df['close'].iloc[-10:].values
        slope = np.polyfit(range(len(prices)), prices, 1)[0]  # Eğilim eğimi
        
        # Güçlü yükselen trend
        if slope > 0 and slope / prices.mean() > 0.005:  # %0.5'den fazla günlük artış
            current_price = df['close'].iloc[-1]
            # Kısa vadeli düzeltme sonrası al
            if current_price < prices.mean() * 0.98:  # Ortalamadan %2 düşükse
                signal = "BUY"
                reason = "Yükselen trend içinde düzeltme sonrası alım fırsatı"
        
        # Güçlü düşen trend
        elif slope < 0 and abs(slope) / prices.mean() > 0.005:  # %0.5'den fazla günlük düşüş
            signal = "SELL"
            reason = "Güçlü düşen trend tespit edildi"
    
    return signal, reason