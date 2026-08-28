<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0) ?: null;

        if ($name && $price > 0) {
            $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, barcode, category_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $stock, $barcode ?: null, $category_id]);
            set_flash('success', 'Product added.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Check if product has sales
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM sale_items WHERE product_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetch()['cnt'] > 0) {
            set_flash('error', 'Cannot delete: this product has sales history. You can set its stock to 0 instead.');
        } else {
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
            set_flash('success', 'Product deleted.');
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0) ?: null;

        $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, stock=?, barcode=?, category_id=? WHERE id=?");
        $stmt->execute([$name, $price, $stock, $barcode ?: null, $category_id, $id]);
        set_flash('success', 'Product updated.');
    }
    header('Location: products.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name")->fetchAll();
$store_name = get_setting('store_name', 'My Store');
$currency = get_setting('currency', '$');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Products - <?= htmlspecialchars($store_name) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #fff; }
        .sidebar { position: fixed; left: 0; top: 0; width: 220px; height: 100vh; background: #16213e; padding: 20px 0; }
        .sidebar h3 { color: #e94560; padding: 0 20px 20px; border-bottom: 1px solid #0f3460; }
        .sidebar a { display: block; color: #ccc; text-decoration: none; padding: 12px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #0f3460; color: #fff; }
        .main { margin-left: 220px; padding: 30px; }
        .main h1 { margin-bottom: 25px; font-size: 24px; }
        .add-form { background: #16213e; padding: 20px; border-radius: 10px; margin-bottom: 25px; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end; }
        .add-form label { font-size: 11px; color: #888; display: block; margin-bottom: 4px; }
        .add-form input, .add-form select { width: 100%; padding: 10px; border: 1px solid #0f3460; border-radius: 6px; background: #1a1a2e; color: #fff; }
        .add-form button { padding: 10px 20px; background: #e94560; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 10px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #0f3460; font-size: 14px; }
        th { background: #0f3460; font-size: 12px; text-transform: uppercase; color: #aaa; }
        .btn-del { background: #e94560; color: #fff; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-edit { background: #40c4ff; color: #000; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 5px; }
        .low-stock { color: #e94560; font-weight: bold; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><?= htmlspecialchars($store_name) ?></h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php" class="active">Products</a>
        <a href="categories.php">Categories</a>
        <a href="sales.php">Sales</a>
        <a href="inventory.php">Inventory</a>
        <a href="settings.php">Settings</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
            <h1 style="margin-bottom:0;">Products</h1>
            <a href="../public/register.php" style="background:#00e676;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">🛒 Open Register</a>
        </div>      
        <?php $f = get_flash(); if ($f): ?>
            <p style="color:<?= $f['type']==='success'?'#00e676':'#e94560' ?>;margin-bottom:15px;"><?= htmlspecialchars($f['msg']) ?></p>
        <?php endif; ?>

        <form method="POST" class="add-form">
            <div><label>Name</label><input type="text" name="name" required></div>
            <div><label>Price (<?= htmlspecialchars($currency) ?>)</label><input type="number" name="price" step="0.01" min="0" required></div>
            <div><label>Stock</label><input type="number" name="stock" min="0" value="0"></div>
            <div><label>Barcode</label><input type="text" name="barcode" placeholder="Optional"></div>
            <div><label>Category</label>
                <select name="category_id">
                    <option value="">None</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><button type="submit" name="action" value="add">Add</button></div>
        </form>

        <table>
            <tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Barcode</th><th>Category</th><th>Actions</th></tr>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= $currency ?><?= number_format($p['price'], 2) ?></td>
                <td class="<?= $p['stock'] <= 5 ? 'low-stock' : '' ?>"><?= $p['stock'] ?></td>
                <td><?= htmlspecialchars($p['barcode'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
                <td>
                    <button class="btn-edit" onclick="editProduct(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['price'] ?>, <?= $p['stock'] ?>, '<?= htmlspecialchars($p['barcode'] ?? '') ?>', <?= $p['category_id'] ?? 0 ?>)">Edit</button>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn-del" type="submit">Del</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <tr><td colspan="7" style="color:#888">No products yet. Add one above.</td></tr>
            <?php endif; ?>
        </table>

        <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:100; align-items:center; justify-content:center;">
            <div style="background:#16213e; padding:30px; border-radius:12px; width:400px;">
                <h3 style="margin-bottom:20px;">Edit Product</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <p style="margin-bottom:15px;"><label style="color:#888;font-size:12px;">Name</label><br><input type="text" name="name" id="edit_name" style="width:100%;padding:10px;margin-top:4px;border:1px solid #0f3460;border-radius:6px;background:#1a1a2e;color:#fff;"></p>
                    <p style="margin-bottom:15px;"><label style="color:#888;font-size:12px;">Price</label><br><input type="number" name="price" id="edit_price" step="0.01" style="width:100%;padding:10px;margin-top:4px;border:1px solid #0f3460;border-radius:6px;background:#1a1a2e;color:#fff;"></p>
                    <p style="margin-bottom:15px;"><label style="color:#888;font-size:12px;">Stock</label><br><input type="number" name="stock" id="edit_stock" style="width:100%;padding:10px;margin-top:4px;border:1px solid #0f3460;border-radius:6px;background:#1a1a2e;color:#fff;"></p>
                    <p style="margin-bottom:15px;"><label style="color:#888;font-size:12px;">Barcode</label><br><input type="text" name="barcode" id="edit_barcode" style="width:100%;padding:10px;margin-top:4px;border:1px solid #0f3460;border-radius:6px;background:#1a1a2e;color:#fff;"></p>
                    <p style="margin-bottom:20px;"><label style="color:#888;font-size:12px;">Category</label><br>
                        <select name="category_id" id="edit_category" style="width:100%;padding:10px;margin-top:4px;border:1px solid #0f3460;border-radius:6px;background:#1a1a2e;color:#fff;">
                            <option value="">None</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <button type="submit" style="padding:10px 20px;background:#e94560;color:#fff;border:none;border-radius:6px;cursor:pointer;">Save</button>
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'" style="padding:10px 20px;background:#333;color:#fff;border:none;border-radius:6px;cursor:pointer;margin-left:10px;">Cancel</button>
                </form>
            </div>
        </div>

        <script>
        function editProduct(id, name, price, stock, barcode, catId) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_stock').value = stock;
            document.getElementById('edit_barcode').value = barcode;
            document.getElementById('edit_category').value = catId;
            document.getElementById('editModal').style.display = 'flex';
        }
        </script>
    </div>
</body>
</html>   