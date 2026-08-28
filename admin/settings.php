<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$store_name = get_setting('store_name', 'My Store');
$currency = get_setting('currency', '$');
$tax_rate = get_setting('tax_rate', '0');
$receipt_footer = get_setting('receipt_footer', 'Thank you for your purchase!');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $new_store = trim($_POST['store_name'] ?? '');
    $new_currency = trim($_POST['currency'] ?? '$');
    $new_tax = (float)($_POST['tax_rate'] ?? 0);
    $new_footer = trim($_POST['receipt_footer'] ?? '');

    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->execute([$new_store, 'store_name']);
    $stmt->execute([$new_currency, 'currency']);
    $stmt->execute([$new_tax, 'tax_rate']);
    $stmt->execute([$new_footer, 'receipt_footer']);

    set_flash('success', 'Settings saved.');
    header('Location: settings.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - <?= htmlspecialchars($store_name) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #fff; }
        .sidebar { position: fixed; left: 0; top: 0; width: 220px; height: 100vh; background: #16213e; padding: 20px 0; }
        .sidebar h3 { color: #e94560; padding: 0 20px 20px; border-bottom: 1px solid #0f3460; }
        .sidebar a { display: block; color: #ccc; text-decoration: none; padding: 12px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #0f3460; color: #fff; }
        .main { margin-left: 220px; padding: 30px; }
        .main h1 { margin-bottom: 25px; font-size: 24px; }
        .settings-form { background: #16213e; padding: 30px; border-radius: 10px; max-width: 500px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: #888; margin-bottom: 6px; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #0f3460; border-radius: 6px; background: #1a1a2e; color: #fff; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .save-btn { padding: 12px 30px; background: #e94560; color: #fff; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; }
        .save-btn:hover { background: #c73650; }
        .preview-box { background: #1a1a2e; padding: 20px; border-radius: 8px; margin-top: 25px; border: 1px dashed #0f3460; }
        .preview-box h4 { color: #888; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; }
        .preview-box .receipt-preview { font-family: 'Courier New', monospace; font-size: 13px; color: #ccc; text-align: center; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><?= htmlspecialchars($store_name) ?></h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="sales.php">Sales</a>
        <a href="inventory.php">Inventory</a>
        <a href="settings.php" class="active">Settings</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
            <h1 style="margin-bottom:0;">Settings</h1>
            <a href="../public/register.php" style="background:#00e676;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">🛒 Open Register</a>
        </div>      
        <?php $f = get_flash(); if ($f): ?>
            <p style="color:<?= $f['type']==='success'?'#00e676':'#e94560' ?>;margin-bottom:15px;"><?= htmlspecialchars($f['msg']) ?></p>
        <?php endif; ?>

        <form method="POST" class="settings-form">
            <input type="hidden" name="action" value="save">

            <div class="form-group">
                <label>Store Name</label>
                <input type="text" name="store_name" value="<?= htmlspecialchars($store_name) ?>" required>
            </div>

            <div class="form-group">
                <label>Currency Symbol</label>
                <input type="text" name="currency" value="<?= htmlspecialchars($currency) ?>" maxlength="3" required>
            </div>

            <div class="form-group">
                <label>Tax Rate (%)</label>
                <input type="number" name="tax_rate" value="<?= htmlspecialchars($tax_rate) ?>" step="0.01" min="0" max="100">
            </div>

            <div class="form-group">
                <label>Receipt Footer Text</label>
                <textarea name="receipt_footer"><?= htmlspecialchars($receipt_footer) ?></textarea>
            </div>

            <button type="submit" class="save-btn">Save Settings</button>
        </form>

        <div class="preview-box">
            <h4>Receipt Preview</h4>
            <div class="receipt-preview">
                <?= htmlspecialchars($store_name) ?><br>
                <span style="font-size:11px;">123 Main St, City</span><br>
                <span style="font-size:11px;">Tel: (555) 123-4567</span><br>
                <hr style="border:none;border-top:1px dashed #555;margin:10px 0;">
                1× Sample Item &nbsp;&nbsp;&nbsp; <?= htmlspecialchars($currency) ?>10.00<br>
                2× Another Item &nbsp;&nbsp;&nbsp; <?= htmlspecialchars($currency) ?>5.00<br>
                <hr style="border:none;border-top:1px dashed #555;margin:10px 0;">
                Subtotal &nbsp;&nbsp;&nbsp; <?= htmlspecialchars($currency) ?>20.00<br>
                Tax (<?= htmlspecialchars($tax_rate) ?>%) &nbsp;&nbsp;&nbsp; <?= htmlspecialchars($currency) ?><?= number_format(20 * $tax_rate / 100, 2) ?><br>
                <strong>TOTAL &nbsp;&nbsp;&nbsp; <?= htmlspecialchars($currency) ?><?= number_format(20 * (1 + $tax_rate / 100), 2) ?></strong><br>
                <hr style="border:none;border-top:1px dashed #555;margin:10px 0;">
                <span style="font-size:11px;"><?= htmlspecialchars($receipt_footer) ?></span>
            </div>
        </div>
    </div>
</body>
</html>   