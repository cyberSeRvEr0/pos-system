<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$store_name = get_setting('store_name', 'My Store');
$currency = get_setting('currency', '$');

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'adjust') {
    $id = (int)$_POST['id'];
    $adjustment = (int)$_POST['adjustment'];
    $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$adjustment, $id]);
    set_flash('success', 'Stock updated.');
    header('Location: inventory.php');
    exit;
}

// Low stock threshold
$lowThreshold = 5;

// Get all products with stock info
$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.stock ASC, p.name")->fetchAll();

$lowStock = array_filter($products, fn($p) => $p['stock'] <= $lowThreshold);
$outOfStock = array_filter($products, fn($p) => $p['stock'] == 0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory - <?= htmlspecialchars($store_name) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #fff; }
        .sidebar { position: fixed; left: 0; top: 0; width: 220px; height: 100vh; background: #16213e; padding: 20px 0; }
        .sidebar h3 { color: #e94560; padding: 0 20px 20px; border-bottom: 1px solid #0f3460; }
        .sidebar a { display: block; color: #ccc; text-decoration: none; padding: 12px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #0f3460; color: #fff; }
        .main { margin-left: 220px; padding: 30px; }
        .main h1 { margin-bottom: 20px; font-size: 24px; }
        .alerts { display: flex; gap: 15px; margin-bottom: 25px; }
        .alert-box { background: #16213e; padding: 15px 20px; border-radius: 8px; border-left: 4px solid #e94560; }
        .alert-box.out { border-left-color: #ff5252; }
        .alert-box.low { border-left-color: #ffab40; }
        .alert-box .count { font-size: 24px; font-weight: bold; }
        .alert-box .label { font-size: 12px; color: #888; }
        table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 10px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #0f3460; font-size: 14px; }
        th { background: #0f3460; font-size: 12px; text-transform: uppercase; color: #aaa; }
        .stock-out { color: #ff5252; font-weight: bold; }
        .stock-low { color: #ffab40; font-weight: bold; }
        .stock-ok { color: #00e676; }
        .adjust-form { display: flex; align-items: center; gap: 6px; }
        .adjust-form input { width: 60px; padding: 6px; border: 1px solid #0f3460; border-radius: 4px; background: #1a1a2e; color: #fff; text-align: center; }
        .adjust-form button { padding: 6px 12px; background: #40c4ff; color: #000; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-out { background: #ff5252; color: #fff; }
        .badge-low { background: #ffab40; color: #000; }
        .badge-ok { background: #00e676; color: #000; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><?= htmlspecialchars($store_name) ?></h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="sales.php">Sales</a>
        <a href="inventory.php" class="active">Inventory</a>
        <a href="settings.php">Settings</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
            <h1 style="margin-bottom:0;">Inventory</h1>
            <a href="../public/register.php" style="background:#00e676;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">🛒 Open Register</a>
        </div>     
        <?php $f = get_flash(); if ($f): ?>
            <p style="color:<?= $f['type']==='success'?'#00e676':'#e94560' ?>;margin-bottom:15px;"><?= htmlspecialchars($f['msg']) ?></p>
        <?php endif; ?>

        <div class="alerts">
            <div class="alert-box out">
                <div class="count"><?= count($outOfStock) ?></div>
                <div class="label">Out of Stock</div>
            </div>
            <div class="alert-box low">
                <div class="count"><?= count($lowStock) - count($outOfStock) ?></div>
                <div class="label">Low Stock (≤ <?= $lowThreshold ?>)</div>
            </div>
            <div class="alert-box" style="border-left-color:#00e676;">
                <div class="count" style="color:#00e676;"><?= count($products) - count($lowStock) ?></div>
                <div class="label">In Stock</div>
            </div>
        </div>

        <table>
            <tr><th>Product</th><th>Category</th><th>Stock</th><th>Status</th><th>Adjust</th></tr>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
                <td class="<?= $p['stock'] == 0 ? 'stock-out' : ($p['stock'] <= $lowThreshold ? 'stock-low' : 'stock-ok') ?>">
                    <?= $p['stock'] ?>
                </td>
                <td>
                    <?php if ($p['stock'] == 0): ?>
                        <span class="badge badge-out">OUT</span>
                    <?php elseif ($p['stock'] <= $lowThreshold): ?>
                        <span class="badge badge-low">LOW</span>
                    <?php else: ?>
                        <span class="badge badge-ok">OK</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" class="adjust-form">
                        <input type="hidden" name="action" value="adjust">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="number" name="adjustment" placeholder="+/−" step="1" required>
                        <button type="submit">Apply</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <tr><td colspan="5" style="color:#888;text-align:center;padding:30px;">No products in inventory.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>   