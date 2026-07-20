<?php
/*
 * =============================================================================
 * FILE: admin/header.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang SHARED HEADER para sa lahat ng admin pages. Nagbibigay ito ng:
 *   - HTML head section (meta tags, CSS, fonts, favicon)
 *   - Admin sidebar na may navigation links
 *   - Topbar na may page title at admin user info
 *
 * GINAGAMIT SA:
 *   Lahat ng admin pages gamit ang: include 'header.php';
 *   (admin/index.php, admin/pets.php, admin/requests.php, atbp.)
 *
 * DYNAMIC NA CONTENT:
 *   - $adminTitle — Itinatakda sa bawat admin page bago i-include ang header
 *   - Awtomatikong nila-highlight ang active na nav link batay sa kasalukuyang page
 *   - Nagpapakita ng pangalan ng naka-login na admin sa topbar
 * =============================================================================
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamic page title — ginagamit ang $adminTitle na variable ng bawat admin page -->
    <title><?= isset($adminTitle) ? $adminTitle . ' – HopePaws Admin' : 'HopePaws Admin' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Admin CSS — hiwalay sa main style.css ng public site -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/admin.css">
    <!-- Emoji favicon — ang paw print icon sa browser tab -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
</head>
<body>

<!-- ADMIN SIDEBAR: Naka-fixed na navigation panel sa kaliwa ng screen -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">🐾 HopePaws</div>
    <nav class="sidebar-nav">
        <!-- MAIN SECTION: Dashboard -->
        <div class="sidebar-nav-label">Main</div>
        <!-- 'active' class ay idinaragdag kapag ang current page ay ang link na ito -->
        <a href="<?= SITE_URL ?>/admin/index.php"    class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='index.php'   ?'active':''?>"><span class="icon">📊</span> Dashboard</a>

        <!-- PETS SECTION: Manage at Add pet links -->
        <div class="sidebar-nav-label">Pets</div>
        <a href="<?= SITE_URL ?>/admin/pets.php"     class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='pets.php'    ?'active':''?>"><span class="icon">🐾</span> Manage Pets</a>
        <a href="<?= SITE_URL ?>/admin/pets.php?action=add" class="sidebar-link"><span class="icon">➕</span> Add New Pet</a>

        <!-- ADOPTIONS SECTION: Adoption requests link -->
        <div class="sidebar-nav-label">Adoptions</div>
        <a href="<?= SITE_URL ?>/admin/requests.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='requests.php'?'active':''?>"><span class="icon">💌</span> Requests</a>

        <!-- CONTENT SECTION: Gallery at Messages links -->
        <div class="sidebar-nav-label">Content</div>
        <a href="<?= SITE_URL ?>/admin/gallery.php"  class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='gallery.php' ?'active':''?>"><span class="icon">🖼️</span> Gallery</a>
        <a href="<?= SITE_URL ?>/admin/messages.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='messages.php'?'active':''?>"><span class="icon">✉️</span> Messages</a>

        <!-- SITE SECTION: Link para bumalik sa public website -->
        <div class="sidebar-nav-label">Site</div>
        <a href="<?= SITE_URL ?>/index.php" class="sidebar-link"><span class="icon">🌐</span> View Website</a>
    </nav>
    <!-- SIDEBAR FOOTER: Logout link sa ibaba ng sidebar -->
    <div class="sidebar-footer"><a href="<?= SITE_URL ?>/admin/logout.php">🚪 Logout</a></div>
</aside>

<!-- MAIN CONTENT AREA: Ang pangunahing lugar ng bawat admin page -->
<main class="admin-main">
    <!-- TOPBAR: Header na may page title at admin user info -->
    <div class="admin-topbar">
        <div>
            <!-- Dynamic na page title — ginagamit ang $adminTitle variable -->
            <div class="topbar-title"><?= $adminTitle ?? 'Dashboard' ?></div>
            <div class="topbar-sub">HopePaws Admin Panel</div>
        </div>
        <!-- Nagpapakita ng pangalan ng naka-login na admin mula sa session -->
        <div class="topbar-user">
            <span>Hello, <strong><?= $_SESSION['admin_username'] ?? 'Admin' ?></strong></span>
            <div class="topbar-avatar">A</div>
        </div>
    </div>
    <!-- Dito nagsisimula ang actual na content ng bawat admin page -->
    <div class="admin-content">
