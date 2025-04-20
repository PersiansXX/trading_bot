import pandas as pd
import numpy as np

def calculate(df, window=14):
    """
    Göreceli Güç İndeksi (RSI) hesapla
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi
    window (int): RSI periyot uzunluğu
    
    Dönen değer:
    Series: RSI değerleri
    """
    if len(df) <= window:
        return None
    
    # Fiyat değişimlerini hesapla
    close_delta = df['close'].diff()
    
    # Pozitif ve negatif fiyat değişimleri
    up = close_delta.clip(lower=0)
    down = -1 * close_delta.clip(upper=0)
    
    # EMA tabanlı ortalama hesapla
    ma_up = up.ewm(com=window-1, adjust=True, min_periods=window).mean()
    ma_down = down.ewm(com=window-1, adjust=True, min_periods=window).mean()
    
    # Göreceli güç hesapla
    rs = ma_up / ma_down
    
    # RSI hesapla
    rsi = 100 - (100 / (1 + rs))
    
    return rsi