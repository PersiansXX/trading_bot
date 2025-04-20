import pandas as pd
import numpy as np

def calculate(df, short_window=50, long_window=200):
    """
    Kısa ve uzun vadeli hareketli ortalamaları hesapla
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi
    short_window (int): Kısa vadeli MA periyodu
    long_window (int): Uzun vadeli MA periyodu
    
    Dönen değer:
    DataFrame: short_ma, long_ma sütunlarını içeren df
    """
    if len(df) < max(short_window, long_window):
        return None
    
    # Basit hareketli ortalamalar (SMA)
    short_ma = df['close'].rolling(window=short_window, min_periods=1).mean()
    long_ma = df['close'].rolling(window=long_window, min_periods=1).mean()
    
    # Verileri birleştir
    df_result = pd.DataFrame(index=df.index)
    df_result['short_ma'] = short_ma
    df_result['long_ma'] = long_ma
    
    return df_result