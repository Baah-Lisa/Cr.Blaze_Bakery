<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/security.php';

$cart = $_SESSION['cart'] ?? [];
$total = 0.0;
$flash_message = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Shopping Cart - Blaze Bakery</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<section class="cart-section">
    <h2>My Shopping Cart</h2>

    <?php if ($flash_message): ?>
        <p class="cart-message cart-message-<?php echo htmlspecialchars($flash_message['type'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flash_message['message'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
        <p>Your shopping cart is empty.</p>
        <a href="index.php#products">Continue Shopping</a>
    <?php else: ?>
        <?php foreach ($cart as $product_id => $quantity): ?>
            <?php
            $product_id = (int) $product_id;
            $quantity = (int) $quantity;
            if ($quantity < 1) {
                unset($_SESSION['cart'][$product_id]);
                continue;
            }
            $stmt = mysqli_prepare($conn, 'SELECT id, name, price, stock FROM products WHERE id = ?');
            if (!$stmt) {
                continue;
            }
            mysqli_stmt_bind_param($stmt, 'i', $product_id);
            if (!mysqli_stmt_execute($stmt)) {
                continue;
            }
            $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$product) {
                unset($_SESSION['cart'][$product_id]);
                continue;
            }
            $stock = (int) $product['stock'];
            if ($stock < 1) {
                unset($_SESSION['cart'][$product_id]);
                continue;
            }
            if ($quantity > $stock) {
                $quantity = $stock;
                $_SESSION['cart'][$product_id] = $stock;
            }
            $subtotal = (float) $product['price'] * $quantity;
            $total += $subtotal;
            ?>
            <div class="cart-item">
                <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p>Price: GH&#8373;<?php echo number_format((float) $product['price'], 2); ?></p>

                <form action="update_cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <label for="quantity-<?php echo $product_id; ?>">Quantity:</label>
                    <input id="quantity-<?php echo $product_id; ?>" type="number" name="quantity" value="<?php echo $quantity; ?>" min="1" max="<?php echo $stock; ?>" required>
                    <button type="submit" class="cart-button">Update Quantity</button>
                </form>

                <form action="update_cart.php" method="POST">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="cart-button">Remove</button>
                </form>

                <p>Subtotal: GH&#8373;<?php echo number_format($subtotal, 2); ?></p>
            </div>
        <?php endforeach; ?>

        <?php if ($total > 0): ?>
            <a href="checkout.php" class="checkout-button">Proceed to Checkout</a>
        <?php else: ?>
            <p class="cart-message cart-message-error">Your cart no longer contains available products.</p>
        <?php endif; ?>

        <form action="update_cart.php" method="POST">
            <input type="hidden" name="action" value="clear">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="cart-button">Clear Cart</button>
        </form>

        <h3>Total: GH&#8373;<?php echo number_format($total, 2); ?></h3>
        <a href="index.php#products">Continue Shopping</a>
    <?php endif; ?>
</section>
</body>
</html>
