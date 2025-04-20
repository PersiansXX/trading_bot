import pandas as pd

def calculate(df, fast_period=12, slow_period=26, signal_period=9):
    """
    MACD hesaplama - pandas kullanarak
    """
    # Hızlı EMA hesapla
    fast_ema = df['close'].ewm(span=fast_period, adjust=False).mean()
    
    # Yavaş EMA hesapla
    slow_ema = df['close'].ewm(span=slow_period, adjust=False).mean()
    
    # MACD Çizgisi
    macd_line = fast_ema - slow_ema
    
    # Sinyal Çizgisi (MACD'nin EMA'sı)
    signal_line = macd_line.ewm(span=signal_period, adjust=False).mean()
    
    # MACD Histogramı
    histogram = macd_line - signal_line
    
    # Sonuçları DataFrame'e ekle
    result = pd.DataFrame({
        'macd': macd_line,
        'signal': signal_line,
        'histogram': histogram
    })
    
    return result