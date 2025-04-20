import pandas as pd

def calculate(df, window=20, num_std=2):
    """
    Bollinger Bands hesaplama - pandas kullanarak
    """
    # Orta band (SMA)
    middle_band = df['close'].rolling(window=window).mean()
    
    # Standart sapma
    std_dev = df['close'].rolling(window=window).std()
    
    # Üst ve alt bantlar
    upper_band = middle_band + (std_dev * num_std)
    lower_band = middle_band - (std_dev * num_std)
    
    # Sonuçları DataFrame'e ekle
    result = pd.DataFrame({
        'middle_band': middle_band,
        'upper_band': upper_band,
        'lower_band': lower_band
    })
    
    return result