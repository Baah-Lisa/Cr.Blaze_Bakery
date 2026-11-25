
<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$order = null;
$items = [];

if ($order_id) {
    $stmt = mysqli_prepare($conn, 'SELECT order_reference, customer_name, phone, address, delivery_location, total_amount, payment_method, payment_status, status, order_date FROM orders WHERE id = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $order_id);
        mysqli_stmt_execute($stmt);
        $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }

    if ($order) {
        $item_stmt = mysqli_prepare($conn, 'SELECT oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
        if ($item_stmt) {
            mysqli_stmt_bind_param($item_stmt, 'i', $order_id);
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
    <title>Order Details - Cr. Blaze Bakery</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<section class="cart-section">
    <h2>Order Details</h2>
    <p><a href="orders.php">Back to Orders</a> | <a href="logout.php">Log out</a></p>
    <?php if (!$order): ?>
        <p class="cart-message cart-message-error">Order not found.</p>
    <?php else: ?>
        <div class="cart-item">
            <h3><?php echo htmlspecialchars($order['order_reference'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p>Customer: <?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Phone: <?php echo htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Address: <?php echo nl2br(htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8')); ?></p>
            <p>Delivery area: <?php echo htmlspecialchars($order['delivery_location'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Status: <?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Payment: <?php echo htmlspecialchars($order['payment_method'] . ' / ' . $order['payment_status'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Total: GH₵<?php echo number_format((float) $order['total_amount'], 2); ?></p>
            <h3>Items</h3>
            <?php foreach ($items as $item): ?>
                <p><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?> × <?php echo (int) $item['quantity']; ?> — GH₵<?php echo number_format((float) $item['price'] * (int) $item['quantity'], 2); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
</body>
</html>
