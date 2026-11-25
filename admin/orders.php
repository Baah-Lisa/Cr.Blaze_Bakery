<?php
require_once __DIR__ . '/../includes/session.php';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();

$allowed_statuses = ['Pending', 'Confirmed', 'Processing', 'Ready for Delivery', 'Delivered', 'Cancelled'];
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_csrf_is_valid($_POST['csrf_token'] ?? null)) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    if ($order_id && in_array($status, $allowed_statuses, true)) {
        $stmt = mysqli_prepare($conn, 'UPDATE orders SET status = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'si', $status, $order_id);
        mysqli_stmt_execute($stmt);
        $message = 'Order status updated.';
    }
}

$filter_status = $_GET['status'] ?? '';
$filter_from = $_GET['date_from'] ?? '';
$filter_to = $_GET['date_to'] ?? '';
$where = [];
$params = [];
$types = '';

if (in_array($filter_status, $allowed_statuses, true)) {
    $where[] = 'status = ?';
    $params[] = $filter_status;
    $types .= 's';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_from)) {
    $where[] = 'order_date >= ?';
    $params[] = $filter_from . ' 00:00:00';
    $types .= 's';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_to)) {
    $where[] = 'order_date < DATE_ADD(?, INTERVAL 1 DAY)';
    $params[] = $filter_to . ' 00:00:00';
    $types .= 's';
}

$query = 'SELECT id, order_reference, customer_name, phone, total_amount, payment_method, payment_status, status, order_date FROM orders';
if ($where) {
    $query .= ' WHERE ' . implode(' AND ', $where);
}
$query .= ' ORDER BY order_date DESC';
$order_stmt = mysqli_prepare($conn, $query);
if ($order_stmt && $params) {
    $references = [$types];
    foreach ($params as $key => $value) {
        $references[] = &$params[$key];
    }
    call_user_func_array([$order_stmt, 'bind_param'], $references);
}
if ($order_stmt) {
    mysqli_stmt_execute($order_stmt);
    $orders = mysqli_stmt_get_result($order_stmt);
} else {
    $orders = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Cr. Blaze Bakery</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<section class="cart-section">
    <h2>Manage Orders</h2>
    <p><a href="index.php">Dashboard</a> | <a href="products.php">Products</a> | <a href="logout.php">Log out</a></p>
    <?php if ($message): ?><p class="cart-message cart-message-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <div class="checkout-form">
        <form method="GET">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach ($allowed_statuses as $status): ?><option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filter_status === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
            </select>
            <label for="date_from">From date</label><input id="date_from" name="date_from" type="date" value="<?php echo htmlspecialchars($filter_from, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="date_to">To date</label><input id="date_to" name="date_to" type="date" value="<?php echo htmlspecialchars($filter_to, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="checkout-button">Filter Orders</button>
        </form>
    </div>
    <?php if ($orders): while ($order = mysqli_fetch_assoc($orders)): ?>
        <div class="cart-item">
            <h3><?php echo htmlspecialchars($order['order_reference'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Total: GH₵<?php echo number_format((float) $order['total_amount'], 2); ?> | Payment: <?php echo htmlspecialchars($order['payment_method'] . ' / ' . $order['payment_status'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a href="order_details.php?id=<?php echo (int) $order['id']; ?>">View details</a></p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                <select name="status">
                    <?php foreach ($allowed_statuses as $status): ?><option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $status === $order['status'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="cart-button">Update Status</button>
            </form>
        </div>
    <?php endwhile; endif; ?>
</section>
</body>
</html>
