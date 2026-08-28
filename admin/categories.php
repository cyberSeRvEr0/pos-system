<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            set_flash('success', 'Category added.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        set_flash('success', 'Category deleted.');
    }
    header('Location: categories.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$store_name = get_setting('store_name', 'My Store');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Categories - <?= htmlspecialchars($store_name) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #fff; }
        .sidebar { position: fixed; left: 0; top: 0; width: 220px; height: 100vh; background: #16213e; padding: 20px 0; }
        .sidebar h3 { color: #e94560; padding: 0 20px 20px; border-bottom: 1px solid #0f3460; }
        .sidebar a { display: block; color: #ccc; text-decoration: none; padding: 12px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #0f3460; color: #fff; }
        .main { margin-left: 220px; padding: 30px; }
        .main h1 { margin-bottom: 25px; font-size: 24px; }
        .add-form { background: #16213e; padding: 20px; border-radius: 10px; margin-bottom: 25px; display: flex; gap: 10px; }
        .add-form input { flex: 1; padding: 10px; border: 1px solid #0f3460; border-radius: 6px; background: #1a1a2e; color: #fff; }
        .add-form button { padding: 10px 20px; background: #e94560; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 10px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #0f3460; }
        th { background: #0f3460; font-size: 13px; text-transform: uppercase; color: #aaa; }
        .btn-del { background: #e94560; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-del:hover { background: #c73650; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><?= htmlspecialchars($store_name) ?></h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php" class="active">Categories</a>
        <a href="sales.php">Sales</a>
        <a href="inventory.php">Inventory</a>
        <a href="settings.php">Settings</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
            <h1 style="margin-bottom:0;">Categories</h1>
            <a href="../public/register.php" style="background:#00e676;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">🛒 Open Register</a>
        </div>     
        <?php $f = get_flash(); if ($f): ?>
            <p style="color:<?= $f['type']==='success'?'#00e676':'#e94560' ?>;margin-bottom:15px;"><?= htmlspecialchars($f['msg']) ?></p>
        <?php endif; ?>

        <form method="POST" class="add-form">
            <input type="hidden" name="action" value="add">
            <input type="text" name="name" placeholder="Category name" required>
            <button type="submit">Add</button>
        </form>

        <table>
            <tr><th>ID</th><th>Name</th><th>Action</th></tr>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this category?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button class="btn-del" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
            <tr><td colspan="3" style="color:#888">No categories yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>   