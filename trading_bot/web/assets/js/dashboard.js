$(document).ready(function() {
    // Bot durumunu kontrol et
    checkBotStatus();
    
    // Bot başlatma butonuna tıklandığında
    $(document).on('click', '.start-bot', function() {
        startBot();
    });
    
    // Bot durdurma butonuna tıklandığında
    $(document).on('click', '.stop-bot', function() {
        stopBot();
    });
    
    // Log yenileme butonuna tıklandığında
    $('.refresh-logs').click(function() {
        refreshLogs();
    });
    
    // Strateji değişikliklerini izle
    $('.strategy-toggle').change(function() {
        var strategy = $(this).data('strategy');
        var enabled = $(this).prop('checked');
        
        $.ajax({
            url: 'api/update_strategy.php',
            type: 'POST',
            data: {
                strategy: strategy,
                enabled: enabled ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Strateji güncellendi: ' + response.message);
                } else {
                    showAlert('danger', 'Hata: ' + response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Sunucu hatası');
            }
        });
    });
    
    // Her 60 saniyede bir bot durumunu kontrol et
    setInterval(function() {
        checkBotStatus();
    }, 60000);
    
    // Her 10 saniyede bir logları yenile
    setInterval(function() {
        refreshLogs();
    }, 10000);
});

/**
 * Bot durumunu kontrol eder
 */
function checkBotStatus() {
    $.ajax({
        url: 'bot_control.php',
        type: 'GET',
        data: { action: 'status' },
        dataType: 'json',
        success: function(response) {
            updateStatusUI(response.running);
        },
        error: function() {
            showAlert('danger', 'Bot durumu alınamadı! Sunucu hatası.');
        }
    });
}

/**
 * Botu başlatır
 */
function startBot() {
    $.ajax({
        url: 'bot_control.php',
        type: 'GET',
        data: { action: 'start' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateStatusUI(true);
                showAlert('success', response.message);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Bot başlatılamadı! Sunucu hatası.');
        }
    });
}

/**
 * Botu durdurur
 */
function stopBot() {
    $.ajax({
        url: 'bot_control.php',
        type: 'GET',
        data: { action: 'stop' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateStatusUI(false);
                showAlert('success', response.message);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Bot durdurulamadı! Sunucu hatası.');
        }
    });
}

/**
 * Logları yeniler
 */
function refreshLogs() {
    $.ajax({
        url: 'api/get_logs.php',
        type: 'GET',
        success: function(logs) {
            $('.log-container').html(logs);
            // Log container'ı en aşağıya kaydır
            var logContainer = $('.log-container');
            logContainer.scrollTop(logContainer.prop('scrollHeight'));
        }
    });
}

/**
 * Bot durum bilgisine göre UI'ı günceller
 */
function updateStatusUI(isRunning) {
    if (isRunning) {
        // Bot çalışıyor
        $('.stop-bot').removeClass('d-none');
        $('.start-bot').addClass('d-none');
        $('h6:contains("Durum:")').html('Durum: <span class="text-success">Çalışıyor</span>');
        $('h3:has(.fa-play-circle, .fa-stop-circle)').html('<i class="fas fa-play-circle text-success"></i>');
    } else {
        // Bot durdu
        $('.start-bot').removeClass('d-none');
        $('.stop-bot').addClass('d-none');
        $('h6:contains("Durum:")').html('Durum: <span class="text-danger">Durdu</span>');
        $('h3:has(.fa-play-circle, .fa-stop-circle)').html('<i class="fas fa-stop-circle text-danger"></i>');
    }
}

/**
 * Alert mesajı gösterir
 */
function showAlert(type, message) {
    var alert = $('<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                  message +
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                  '<span aria-hidden="true">&times;</span></button></div>');
    
    // Önceki alert'ı kaldır ve yenisini ekle
    $('.alert').remove();
    $('.card-header').after(alert);
    
    // 5 saniye sonra alert'ı gizle
    setTimeout(function() {
        alert.alert('close');
    }, 5000);
}