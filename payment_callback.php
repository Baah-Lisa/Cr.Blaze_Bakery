<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/paystack.php';

$reference = trim((string) ($_GET['reference'] ?? ''));
$verified = false;

if (preg_match('/^CB-[A-F0-9]{12}$/', $reference)) {
    $response = paystack_verify($reference);
    $data = $response['data'] ?? null;
    if (($response['status'] ?? false) === true && is_array($data) && ($data['status'] ?? '') === 'success') {
        $stmt = mysqli_prepare($conn, 'SELECT id, total_amount FROM orders WHERE payment_reference = ? AND payment_method = "momo" LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $reference);
            mysqli_stmt_execute($stmt);
            $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            $amount_matches = $order
                && ($data['reference'] ?? '') === $reference
                && (int) ($data['amount'] ?? 0) === (int) round((float) $order['total_amount'] * 100)
                && ($data['currency'] ?? '') === 'GHS';
            if ($amount_matches) {
                $update = mysqli_prepare($conn, 'UPDATE orders SET payment_status = "paid", payment_verified_at = CURRENT_TIMESTAMP WHERE id = ? AND payment_status <> "paid"');
                if ($update) {
                    mysqli_stmt_bind_param($update, 'i', $order['id']);
                    $verified = mysqli_stmt_execute($update);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Payment Status - Cr. Blaze Bakery</title><link rel="stylesheet" href="assets/style.css"></head>
<body><section class="cart-section">
<?php if ($verified): ?><h2>Payment verified</h2><p>Your Mobile Money payment was verified successfully.</p><?php else: ?><h2>Payment not verified</h2><p>We could not verify this payment. Your order remains pending. Please contact the bakery if money was deducted.</p><?php endif; ?>
<a href="index.php#products">Continue Shopping</a>
</section></body></html>
