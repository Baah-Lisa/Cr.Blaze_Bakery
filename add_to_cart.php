
<?php
ob_start();
require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/includes/security.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        set_flash_message('error', 'Your request could not be verified. Please try again.');
        header("Location: index.php#products");
        exit();
    }

    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if (!$product_id || $product_id < 1) {
        set_flash_message('error', 'That product could not be added to your cart.');
        header("Location: index.php#products");
        exit();
    }

    $query = "SELECT id, name, price, stock FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    if ($product && $product["stock"] > 0) {

        if (!isset($_SESSION["cart"])) {
            $_SESSION["cart"] = [];
        }

        if (!isset($_SESSION["cart"][$product_id])) {
            $_SESSION["cart"][$product_id] = 1;
        } else {
            if ($_SESSION["cart"][$product_id] < $product["stock"]) {
                $_SESSION["cart"][$product_id]++;
            } else {
                set_flash_message('warning', 'You already have the maximum available quantity of that product in your cart.');
            }
        }

        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('success', $product['name'] . ' was added to your cart.');
        }
    } else {
        set_flash_message('error', 'That product is unavailable.');
    }

    header("Location: index.php#products");
    exit();
}

header("Location: index.php");
exit();
?>
