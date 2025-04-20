def analyze(df, indicators):
    """
    Fiyat kırılımı stratejisi - Bollinger Bands tabanlı
    """
    if 'upper_band' not in indicators or 'lower_band' not in indicators:
        return None, "Bollinger Bands hesaplanamadı"

    upper_band = indicators['upper_band']
    lower_band = indicators['lower_band']
    close = df['close'].values
    
    # Bollinger Bands kırılımı
    if close[-1] > upper_band[-1] and close[-2] <= upper_band[-2]:
        return "SELL", f"Fiyat üst bandı yukarı kırdı: {close[-1]:.2f} > {upper_band[-1]:.2f}"
    elif close[-1] < lower_band[-1] and close[-2] >= lower_band[-2]:
        return "BUY", f"Fiyat alt bandı aşağı kırdı: {close[-1]:.2f} < {lower_band[-1]:.2f}"
    
    return None, ""