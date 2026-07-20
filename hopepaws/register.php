<?php
require_once 'includes/config.php';

// Redirect to user register page if it exists, otherwise handle registration here
if (isUserLoggedIn())  { header('Location: ' . SITE_URL . '/index.php'); exit; }
if (isAdminLoggedIn()) { header('Location: ' . SITE_URL . '/admin/index.php'); exit; }

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$username || !$email || !$password) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if username already taken
        $check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param('ss', $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Username or email already in use.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $username, $email, $hash);
            if ($stmt->execute()) {
                $success = 'Account created! You can now log in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – HopePaws</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
    <style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem 1rem;background:#f3f4f6;}</style>
</head>
<body>
<div class="card">
    <div class="card-left">
        <div class="brand-icon">🐾</div>
        <div class="brand-name">Hope Paws</div>
        <p class="brand-tagline">Create an account to start your adoption journey.</p>
        <div class="brand-footer">© <?= date('Y') ?> HopePaws Animal Rescue · Boac, Marinduque</div>
    </div>
    <div class="card-right">
        <h2>Create Account</h2>
        <p class="sub">Join HopePaws and find your new best friend.</p>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1rem;padding:.75rem;background:#d4edda;border-radius:8px;color:#155724;">
                ✅ <?= $success ?> <a href="<?= SITE_URL ?>/login.php?role=user">Login here</a>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-box">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" class="login-form show">
            <div class="form-group"><label>Username *</label><input type="text" name="username" required placeholder="Choose a username" value="<?= sanitize($_POST['username'] ?? '') ?>"></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="your@email.com" value="<?= sanitize($_POST['email'] ?? '') ?>"></div>
            <div class="form-group"><label>Password *</label><input type="password" name="password" required placeholder="At least 6 characters"></div>
            <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" required placeholder="Repeat your password"></div>
            <button type="submit" class="btn-login user-btn">Create Account →</button>
        </form>
        <?php endif; ?>

        <div class="register-link">
            Already have an account? <a href="<?= SITE_URL ?>/login.php?role=user">Login here</a>
        </div>
    </div>
</div>
</body>
</html>
