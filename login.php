<?php
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'public/register.php'));
        exit;
    }
    $error = 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - POS System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-box { background: #16213e; padding: 40px; border-radius: 12px; width: 350px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .login-box h2 { color: #fff; text-align: center; margin-bottom: 30px; }
        .login-box input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #0f3460; border-radius: 6px; background: #1a1a2e; color: #fff; font-size: 14px; }
        .login-box button { width: 100%; padding: 12px; background: #e94560; color: #fff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .login-box button:hover { background: #c73650; }
        .error { color: #e94560; text-align: center; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>POS System</h2>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>   