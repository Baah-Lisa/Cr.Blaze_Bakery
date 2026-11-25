<?php
require_once __DIR__ . '/../includes/session.php';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();

$message = null;
$message_type = 'success';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $message = 'Your request could not be verified.';
        $message_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

        if ($action === 'delete' && $product_id) {
            $stmt = mysqli_prepare($conn, 'DELETE FROM products WHERE id = ?');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $product_id);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Product deleted.';
                } else {
                    $message = 'Product could not be deleted. It may already be used in an order.';
                    $message_type = 'error';
                }
            }
        } elseif ($action === 'save') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

            if ($name === '' || strlen($name) > 100 || $price === false || $price < 0 || $stock === false || $stock < 0) {
                $message = 'Enter a valid name, price, and non-negative stock quantity.';
                $message_type = 'error';
            } else {
                $image_name = null;
                if (!empty($_FILES['image']['tmp_name'])) {
                    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK || $_FILES['image']['size'] > 2 * 1024 * 1024) {
                        $message = 'The image must be no larger than 2 MB.';
                        $message_type = 'error';
                    } else {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($_FILES['image']['tmp_name']);
                        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                        if (!isset($extensions[$mime]) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
                            $message = 'Only JPG, PNG, and WebP images are allowed.';
                            $message_type = 'error';
                        } else {
                            $image_name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../images/' . $image_name);
                        }
                    }
                }

                if (!$message) {
                    if ($product_id) {
                        if ($image_name) {
                            $stmt = mysqli_prepare($conn, 'UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?');
                            mysqli_stmt_bind_param($stmt, 'ssdisi', $name, $description, $price, $stock, $image_name, $product_id);
                        } else {
                            $stmt = mysqli_prepare($conn, 'UPDATE products SET name = ?, description = ?, price = ?, stock = ? WHERE id = ?');
                            mysqli_stmt_bind_param($stmt, 'ssdii', $name, $description, $price, $stock, $product_id);
                        }
                    } else {
                        $image_name ??= 'cookies.jpg';
                        $stmt = mysqli_prepare($conn, 'INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)');
                        mysqli_stmt_bind_param($stmt, 'ssdis', $name, $description, $price, $stock, $image_name);
                    }

                    if ($stmt && mysqli_stmt_execute($stmt)) {
                        $message = 'Product saved.';
                    } else {
                        $message = 'The product could not be saved.';
                        $message_type = 'error';
                    }
                }
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt = mysqli_prepare($conn, 'SELECT id, name, description, price, stock, image FROM products WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $edit_id);
        mysqli_stmt_execute($stmt);
        $editing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

$products = mysqli_query($conn, 'SELECT id, name, description, price, stock, image FROM products ORDER BY id DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Cr. Blaze Bakery</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<section class="cart-section">
    <h2>Manage Products</h2>
    <p><a href="index.php">Dashboard</a> | <a href="orders.php">Orders</a> | <a href="logout.php">Log out</a></p>
    <?php if ($message): ?><p class="cart-message cart-message-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

    <div class="checkout-form">
        <h3><?php echo $editing ? 'Edit Product' : 'Add Product'; ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save">
            <?php if ($editing): ?><input type="hidden" name="product_id" value="<?php echo (int) $editing['id']; ?>"><?php endif; ?>
            <label>Name</label><input name="name" maxlength="100" required value="<?php echo htmlspecialchars($editing['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <label>Description</label><textarea name="description"><?php echo htmlspecialchars($editing['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <label>Price</label><input name="price" type="number" min="0" step="0.01" required value="<?php echo htmlspecialchars((string) ($editing['price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label>Stock</label><input name="stock" type="number" min="0" required value="<?php echo htmlspecialchars((string) ($editing['stock'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?>">
            <label>Image (JPG, PNG, WebP; max 2 MB)</label><input name="image" type="file" accept="image/jpeg,image/png,image/webp">
            <button type="submit" class="checkout-button">Save Product</button>
        </form>
    </div>

    <?php while ($product = mysqli_fetch_assoc($products)): ?>
        <div class="cart-item">
            <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p>GH₵<?php echo number_format((float) $product['price'], 2); ?> | Stock: <?php echo (int) $product['stock']; ?></p>
            <a href="products.php?edit=<?php echo (int) $product['id']; ?>">Edit</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                <button type="submit" class="cart-button">Delete</button>
            </form>
        </div>
    <?php endwhile; ?>
</section>
</body>
</html>
