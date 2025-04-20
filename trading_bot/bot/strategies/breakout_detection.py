import numpy as np

def analyze(df, indicators):
    """
    Fiyat kırılmalarını tespit eden strateji
    """
    signal = None
    reason = ""
    
    if len(df) < 20:
        return None, "Yetersiz veri"
    
    # Son 20 mumun en yüksek ve en düşük noktaları
    recent_high = df['high'].iloc[-20:].max()
    recent_low = df['low'].iloc[-20:].min()
    
    current_price = df['close'].iloc[-1]
    previous_price = df['close'].iloc[-2]
    
    # Direnç kırılması (Bullish Breakout)
    if previous_price < recent_high and current_price > recent_high:
        signal = "BUY"
        reason = f"Direnç kırıldı ({recent_high:.2f})"
    
    # Destek kırılması (Bearish Breakout)
    elif previous_price > recent_low and current_price < recent_low:
        signal = "SELL"
        reason = f"Destek kırıldı ({recent_low:.2f})"
    
    # Volatilite patlaması tespiti
    if signal is None:
        # Son 5 mumun ortalama true range değeri
        atr = calculate_atr(df, 14)
        
        # Son mumun range'i ortalama true range'in 2 katından fazlaysa
        last_candle_range = df['high'].iloc[-1] - df['low'].iloc[-1]
        if last_candle_range > 2 * atr:
            # Eğer yükselen mum ise
            if df['close'].iloc[-1] > df['open'].iloc[-1]:
                signal = "BUY"
                reason = "Volatilite patlaması ve yükselen mum"
            # Eğer düşen mum ise
            else:
                signal = "SELL"
                reason = "Volatilite patlaması ve düşen mum"
    
    return signal, reason

def calculate_atr(df, period=14):
    """
    Average True Range (ATR) hesapla
    """
    high = df['high']
    low = df['low']
    close = df['close']
    
    # True Range hesapla
    tr1 = high - low
    tr2 = abs(high - close.shift())
    tr3 = abs(low - close.shift())
    
    tr = pd.DataFrame({'tr1': tr1, 'tr2': tr2, 'tr3': tr3}).max(axis=1)
    
    # Average True Range
    atr = tr.rolling(window=period).mean().iloc[-1]
    
    return atr