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

// Tarih aralığı filtreleme
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Özet raporları al (örnek veri, normalde veritabanından çekilir)
$daily_profits = [
    ['date' => date('Y-m-d', strtotime('-6 days')), 'profit' => 5.23],
    ['date' => date('Y-m-d', strtotime('-5 days')), 'profit' => -2.15],
    ['date' => date('Y-m-d', strtotime('-4 days')), 'profit' => 8.47],
    ['date' => date('Y-m-d', strtotime('-3 days')), 'profit' => 3.89],
    ['date' => date('Y-m-d', strtotime('-2 days')), 'profit' => -1.25],
    ['date' => date('Y-m-d', strtotime('-1 days')), 'profit' => 4.56],
    ['date' => date('Y-m-d'), 'profit' => 2.12],
];

// Toplam kar/zarar
$total_profit = array_sum(array_column($daily_profits, 'profit'));

// En iyi performans gösteren semboller (örnek veri)
$best_performers = [
    ['symbol' => 'BTC/USDT', 'profit' => 24.5],
    ['symbol' => 'ETH/USDT', 'profit' => 18.3],
    ['symbol' => 'SOL/USDT', 'profit' => 12.7],
    ['symbol' => 'ADA/USDT', 'profit' => 9.8],
    ['symbol' => 'DOT/USDT', 'profit' => 8.2]
];

// Sayfa başlığı
$page_title = 'Performans Raporları';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> <?php echo $page_title; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form class="form-inline">
                                    <div class="form-group mr-2">
                                        <label for="start_date" class="mr-2">Başlangıç:</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                                    </div>
                                    <div class="form-group mr-2">
                                        <label for="end_date" class="mr-2">Bitiş:</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Filtrele</button>
                                </form>
                            </div>
                            <div class="col-md-6 text-right">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="changeTimeframe('week')">Haftalık</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="changeTimeframe('month')">Aylık</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="changeTimeframe('year')">Yıllık</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Günlük Kar/Zarar Grafiği</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="profitChart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Performans Özeti</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-3">
                                            <span>Toplam Kar/Zarar:</span>
                                            <span class="<?php echo $total_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <strong><?php echo number_format($total_profit, 2); ?> USDT</strong>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span>Kazançlı Günler:</span>
                                            <span class="text-success">
                                                <?php 
                                                $profit_days = count(array_filter($daily_profits, function($day) { 
                                                    return $day['profit'] > 0; 
                                                }));
                                                echo $profit_days;
                                                ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span>Zararlı Günler:</span>
                                            <span class="text-danger">
                                                <?php 
                                                $loss_days = count(array_filter($daily_profits, function($day) { 
                                                    return $day['profit'] < 0; 
                                                }));
                                                echo $loss_days;
                                                ?>
                                            </span>
                                        </div>
                                        <hr>
                                        <h6>En İyi Performans Gösteren Coinler</h6>
                                        <ul class="list-group">
                                            <?php foreach ($best_performers as $coin): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?php echo $coin['symbol']; ?>
                                                    <span class="badge badge-success badge-pill"><?php echo number_format($coin['profit'], 2); ?>%</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-table"></i> Detaylı Rapor</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>İşlem Sayısı</th>
                                        <th>Alım</th>
                                        <th>Satım</th>
                                        <th>Kar/Zarar</th>
                                        <th>En İyi Coin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daily_profits as $day): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($day['date'])); ?></td>
                                            <td><?php echo rand(5, 20); ?></td>
                                            <td><?php echo rand(2, 10); ?></td>
                                            <td><?php echo rand(2, 10); ?></td>
                                            <td class="<?php echo $day['profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <strong><?php echo number_format($day['profit'], 2); ?> USDT</strong>
                                            </td>
                                            <td>
                                                <?php 
                                                    $coins = ['BTC/USDT', 'ETH/USDT', 'SOL/USDT', 'ADA/USDT', 'DOT/USDT'];
                                                    echo $coins[array_rand($coins)];
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Kar/Zarar grafiği
        const profitCtx = document.getElementById('profitChart').getContext('2d');
        const profitChart = new Chart(profitCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(', ', array_map(function($day) { 
                    return "'" . date('d.m', strtotime($day['date'])) . "'"; 
                }, $daily_profits)); ?>],
                datasets: [{
                    label: 'Günlük Kar/Zarar (USDT)',
                    data: [<?php echo implode(', ', array_column($daily_profits, 'profit')); ?>],
                    backgroundColor: [
                        <?php foreach ($daily_profits as $day): ?>
                            '<?php echo $day['profit'] >= 0 ? 'rgba(40, 167, 69, 0.6)' : 'rgba(220, 53, 69, 0.6)'; ?>',
                        <?php endforeach; ?>
                    ],
                    borderColor: [
                        <?php foreach ($daily_profits as $day): ?>
                            '<?php echo $day['profit'] >= 0 ? 'rgba(40, 167, 69, 1)' : 'rgba(220, 53, 69, 1)'; ?>',
                        <?php endforeach; ?>
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Zaman aralığı değiştirme fonksiyonu
        function changeTimeframe(timeframe) {
            let startDate = new Date();
            let endDate = new Date();
            
            switch(timeframe) {
                case 'week':
                    startDate.setDate(startDate.getDate() - 7);
                    break;
                case 'month':
                    startDate.setMonth(startDate.getMonth() - 1);
                    break;
                case 'year':
                    startDate.setFullYear(startDate.getFullYear() - 1);
                    break;
            }
            
            document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
            document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
            
            // Form gönder
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>