<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/includes/security.php";

$query = "SELECT * FROM products";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Product loading failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cr. Blaze Bakery</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <!-- NAVIGATION BAR -->
    <nav class="navbar">
        <a href="index.php">Home</a>
        <a href="#products">Our Products</a>
        <a href="#about">About Us</a>
        <a href="#contact">Contact</a>
        <a href="cart.php">View Cart</a>
    <a href="track_order.php">Track Order</a>
    </nav>

    <!-- LOGO AND BRAND NAME -->
    <header class="site-header">

        <img src="images/logo.jpg"
             alt="Cr. Blaze Bakery Logo"
             class="logo">

        <div>
            <h2>Cr. Blaze Bakery</h2>
            <p>Freshly baked happiness, made with love!</p>
        </div>

    </header>

    <!-- WELCOME SECTION -->
    <section class="welcome">
        <h1>Welcome to Cr. Blaze Bakery!</h1>

        <p>
            Delicious treats, freshly prepared
            to make your day sweeter.
        </p>

        <a href="#products" class="shop-button">
            Explore Our Products
        </a>
    </section>

    <!-- PRODUCTS SECTION -->
    <section id="products" class="products-section">

        <h2>Our Products</h2>

        <p>Discover our delicious bakery treats.</p>

        <div class="products">

    <?php while ($product = mysqli_fetch_assoc($result)): ?>

        <div class="product">

            <img src="images/<?php echo htmlspecialchars($product['image']); ?>"
                 alt="<?php echo htmlspecialchars($product['name']); ?>">

            <h3><?php echo htmlspecialchars($product['name']); ?></h3>

            <p><?php echo htmlspecialchars($product['description']); ?></p>

            <p>GH₵<?php echo number_format($product['price'], 2); ?></p>

            <p>Available: <?php echo (int)$product['stock']; ?></p>
            <form action="add_to_cart.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="product_id"
           value="<?php echo (int)$product['id']; ?>">

    <button type="submit" class="cart-button" <?php echo (int) $product['stock'] < 1 ? 'disabled' : ''; ?>>
        Add to Cart
    </button>
</form>

        </div>

    <?php endwhile; ?>

</div>

    </section>

    <!-- ABOUT US -->
    <section id="about" class="about-section">

        <h2>About Us</h2>

        <p>
            Cr. Blaze Bakery is dedicated to bringing
            you delicious treats made with love.
            We believe every bite should bring happiness.
        </p>

    </section>

    <!-- CONTACT -->
    <section id="contact" class="contact-section">

        <h2>Contact Us</h2>

        <p>Have questions or want to place an order?</p>

        <p>We would love to hear from you!</p>

    </section>

    <!-- FOOTER -->
    <footer>
        <p>&copy; 2026 Cr. Blaze Bakery. All rights reserved.</p>
    </footer>

</body>
</html>
