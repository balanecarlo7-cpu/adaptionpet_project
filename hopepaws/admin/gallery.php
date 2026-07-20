<?php
/*
 * =============================================================================
 * FILE: admin/gallery.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang GALLERY MANAGEMENT PAGE para sa admin. Dito makakapag-upload
 *   at makakapagtanggal ng mga larawan ang admin para sa public gallery page.
 *
 * MGA FEATURES:
 *   - Upload ng larawan (JPG, PNG, GIF, WEBP — max 5MB)
 *   - Optional caption para sa bawat larawan
 *   - Grid view ng lahat ng gallery photos
 *   - Delete individual photos (kasama ang actual na file sa server)
 *
 * AUTHENTICATION:
 *   Admin only — gumagamit ng requireAdmin().
 *
 * GINAGAMIT NA FILES:
 *   - ../includes/config.php  (requireAdmin, getDB, sanitize, UPLOAD_URL)
 *   - header.php, footer.php
 * =============================================================================
 */

require_once '../includes/config.php';
requireAdmin();
$db = getDB();
$adminTitle = 'Gallery';

$success = $error = '';

// =============================================================================
// FORM PROCESSING: Upload at Delete actions
// =============================================================================

// UPLOAD ACTION: I-process ang bagong larawan na ini-upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    $caption = sanitize($_POST['caption'] ?? '');

    if (empty($_FILES['image']['name'])) {
        $error = 'Please select an image to upload.';
    } else {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp']; // Allowed file types

        // VALIDATION: Suriin ang file extension at size
        if (!in_array($ext, $allowed)) {
            $error = 'Only JPG, PNG, GIF, WEBP files allowed.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) { // Max 5MB
            $error = 'Image must be under 5MB.';
        } else {
            // Gumawa ng unique filename para hindi mag-overwrite ng existing files
            $filename = uniqid('gallery_') . '.' . $ext;
            $dest = __DIR__ . '/../uploads/gallery/' . $filename;

            // Ilipat ang uploaded file sa gallery uploads folder
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                // I-save ang record sa database kasama ang filename at caption
                $stmt = $db->prepare("INSERT INTO gallery (image, caption) VALUES (?, ?)");
                $stmt->bind_param('ss', $filename, $caption);
                $stmt->execute();
                $success = 'Photo uploaded successfully! 📷';
            } else {
                $error = 'Upload failed. Check folder permissions.';
            }
        }
    }
}

// DELETE ACTION: Burahin ang larawan mula sa database at sa server
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id   = (int)($_POST['id'] ?? 0);
    $item = $db->query("SELECT * FROM gallery WHERE id=$id")->fetch_assoc();
    if ($item) {
        // Burahin ang actual na file mula sa uploads/gallery folder
        if ($item['image'] && file_exists(__DIR__.'/../uploads/gallery/'.$item['image'])) {
            unlink(__DIR__.'/../uploads/gallery/'.$item['image']);
        }
        // Burahin ang record mula sa database
        $db->query("DELETE FROM gallery WHERE id=$id");
        $success = 'Photo deleted.';
    }
}

// Kunin ang lahat ng gallery photos, pinaka-bago muna
$gallery = $db->query("SELECT * FROM gallery ORDER BY uploaded_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'header.php'; ?>

<?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- UPLOAD FORM: Para mag-upload ng bagong larawan sa gallery -->
<div class="admin-form-card" style="margin-bottom:1.5rem;">
    <h2 class="admin-form-title">📷 Upload Photo to Gallery</h2>
    <!-- enctype="multipart/form-data" — kailangan para gumana ang file upload -->
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="form-row">
            <div class="form-group">
                <label>Select Image *</label>
                <!-- data-preview="gallery-preview" — ginagamit ng main.js para i-preview ang larawan -->
                <input type="file" name="image" accept="image/*" required data-preview="gallery-preview">
            </div>
            <div class="form-group">
                <label>Caption</label>
                <input type="text" name="caption" placeholder="e.g. Adoption Day – Mochi finds her home!">
            </div>
        </div>
        <!-- Image preview area — magiging may larawan dito pagkatapos pumili ng file -->
        <div id="gallery-preview" class="img-preview" style="width:100px;height:100px;margin-bottom:1rem;">🖼️</div>
        <button type="submit" class="btn btn-primary">📤 Upload Photo</button>
    </form>
</div>

<!-- GALLERY GRID: Nagpapakita ng lahat ng uploaded photos sa grid format -->
<div class="admin-card">
    <div class="admin-card-header">
        <span class="admin-card-title">Gallery Photos <span style="font-size:0.85rem;font-weight:400;color:var(--text-light);">(<?= count($gallery) ?> total)</span></span>
    </div>
    <?php if (empty($gallery)): ?>
        <div style="padding:3rem;text-align:center;color:var(--text-light);">
            <div style="font-size:3rem;">🖼️</div>
            <p>No photos yet. Upload one above!</p>
        </div>
    <?php else: ?>
    <div class="admin-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">
            <?php foreach ($gallery as $item): ?>
            <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;background:white;box-shadow:0 2px 10px var(--shadow);">
                <!-- Larawan o placeholder kung hindi makita ang file -->
                <div style="height:140px;background:linear-gradient(135deg,#FDE8D8,#F5C9A8);display:flex;align-items:center;justify-content:center;font-size:3rem;overflow:hidden;">
                    <?php if ($item['image'] && file_exists(__DIR__.'/../uploads/gallery/'.$item['image'])): ?>
                        <img src="<?= UPLOAD_URL ?>gallery/<?= htmlspecialchars($item['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        🖼️ <!-- Placeholder emoji kung wala ang file -->
                    <?php endif; ?>
                </div>
                <div style="padding:0.75rem;">
                    <?php if ($item['caption']): ?>
                        <p style="font-size:0.8rem;color:var(--text-mid);margin-bottom:0.5rem;line-height:1.4;"><?= sanitize($item['caption']) ?></p>
                    <?php endif; ?>
                    <p style="font-size:0.7rem;color:var(--text-light);margin-bottom:0.5rem;"><?= date('M d, Y', strtotime($item['uploaded_at'])) ?></p>
                    <!-- DELETE BUTTON: confirm-delete class ay nagtatrigger ng JS confirm dialog -->
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-danger confirm-delete" style="width:100%;padding:0.35rem;font-size:0.8rem;">🗑️ Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
