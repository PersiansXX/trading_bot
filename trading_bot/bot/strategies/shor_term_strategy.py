import numpy as np

def analyze(df, indicators):
    """
    Kısa vadeli alım-satım stratejisi
    """
    signal = None
    reason = ""
    
    # RSI aşırı alım/satım durumları
    if "rsi" in indicators:
        rsi = indicators["rsi"]
        
        if rsi < 30:
            signal = "BUY"
            reason = f"RSI aşırı satım bölgesinde ({rsi:.2f})"
        elif rsi > 70:
            signal = "SELL"
            reason = f"RSI aşırı alım bölgesinde ({rsi:.2f})"
    
    # Bollinger Bands sıkışma ve genişleme
    if signal is None and "bollinger_bands" in indicators:
        bb = indicators["bollinger_bands"]
        current_price = df["close"].iloc[-1]
        
        # Bollinger alt bandına yakınsa al
        if current_price <= bb["lower"] * 1.01:
            signal = "BUY"
            reason = "Fiyat Bollinger alt bandında"
        
        # Bollinger üst bandına yakınsa sat
        elif current_price >= bb["upper"] * 0.99:
            signal = "SELL"
            reason = "Fiyat Bollinger üst bandında"
    
    # MACD kesişimi
    if signal is None and "macd" in indicators:
        macd_data = indicators["macd"]
        
        # Son iki değeri kontrol etmek için DataFrame'den alalım
        macd_values = df["macd"].iloc[-2:].values if "macd" in df else None
        signal_values = df["signal"].iloc[-2:].values if "signal" in df else None
        
        # Eğer önceki değerler varsa kesişimi kontrol et
        if macd_values is not None and signal_values is not None:
            # MACD sinyal çizgisini yukarı kesiyor (Golden Cross)
            if macd_values[0] < signal_values[0] and macd_values[1] > signal_values[1]:
                signal = "BUY"
                reason = "MACD sinyal çizgisini yukarı kesiyor (Golden Cross)"
            
            # MACD sinyal çizgisini aşağı kesiyor (Death Cross)
            elif macd_values[0] > signal_values[0] and macd_values[1] < signal_values[1]:
                signal = "SELL"
                reason = "MACD sinyal çizgisini aşağı kesiyor (Death Cross)"
    
    return signal, reason