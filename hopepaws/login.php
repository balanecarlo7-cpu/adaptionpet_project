<?php
/*
 * =============================================================================
 * FILE: login.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang MAIN LOGIN PAGE ng HopePaws. Dito naglo-login ang parehong
 *   Admin at Regular User. May role selector (User o Admin) bago ipasok
 *   ang credentials.
 *
 * DALAWANG ROLES (Two Roles):
 *   1. USER  - Regular na tao na gustong mag-adopt ng pet
 *   2. ADMIN - May-ari ng shelter na nag-aadminister ng website
 *
 * LOGIC NG LOGIN:
 *   - Kung admin: Naghahanap sa 'admin_users' table, may bcrypt password check
 *     at auto-upgrade mula sa MD5 papuntang bcrypt para sa mas mahusay na security
 *   - Kung user: Naghahanap sa 'users' table, ginagamit ang password_verify()
 *
 * KAPAG MATAGUMPAY:
 *   - Admin  → ire-redirect sa admin/index.php
 *   - User   → ire-redirect sa index.php (homepage)
 *
 * GINAGAMIT NA FILES:
 *   - includes/config.php (getDB, sanitize, session functions)
 * =============================================================================
 */

require_once 'includes/config.php';

// Kung naka-login na bilang admin, dala-dalahin sa admin dashboard
if (isAdminLoggedIn()) { header('Location: ' . SITE_URL . '/admin/index.php'); exit; }
// Kung naka-login na bilang user, dala-dalahin sa homepage
if (isUserLoggedIn())  { header('Location: ' . SITE_URL . '/index.php'); exit; }

$db    = getDB();
$error = '';
$role  = $_POST['role'] ?? $_GET['role'] ?? '';

// =============================================================================
// FORM PROCESSING: Pinoproseso ang login form kapag nai-submit
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role) {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Tingnan kung kumpleto ang form — kailangan ng username at password
    if (!$username || !$password) {
        $error = 'Please enter your username and password.';

    } elseif ($role === 'admin') {
        // ================================================================
        // ADMIN LOGIN PROCESS
        // Naghahanap ng admin account sa 'admin_users' table.
        // Sinusuportahan ang tatlong uri ng password format:
        //   1. bcrypt (pinaka-moderno at secure)
        //   2. MD5    (lumang format — awtomatikong ina-upgrade sa bcrypt)
        //   3. plain text (backup lang — ina-upgrade din sa bcrypt)
        // ================================================================
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->bind_param('s', $username); $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc(); $stmt->close();
        $ok = false;
        if ($admin) {
            if (password_verify($password, $admin['password'])) { $ok = true; }
            // Kung MD5 o plain text ang password, i-verify at i-upgrade sa bcrypt
            elseif ($admin['password'] === md5($password) || $admin['password'] === $password) {
                $ok = true;
                $h = password_hash($password, PASSWORD_BCRYPT); // I-hash gamit ang bcrypt
                $upd = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $upd->bind_param('si', $h, $admin['id']); $upd->execute(); $upd->close();
            }
        }
        if ($ok) {
            // Matagumpay na login — i-set ang session variables para sa admin
            $_SESSION['admin_logged_in'] = true; $_SESSION['admin_username'] = $admin['username'];
            header('Location: ' . SITE_URL . '/admin/index.php'); exit;
        } else { $error = 'Incorrect admin username or password.'; }

    } else {
        // ================================================================
        // USER LOGIN PROCESS
        // Naghahanap ng user account sa 'users' table.
        // Ginagamit ang password_verify() para i-check ang bcrypt password.
        // ================================================================
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?"); // sql syntax para sa SELECT
        $stmt->bind_param('s', $username); // I-bind ang parameter sa prepared statement
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($user && password_verify($password, $user['password'])) {
            // Matagumpay na login — i-set ang session variables para sa user
            $_SESSION['user_logged_in'] = true; $_SESSION['user_id'] = $user['id']; $_SESSION['user_username'] = $user['username'];
            header('Location: ' . SITE_URL . '/index.php'); exit;
        } else { $error = 'Incorrect username or password.'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome – HopePaws</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
    <style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem 1rem;background:#f3f4f6;}</style>
</head>
<body>
<div class="card">
    <!-- LEFT PANEL: Branding area na may logo at tagline ng HopePaws -->
    <div class="card-left">
        <div class="brand-icon">🐾</div>
        <div class="brand-name">Hope Paws</div>
        <p class="brand-tagline">Rescuing, rehabilitating, and rehoming animals across Marinduque since 2026.</p>
        <div class="brand-footer">© <?= date('Y') ?> HopePaws Animal Rescue · Boac, Marinduque</div>
    </div>

    <!-- RIGHT PANEL: Login form na may role selector -->
    <div class="card-right">
        <h2>Welcome to Hopepaws</h2>
        <p class="sub">Pick your role to continue</p>

        <!-- Ipakita ang error message kung may mali sa login -->
        <?php if ($error): ?>
            <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ROLE SELECTOR: Pumili ng User o Admin bago mag-login -->
        <div class="role-pick">
            <a href="#" class="role-btn <?= $role==='user' ? 'active-user' : '' ?>" onclick="pickRole('user');return false;">
                <span class="ri">👤</span><span>User</span>
            </a>
            <a href="#" class="role-btn <?= $role==='admin' ? 'active-admin' : '' ?>" onclick="pickRole('admin');return false;">
                <span class="ri">⚙️</span><span>Admin</span>
            </a>
        </div>

        <!-- USER LOGIN FORM: Nagpapakita kapag napili ang "User" role -->
        <form method="POST" id="form-user" class="login-form <?= $role==='user' ? 'show' : '' ?>">
            <input type="hidden" name="role" value="user">
            <div class="form-group"><label>Username</label><input type="text" name="username" required placeholder="Enter your username" value="<?= $role==='user' ? htmlspecialchars($_POST['username']??'') : '' ?>" autocomplete="username"></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="••••••••" autocomplete="current-password"></div>
            <button type="submit" class="btn-login user-btn">Sign In as User →</button>
        </form>

        <!-- ADMIN LOGIN FORM: Nagpapakita kapag napili ang "Admin" role -->
        <form method="POST" id="form-admin" class="login-form <?= $role==='admin' ? 'show' : '' ?>">
            <input type="hidden" name="role" value="admin">
            <div class="form-group"><label>Admin Username</label><input type="text" name="username" required placeholder="Enter admin username" value="<?= $role==='admin' ? htmlspecialchars($_POST['username']??'') : '' ?>" autocomplete="username"></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="••••••••" autocomplete="current-password"></div>
            <button type="submit" class="btn-login admin-btn">Sign In as Admin →</button>
        </form>

        <!-- REGISTER LINK: Nagpapakita lang kung User ang napiling role -->
        <div class="register-link" id="reg-link" style="<?= $role==='admin' ? 'display:none' : '' ?>">
            If you dont have an account? <a href="<?= SITE_URL ?>/register.php">Register here</a>
        </div>
    </div>
</div>

<script>
// FUNCTION: pickRole(role)
// Kapag napili ang role (user o admin), ang tamang form ang ipapakita
// at itatago ang hindi ginagamit na form.
function pickRole(role){
    // Alisin muna ang lahat ng active styling sa role buttons
    document.querySelectorAll('.role-btn').forEach(b=>b.classList.remove('active-user','active-admin'));
    if(role==='user'){
        document.querySelectorAll('.role-btn')[0].classList.add('active-user');
        document.getElementById('form-user').classList.add('show');
        document.getElementById('form-admin').classList.remove('show');
        document.getElementById('reg-link').style.display='';
        document.querySelector('#form-user input[name="username"]').focus();
    } else {
        document.querySelectorAll('.role-btn')[1].classList.add('active-admin');
        document.getElementById('form-admin').classList.add('show');
        document.getElementById('form-user').classList.remove('show');
        document.getElementById('reg-link').style.display='none';
        document.querySelector('#form-admin input[name="username"]').focus();
    }
}
// Kung may pre-selected na role (galing sa URL parameter), i-trigger agad ang pickRole
<?php if($role): ?>pickRole('<?= $role ?>');<?php endif; ?>
</script>
</body>
</html>
