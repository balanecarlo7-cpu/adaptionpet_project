<?php
/*
 * =============================================================================
 * FILE: admin/index.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang ADMIN DASHBOARD — ang unang makikita ng admin pagpasok sa
 *   admin panel. Nagbibigay ito ng overview ng buong sistema:
 *   mga statistics, quick action buttons, at recent activity.
 *
 * NILALAMAN (Contents):
 *   - 6 stat cards: Total Pets, Available, Adopted, Pending Requests,
 *                   Unread Messages, Gallery Photos
 *   - Quick Action buttons para sa mabilis na navigation
 *   - Talahanayan ng 5 pinakabagong adoption requests
 *   - Talahanayan ng 5 pinakabagong mensahe mula sa contact form
 *
 * AUTHENTICATION:
 *   Kailangan ng admin session — ginagamit ang requireAdmin() function.
 *   Kung hindi admin, awtomatikong nire-redirect sa admin login.
 *
 * GINAGAMIT NA FILES:
 *   - ../includes/config.php  (requireAdmin, getDB, sanitize, timeAgo)
 *   - header.php              (admin sidebar at topbar)
 *   - footer.php              (closing tags at scripts)
 * =============================================================================
 */

require_once '../includes/config.php';

// Siguraduhing admin ang naka-login — kung hindi, ire-redirect sa login
requireAdmin();

$db = getDB();
$adminTitle = 'Dashboard';

// =============================================================================
// STATISTICS QUERIES: Kunin ang lahat ng bilang para sa stat cards
// =============================================================================
$totalPets       = $db->query("SELECT COUNT(*) as c FROM pets")->fetch_assoc()['c'];                                // Lahat ng pets
$available       = $db->query("SELECT COUNT(*) as c FROM pets WHERE status='available'")->fetch_assoc()['c'];      // Available pa
$adopted         = $db->query("SELECT COUNT(*) as c FROM pets WHERE status='adopted'")->fetch_assoc()['c'];        // May bagong tahanan na
$pendingRequests = $db->query("SELECT COUNT(*) as c FROM adoption_requests WHERE status='pending'")->fetch_assoc()['c']; // Hinihintay ng desisyon
$unreadMessages  = $db->query("SELECT COUNT(*) as c FROM messages WHERE is_read=0")->fetch_assoc()['c'];           // Hindi pa nababasa
$galleryCount    = $db->query("SELECT COUNT(*) as c FROM gallery")->fetch_assoc()['c'];                            // Larawan sa gallery

// Kunin ang 5 pinakabagong adoption requests kasama ang info ng pet
// Gumagamit ng JOIN para makuha ang pangalan at species ng pet
$recentRequests = $db->query("
    SELECT ar.*, p.name as pet_name, p.species
    FROM adoption_requests ar 
    JOIN pets p ON ar.pet_id=p.id 
    ORDER BY ar.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Kunin ang 5 pinakabagong mensahe mula sa contact form
$recentMessages = $db->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'header.php'; ?>

<!-- STAT CARDS SECTION: 6 na cards na nagpapakita ng key statistics -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon terracotta">🐾</div>
        <div class="stat-info">
            <span class="num"><?= $totalPets ?></span>
            <span class="label">Total Pets</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon sage">✅</div>
        <div class="stat-info">
            <span class="num"><?= $available ?></span>
            <span class="label">Available</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold">❤️</div>
        <div class="stat-info">
            <span class="num"><?= $adopted ?></span>
            <span class="label">Adopted</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon brown">💌</div>
        <div class="stat-info">
            <span class="num"><?= $pendingRequests ?></span>
            <span class="label">Pending Requests</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon terracotta">✉️</div>
        <div class="stat-info">
            <span class="num"><?= $unreadMessages ?></span>
            <span class="label">Unread Messages</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon sage">🖼️</div>
        <div class="stat-info">
            <span class="num"><?= $galleryCount ?></span>
            <span class="label">Gallery Photos</span>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS: Mga shortcut buttons para sa mabilis na navigation -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <div class="admin-card-header">
        <span class="admin-card-title">Quick Actions</span>
    </div>
    <div class="admin-card-body" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
        <a href="pets.php?action=add" class="btn btn-primary">➕ Add New Pet</a>
        <!-- Nagpapakita ng bilang ng pending requests sa button kung mayroon -->
        <a href="requests.php" class="btn btn-warning">💌 View Requests <?= $pendingRequests > 0 ? "($pendingRequests)" : '' ?></a>
        <!-- Nagpapakita ng bilang ng unread messages sa button kung mayroon -->
        <a href="messages.php" class="btn btn-success">✉️ Messages <?= $unreadMessages > 0 ? "($unreadMessages)" : '' ?></a>
        <a href="gallery.php" class="btn btn-secondary">🖼️ Gallery</a>
        <a href="<?= SITE_URL ?>/index.php" class="btn btn-secondary">🌐 View Site</a>
    </div>
</div>

<!-- TWO-COLUMN LAYOUT: Recent requests at recent messages na magkatabi -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

    <!-- RECENT ADOPTION REQUESTS TABLE: 5 pinakabagong adoption requests -->
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Recent Adoption Requests</span>
            <a href="requests.php" class="btn btn-secondary" style="padding:0.4rem 0.9rem;font-size:0.8rem;">View All</a>
        </div>
        <div style="overflow-x:auto;">
        <?php if (empty($recentRequests)): ?>
            <div style="padding:2rem;text-align:center;color:var(--text-light);">No requests yet</div>
        <?php else: ?>
        <table>
            <thead><tr><th>Adopter</th><th>Pet</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentRequests as $r): ?>
            <tr>
                <td><strong><?= sanitize($r['adopter_name']) ?></strong></td>
                <td><?= sanitize($r['pet_name']) ?></td>
                <!-- Nagpapakita ng colored status badge (pending/approved/rejected) -->
                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <!-- timeAgo() — Kino-convert ang timestamp sa "2 hrs ago" format -->
                <td style="color:var(--text-light);font-size:0.8rem;"><?= timeAgo($r['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>
    </div>

    <!-- RECENT MESSAGES TABLE: 5 pinakabagong mensahe mula sa contact form -->
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Recent Messages</span>
            <a href="messages.php" class="btn btn-secondary" style="padding:0.4rem 0.9rem;font-size:0.8rem;">View All</a>
        </div>
        <?php if (empty($recentMessages)): ?>
            <div style="padding:2rem;text-align:center;color:var(--text-light);">No messages yet</div>
        <?php else: ?>
        <table>
            <thead><tr><th>From</th><th>Subject</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentMessages as $m): ?>
            <tr>
                <td><strong><?= sanitize($m['sender_name']) ?></strong></td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= sanitize($m['subject'] ?: 'No subject') ?>
                </td>
                <!-- Nagpapakita ng "New" o "Read" badge batay sa is_read field -->
                <td>
                    <span class="badge badge-<?= $m['is_read'] ? 'read' : 'unread' ?>">
                        <?= $m['is_read'] ? 'Read' : 'New' ?>
                    </span>
                </td>
                <td style="color:var(--text-light);font-size:0.8rem;"><?= timeAgo($m['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
