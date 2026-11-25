<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/db.php';
mysqli_report(MYSQLI_REPORT_OFF);
$reference = trim((string) ($_GET['ref'] ?? ''));
$order = null;
$items = [];
$migration_required = false;
if (preg_match('/^CB-[A-F0-9]{12}$/', $reference)) {
    $order_stmt = mysqli_prepare($conn, 'SELECT order_reference, customer_name, total_amount, payment_method, payment_status, status, order_date FROM orders WHERE order_reference = ?');
    if ($order_stmt) {
        mysqli_stmt_bind_param($order_stmt, 's', $reference);
        mysqli_stmt_execute($order_stmt);
        $order = mysqli_fetch_assoc(mysqli_stmt_get_result($order_stmt));
    } else {
        $migration_required = true;
    }
    if ($order) {
        $item_stmt = mysqli_prepare($conn, 'SELECT oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id JOIN orders o ON o.id = oi.order_id WHERE o.order_reference = ?');
        if ($item_stmt) {
            mysqli_stmt_bind_param($item_stmt, 's', $reference);
            mysqli_stmt_execute($item_stmt);
            $result = mysqli_stmt_get_result($item_stmt);
            while ($item = mysqli_fetch_assoc($result)) {
                $items[] = $item;
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
    <title>Order Confirmation - Cr. Blaze Bakery</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<section class="cart-section">
    <?php if ($migration_required): ?>
        <h2>Checkout setup is incomplete</h2>
        <p>The database migration for checkout has not been run yet. Run <code>database_migration_stage3.sql</code> in the <code>blaze_bakery</code> database, then try again.</p>
    <?php elseif (!$order): ?>
        <h2>Order not found</h2>
        <p>We could not find that order reference.</p>
    <?php else: ?>
        <h2>Thank you, <?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?>!</h2>
        <p>Your order reference is <strong><?php echo htmlspecialchars($order['order_reference'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
        <p>Order status: <?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Payment status: <?php echo htmlspecialchars($order['payment_status'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($order['payment_method'] === 'momo'): ?>
            <p>Mobile Money payment remains pending until it is verified by the payment provider.</p>
        <?php endif; ?>
        <h3>Total: GH&#8373;<?php echo number_format((float) $order['total_amount'], 2); ?></h3>
        <?php foreach ($items as $item): ?>
            <p><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?> &times; <?php echo (int) $item['quantity']; ?> &mdash; GH&#8373;<?php echo number_format((float) $item['price'] * (int) $item['quantity'], 2); ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
    <p><a href="track_order.php">Track this order later</a></p>
    <a href="index.php#products">Continue Shopping</a>
</section>
</body>
</html>
