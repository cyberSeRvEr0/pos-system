<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

// Get stats
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM sales WHERE DATE(created_at) = " . db_today());
$stmt->execute();
$today_stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM sales WHERE " . db_month_year_check('created_at'));
$stmt->execute();
$month_stats = $stmt->fetch();      

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM products");
$product_count = $stmt->fetch()['cnt'];

$store_name = get_setting('store_name', 'My Store');
$currency = get_setting('currency', '$');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - <?= htmlspecialchars($store_name) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #fff; }
        .sidebar { position: fixed; left: 0; top: 0; width: 220px; height: 100vh; background: #16213e; padding: 20px 0; }
        .sidebar h3 { color: #e94560; padding: 0 20px 20px; border-bottom: 1px solid #0f3460; }
        .sidebar a { display: block; color: #ccc; text-decoration: none; padding: 12px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #0f3460; color: #fff; }
        .main { margin-left: 220px; padding: 30px; }
        .main h1 { margin-bottom: 25px; font-size: 24px; }
        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .card { background: #16213e; padding: 25px; border-radius: 10px; }
        .card h4 { color: #888; font-size: 13px; margin-bottom: 10px; text-transform: uppercase; }
        .card .value { font-size: 28px; font-weight: bold; color: #e94560; }
        .logout { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><?= htmlspecialchars($store_name) ?></h3>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="sales.php">Sales</a>
        <a href="inventory.php">Inventory</a>
        <a href="settings.php">Settings</a>
        <a href="../logout.php" class="logout">Logout</a>
    </div>
    <div class="main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
            <h1 style="margin-bottom:0;">Dashboard</h1>
            <a href="../public/register.php" style="background:#00e676;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">🛒 Open Register</a>
        </div>     
        <?php $f = get_flash(); if ($f): ?>
            <p style="color:<?= $f['type']==='success'?'#00e676':'#e94560' ?>;margin-bottom:15px;"><?= htmlspecialchars($f['msg']) ?></p>
        <?php endif; ?>
        <div class="cards">
            <div class="card">
                <h4>Today's Sales</h4>
                <div class="value"><?= $currency ?><?= number_format($today_stats['rev'], 2) ?></div>
                <p style="color:#888;font-size:13px;margin-top:5px;"><?= $today_stats['cnt'] ?> transactions</p>
            </div>
            <div class="card">
                <h4>This Month</h4>
                <div class="value"><?= $currency ?><?= number_format($month_stats['rev'], 2) ?></div>
                <p style="color:#888;font-size:13px;margin-top:5px;"><?= $month_stats['cnt'] ?> transactions</p>
            </div>
            <div class="card">
                <h4>Products</h4>
                <div class="value"><?= $product_count ?></div>
                <p style="color:#888;font-size:13px;margin-top:5px;">in catalog</p>
            </div>
        </div>
    </div>
</body>
</html>   