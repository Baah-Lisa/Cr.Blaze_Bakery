<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security.php';

$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$setup_token = getenv('BLAZE_ADMIN_SETUP_TOKEN') ?: '';
$setup_available = $is_local || $setup_token !== '';
$admin_count = 0;
$count_result = mysqli_query($conn, 'SELECT COUNT(*) FROM admin_users');
if ($count_result) {
    $admin_count = (int) mysqli_fetch_row($count_result)[0];
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin_count === 0) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Your request could not be verified.';
    } elseif (!$is_local && ($setup_token === '' || !hash_equals($setup_token, (string) ($_POST['setup_token'] ?? '')))) {
        $error = 'The administrator setup token is invalid.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (!preg_match('/^[A-Za-z0-9_.-]{3,8}$/', $username)) {
            $error = 'Use a username containing 3-8 letters, numbers, dots, dashes, or underscores.';
        } elseif (strlen($password) < 12 || $password !== $confirmation) {
            $error = 'Passwords must match and contain at least 12 characters.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 'INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $username, $hash);
                $success = mysqli_stmt_execute($stmt);
            }
            if (!$success) {
                $error = 'The administrator account could not be created.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin - Cr. Blaze Bakery</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<section class="cart-section">
    <?php if (!$setup_available): ?>
        <h2>Setup unavailable</h2>
        <p>Administrator setup is available locally, or after a temporary server setup token is configured.</p>
    <?php elseif ($admin_count > 0 || $success): ?>
        <h2>Administrator setup is complete</h2>
        <p>Remove <code>BLAZE_ADMIN_SETUP_TOKEN</code> from the server environment, then use the administrator login page.</p>
        <a href="login.php">Administrator login</a>
    <?php else: ?>
        <h2>Create the first administrator</h2>
        <?php if ($error): ?><p class="cart-message cart-message-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <div class="checkout-form">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (!$is_local): ?>
                    <label for="setup_token">Temporary setup token</label>
                    <input id="setup_token" name="setup_token" type="password" required autocomplete="off">
                <?php endif; ?>
                <label for="username">Username</label>
                <input id="username" name="username" maxlength="8" required autocomplete="username">
                <label for="password">Password</label>
                <div class="password-field">
                    <input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
                    <button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-pressed="false">Show password</button>
                </div>
                <label for="password_confirmation">Confirm password</label>
                <div class="password-field">
                    <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" required autocomplete="new-password">
                    <button type="button" class="password-toggle" data-password-toggle aria-controls="password_confirmation" aria-pressed="false">Show password</button>
                </div>
                <button type="submit" class="checkout-button">Create Administrator</button>
            </form>
        </div>
    <?php endif; ?>
</section>
<script src="../assets/password-toggle.js"></script>
</body>
</html>
