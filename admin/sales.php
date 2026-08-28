<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$store_name = get_setting('store_name', 'My Store');
$currency = get_setting('currency', '$');

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)$_POST['id'];
    // Delete items first (child), then sale (parent)
    $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$id]);
    set_flash('success', 'Sale deleted.');
    header('Location: sales.php');
    exit;
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = '';

if ($filter === 'today') {
    $where = "WHERE DATE(s.created_at) = " . db_today();
} elseif ($filter === 'month') {
    $where = "WHERE " . db_month_year_check('s.created_at');
}     

$sql = "SELECT s.*, u.username FROM sales s LEFT JOIN users u ON s.user_id = u.id $where ORDER BY s.created_at DESC LIMIT 100";
$sales = $pdo->query($sql)->fetchAll();

// Detail view
$detail_sale = null;
$detail_items = [];
if (isset($_GET['view'])) {
    $sid = (int)$_GET['view'];
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->execute([$sid]);
    $detail_sale = $stmt->fetch();

    if ($detail_sale) {
        $stmt = $pdo->prepare("SELECT si.*, p.name FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
        $stmt->execute([$sid]);
        $detail_items = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales - <?= htmlspecialchars($store_name) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #fff; }
        .sidebar { position: fixed; left: 0; top: 0; width: 220px; height: 100vh; background: #16213e; padding: 20px 0; }
        .sidebar h3 { color: #e94560; padding: 0 20px 20px; border-bottom: 1px solid #0f3460; }
        .sidebar a { display: block; color: #ccc; text-decoration: none; padding: 12px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #0f3460; color: #fff; }
        .main { margin-left: 220px; padding: 30px; }
        .main h1 { margin-bottom: 20px; font-size: 24px; }
        .filters { display: flex; gap: 10px; margin-bottom: 20px; }
        .filters a { padding: 8px 16px; background: #16213e; color: #ccc; text-decoration: none; border-radius: 6px; font-size: 13px; border: 1px solid #0f3460; }
        .filters a.active { background: #e94560; color: #fff; border-color: #e94560; }
        table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 10px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #0f3460; font-size: 14px; }
        th { background: #0f3460; font-size: 12px; text-transform: uppercase; color: #aaa; }
        .btn-view { background: #40c4ff; color: #000; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-del { background: #e94560; color: #fff; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .detail-box { background: #16213e; padding: 25px; border-radius: 10px; margin-bottom: 25px; }
        .detail-box h3 { margin-bottom: 15px; color: #e94560; }
        .detail-box table { margin-top: 10px; }
        .summary-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .summary-card { background: #16213e; padding: 15px 25px; border-radius: 8px; }
        .summary-card .label { font-size: 12px; color: #888; }
        .summary-card .val { font-size: 20px; font-weight: bold; color: #e94560; }
        .ref-badge { background: #0f3460; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><?= htmlspecialchars($store_name) ?></h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="sales.php" class="active">Sales</a>
        <a href="inventory.php">Inventory</a>
        <a href="settings.php">Settings</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
            <h1 style="margin-bottom:0;">Sales</h1>
            <a href="../public/register.php" style="background:#00e676;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">🛒 Open Register</a>
        </div>     
        <?php $f = get_flash(); if ($f): ?>
            <p style="color:<?= $f['type']==='success'?'#00e676':'#e94560' ?>;margin-bottom:15px;"><?= htmlspecialchars($f['msg']) ?></p>
        <?php endif; ?>

        <?php if ($detail_sale): ?>
        <div class="detail-box">
            <h3>Invoice: <?= htmlspecialchars($detail_sale['invoice_no']) ?></h3>
            <p style="color:#888;font-size:13px;margin-bottom:10px;">
                <?= date('M d, Y h:i A', strtotime($detail_sale['created_at'])) ?>
                | Cashier: <?= htmlspecialchars($detail_sale['username'] ?? '—') ?>
                | Payment: <?= strtoupper($detail_sale['payment_method']) ?>
                <?php if ($detail_sale['transaction_ref']): ?>
                    | <span class="ref-badge">Ref: <?= htmlspecialchars($detail_sale['transaction_ref']) ?></span>
                <?php endif; ?>
            </p>
            <table>
                <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
                <?php foreach ($detail_items as $di): ?>
                <tr>
                    <td><?= htmlspecialchars($di['name'] ?? 'Deleted product') ?></td>
                    <td><?= $di['qty'] ?></td>
                    <td><?= $currency ?><?= number_format($di['price'], 2) ?></td>
                    <td><?= $currency ?><?= number_format($di['price'] * $di['qty'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight:bold;border-top:2px solid #0f3460;">
                    <td colspan="3">Total (incl. tax)</td>
                    <td><?= $currency ?><?= number_format($detail_sale['total'], 2) ?></td>
                </tr>
            </table>
            <a href="sales.php" style="display:inline-block;margin-top:15px;color:#40c4ff;text-decoration:none;font-size:13px;">← Back to list</a>
        </div>
        <?php else: ?>

        <?php
        $stmt = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(s.total),0) as rev FROM sales s $where");
        $summary = $stmt->fetch();
        ?>
        <div class="summary-row">
            <div class="summary-card"><div class="label">Transactions</div><div class="val"><?= $summary['cnt'] ?></div></div>
            <div class="summary-card"><div class="label">Revenue</div><div class="val"><?= $currency ?><?= number_format($summary['rev'], 2) ?></div></div>
        </div>

        <div class="filters">
            <a href="sales.php?filter=all" class="<?= $filter==='all'?'active':'' ?>">All</a>
            <a href="sales.php?filter=today" class="<?= $filter==='today'?'active':'' ?>">Today</a>
            <a href="sales.php?filter=month" class="<?= $filter==='month'?'active':'' ?>">This Month</a>
        </div>

        <table>
            <tr><th>Invoice #</th><th>Date</th><th>Cashier</th><th>Payment</th><th>Reference</th><th>Total</th><th>Actions</th></tr>
            <?php foreach ($sales as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['invoice_no']) ?></td>
                <td><?= date('M d, h:i A', strtotime($s['created_at'])) ?></td>
                <td><?= htmlspecialchars($s['username'] ?? '—') ?></td>
                <td><?= strtoupper($s['payment_method']) ?></td>
                <td><?= $s['transaction_ref'] ? '<span class="ref-badge">' . htmlspecialchars($s['transaction_ref']) . '</span>' : '<span style="color:#888">—</span>' ?></td>
                <td><strong><?= $currency ?><?= number_format($s['total'], 2) ?></strong></td>
                <td>
                    <a class="btn-view" href="sales.php?view=<?= $s['id'] ?>">View</a>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="btn-del" type="submit">Del</button>
                    </form>   
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sales)): ?>
            <tr><td colspan="7" style="color:#888;text-align:center;padding:30px;">No sales recorded yet.</td></tr>
            <?php endif; ?>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>   