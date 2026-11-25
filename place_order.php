<?php
ob_start();
require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/paystack.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    set_flash_message('error', 'Your request could not be verified.');
    header('Location: checkout.php');
    exit();
}

if (!hash_equals((string) ($_SESSION['checkout_key'] ?? ''), (string) ($_POST['checkout_key'] ?? ''))) {
    set_flash_message('error', 'This checkout session has expired. Please try again.');
    header('Location: checkout.php');
    exit();
}

$full_name = trim((string) ($_POST['full_name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$payment_method = $_POST['payment_method'] ?? '';

// Until a real provider is configured and verified server-side, MoMo stays pending.

if ($full_name === '' || mb_strlen($full_name) > 100 || $phone === '' || mb_strlen($phone) > 20 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) || mb_strlen($email) > 100 || ($payment_method === 'momo' && $email === '') || $address === '' || mb_strlen($address) > 500 || !in_array($payment_method, ['cod', 'momo'], true) || ($_POST['delivery_confirmed'] ?? '') !== '1') {
    set_flash_message('error', 'Please complete all checkout fields and confirm your delivery location.');
    header('Location: checkout.php');
    exit();
}

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    set_flash_message('error', 'Your cart is empty.');
    header('Location: cart.php');
    exit();
}

$existing_stmt = mysqli_prepare($conn, 'SELECT order_reference FROM orders WHERE idempotency_key = ? LIMIT 1');
if ($existing_stmt) {
    $existing_key = (string) $_SESSION['checkout_key'];
    mysqli_stmt_bind_param($existing_stmt, 's', $existing_key);
    mysqli_stmt_execute($existing_stmt);
    $existing_order = mysqli_fetch_assoc(mysqli_stmt_get_result($existing_stmt));
    if ($existing_order && !empty($existing_order['order_reference'])) {
        $_SESSION['cart'] = [];
        unset($_SESSION['checkout_key']);
        header('Location: order_confirmation.php?ref=' . rawurlencode($existing_order['order_reference']));
        exit();
    }
}

$order_reference = 'CB-' . strtoupper(bin2hex(random_bytes(6)));
$payment_status = 'pending';
$order_status = 'Pending';

mysqli_begin_transaction($conn);
$transaction_active = true;

try {
    $customer_stmt = mysqli_prepare($conn, 'INSERT INTO customers (full_name, phone, email, address) VALUES (?, ?, ?, ?)');
    if (!$customer_stmt) {
        throw new RuntimeException('Customer preparation failed.');
    }
    mysqli_stmt_bind_param($customer_stmt, 'ssss', $full_name, $phone, $email, $address);
    if (!mysqli_stmt_execute($customer_stmt)) {
        throw new RuntimeException('Customer insert failed.');
    }
    $customer_id = mysqli_insert_id($conn);

    $total = 0.0;
    $validated_items = [];
    $product_stmt = mysqli_prepare($conn, 'SELECT id, price, stock FROM products WHERE id = ? FOR UPDATE');
    if (!$product_stmt) {
        throw new RuntimeException('Product preparation failed.');
    }

    foreach ($cart as $product_id => $requested_quantity) {
        $product_id = (int) $product_id;
        $quantity = (int) $requested_quantity;
        mysqli_stmt_bind_param($product_stmt, 'i', $product_id);
        if (!mysqli_stmt_execute($product_stmt)) {
            throw new RuntimeException('Product lookup failed.');
        }
        $product = mysqli_fetch_assoc(mysqli_stmt_get_result($product_stmt));

        if (!$product || $quantity < 1 || $quantity > (int) $product['stock']) {
            throw new RuntimeException('Stock changed. Please review your cart.');
        }

        $price = (float) $product['price'];
        $total += $price * $quantity;
        $validated_items[] = ['product_id' => $product_id, 'quantity' => $quantity, 'price' => $price];
    }

    if (!$validated_items) {
        throw new RuntimeException('Your cart is empty.');
    }

    $order_stmt = mysqli_prepare($conn, 'INSERT INTO orders (order_reference, customer_id, customer_name, phone, address, delivery_location, total_amount, payment_method, payment_status, status, idempotency_key) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$order_stmt) {
        throw new RuntimeException('Order preparation failed. Run database_migration_stage3.sql first.');
    }
    $idempotency_key = $_SESSION['checkout_key'];
    $delivery_location = DELIVERY_COMMUNITY;
    mysqli_stmt_bind_param($order_stmt, 'sissssdssss', $order_reference, $customer_id, $full_name, $phone, $address, $delivery_location, $total, $payment_method, $payment_status, $order_status, $idempotency_key);
    if (!mysqli_stmt_execute($order_stmt)) {
        throw new RuntimeException('Order insert failed.');
    }
    $order_id = mysqli_insert_id($conn);

    $item_stmt = mysqli_prepare($conn, 'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
    $stock_stmt = mysqli_prepare($conn, 'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
    if (!$item_stmt || !$stock_stmt) {
        throw new RuntimeException('Order item preparation failed.');
    }

    foreach ($validated_items as $item) {
        mysqli_stmt_bind_param($item_stmt, 'iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
        if (!mysqli_stmt_execute($item_stmt)) {
            throw new RuntimeException('Order item insert failed.');
        }
        mysqli_stmt_bind_param($stock_stmt, 'iii', $item['quantity'], $item['product_id'], $item['quantity']);
        if (!mysqli_stmt_execute($stock_stmt)) {
            throw new RuntimeException('Stock update failed.');
        }
        if (mysqli_stmt_affected_rows($stock_stmt) !== 1) {
            throw new RuntimeException('Stock changed while placing the order.');
        }
    }

    mysqli_commit($conn);
    $transaction_active = false;
    $_SESSION['cart'] = [];
    unset($_SESSION['checkout_key']);

    if ($payment_method === 'momo' && getenv('PAYSTACK_SECRET_KEY') && $email !== '') {
        $callback_url = (getenv('BLAZE_PUBLIC_URL') ?: 'http://localhost/Cr.blaze_bakery') . '/payment_callback.php';
        $payment = paystack_initialize($email, $total, $order_reference, $callback_url);
        $authorization_url = $payment['data']['authorization_url'] ?? null;
        $payment_reference = $payment['data']['reference'] ?? null;
        if ($authorization_url && $payment_reference) {
            $payment_stmt = mysqli_prepare($conn, 'UPDATE orders SET payment_reference = ? WHERE id = ?');
            if ($payment_stmt) {
                mysqli_stmt_bind_param($payment_stmt, 'si', $payment_reference, $order_id);
                if (mysqli_stmt_execute($payment_stmt) && mysqli_stmt_affected_rows($payment_stmt) === 1) {
                    header('Location: ' . $authorization_url);
                    exit();
                }
            }
            error_log('Cr. Blaze Bakery payment reference could not be saved for order ' . $order_reference . '.');
        }
    }

    header('Location: order_confirmation.php?ref=' . rawurlencode($order_reference));
    exit();
} catch (Throwable $exception) {
    if ($transaction_active) {
        mysqli_rollback($conn);
    }
    error_log('Cr. Blaze Bakery order error: ' . $exception->getMessage());
    set_flash_message('error', 'We could not place your order right now. Please try again or contact the bakery.');
    header('Location: checkout.php');
    exit();
}
