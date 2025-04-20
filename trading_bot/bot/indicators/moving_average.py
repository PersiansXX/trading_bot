import pandas as pd

def calculate(df, short_window=10, long_window=50):
    """
    Hareketli ortalama hesaplama - pandas kullanarak
    """
    # Kısa vadeli hareketli ortalama (SMA)
    short_ma = df['close'].rolling(window=short_window).mean()
    
    # Uzun vadeli hareketli ortalama (SMA)
    long_ma = df['close'].rolling(window=long_window).mean()
    
    # Sonuçları DataFrame'e ekle
    result = pd.DataFrame({
        'short_ma': short_ma,
        'long_ma': long_ma
    })
    
    return result