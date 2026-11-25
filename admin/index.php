<?php
require_once __DIR__ . '/../includes/session.php';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();

function count_query(mysqli $conn, string $query): int
{
    $result = mysqli_query($conn, $query);
    $row = $result ? mysqli_fetch_row($result) : [0];
    return (int) ($row[0] ?? 0);
}

$total_products = count_query($conn, 'SELECT COUNT(*) FROM products');
$total_orders = count_query($conn, 'SELECT COUNT(*) FROM orders');
$pending_orders = count_query($conn, "SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
$completed_orders = count_query($conn, "SELECT COUNT(*) FROM orders WHERE status = 'Delivered'");
$low_stock = count_query($conn, 'SELECT COUNT(*) FROM products WHERE stock <= 5');
$verified_sales_result = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'Delivered' AND (payment_method = 'cod' OR payment_status = 'paid')");
$verified_sales = (float) (mysqli_fetch_row($verified_sales_result)[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cr. Blaze Bakery</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<section class="cart-section">
    <h2>Administrator Dashboard</h2>
    <p>Signed in as <?php echo htmlspecialchars($_SESSION['admin_username'], ENT_QUOTES, 'UTF-8'); ?>.</p>
    <p><a href="logout.php">Log out</a></p>
    <p><a href="products.php">Manage Products</a> | <a href="orders.php">Manage Orders</a></p>

    <div class="products">
        <div class="product"><h3>Products</h3><p><?php echo $total_products; ?></p></div>
        <div class="product"><h3>Total Orders</h3><p><?php echo $total_orders; ?></p></div>
        <div class="product"><h3>Pending Orders</h3><p><?php echo $pending_orders; ?></p></div>
        <div class="product"><h3>Delivered Orders</h3><p><?php echo $completed_orders; ?></p></div>
        <div class="product"><h3>Low Stock</h3><p><?php echo $low_stock; ?></p></div>
        <div class="product"><h3>Verified Sales</h3><p>GH₵<?php echo number_format($verified_sales, 2); ?></p></div>
    </div>
</section>
</body>
</html>
