<?php
require_once '../includes/config.php';

// Kung naka-login na bilang admin, dala-dalahin sa admin dashboard
if (isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/admin/index.php');
    exit;
}
// Kung naka-login na bilang user, dala-dalahin sa homepage
if (isUserLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$db    = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $ok = false;
        if ($admin) {
            if (password_verify($password, $admin['password'])) {
                $ok = true;
            } elseif ($admin['password'] === md5($password) || $admin['password'] === $password) {
                $ok = true;
                $h = password_hash($password, PASSWORD_BCRYPT);
                $upd = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $upd->bind_param('si', $h, $admin['id']);
                $upd->execute();
                $upd->close();
            }
        }

        if ($ok) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username']  = $admin['username'];
            header('Location: ' . SITE_URL . '/admin/index.php');
            exit;
        } else {
            $error = 'Incorrect admin username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – HopePaws</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/admin.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
    <style>
        .login-note { margin-top:1rem; padding:.85rem; background:var(--gray-50); border:1px solid var(--gray-100); border-radius:var(--radius-sm); text-align:center; }
        .login-note p { margin:0; font-size:.82rem; color:var(--gray-500); }
        .login-note a { color:var(--peach); font-weight:600; text-decoration:none; }
        .login-note a:hover { text-decoration:underline; }
        body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem 1rem;background:#f3f4f6;}
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <span class="icon">⚙️</span>
            <h1>HopePaws</h1>
            <p>Admin Panel Login</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="username" required placeholder="Enter admin username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       autocomplete="username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••"
                       autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:.65rem;">
                Sign In as Admin →
            </button>
        </form>

        <div class="login-note">
            <p>Not an admin? <a href="<?= SITE_URL ?>/login.php">Go to main login</a></p>
        </div>

        <p style="text-align:center;margin-top:1rem;font-size:.82rem;">
            <a href="<?= SITE_URL ?>/index.php" style="color:var(--peach);text-decoration:none;">← Back to Website</a>
        </p>
    </div>
</div>
</body>
</html>
