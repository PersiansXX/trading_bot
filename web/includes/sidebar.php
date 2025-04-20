<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Menü</h5>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <a href="index.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Ana Sayfa
            </a>
            <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="coins.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'coins.php' ? 'active' : ''; ?>">
                <i class="fas fa-coins"></i> Coinler
            </a>
            <a href="trades.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'trades.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i> İşlemler
            </a>
            <a href="reports.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Raporlar
            </a>
            <a href="settings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Ayarlar
            </a>
        </div>
    </div>
    
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small>Bot Durumu:</small>
            <?php
            // Bot API'ye bağlan ve durum kontrolü yap
            require_once 'api/bot_api.php';
            $bot_api = new BotAPI();
            $status = $bot_api->getStatus();
            ?>
            <span class="badge <?php echo $status['running'] ? 'badge-success' : 'badge-danger'; ?>">
                <?php echo $status['running'] ? 'Çalışıyor' : 'Durdu'; ?>
            </span>
        </div>
    </div>
</div>