<?php
session_start();

// ===== DATABASE TYPE: change to 'mysql' if you prefer MySQL =====
define('DB_TYPE', 'sqlite'); // 'sqlite' or 'mysql'

// SQLite settings (used when DB_TYPE = 'sqlite')
define('DB_PATH', __DIR__ . '/../database/pos.db');

// MySQL settings (used when DB_TYPE = 'mysql')
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pos_system');

// Base URL
define('BASE_URL', 'http://localhost/pos-system');

// Connect
try {
    if (DB_TYPE === 'sqlite') {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA journal_mode=WAL");
    } else {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get a setting value
function get_setting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

// Auth check
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/public/register.php');
        exit;
    }
}

// Flash messages
function set_flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Date helpers (compatible with both SQLite and MySQL)
function db_today() {
    return DB_TYPE === 'sqlite' ? "DATE('now')" : "CURDATE()";
}

function db_month_year_check($column) {
    if (DB_TYPE === 'sqlite') {
        return "strftime('%m', $column) = strftime('%m', 'now') AND strftime('%Y', $column) = strftime('%Y', 'now')";
    } else {
        return "MONTH($column) = MONTH(CURDATE()) AND YEAR($column) = YEAR(CURDATE())";
    }
}   
?>   