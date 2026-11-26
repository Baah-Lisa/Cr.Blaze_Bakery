<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/security.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = null;
$username = '';
$csrf_token = csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Your request could not be verified. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1');
        $admin = null;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        }

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
            $login_stmt = mysqli_prepare($conn, 'UPDATE admin_users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?');
            if ($login_stmt) {
                mysqli_stmt_bind_param($login_stmt, 'i', $_SESSION['admin_id']);
                mysqli_stmt_execute($login_stmt);
            }
            header('Location: index.php');
            exit();
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - Cr. Blaze Bakery</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<section class="cart-section">
<h2>Administrator Login</h2>
<?php if ($error): ?>
<p class="cart-message cart-message-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<div class="checkout-form">
<form method="POST" action="login.php">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
<label for="username">Username</label>
<input id="username" name="username" type="text" maxlength="8" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="username">
<label for="password">Password</label>
<div class="password-field">
<input id="password" name="password" type="password" required autocomplete="current-password">
<button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-pressed="false">Show password</button>
</div>
<button type="submit" class="checkout-button">Log In</button>
</form>
</div>
<p><a href="create_admin.php">First-time local administrator setup</a></p>
</section>
<script src="../assets/password-toggle.js"></script>
</body>
</html>
