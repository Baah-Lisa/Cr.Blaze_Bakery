
<?php
ob_start();
require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/includes/security.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        set_flash_message('error', 'Your request could not be verified. Please try again.');
        header("Location: cart.php");
        exit();
    }

    $action = $_POST['action'] ?? 'update';

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        set_flash_message('success', 'Your cart has been cleared.');
        header("Location: cart.php");
        exit();
    }

    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if (!$product_id || $product_id < 1 || !isset($_SESSION['cart'][$product_id])) {
        set_flash_message('error', 'That cart item could not be found.');
        header("Location: cart.php");
        exit();
    }

    if ($action === 'remove') {
        unset($_SESSION['cart'][$product_id]);
        set_flash_message('success', 'The item was removed from your cart.');
        header("Location: cart.php");
        exit();
    }

    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    $query = "SELECT stock FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        set_flash_message('error', 'The cart could not be updated right now.');
        header("Location: cart.php");
        exit();
    }

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    if ($product && $quantity !== false && $quantity >= 1 && $quantity <= (int) $product["stock"]) {
        $_SESSION["cart"][$product_id] = $quantity;
        set_flash_message('success', 'Your cart was updated.');
    } elseif ($product && (int) $product['stock'] < 1) {
        unset($_SESSION['cart'][$product_id]);
        set_flash_message('warning', 'That product is now out of stock and was removed from your cart.');
    } else {
        set_flash_message('error', 'Please choose a quantity within the available stock.');
    }
}

header("Location: cart.php");
exit();
?>
