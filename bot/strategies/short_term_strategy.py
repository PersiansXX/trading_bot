def analyze(df, indicators):
    """
    Kısa vadeli strateji analizi
    """
    # Stratejinizin içeriği burada olmalı...
    # Örnek:
    if 'rsi' not in indicators:
        return None, "RSI hesaplanamadı"
        
    rsi = indicators['rsi']
    last_rsi = rsi[-1] if isinstance(rsi, list) else rsi
    
    if last_rsi > 70:
        return "SELL", f"RSI yüksek seviyede: {last_rsi:.2f}"
    elif last_rsi < 30:
        return "BUY", f"RSI düşük seviyede: {last_rsi:.2f}"
        
    return None, ""
