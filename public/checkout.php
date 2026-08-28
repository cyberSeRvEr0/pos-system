<?php
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cart = $data['cart'] ?? [];
$paymentMethod = $data['payment_method'] ?? 'cash';
$transactionRef = $data['transaction_ref'] ?? null;
$cashReceived = $data['cash_received'] ?? null;

if (empty($cart)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

$taxRate = (float)get_setting('tax_rate', '0');
$subtotal = 0;

foreach ($cart as $item) {
    $stmt = $pdo->prepare("SELECT stock, price FROM products WHERE id = ?");
    $stmt->execute([$item['id']]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'error' => "Product #{$item['id']} not found"]);
        exit;
    }
    if ($product['stock'] < $item['qty']) {
        echo json_encode(['success' => false, 'error' => "Insufficient stock for {$product['name']} (have {$product['stock']}, need {$item['qty']})"]);
        exit;
    }
    $subtotal += $product['price'] * $item['qty'];
}

$tax = $subtotal * ($taxRate / 100);
$total = $subtotal + $tax;
$change = 0;
if ($paymentMethod === 'cash' && $cashReceived) {
    $change = $cashReceived - $total;
    if ($change < 0) {
        echo json_encode(['success' => false, 'error' => 'Cash received is less than total']);
        exit;
    }
}

$invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

// Auto-generate reference for cash if none provided
if ($paymentMethod === 'cash' && empty($transactionRef)) {
    $transactionRef = strtoupper(substr(md5(uniqid()), 0, 6));
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, total, tax, discount, payment_method, user_id, transaction_ref) VALUES (?, ?, ?, 0, ?, ?, ?)");
    $stmt->execute([$invoiceNo, $total, $tax, $paymentMethod, $_SESSION['user_id'], $transactionRef]);
    $saleId = $pdo->lastInsertId();

    $itemStmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
    $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($cart as $item) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $price = $stmt->fetch()['price'];

        $itemStmt->execute([$saleId, $item['id'], $item['qty'], $price]);
        $stockStmt->execute([$item['qty'], $item['id']]);
    }

    $pdo->commit();

    $items = [];
    foreach ($cart as $item) {
        $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $p = $stmt->fetch();
        $items[] = ['name' => $p['name'], 'qty' => $item['qty'], 'subtotal' => $p['price'] * $item['qty']];
    }

    $response = [
        'success' => true,
        'invoice_no' => $invoiceNo,
        'store_name' => get_setting('store_name', 'My Store'),
        'date' => date('M d, Y h:i A'),
        'items' => $items,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total,
        'payment_method' => $paymentMethod,
        'transaction_ref' => $transactionRef,
        'footer' => get_setting('receipt_footer', 'Thank you!')
    ];

    if ($paymentMethod === 'cash' && $cashReceived) {
        $response['cash_received'] = $cashReceived;
        $response['change'] = $change;
    }

    echo json_encode($response);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>   