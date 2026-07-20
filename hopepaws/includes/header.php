<?php
/*
 * =============================================================================
 * FILE: includes/header.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang SHARED HEADER para sa lahat ng public pages ng HopePaws website.
 *   Nagbibigay ito ng:
 *   - Kumpleto na HTML head section (meta tags, fonts, CSS, favicon)
 *   - Responsive navigation bar na may logo at mga links
 *   - Hamburger menu para sa mobile devices
 *   - Dynamic na navigation batay sa kung sino ang naka-login
 *
 * DYNAMIC NA NAVIGATION:
 *   - Kung naka-login bilang USER   → Nagpapakita ng username na may link sa dashboard
 *   - Kung naka-login bilang ADMIN  → Nagpapakita ng "Admin" link sa admin panel
 *   - Kung HINDI naka-login         → Nagpapakita ng "Login" button
 *
 * ACTIVE LINK DETECTION:
 *   Gumagamit ng basename($_SERVER['PHP_SELF']) para malaman kung aling page
 *   ang kasalukuyang binubuksan at magdagdag ng "active" class sa tamang link.
 *
 * GINAGAMIT SA:
 *   Lahat ng public pages gamit ang: include 'includes/header.php';
 *   (index.php, pets.php, gallery.php, about.php, contact.php)
 * =============================================================================
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamic page title — ginagamit ang $pageTitle variable ng bawat page -->
    <title><?= isset($pageTitle) ? $pageTitle . ' – HopePaws' : 'HopePaws – Every Paw Deserves a Home' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Google Fonts: Lora para sa mga heading, Poppins para sa body text -->
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main stylesheet para sa public website -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/style.css">
    <!-- Emoji favicon — ang paw print icon sa browser tab -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
</head>
<body>

<!-- NAVIGATION BAR: Responsive navbar na may logo at links -->
<nav class="navbar">
    <!-- BRAND LOGO: Click para makabalik sa homepage -->
    <a href="<?= SITE_URL ?>/index.php" class="nav-brand">🐾 Hope<span class="t">Paws</span></a>

    <!-- HAMBURGER BUTTON: Para sa mobile — nagbubukas/nagsasara ng mobile menu -->
    <button class="nav-toggle" onclick="this.classList.toggle('open');document.querySelector('.nav-links').classList.toggle('open')">
        <span></span><span></span><span></span>
    </button>

    <!-- NAVIGATION LINKS: May 'active' class ang kasalukuyang page -->
    <ul class="nav-links">
        <li><a href="<?= SITE_URL ?>/index.php"   <?= basename($_SERVER['PHP_SELF'])=='index.php'  ?'class="active"':''?>>Home</a></li>
        <li><a href="<?= SITE_URL ?>/pets.php"    <?= basename($_SERVER['PHP_SELF'])=='pets.php'   ?'class="active"':''?>>Adopt a Pet</a></li>
        <li><a href="<?= SITE_URL ?>/gallery.php" <?= basename($_SERVER['PHP_SELF'])=='gallery.php'?'class="active"':''?>>Gallery</a></li>
        <li><a href="<?= SITE_URL ?>/about.php"   <?= basename($_SERVER['PHP_SELF'])=='about.php'  ?'class="active"':''?>>About</a></li>
        <li><a href="<?= SITE_URL ?>/contact.php" <?= basename($_SERVER['PHP_SELF'])=='contact.php'?'class="active"':''?>>Contact</a></li>

        <?php if (isUserLoggedIn()): ?>
            <!-- Kung user ang naka-login: Ipakita ang username na may link sa dashboard -->
            <li><a href="<?= SITE_URL ?>/user/dashboard.php" class="nav-cta">🐾 <?= htmlspecialchars($_SESSION['user_username']) ?></a></li>
        <?php elseif (isAdminLoggedIn()): ?>
            <!-- Kung admin ang naka-login: Ipakita ang Admin link papunta sa admin panel -->
            <li><a href="<?= SITE_URL ?>/admin/index.php" class="nav-cta">⚙️ Admin</a></li>
        <?php else: ?>
            <!-- Kung walang naka-login: Ipakita ang Login button -->
            <li><a href="<?= SITE_URL ?>/login.php" class="nav-cta">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>
