<?php
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Read config to get DB type
            $configFile = __DIR__ . '/config/config.php';
            $content = file_get_contents($configFile);
            preg_match("/define\('DB_TYPE',\s*'(\w+)'\)/", $content, $m);
            $dbType = $m[1] ?? 'sqlite';

            if ($dbType === 'sqlite') {
                // SQLite: just create the file with all tables
                $dbPath = __DIR__ . '/database/pos.db';
                if (file_exists($dbPath)) unlink($dbPath);

                $pdo = new PDO('sqlite:' . $dbPath);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec("PRAGMA journal_mode=WAL");

            } else {
                // MySQL: connect and create database
                preg_match("/define\('DB_HOST',\s*'([^']*)'\)/", $content, $h);
                preg_match("/define\('DB_USER',\s*'([^']*)'\)/", $content, $u);
                preg_match("/define\('DB_PASS',\s*'([^']*)'\)/", $content, $p);
                preg_match("/define\('DB_NAME',\s*'([^']*)'\)/", $content, $n);

                $pdo = new PDO("mysql:host=" . ($h[1] ?? 'localhost'), $u[1] ?? 'root', $p[1] ?? '');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $dbName = $n[1] ?? 'pos_system';
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
                $pdo->exec("USE `$dbName`");
            }

            // Create tables (works for both SQLite and MySQL)
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role TEXT DEFAULT 'cashier',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(200) NOT NULL,
                barcode VARCHAR(100) UNIQUE,
                price DECIMAL(10,2) NOT NULL,
                stock INTEGER DEFAULT 0,
                category_id INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_no VARCHAR(50) UNIQUE NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                tax DECIMAL(10,2) DEFAULT 0,
                discount DECIMAL(10,2) DEFAULT 0,
                payment_method TEXT DEFAULT 'cash',
                user_id INTEGER,
                transaction_ref VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS sale_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sale_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                qty INTEGER NOT NULL,
                price DECIMAL(10,2) NOT NULL
            )");

            // Insert default settings
            $pdo->exec("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES
                ('store_name', 'My Store'),
                ('currency', '$'),
                ('tax_rate', '0'),
                ('receipt_footer', 'Thank you for your purchase!')");

            // Create admin
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
            $stmt->execute([$username, $hashed]);

            $success = true;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install - POS System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .box { background: #16213e; padding: 40px; border-radius: 12px; width: 420px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .box h2 { color: #fff; text-align: center; margin-bottom: 25px; }
        .box label { color: #888; font-size: 13px; display: block; margin-bottom: 5px; }
        .box input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #0f3460; border-radius: 6px; background: #1a1a2e; color: #fff; font-size: 14px; }
        .box button { width: 100%; padding: 12px; background: #e94560; color: #fff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .box button:hover { background: #c73650; }
        .error { color: #e94560; text-align: center; margin-bottom: 15px; font-size: 14px; }
        .success-box { text-align: center; }
        .success-box h2 { color: #00e676; }
        .success-box p { color: #ccc; margin: 10px 0; }
        .success-box a { display: inline-block; margin-top: 20px; padding: 12px 30px; background: #00e676; color: #000; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .warning { color: #ffab40; font-size: 12px; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="box">
        <?php if ($success): ?>
            <div class="success-box">
                <h2>✅ Installation Complete!</h2>
                <p>Admin: <strong><?= htmlspecialchars($username) ?></strong></p>
                <p><a href="login.php">Go to Login →</a></p>
                <p class="warning">⚠️ Delete <strong>install.php</strong> for security!</p>
            </div>
        <?php else: ?>
            <h2>POS System Setup</h2>
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <form method="POST">
                <label>Admin Username</label>
                <input type="text" name="username" placeholder="e.g. myshop" required>
                <label>Password</label>
                <input type="password" name="password" placeholder="Min 6 characters" required>
                <label>Confirm Password</label>
                <input type="password" name="confirm" placeholder="Repeat password" required>
                <button type="submit">Install & Create Admin</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>   
