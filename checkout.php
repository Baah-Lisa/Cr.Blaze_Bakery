<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/security.php';

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0.0;
$error = null;
$paystack_configured = (bool) getenv('PAYSTACK_SECRET_KEY');

if (empty($cart)) {
    $error = 'Your cart is empty. Add a product before checking out.';
} else {
    $stmt = mysqli_prepare($conn, 'SELECT id, name, price, stock FROM products WHERE id = ?');

    if (!$stmt) {
        $error = 'Checkout is temporarily unavailable.';
    } else {
        foreach ($cart as $product_id => $requested_quantity) {
            $product_id = (int) $product_id;
            $requested_quantity = (int) $requested_quantity;
            if ($requested_quantity < 1) {
                unset($_SESSION['cart'][$product_id]);
                continue;
            }
            mysqli_stmt_bind_param($stmt, 'i', $product_id);
            if (!mysqli_stmt_execute($stmt)) {
                $error = 'Checkout is temporarily unavailable.';
                break;
            }
            $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!$product || (int) $product['stock'] < 1) {
                unset($_SESSION['cart'][$product_id]);
                continue;
            }

            $quantity = min($requested_quantity, (int) $product['stock']);
            $subtotal = (float) $product['price'] * $quantity;
            $items[] = ['product' => $product, 'quantity' => $quantity, 'subtotal' => $subtotal];
            $total += $subtotal;
        }

        if (!$items && !$error) {
            $error = 'The products in your cart are no longer available.';
        }
    }
}

$_SESSION['checkout_key'] ??= bin2hex(random_bytes(32));
$flash_message = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Cr. Blaze Bakery</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<section class="cart-section">
    <h2>Checkout</h2>

    <?php if ($flash_message): ?>
        <p class="cart-message cart-message-<?php echo htmlspecialchars($flash_message['type'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flash_message['message'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="cart-message cart-message-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="cart.php">Return to cart</a>
    <?php else: ?>
        <div class="checkout-form">
            <h3>Order total: GH&#8373;<?php echo number_format($total, 2); ?></h3>
            <p>Delivery is available only within <?php echo htmlspecialchars(DELIVERY_COMMUNITY, ENT_QUOTES, 'UTF-8'); ?>.</p>
            <div class="checkout-summary">
                <h3>Order summary</h3>
                <?php foreach ($items as $item): ?>
                    <p><?php echo htmlspecialchars($item['product']['name'], ENT_QUOTES, 'UTF-8'); ?> &times; <?php echo (int) $item['quantity']; ?> &mdash; GH&#8373;<?php echo number_format($item['subtotal'], 2); ?></p>
                <?php endforeach; ?>
            </div>

            <form action="place_order.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="checkout_key" value="<?php echo htmlspecialchars($_SESSION['checkout_key'], ENT_QUOTES, 'UTF-8'); ?>">

                <label for="full_name">Full name</label>
                <input id="full_name" name="full_name" type="text" maxlength="100" autocomplete="name" required>

                <label for="phone">Phone number</label>
                <input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" required>

                <label for="email">Email address <span>(required for Mobile Money)</span></label>
                <input id="email" name="email" type="email" maxlength="100" autocomplete="email">

                <label for="address">Delivery address</label>
                <textarea id="address" name="address" maxlength="500" autocomplete="street-address" required></textarea>

                <label for="payment_method">Payment method</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="cod">Cash on Delivery</option>
                    <option value="momo">Mobile Money<?php echo $paystack_configured ? '' : ' (pending test mode)'; ?></option>
                </select>
                <?php if (!$paystack_configured): ?><p id="momo-warning" class="cart-message cart-message-warning" hidden>Mobile Money credentials are not configured yet. A Mobile Money order will remain pending until a real provider verifies payment.</p><?php endif; ?>

                <label>
                    <input type="checkbox" name="delivery_confirmed" value="1" required>
                    I confirm this address is within the stated delivery community.
                </label>

                <button type="submit" class="checkout-button">Place Order</button>
            </form>
        </div>
    <?php endif; ?>
</section>
<?php if (!$error): ?>
<script>
    const paymentMethod = document.getElementById('payment_method');
    const emailField = document.getElementById('email');
    const momoWarning = document.getElementById('momo-warning');

    function updatePaymentRequirements() {
        const isMomo = paymentMethod.value === 'momo';
        emailField.required = isMomo;
        if (momoWarning) {
            momoWarning.hidden = !isMomo;
        }
    }

    paymentMethod.addEventListener('change', updatePaymentRequirements);
    updatePaymentRequirements();
</script>
<?php endif; ?>
</body>
</html>
