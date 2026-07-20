<?php
require_once 'includes/config.php';

// Kung hindi naka-login, ibalik sa login page muna
if (!isUserLoggedIn() && !isAdminLoggedIn()) { header('Location: ' . SITE_URL . '/login.php'); exit; }

$db = getDB();
$pageTitle = 'Home';

$pets       = $db->query("SELECT * FROM pets WHERE status='available' ORDER BY created_at DESC LIMIT 12")->fetch_all(MYSQLI_ASSOC);
$totalPets  = $db->query("SELECT COUNT(*) FROM pets")->fetch_row()[0];
$totalAdopt = $db->query("SELECT COUNT(*) FROM pets WHERE status='adopted'")->fetch_row()[0];
$totalAvail = $db->query("SELECT COUNT(*) FROM pets WHERE status='available'")->fetch_row()[0];
?>
<?php include 'includes/header.php'; ?>

<section class="page-hero" style="text-align:unset;">
    <div style="max-width:1100px;margin:0 auto;display:flex;align-items:center;gap:3rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:280px;text-align:left;">
            <span class="ey">🐾 Marinduque Animal Rescue</span>
            <h1>Every <span style="color:var(--peach);font-style:italic;">Paw</span> Deserves a Home</h1>
            <p style="margin:0;">Browse our rescued animals and give them the love and family they deserve.</p>
            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1.5rem;">
                <a href="<?= SITE_URL ?>/pets.php" class="btn-primary">Find Your Companion →</a>
                <a href="<?= SITE_URL ?>/about.php" class="btn-outline">Learn More</a>
            </div>
            <div style="display:flex;gap:3rem;flex-wrap:wrap;margin-top:2.5rem;padding-top:2rem;border-top:1px solid var(--gray-100);">
                <div>
                    <span style="font-family:'Lora',serif;font-size:2.2rem;font-weight:700;color:var(--peach);display:block;"><?= $totalPets ?></span>
                    <span style="font-size:.72rem;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:1.5px;">Pets Rescued</span>
                </div>
                <div>
                    <span style="font-family:'Lora',serif;font-size:2.2rem;font-weight:700;color:var(--teal);display:block;"><?= $totalAdopt ?></span>
                    <span style="font-size:.72rem;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:1.5px;">Adopted</span>
                </div>
                <div>
                    <span style="font-family:'Lora',serif;font-size:2.2rem;font-weight:700;color:var(--peach);display:block;"><?= $totalAvail ?></span>
                    <span style="font-size:.72rem;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:1.5px;">Needs a Home</span>
                </div>
            </div>
        </div>
        <div style="flex:0 0 auto;">
            <img src="<?= SITE_URL ?>/assets/images/dha.png" alt="Person with dog"
                 style="width:340px;max-width:100%;height:400px;object-fit:cover;border-radius:1.5rem;box-shadow:0 8px 32px rgba(0,0,0,.12);">
        </div>
    </div>
</section>

<div class="sec">
    <div class="container">
        <div class="sec-head center">
            <span class="ey">🐾 Meet the Animals</span>
            <h2>Available for Adoption</h2>
            <p>Each one is waiting for a loving family. Could that be you?</p>
        </div>
        <?php if (empty($pets)): ?>
            <p style="text-align:center;color:var(--gray-400);padding:3rem 0;">No pets available right now — check back soon! 🐾</p>
        <?php else: ?>
        <div class="pets-grid">
            <?php foreach ($pets as $pet): ?>
            <div class="pet-card">
                <div class="pet-card-img">
                    <img src="<?= getPetImageUrl($pet['picture'] ?? '', $pet['species'], $pet['id']) ?>"
                         alt="<?= sanitize($pet['name']) ?>" loading="lazy">
                    <span style="position:absolute;top:.75rem;right:.75rem;"><?= getStatusBadge($pet['status']) ?></span>
                </div>
                <div class="pet-card-body">
                    <div class="pet-card-top">
                        <span class="pet-name"><?= sanitize($pet['name']) ?></span>
                    </div>
                    <div class="pet-meta">
                        <span>🐾 <?= sanitize($pet['species']) ?></span>
                        <span>🦴 <?= sanitize($pet['breed'] ?: 'Mixed') ?></span>
                        <span>🗓 <?= sanitize($pet['age'] ?: '?') ?></span>
                    </div>
                    <?php if (isUserLoggedIn()): ?>
                        <a href="<?= SITE_URL ?>/pets.php?id=<?= $pet['id'] ?>" class="btn-sm primary">Adopt Me →</a>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/login.php?role=user" class="btn-sm ghost">Login to Adopt</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:2.5rem;">
            <a href="<?= SITE_URL ?>/pets.php" class="btn-outline">View All Pets →</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
