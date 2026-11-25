<?php
require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/security.php';

$order = null;
$items = [];
$error = null;
$reference = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = strtoupper(trim((string) ($_POST['order_reference'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));

    if (!preg_match('/^CB-[A-F0-9]{12}$/', $reference) || $phone === '' || strlen($phone) > 20) {
        $error = 'Enter a valid order reference and the phone number used for the order.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, order_reference, customer_name, total_amount, payment_method, payment_status, status, order_date FROM orders WHERE order_reference = ? AND phone = ? LIMIT 1');

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $reference, $phone);
            mysqli_stmt_execute($stmt);
            $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        }

        if (!$order) {
            $error = 'We could not find an order matching both details.';
        } else {
            $item_stmt = mysqli_prepare($conn, 'SELECT oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
            if ($item_stmt) {
                mysqli_stmt_bind_param($item_stmt, 'i', $order['id']);
                mysqli_stmt_execute($item_stmt);
                $result = mysqli_stmt_get_result($item_stmt);
                while ($item = mysqli_fetch_assoc($result)) {
                    $items[] = $item;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Cr. Blaze Bakery</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<section class="cart-section">
    <h2>Track Your Order</h2>
    <p>Enter your order reference and the phone number used at checkout.</p>

    <?php if ($error): ?>
        <p class="cart-message cart-message-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div class="checkout-form">
        <form method="POST" action="track_order.php">
            <label for="order_reference">Order reference</label>
            <input id="order_reference" name="order_reference" type="text" maxlength="15" value="<?php echo htmlspecialchars($reference, ENT_QUOTES, 'UTF-8'); ?>" placeholder="CB-ABC123DEF456" required>

            <label for="phone">Phone number</label>
            <input id="phone" name="phone" type="tel" maxlength="20" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" required>

            <button type="submit" class="checkout-button">Find Order</button>
        </form>
    </div>

    <?php if ($order): ?>
        <div class="cart-item">
            <h3>Order <?php echo htmlspecialchars($order['order_reference'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p>Status: <strong><?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p>Payment status: <?php echo htmlspecialchars($order['payment_status'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Total: GH₵<?php echo number_format((float) $order['total_amount'], 2); ?></p>
            <?php foreach ($items as $item): ?>
                <p><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?> × <?php echo (int) $item['quantity']; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <a href="index.php#products">Continue Shopping</a>
</section>
</body>
</html>
