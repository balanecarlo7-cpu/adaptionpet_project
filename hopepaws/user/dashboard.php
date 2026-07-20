<?php
/*
 * =============================================================================
 * FILE: user/dashboard.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang USER DASHBOARD — ang personal na page ng bawat registered user.
 *   Dito makikita ng user ang kanyang account information at lahat ng
 *   adoption requests na kanyang na-submit.
 *
 * NILALAMAN (Contents):
 *   - Profile card (username, email, member since date)
 *   - Stats: Total Requests, Pending, Approved
 *   - Talahanayan ng lahat ng adoption requests ng user (kasama ang pet info)
 *
 * AUTHENTICATION:
 *   Kailangan mag-login bilang regular user — gumagamit ng requireUser().
 *   Kung hindi naka-login, ire-redirect sa user/login.php.
 *
 * DATABASE QUERIES:
 *   - Kukuha ng user info mula sa 'users' table
 *   - Kukuha ng lahat ng adoption requests ng user (JOIN sa 'pets' table)
 *
 * GINAGAMIT NA FILES:
 *   - ../includes/config.php  (requireUser, getDB, sanitize, SITE_URL)
 *   - ../includes/header.php  (public navigation)
 *   - ../includes/footer.php  (footer)
 * =============================================================================
 */

require_once '../includes/config.php';

// Siguraduhing user ang naka-login — kung hindi, ire-redirect sa login
requireUser();
$db = getDB();

$pageTitle   = 'My Account';
$username    = $_SESSION['user_username']; // Pangalan ng naka-login na user
$userId      = $_SESSION['user_id'];       // ID ng naka-login na user

// Kunin ang buong user info mula sa database (para makuha ang email at created_at)
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// =============================================================================
// ADOPTION REQUESTS QUERY:
// Kunin ang lahat ng adoption requests ng user kasama ang impormasyon ng pet.
// Ginagamit ang JOIN para makuha ang pet name, species, at picture.
// Ang paghahanap ay batay sa email (hindi sa user_id) para makita rin ang
// mga request na ginawa bago mag-login.
// =============================================================================
$stmt = $db->prepare("
    SELECT ar.*, p.name as pet_name, p.species, p.picture
    FROM adoption_requests ar
    JOIN pets p ON ar.pet_id = p.id
    WHERE ar.adopter_email = ?
    ORDER BY ar.created_at DESC
");
$stmt->bind_param('s', $user['email']);
$stmt->execute();
$myRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Kalkulahin ang statistics gamit ang array_filter()
$pendingCount  = count(array_filter($myRequests, fn($r) => $r['status'] === 'pending'));
$approvedCount = count(array_filter($myRequests, fn($r) => $r['status'] === 'approved'));
$totalCount    = count($myRequests);

// I-include ang shared header (nagbibigay ng <!DOCTYPE>, <head>, <nav>)
include '../includes/header.php';
?>

<div class="dash-wrap">

    <!-- PROFILE CARD: Nagpapakita ng user info at action buttons -->
    <div class="profile-card">
        <div class="profile-avatar">🐾</div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($username) ?></h2>
            <!-- Nagpapakita ng email at petsa ng pagiging member -->
            <p><?= htmlspecialchars($user['email']) ?> &nbsp;·&nbsp; Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
        </div>
        <div class="profile-actions">
            <a href="<?= SITE_URL ?>/pets.php" class="btn-sm btn-outline-sm">🐾 Browse Pets</a>
            <a href="<?= SITE_URL ?>/logout.php" class="btn-sm btn-danger-sm">Sign Out</a>
        </div>
    </div>

    <!-- STATS SECTION: Quick overview ng adoption request statistics ng user -->
    <div class="dash-stats">
        <div class="dash-stat">
            <span class="s-num"><?= $totalCount ?></span>
            <span class="s-lbl">Requests Sent</span>
        </div>
        <div class="dash-stat">
            <span class="s-num"><?= $pendingCount ?></span>
            <span class="s-lbl">Pending</span>
        </div>
        <div class="dash-stat">
            <span class="s-num"><?= $approvedCount ?></span>
            <span class="s-lbl">Approved</span>
        </div>
    </div>

    <!-- ADOPTION REQUESTS TABLE: Lahat ng requests ng user -->
    <div class="section-card">
        <h3>🐾 My Adoption Requests</h3>

        <?php if (empty($myRequests)): ?>
            <!-- Ipakita ito kung wala pang adoption request ang user -->
            <div class="empty-dash">
                <div class="ei">🐾</div>
                <h4>No adoption requests yet</h4>
                <p>Browse available pets and submit an adoption request!</p>
                <a href="<?= SITE_URL ?>/pets.php" class="btn-sm btn-outline-sm" style="padding:.55rem 1.4rem;">Browse Pets →</a>
            </div>
        <?php else: ?>
            <table class="req-table">
                <thead>
                    <tr>
                        <th>Pet</th>
                        <th>Species</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myRequests as $req): ?>
                    <tr>
                        <td>
                            <!-- Pet thumbnail at pangalan -->
                            <div style="display:flex;align-items:center;gap:.65rem;">
                                <?php
                                // Suriin kung mayroon ang pet photo sa server
                                $imgPath = __DIR__ . '/../uploads/pets/' . $req['picture'];
                                if ($req['picture'] && file_exists($imgPath)): ?>
                                    <img src="<?= SITE_URL ?>/uploads/pets/<?= htmlspecialchars($req['picture']) ?>" class="pet-thumb" alt="">
                                <?php else: ?>
                                    <!-- Kung wala ang larawan, magpakita ng paw emoji bilang fallback -->
                                    <span class="pet-thumb-fallback">🐾</span>
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($req['pet_name']) ?></strong>
                            </div>
                        </td>
                        <td><?= ucfirst(htmlspecialchars($req['species'])) ?></td>
                        <td><?= date('M d, Y', strtotime($req['created_at'])) ?></td>
                        <td>
                            <?php
                            // Pumili ng tamang badge class at icon batay sa status ng request
                            $s   = $req['status'];
                            $cls = match($s) { 'approved' => 'badge-approved', 'rejected' => 'badge-rejected', default => 'badge-pending' };
                            $icon = match($s) { 'approved' => '✅', 'rejected' => '❌', default => '⏳' };
                            ?>
                            <span class="badge <?= $cls ?>"><?= $icon ?> <?= ucfirst($s) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
