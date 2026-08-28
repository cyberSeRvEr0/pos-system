<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$store_name = get_setting('store_name', 'My Store');
$currency = get_setting('currency', '$');
$tax_rate = (float)get_setting('tax_rate', '0');

$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock > 0 ORDER BY c.name, p.name")->fetchAll();

$grouped = [];
foreach ($products as $p) {
    $cat = $p['cat_name'] ?: 'Uncategorized';
    $grouped[$cat][] = $p;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>POS - <?= htmlspecialchars($store_name) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #fff; display: flex; height: 100vh; overflow: hidden; }

        .pos-left { flex: 1; display: flex; flex-direction: column; }
        .pos-header { padding: 15px 20px; background: #16213e; display: flex; justify-content: space-between; align-items: center; }
        .pos-header h2 { font-size: 18px; }
        .pos-header a { color: #e94560; text-decoration: none; font-size: 13px; }
        .search-bar { padding: 10px 20px; background: #16213e; }
        .search-bar input { width: 100%; padding: 10px 15px; border: 1px solid #0f3460; border-radius: 6px; background: #1a1a2e; color: #fff; font-size: 14px; }

        .product-grid { flex: 1; overflow-y: auto; padding: 15px; display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; align-content: start; }
        .product-card { background: #16213e; border: 2px solid #0f3460; border-radius: 8px; padding: 12px; cursor: pointer; text-align: center; transition: all 0.15s; }
        .product-card:hover { border-color: #e94560; transform: scale(1.03); }
        .product-card .name { font-size: 13px; font-weight: 600; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-card .price { color: #e94560; font-size: 15px; font-weight: bold; }
        .product-card .stock { color: #888; font-size: 11px; margin-top: 4px; }

        .pos-right { width: 350px; background: #16213e; display: flex; flex-direction: column; border-left: 1px solid #0f3460; }
        .cart-header { padding: 15px 20px; border-bottom: 1px solid #0f3460; display: flex; justify-content: space-between; align-items: center; }
        .cart-header h3 { font-size: 16px; }
        .cart-header button { background: #e94560; color: #fff; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }

        .cart-items { flex: 1; overflow-y: auto; padding: 10px; }
        .cart-item { display: flex; align-items: center; padding: 10px; background: #1a1a2e; border-radius: 6px; margin-bottom: 8px; }
        .cart-item .info { flex: 1; }
        .cart-item .info .name { font-size: 13px; font-weight: 600; }
        .cart-item .info .price { font-size: 12px; color: #888; }
        .cart-item .qty-controls { display: flex; align-items: center; gap: 8px; }
        .cart-item .qty-controls button { width: 26px; height: 26px; border-radius: 4px; border: none; background: #0f3460; color: #fff; cursor: pointer; font-size: 14px; }
        .cart-item .qty-controls button:hover { background: #e94560; }
        .cart-item .qty-controls span { min-width: 20px; text-align: center; font-size: 14px; }
        .cart-item .remove { color: #e94560; cursor: pointer; font-size: 16px; margin-left: 8px; }

        .cart-footer { padding: 15px 20px; border-top: 1px solid #0f3460; }
        .cart-total-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .cart-total-row.grand { font-size: 20px; font-weight: bold; color: #e94560; border-top: 1px solid #0f3460; padding-top: 10px; margin-top: 10px; }
        .payment-methods { display: flex; gap: 8px; margin: 12px 0; }
        .payment-methods button { flex: 1; padding: 10px; border: 2px solid #0f3460; border-radius: 6px; background: #1a1a2e; color: #ccc; cursor: pointer; font-size: 13px; }
        .payment-methods button.active { border-color: #e94560; background: #e94560; color: #fff; }

        .ref-field { margin: 10px 0; display: none; }
        .ref-field label { font-size: 12px; color: #888; display: block; margin-bottom: 5px; }
        .ref-field input { width: 100%; padding: 10px; border: 1px solid #0f3460; border-radius: 6px; background: #1a1a2e; color: #fff; font-size: 13px; }

        .cash-field { margin: 10px 0; display: none; }
        .cash-field label { font-size: 12px; color: #888; display: block; margin-bottom: 5px; }
        .cash-field input { width: 100%; padding: 10px; border: 1px solid #0f3460; border-radius: 6px; background: #1a1a2e; color: #fff; font-size: 13px; }
        .change-display { font-size: 13px; color: #00e676; margin-top: 5px; }

        .checkout-btn { width: 100%; padding: 14px; background: #00e676; color: #000; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; }
        .checkout-btn:hover { background: #00c853; }
        .checkout-btn:disabled { background: #333; color: #666; cursor: not-allowed; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 200; align-items: center; justify-content: center; }
        .receipt-box { background: #fff; color: #000; padding: 30px; border-radius: 8px; width: 320px; font-family: 'Courier New', monospace; }
        .receipt-box h3 { text-align: center; margin-bottom: 15px; }
        .receipt-box .line { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; }
        .receipt-box .total-line { font-weight: bold; font-size: 15px; border-top: 1px dashed #000; margin-top: 10px; padding-top: 10px; }
        .receipt-actions { margin-top: 20px; display: flex; gap: 10px; }
        .receipt-actions button { flex: 1; padding: 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn-print { background: #000; color: #fff; }
        .btn-new { background: #e94560; color: #fff; }
        @media print {
            .receipt-actions { display: none !important; }
            body * { visibility: hidden; }
            .receipt-box, .receipt-box * { visibility: visible; }
            .receipt-box { position: absolute; top: 0; left: 0; width: 100%; }
        }   
    </style>
</head>
<body>
    <div class="pos-left">
        <div class="pos-header">
            <h2><?= htmlspecialchars($store_name) ?> — POS</h2>
            <a href="../logout.php">Logout</a>
        </div>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Search product or scan barcode..." autocomplete="off">
        </div>
        <div class="product-grid" id="productGrid">
            <?php foreach ($grouped as $cat => $items): ?>
                <?php foreach ($items as $p): ?>
                <div class="product-card" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-price="<?= $p['price'] ?>" data-barcode="<?= htmlspecialchars($p['barcode'] ?? '') ?>" onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['price'] ?>)">
                    <div class="name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="price"><?= $currency ?><?= number_format($p['price'], 2) ?></div>
                    <div class="stock"><?= $p['stock'] ?> left</div>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
                <p style="color:#888;grid-column:1/-1;text-align:center;padding:40px;">No products with stock. Add products in Admin → Products.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="pos-right">
        <div class="cart-header">
            <h3>🛒 Cart (<span id="cartCount">0</span>)</h3>
            <button onclick="clearCart()">Clear</button>
        </div>
        <div class="cart-items" id="cartItems">
            <p style="color:#888;text-align:center;padding:30px;">Cart is empty</p>
        </div>
        <div class="cart-footer">
            <div class="cart-total-row"><span>Subtotal</span><span id="subtotal"><?= $currency ?>0.00</span></div>
            <div class="cart-total-row"><span>Tax (<?= $tax_rate ?>%)</span><span id="taxAmount"><?= $currency ?>0.00</span></div>
            <div class="cart-total-row grand"><span>Total</span><span id="grandTotal"><?= $currency ?>0.00</span></div>

            <div class="payment-methods">
                <button class="active" data-method="cash" onclick="setPayment(this)">💵 Cash</button>
                <button data-method="card" onclick="setPayment(this)">💳 Card</button>
                <button data-method="mobile" onclick="setPayment(this)">📱 Mobile</button>
            </div>

            <!-- Cash: amount received -->
            <div class="cash-field" id="cashField">
                <label>Amount Received</label>
                <input type="number" id="cashReceived" step="0.01" min="0" oninput="calcChange()">
                <div class="change-display" id="changeDisplay"></div>
            </div>

            <!-- Card/Mobile: transaction reference -->
            <div class="ref-field" id="refField">
                <label>Transaction Reference (from device / SMS)</label>
                <input type="text" id="transactionRef" placeholder="e.g. QGH7XK2 or auth_3xK9mP">
            </div>

            <button class="checkout-btn" id="checkoutBtn" onclick="checkout()" disabled>Complete Sale</button>
        </div>
    </div>

    <div class="modal-overlay" id="receiptModal">
        <div class="receipt-box" id="receiptContent"></div>
    </div>

    <script>
    const currency = '<?= $currency ?>';
    const taxRate = <?= $tax_rate ?>;
    let cart = [];
    let paymentMethod = 'cash';

    // Barcode / search
    document.getElementById('searchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const val = this.value.trim();
            const card = document.querySelector(`.product-card[data-barcode="${val}"]`);
            if (card) {
                addToCart(parseInt(card.dataset.id), card.dataset.name, parseFloat(card.dataset.price));
                this.value = '';
                return;
            }
        }
        const cards = document.querySelectorAll('.product-card');
        cards.forEach(c => {
            c.style.display = c.dataset.name.toLowerCase().includes(val.toLowerCase()) ? '' : 'none';
        });
    });

    function addToCart(id, name, price) {
        const existing = cart.find(i => i.id === id);
        if (existing) { existing.qty++; }
        else { cart.push({ id, name, price, qty: 1 }); }
        renderCart();
    }

    function changeQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (!item) return;
        item.qty += delta;
        if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
        renderCart();
    }

    function removeItem(id) {
        cart = cart.filter(i => i.id !== id);
        renderCart();
    }

    function clearCart() {
        cart = [];
        renderCart();
    }

    function setPayment(btn) {
        document.querySelectorAll('.payment-methods button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        paymentMethod = btn.dataset.method;

        document.getElementById('cashField').style.display = (paymentMethod === 'cash') ? 'block' : 'none';
        document.getElementById('refField').style.display = (paymentMethod === 'cash') ? 'none' : 'block';
        document.getElementById('changeDisplay').textContent = '';
    }

    function calcChange() {
        const received = parseFloat(document.getElementById('cashReceived').value) || 0;
        const total = cart.reduce((s, i) => s + i.price * i.qty, 0) * (1 + taxRate / 100);
        const change = received - total;
        const el = document.getElementById('changeDisplay');
        if (received > 0 && cart.length > 0) {
            el.textContent = change >= 0 ? 'Change: ' + currency + change.toFixed(2) : 'Insufficient: ' + currency + Math.abs(change).toFixed(2);
            el.style.color = change >= 0 ? '#00e676' : '#e94560';
        } else {
            el.textContent = '';
        }
    }

    function renderCart() {
        const container = document.getElementById('cartItems');
        const count = cart.reduce((s, i) => s + i.qty, 0);
        document.getElementById('cartCount').textContent = count;
        document.getElementById('checkoutBtn').disabled = cart.length === 0;

        if (cart.length === 0) {
            container.innerHTML = '<p style="color:#888;text-align:center;padding:30px;">Cart is empty</p>';
        } else {
            container.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="info">
                        <div class="name">${item.name}</div>
                        <div class="price">${currency}${item.price.toFixed(2)} × ${item.qty} = ${currency}${(item.price * item.qty).toFixed(2)}</div>
                    </div>
                    <div class="qty-controls">
                        <button onclick="changeQty(${item.id}, -1)">−</button>
                        <span>${item.qty}</span>
                        <button onclick="changeQty(${item.id}, 1)">+</button>
                    </div>
                    <span class="remove" onclick="removeItem(${item.id})">✕</span>
                </div>
            `).join('');
        }

        const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
        const tax = subtotal * (taxRate / 100);
        const total = subtotal + tax;

        document.getElementById('subtotal').textContent = currency + subtotal.toFixed(2);
        document.getElementById('taxAmount').textContent = currency + tax.toFixed(2);
        document.getElementById('grandTotal').textContent = currency + total.toFixed(2);
        calcChange();
    }

    function checkout() {
        if (cart.length === 0) return;

        const payload = {
            cart: cart,
            payment_method: paymentMethod,
            transaction_ref: document.getElementById('transactionRef').value.trim() || null,
            cash_received: paymentMethod === 'cash' ? (parseFloat(document.getElementById('cashReceived').value) || null) : null
        };   

        fetch('checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showReceipt(data);
                cart = [];
                document.getElementById('transactionRef').value = '';
                document.getElementById('cashReceived').value = '';
                renderCart();
            } else {
                alert('Error: ' + (data.error || 'Checkout failed'));
            }
        })
        .catch(e => alert('Network error: ' + e.message));
    }

    function showReceipt(data) {
        const items = data.items.map(i => `<div class="line"><span>${i.qty}× ${i.name}</span><span>${currency}${i.subtotal.toFixed(2)}</span></div>`).join('');
        let extra = '';
        if (data.cash_received) {
            extra += `<div class="line"><span>Cash Received</span><span>${currency}${data.cash_received.toFixed(2)}</span></div>`;
            extra += `<div class="line"><span>Change</span><span>${currency}${data.change.toFixed(2)}</span></div>`;
        }
        if (data.transaction_ref) {
            extra += `<div class="line" style="font-weight:bold;font-size:14px;"><span>REF:</span><span>${data.transaction_ref}</span></div>`;
        }
        document.getElementById('receiptContent').innerHTML = `
            <h3>${data.store_name}</h3>
            <p style="text-align:center;font-size:12px;">${data.date}</p>
            <p style="text-align:center;font-size:12px;margin-bottom:15px;">Invoice: ${data.invoice_no}</p>
            <hr>
            ${items}
            <hr>
            <div class="line"><span>Subtotal</span><span>${currency}${data.subtotal.toFixed(2)}</span></div>
            <div class="line"><span>Tax</span><span>${currency}${data.tax.toFixed(2)}</span></div>
            <div class="line total-line"><span>TOTAL</span><span>${currency}${data.total.toFixed(2)}</span></div>
            <div class="line"><span>Paid via</span><span>${data.payment_method.toUpperCase()}</span></div>
            ${extra}
            <p style="text-align:center;margin-top:15px;font-size:12px;">${data.footer}</p>
            <div class="receipt-actions">
                <button class="btn-print" onclick="window.print()">🖨️ Print</button>
                <button class="btn-new" onclick="document.getElementById('receiptModal').style.display='none'">New Sale</button>
            </div>
        `;
        document.getElementById('receiptModal').style.display = 'flex';
    }   

    // Show cash field by default
    document.getElementById('cashField').style.display = 'block';
    </script>
</body>
</html>   