<!DOCTYPE html>
<html>
<head>
    <title>Bot API Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        button { margin: 5px; padding: 8px 15px; }
        #response { background: #f3f3f3; padding: 15px; margin-top: 20px; border-radius: 5px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Bot API Test</h1>
    <div>
        <button id="btn-status">Bot Durumunu Kontrol Et</button>
        <button id="btn-start">Bot Başlat</button>
        <button id="btn-stop">Bot Durdur</button>
    </div>
    
    <h3>API Yanıtı:</h3>
    <div id="response">Henüz bir işlem yapılmadı</div>

    <script>
        function callAPI(action) {
            $('#response').text('Yükleniyor...');
            
            $.ajax({
                url: 'simple_bot_control.php?action=' + action,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#response').text(JSON.stringify(data, null, 4));
                },
                error: function(xhr, status, error) {
                    $('#response').text('HATA: ' + error + '\n\nYanıt: ' + xhr.responseText);
                }
            });
        }
        
        $('#btn-status').click(function() { callAPI('status'); });
        $('#btn-start').click(function() { callAPI('start'); });
        $('#btn-stop').click(function() { callAPI('stop'); });
    </script>
</body>
</html>