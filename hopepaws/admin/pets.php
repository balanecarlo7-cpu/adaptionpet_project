<?php
/*
 * =============================================================================
 * FILE: admin/pets.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang PET MANAGEMENT PAGE para sa admin. Dito maaaring:
 *   - Makita ang lahat ng pets sa isang talahanayan
 *   - Magdagdag ng bagong pet (kasama ang larawan upload)
 *   - Mag-edit ng impormasyon ng isang pet
 *   - Burahin ang isang pet (kasama ang larawan nito)
 *   - Baguhin ang status (Available ↔ Adopted) nang mabilis
 *
 * MGA ACTIONS (Post Actions):
 *   - 'add'    → Magdagdag ng bagong pet
 *   - 'edit'   → I-update ang impormasyon ng existing pet
 *   - 'delete' → Burahin ang pet at ang larawan nito
 *   - 'status' → Mabilis na palitan ang status ng pet
 *
 * AUTHENTICATION:
 *   Gumagamit ng requireAdmin() — admin lang ang may access.
 *
 * GINAGAMIT NA FILES:
 *   - ../includes/config.php  (requireAdmin, getDB, sanitize, getPetImageUrl)
 *   - header.php              (admin sidebar)
 *   - footer.php              (closing tags)
 * =============================================================================
 */

require_once '../includes/config.php';

// Siguraduhing admin ang naka-login
requireAdmin();

$db = getDB();
$adminTitle = 'Manage Pets';

$success = $error = '';
$editPet = null;
$action = $_GET['action'] ?? 'list'; // Default action ay 'list'

// =============================================================================
// FORM PROCESSING: Pinoproseso ang lahat ng POST requests (add, edit, delete, status)
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // ================================================================
    // ACTION: ADD PET
    // Nagdadagdag ng bagong pet sa database kasama ang larawan upload.
    // ================================================================
    if ($postAction === 'add') {
        $name    = sanitize($_POST['name'] ?? '');
        $breed   = sanitize($_POST['breed'] ?? '');
        $age     = sanitize($_POST['age'] ?? '');
        $species = sanitize($_POST['species'] ?? 'dog');
        $status  = sanitize($_POST['status'] ?? 'available');
        $desc    = sanitize($_POST['description'] ?? '');
        $picture = '';

        // Validation: Kailangan ng name, breed, at age
        if (!$name || !$breed || !$age) {
            $error = 'Name, breed, and age are required.';
            $action = 'add';
        } else {
            // IMAGE UPLOAD: Kung may pinili na larawan, i-upload ito
            if (!empty($_FILES['picture']['name'])) {
                $ext = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp']; // Allowed file extensions
                if (!in_array($ext, $allowed)) {
                    $error = 'Only JPG, PNG, GIF, WEBP files are allowed.';
                    $action = 'add';
                } elseif ($_FILES['picture']['size'] > 5 * 1024 * 1024) { // Max 5MB
                    $error = 'Image must be under 5MB.';
                    $action = 'add';
                } else {
                    // Gumawa ng unique filename gamit ang uniqid()
                    $picture = uniqid('pet_') . '.' . $ext;
                    $uploadDir = __DIR__ . '/../uploads/pets/';
                    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); } // Gumawa ng folder kung wala
                    $uploadPath = $uploadDir . $picture;
                    if (!move_uploaded_file($_FILES['picture']['tmp_name'], $uploadPath)) {
                        $error = 'Failed to upload image. Check folder permissions.';
                        $picture = '';
                        $action = 'add';
                    }
                }
            }

            // INSERT sa database kung walang error
            if (!$error) {
                $stmt = $db->prepare("INSERT INTO pets (name, breed, age, species, status, description, picture) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssss', $name, $breed, $age, $species, $status, $desc, $picture);
                if ($stmt->execute()) {
                    $success = "Pet '$name' added successfully! 🐾";
                    $action = 'list';
                } else {
                    $error = 'Database error. Please try again.';
                    $action = 'add';
                }
            }
        }
    }

    // ================================================================
    // ACTION: EDIT PET
    // Ina-update ang impormasyon ng existing pet.
    // Kung may bagong larawan, papalitan ang luma.
    // ================================================================
    if ($postAction === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $name    = sanitize($_POST['name'] ?? '');
        $breed   = sanitize($_POST['breed'] ?? '');
        $age     = sanitize($_POST['age'] ?? '');
        $species = sanitize($_POST['species'] ?? 'dog');
        $status  = sanitize($_POST['status'] ?? 'available');
        $desc    = sanitize($_POST['description'] ?? '');

        if (!$id || !$name || !$breed || !$age) {
            $error = 'All required fields must be filled.';
            $action = 'edit';
        } else {
            // Kunin ang existing pet para malaman ang current na larawan
            $existing = $db->query("SELECT * FROM pets WHERE id=$id")->fetch_assoc();
            $picture = $existing['picture']; // I-keep ang lumang larawan by default

            // Kung may bagong larawan na ini-upload, palitan ang luma
            if (!empty($_FILES['picture']['name'])) {
                $ext = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed) && $_FILES['picture']['size'] <= 5*1024*1024) {
                    $newPicture = uniqid('pet_') . '.' . $ext;
                    $uploadDir = __DIR__ . '/../uploads/pets/';
                    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
                    $uploadPath = $uploadDir . $newPicture;
                    if (move_uploaded_file($_FILES['picture']['tmp_name'], $uploadPath)) {
                        // Burahin ang lumang larawan kung mayroon
                        if ($picture && file_exists(__DIR__ . '/../uploads/pets/' . $picture)) {
                            unlink(__DIR__ . '/../uploads/pets/' . $picture);
                        }
                        $picture = $newPicture; // Gamitin ang bagong filename
                    }
                }
            }

            // I-update ang pet record sa database
            $stmt = $db->prepare("UPDATE pets SET name=?, breed=?, age=?, species=?, status=?, description=?, picture=? WHERE id=?");
            $stmt->bind_param('sssssssi', $name, $breed, $age, $species, $status, $desc, $picture, $id);
            if ($stmt->execute()) {
                $success = "Pet '$name' updated successfully! ✅";
                $action = 'list';
            } else {
                $error = 'Update failed. Please try again.';
                $action = 'edit';
            }
        }
    }

    // ================================================================
    // ACTION: DELETE PET
    // Binubura ang pet mula sa database at tinatanggal ang larawan nito.
    // ================================================================
    if ($postAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pet = $db->query("SELECT * FROM pets WHERE id=$id")->fetch_assoc();
        if ($pet) {
            // Burahin ang larawan ng pet mula sa uploads folder
            if ($pet['picture'] && file_exists(__DIR__.'/../uploads/pets/'.$pet['picture'])) {
                unlink(__DIR__.'/../uploads/pets/'.$pet['picture']);
            }
            // Burahin ang pet record mula sa database
            $db->query("DELETE FROM pets WHERE id=$id");
            $success = "Pet '{$pet['name']}' deleted successfully.";
        } else {
            $error = 'Pet not found.';
        }
        $action = 'list';
    }

    // ================================================================
    // ACTION: QUICK STATUS UPDATE
    // Mabilis na pagpapalit ng status ng pet (Available ↔ Adopted)
    // nang hindi bumabalik sa edit form.
    // ================================================================
    if ($postAction === 'status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = $_POST['new_status'] === 'adopted' ? 'adopted' : 'available';
        $db->query("UPDATE pets SET status='$newStatus' WHERE id=$id");
        $pet = $db->query("SELECT name FROM pets WHERE id=$id")->fetch_assoc();
        $success = "Status updated: {$pet['name']} is now " . ucfirst($newStatus) . "!";
        $action = 'list';
    }
}

// I-load ang pet data para sa edit form (kapag nag-click ng "Edit" button)
if ($action === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    $editPet = $db->query("SELECT * FROM pets WHERE id=$editId")->fetch_assoc();
    if (!$editPet) { $action = 'list'; }
}

// Kunin ang listahan ng pets para sa talahanayan (may optional filter by status)
$filterStatus   = $_GET['status'] ?? 'all';
$where          = $filterStatus !== 'all' ? "WHERE status='$filterStatus'" : '';
$pets           = $db->query("SELECT * FROM pets $where ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Bilang ng bawat status para sa filter buttons
$totalAll       = $db->query("SELECT COUNT(*) as c FROM pets")->fetch_assoc()['c'];
$totalAvailable = $db->query("SELECT COUNT(*) as c FROM pets WHERE status='available'")->fetch_assoc()['c'];
$totalAdopted   = $db->query("SELECT COUNT(*) as c FROM pets WHERE status='adopted'")->fetch_assoc()['c'];
?>
<?php include 'header.php'; ?>

<!-- SUCCESS / ERROR ALERTS -->
<?php if ($success): ?>
    <div class="alert alert-success">✅ <?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= $error ?></div>
<?php endif; ?>

<!-- ADD / EDIT FORM: Nagpapakita kapag ang action ay 'add' o 'edit' -->
<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="admin-form-card">
    <h2 class="admin-form-title">
        <?= $action === 'add' ? '➕ Add New Pet' : '✏️ Edit Pet: ' . sanitize($editPet['name'] ?? '') ?>
    </h2>
    <!-- enctype="multipart/form-data" kailangan para gumana ang file upload -->
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $action ?>">
        <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?= $editPet['id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>Pet Name *</label>
                <input type="text" name="name" required placeholder="e.g. Mochi"
                       value="<?= sanitize($editPet['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Breed *</label>
                <input type="text" name="breed" required placeholder="e.g. Shih Tzu"
                       value="<?= sanitize($editPet['breed'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Age *</label>
                <input type="text" name="age" required placeholder="e.g. 2 years, 6 months"
                       value="<?= sanitize($editPet['age'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Species</label>
                <select name="species">
                    <?php foreach (['dog','cat','rabbit','bird','other'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($editPet['species'] ?? 'dog') === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="available" <?= ($editPet['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>🐾 Available</option>
                    <option value="adopted" <?= ($editPet['status'] ?? '') === 'adopted' ? 'selected' : '' ?>>❤️ Adopted</option>
                </select>
            </div>
            <div class="form-group">
                <label>Photo <?= $action === 'add' ? '' : '(leave blank to keep current)' ?></label>
                <!-- data-preview ay ginagamit ng main.js para i-preview ang larawan bago i-upload -->
                <input type="file" name="picture" accept="image/*" data-preview="img-preview">
            </div>
        </div>

        <!-- Ipakita ang current photo kapag nasa edit mode -->
        <?php if ($action === 'edit' && $editPet['picture']): ?>
        <div class="form-group">
            <label>Current Photo</label>
            <div class="img-preview" id="img-preview">
                <img src="<?= getPetImageUrl($editPet['picture'], $editPet['species'] ?? 'dog', $editPet['id']) ?>"
                     alt="Current photo">
            </div>
        </div>
        <?php else: ?>
            <div class="img-preview" id="img-preview">
                <img src="<?= getPetPlaceholderUrl($editPet['id'] ?? 1, $editPet['species'] ?? 'dog') ?>"
                     alt="No photo yet"
                     style="opacity:0.6;">
            </div>
            <br>
        <?php endif; ?>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Tell us about this pet's personality, needs, and story..."><?= sanitize($editPet['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
                <?= $action === 'add' ? '➕ Add Pet' : '💾 Save Changes' ?>
            </button>
            <a href="pets.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- PET LIST TABLE: Nagpapakita ng lahat ng pets sa talahanayan -->
<div class="admin-card">
    <div class="admin-card-header">
        <span class="admin-card-title">All Pets
            <span style="font-size:0.85rem;font-weight:400;color:var(--text-light);margin-left:0.5rem;">(<?= count($pets) ?> total)</span>
        </span>
        <!-- Status filter buttons -->
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            <a href="?status=all" class="btn btn-secondary" style="padding:0.4rem 0.8rem;font-size:0.8rem;<?= $filterStatus==='all'?'background:var(--terracotta);color:white;':'' ?>">All (<?= $totalAll ?>)</a>
            <a href="?status=available" class="btn btn-secondary" style="padding:0.4rem 0.8rem;font-size:0.8rem;<?= $filterStatus==='available'?'background:var(--sage);color:white;':'' ?>">Available (<?= $totalAvailable ?>)</a>
            <a href="?status=adopted" class="btn btn-secondary" style="padding:0.4rem 0.8rem;font-size:0.8rem;<?= $filterStatus==='adopted'?'background:var(--gold);color:white;':'' ?>">Adopted (<?= $totalAdopted ?>)</a>
            <a href="?action=add" class="btn btn-primary" style="padding:0.4rem 0.9rem;font-size:0.85rem;">➕ Add Pet</a>
        </div>
    </div>

    <?php if (empty($pets)): ?>
        <div style="padding:3rem;text-align:center;color:var(--text-light);">
            <div style="font-size:3rem;">🐾</div>
            <p>No pets found. <a href="?action=add" style="color:var(--terracotta);">Add one now!</a></p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>Photo</th><th>Name</th><th>Breed</th><th>Age</th>
                <th>Species</th><th>Status</th><th>Added</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pets as $pet): ?>
        <tr>
            <td>
                <div class="img-preview">
                    <img src="<?= getPetImageUrl($pet['picture'] ?? '', $pet['species'], $pet['id']) ?>"
                         alt="<?= sanitize($pet['name']) ?>"
                         loading="lazy">
                </div>
            </td>
            <td><strong><?= sanitize($pet['name']) ?></strong></td>
            <td><?= sanitize($pet['breed']) ?></td>
            <td><?= sanitize($pet['age']) ?></td>
            <td><?= ucfirst($pet['species']) ?></td>
            <td><span class="badge badge-<?= $pet['status'] ?>"><?= ucfirst($pet['status']) ?></span></td>
            <td style="color:var(--text-light);font-size:0.8rem;"><?= date('M d, Y', strtotime($pet['created_at'])) ?></td>
            <td>
                <div class="action-btns">
                    <!-- EDIT BUTTON: Dala-dalhin sa edit form -->
                    <a href="?action=edit&id=<?= $pet['id'] ?>" class="btn btn-warning" style="padding:0.3rem 0.7rem;font-size:0.8rem;">✏️ Edit</a>

                    <!-- QUICK STATUS TOGGLE: Mabilis na palitan ang status -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="id" value="<?= $pet['id'] ?>">
                        <input type="hidden" name="new_status" value="<?= $pet['status']==='available'?'adopted':'available' ?>">
                        <button type="submit" class="btn <?= $pet['status']==='available'?'btn-success':'btn-secondary' ?>"
                                style="padding:0.3rem 0.7rem;font-size:0.8rem;">
                            <?= $pet['status']==='available' ? '❤️ Mark Adopted' : '🔄 Set Available' ?>
                        </button>
                    </form>

                    <!-- DELETE BUTTON: confirm-delete class ay ginagamit ng JS para magpakita ng confirm dialog -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $pet['id'] ?>">
                        <button type="submit" class="btn btn-danger confirm-delete" style="padding:0.3rem 0.7rem;font-size:0.8rem;">🗑️</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
