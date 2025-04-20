import pandas as pd
import numpy as np

def calculate(df, window=14):
    """
    RSI hesaplama - pandas kullanarak
    """
    # Fiyat değişimini hesapla
    delta = df['close'].diff()
    
    # Pozitif ve negatif değişimleri ayır
    gain = delta.copy()
    loss = delta.copy()
    
    gain[gain < 0] = 0
    loss[loss > 0] = 0
    loss = abs(loss)
    
    # İlk ortalama kazanç ve kayıpları hesapla
    avg_gain = gain.rolling(window=window).mean()
    avg_loss = loss.rolling(window=window).mean()
    
    # Ortalama kazanç ve kayıpları düzgünleştir
    avg_gain = avg_gain.fillna(0)
    avg_loss = avg_loss.fillna(0)
    
    # RS (Relative Strength) hesapla
    rs = avg_gain / avg_loss.replace(0, 0.00001)  # Sıfıra bölmeyi önle
    
    # RSI hesapla: 100 - (100 / (1 + RS))
    rsi = 100 - (100 / (1 + rs))
    
    return rsi