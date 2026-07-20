<?php
require_once 'includes/config.php';

$db = getDB();
$pageTitle = 'Adopt a Pet';

$success = $error = '';

// Handle adoption request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pet_id'])) {
    if (!isUserLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php?role=user'); exit;
    }
    $petId        = (int)($_POST['pet_id'] ?? 0);
    $adopterName  = sanitize($_POST['adopter_name'] ?? '');
    $adopterEmail = sanitize($_POST['adopter_email'] ?? '');
    $adopterPhone = sanitize($_POST['adopter_phone'] ?? '');
    $message      = sanitize($_POST['message'] ?? '');

    if (!$adopterName || !$adopterEmail) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $db->prepare("INSERT INTO adoption_requests (pet_id, adopter_name, adopter_email, adopter_phone, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param('issss', $petId, $adopterName, $adopterEmail, $adopterPhone, $message);
        $success = $stmt->execute() ? 'Your adoption request has been submitted! We will contact you soon. 🐾' : 'Something went wrong. Please try again.';
        $stmt->close();
    }
}

// Fetch with filters
$speciesFilter = sanitize($_GET['species'] ?? '');
$search        = sanitize($_GET['q'] ?? '');
$where = "WHERE 1=1"; $params = []; $types = '';
if ($speciesFilter) { $where .= " AND species = ?";            $params[] = $speciesFilter; $types .= 's'; }
if ($search)        { $where .= " AND (name LIKE ? OR breed LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }
$stmt = $db->prepare("SELECT * FROM pets $where ORDER BY status ASC, created_at DESC");
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$pets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Single pet view
$viewPet = null;
if (isset($_GET['id'])) {
    $s = $db->prepare("SELECT * FROM pets WHERE id = ?");
    $petId = (int)$_GET['id'];
    $s->bind_param('i', $petId);
    $s->execute();
    $viewPet = $s->get_result()->fetch_assoc();
    $s->close();
}
?>
<?php include 'includes/header.php'; ?>

<section class="sec">
<div class="container">

<?php if ($success): ?>
  <div style="margin-bottom:1.5rem;padding:1rem 1.25rem;background:#DCFCE7;border-radius:var(--radius);color:#15803D;font-weight:500;">✅ <?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div style="margin-bottom:1.5rem;padding:1rem 1.25rem;background:#FEE2E2;border-radius:var(--radius);color:#B91C1C;font-weight:500;">⚠️ <?= $error ?></div>
<?php endif; ?>

<?php if ($viewPet): ?>
  <!-- SINGLE PET DETAIL -->
  <a href="<?= SITE_URL ?>/pets.php" style="color:var(--peach);text-decoration:none;font-weight:500;">← Back to all pets</a>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:2.5rem;margin-top:1.5rem;align-items:start;">
    <div>
      <img src="<?= getPetImageUrl($viewPet['picture'] ?? '', $viewPet['species'], $viewPet['id']) ?>"
           alt="<?= sanitize($viewPet['name']) ?>"
           style="width:100%;border-radius:var(--radius-lg);object-fit:cover;max-height:420px;box-shadow:var(--shadow-lg);">
    </div>
    <div>
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
        <h1 style="font-family:'Lora',serif;font-size:2rem;"><?= sanitize($viewPet['name']) ?></h1>
        <?= getStatusBadge($viewPet['status']) ?>
      </div>
      <div class="pet-meta" style="margin-bottom:1.25rem;">
        <span>🐾 <?= sanitize($viewPet['species']) ?></span>
        <span>🦴 <?= sanitize($viewPet['breed'] ?: 'Mixed') ?></span>
        <span>🗓 <?= sanitize($viewPet['age'] ?: 'Unknown') ?></span>
        <?php if (!empty($viewPet['gender'])): ?><span>⚥ <?= sanitize($viewPet['gender']) ?></span><?php endif; ?>
      </div>
      <?php if (!empty($viewPet['description'])): ?>
        <p style="color:var(--gray-600);line-height:1.75;margin-bottom:1.5rem;"><?= nl2br(sanitize($viewPet['description'])) ?></p>
      <?php endif; ?>

      <?php if ($viewPet['status'] === 'available'): ?>
        <?php if (isUserLoggedIn()): ?>
          <h3 style="font-family:'Lora',serif;margin-bottom:1rem;">Apply to Adopt <?= sanitize($viewPet['name']) ?></h3>
          <form method="POST" style="display:flex;flex-direction:column;gap:.85rem;">
            <input type="hidden" name="pet_id" value="<?= $viewPet['id'] ?>">
            <div><label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Your Name *</label>
              <input type="text" name="adopter_name" required value="<?= sanitize($_SESSION['user_username'] ?? '') ?>"
                style="width:100%;padding:.6rem .9rem;border:1px solid var(--gray-200);border-radius:var(--radius-sm);font-family:inherit;"></div>
            <div><label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Email *</label>
              <input type="email" name="adopter_email" required
                style="width:100%;padding:.6rem .9rem;border:1px solid var(--gray-200);border-radius:var(--radius-sm);font-family:inherit;"></div>
            <div><label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Phone</label>
              <input type="text" name="adopter_phone"
                style="width:100%;padding:.6rem .9rem;border:1px solid var(--gray-200);border-radius:var(--radius-sm);font-family:inherit;"></div>
            <div><label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Why do you want to adopt?</label>
              <textarea name="message" rows="3"
                style="width:100%;padding:.6rem .9rem;border:1px solid var(--gray-200);border-radius:var(--radius-sm);font-family:inherit;resize:vertical;"></textarea></div>
            <button type="submit" class="btn-primary" style="align-self:flex-start;">Submit Adoption Request 🐾</button>
          </form>
        <?php else: ?>
          <a href="<?= SITE_URL ?>/login.php?role=user" class="btn-primary">Login to Adopt</a>
        <?php endif; ?>
      <?php else: ?>
        <p style="color:var(--gray-400);margin-top:1rem;">❤️ This pet has already been adopted.</p>
      <?php endif; ?>
    </div>
  </div>

<?php else: ?>
  <!-- PET LISTING -->
  <div class="sec-head center" style="margin-bottom:2rem;">
    <span class="ey">🐾 Find Your Match</span>
    <h2>Adopt a Pet</h2>
    <p>Every pet here is looking for a forever home.</p>
  </div>

  <!-- FILTER BAR -->
  <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:2rem;justify-content:center;">
    <input type="text" name="q" placeholder="Search by name or breed..."
           value="<?= sanitize($_GET['q'] ?? '') ?>"
           style="flex:1;min-width:200px;padding:.6rem 1rem;border:1px solid var(--gray-200);border-radius:var(--radius);font-family:inherit;">
    <select name="species"
            style="padding:.6rem 1rem;border:1px solid var(--gray-200);border-radius:var(--radius);font-family:inherit;background:var(--white);">
      <option value="">All Species</option>
      <?php foreach (['dog','cat','rabbit','bird','other'] as $sp): ?>
        <option value="<?= $sp ?>" <?= $speciesFilter===$sp?'selected':''?>><?= ucfirst($sp) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-primary">Search</button>
    <?php if ($speciesFilter || $search): ?>
      <a href="<?= SITE_URL ?>/pets.php" class="btn-outline">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (empty($pets)): ?>
    <p style="text-align:center;padding:3rem 0;color:var(--gray-400);">No pets found. Try a different search! 🐾</p>
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
        <?php if ($pet['status'] === 'available'): ?>
          <a href="<?= SITE_URL ?>/pets.php?id=<?= $pet['id'] ?>" class="btn-sm primary">View &amp; Adopt →</a>
        <?php else: ?>
          <span style="font-size:.8rem;color:var(--gray-400);">Already adopted ❤️</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>

</div>
</section>

<?php include 'includes/footer.php'; ?>
