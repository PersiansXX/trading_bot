<?php
session_start();

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Veritabanı ve API bağlantısı
require_once 'api/bot_api.php';
$bot_api = new BotAPI();

// Sayfa ayarları
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
$offset = ($page - 1) * $limit;

// Tüm işlemleri al
$trades = $bot_api->getRecentTrades($limit * 2); // Şimdilik bot API ile 2 sayfa kadar veri alalım

// İstatistikleri al
$today_stats = $bot_api->getTodayStats();

// Sayfa başlığı
$page_title = 'İşlem Geçmişi';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sol Menü -->
            <div class="col-md-2">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Ana İçerik -->
            <div class="col-md-10">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $today_stats['total_trades']; ?></h3>
                                <h6>Toplam İşlem</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $today_stats['buy_trades']; ?></h3>
                                <h6>Alım İşlemleri</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $today_stats['sell_trades']; ?></h3>
                                <h6>Satım İşlemleri</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card <?php echo $today_stats['profit_loss'] >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                            <div class="card-body text-center">
                                <h3><?php echo number_format($today_stats['profit_loss'], 2); ?> USDT</h3>
                                <h6>Günlük Kar/Zarar</h6>
                            </div>
                        </div>
                    </div>
                </div>
            
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> <?php echo $page_title; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tarih</th>
                                        <th>Sembol</th>
                                        <th>Tür</th>
                                        <th>Fiyat</th>
                                        <th>Miktar</th>
                                        <th>Toplam</th>
                                        <th>Kar/Zarar</th>
                                        <th>Strateji</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($trades)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">İşlem geçmişi bulunamadı</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($trades as $trade): ?>
                                            <tr>
                                                <td><?php echo $trade['id']; ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($trade['timestamp'])); ?></td>
                                                <td><strong><?php echo $trade['symbol']; ?></strong></td>
                                                <td>
                                                    <?php if ($trade['type'] == 'BUY'): ?>
                                                        <span class="badge badge-success">ALIŞ</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">SATIŞ</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo number_format($trade['price'], 8); ?></td>
                                                <td><?php echo number_format($trade['amount'], 8); ?></td>
                                                <td><?php echo number_format($trade['total'], 8); ?> <?php echo $trade['base_currency']; ?></td>
                                                <td class="<?php echo $trade['profit_loss'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php if ($trade['type'] == 'SELL'): ?>
                                                        <?php echo number_format($trade['profit_loss'], 8); ?> <?php echo $trade['base_currency']; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $trade['strategy']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&limit=<?php echo $limit; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= ceil(count($trades) / $limit)): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < ceil(count($trades) / $limit)): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&limit=<?php echo $limit; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>