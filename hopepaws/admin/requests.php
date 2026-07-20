<?php
/*
 * =============================================================================
 * FILE: admin/requests.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang ADOPTION REQUESTS MANAGEMENT PAGE para sa admin. Dito makikita
 *   at mamamahalaan ng admin ang lahat ng adoption requests mula sa mga user.
 *
 * MGA AKSYON NG ADMIN:
 *   - APPROVE: Tanggapin ang request → pet ay magiging "adopted", at lahat ng
 *              ibang pending requests para sa parehong pet ay ire-reject
 *   - REJECT:  Tanggihan ang request
 *   - RESET:   Ibalik ang status sa "pending" (para ma-reconsider)
 *
 * FILTER OPTIONS:
 *   - All, Pending, Approved, Rejected
 *
 * AUTHENTICATION:
 *   Admin only — gumagamit ng requireAdmin().
 *
 * GINAGAMIT NA FILES:
 *   - ../includes/config.php  (requireAdmin, getDB, sanitize, timeAgo)
 *   - header.php, footer.php
 * =============================================================================
 */

require_once '../includes/config.php';
requireAdmin();
$db = getDB();
$adminTitle = 'Adoption Requests';

$success = $error = '';

// =============================================================================
// FORM PROCESSING: Pinoproseso ang status update kapag nai-submit ang form
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = (int)($_POST['id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $petId     = (int)($_POST['pet_id'] ?? 0);

    // Validate: Dapat may valid ID at ang status ay isa sa tatlong allowed values
    if ($id && in_array($newStatus, ['approved','rejected','pending'])) {
        // I-update ang status ng request
        $db->query("UPDATE adoption_requests SET status='$newStatus' WHERE id=$id");

        // KUNG APPROVED: Awtomatikong i-mark ang pet bilang "adopted"
        // at i-reject ang lahat ng ibang pending requests para sa parehong pet
        if ($newStatus === 'approved' && $petId) {
            $db->query("UPDATE pets SET status='adopted' WHERE id=$petId");
            // I-reject ang lahat ng ibang pending requests para sa pet na ito
            // (para hindi ma-adopt ng dalawa ang iisang pet)
            $db->query("UPDATE adoption_requests SET status='rejected' WHERE pet_id=$petId AND id!=$id AND status='pending'");
        }

        $success = "Request #$id status updated to " . ucfirst($newStatus) . "!";
    }
}

// FILTER: Kunin ang filter value mula sa URL parameter
$filter = $_GET['filter'] ?? 'all';
$where = $filter !== 'all' ? "WHERE ar.status='$filter'" : '';

// Kunin ang adoption requests kasama ang impormasyon ng pet (JOIN)
$requests = $db->query("
    SELECT ar.*, p.name as pet_name, p.species, p.status as pet_status
    FROM adoption_requests ar
    JOIN pets p ON ar.pet_id = p.id
    $where
    ORDER BY ar.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Bilang ng bawat status para sa filter buttons at stat cards
$pendingCount  = $db->query("SELECT COUNT(*) as c FROM adoption_requests WHERE status='pending'")->fetch_assoc()['c'];
$approvedCount = $db->query("SELECT COUNT(*) as c FROM adoption_requests WHERE status='approved'")->fetch_assoc()['c'];
$rejectedCount = $db->query("SELECT COUNT(*) as c FROM adoption_requests WHERE status='rejected'")->fetch_assoc()['c'];
?>
<?php include 'header.php'; ?>

<!-- ALERTS -->
<?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<!-- STATS ROW: Quick overview ng bilang ng requests per status -->
<div class="stats-row" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-icon gold">⏳</div>
        <div class="stat-info"><span class="num"><?= $pendingCount ?></span><span class="label">Pending</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon sage">✅</div>
        <div class="stat-info"><span class="num"><?= $approvedCount ?></span><span class="label">Approved</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon terracotta">❌</div>
        <div class="stat-info"><span class="num"><?= $rejectedCount ?></span><span class="label">Rejected</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon brown">💌</div>
        <div class="stat-info"><span class="num"><?= $pendingCount + $approvedCount + $rejectedCount ?></span><span class="label">Total Requests</span></div>
    </div>
</div>

<!-- REQUESTS TABLE: Nagpapakita ng lahat ng adoption requests -->
<div class="admin-card">
    <div class="admin-card-header">
        <span class="admin-card-title">Adoption Requests</span>
        <!-- Filter buttons -->
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <a href="?filter=all"      class="btn btn-secondary" style="padding:0.35rem 0.75rem;font-size:0.8rem;<?= $filter==='all'     ?'background:var(--terracotta);color:white;':'' ?>">All</a>
            <a href="?filter=pending"  class="btn btn-secondary" style="padding:0.35rem 0.75rem;font-size:0.8rem;<?= $filter==='pending'  ?'background:var(--gold);color:white;':''        ?>">Pending (<?= $pendingCount ?>)</a>
            <a href="?filter=approved" class="btn btn-secondary" style="padding:0.35rem 0.75rem;font-size:0.8rem;<?= $filter==='approved' ?'background:var(--sage);color:white;':''         ?>">Approved</a>
            <a href="?filter=rejected" class="btn btn-secondary" style="padding:0.35rem 0.75rem;font-size:0.8rem;<?= $filter==='rejected' ?'background:#c0392b;color:white;':''             ?>">Rejected</a>
        </div>
    </div>

    <?php if (empty($requests)): ?>
        <div style="padding:3rem;text-align:center;color:var(--text-light);">
            <div style="font-size:3rem;">💌</div>
            <p>No requests found for this filter.</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr><th>#</th><th>Adopter</th><th>Contact</th><th>Pet</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
            <td style="color:var(--text-light);font-size:0.8rem;">#<?= $r['id'] ?></td>
            <td><strong><?= sanitize($r['adopter_name']) ?></strong></td>
            <td>
                <div style="font-size:0.8rem;">
                    <div><?= sanitize($r['adopter_email']) ?></div>
                    <?php if ($r['adopter_phone']): ?>
                        <div style="color:var(--text-light);"><?= sanitize($r['adopter_phone']) ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <strong><?= sanitize($r['pet_name']) ?></strong>
                <div style="font-size:0.75rem;color:var(--text-light);">
                    <span class="badge badge-<?= $r['pet_status'] ?>"><?= ucfirst($r['pet_status']) ?></span>
                </div>
            </td>
            <td style="max-width:180px;">
                <?php if ($r['message']): ?>
                    <!-- Pinutol ang mahaba messaging sa 60 characters para sa talahanayan -->
                    <span style="font-size:0.85rem;color:var(--text-mid);" title="<?= sanitize($r['message']) ?>">
                        <?= strlen($r['message']) > 60 ? substr(sanitize($r['message']), 0, 60) . '…' : sanitize($r['message']) ?>
                    </span>
                <?php else: ?>
                    <span style="color:var(--text-light);font-size:0.8rem;">No message</span>
                <?php endif; ?>
            </td>
            <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            <td style="color:var(--text-light);font-size:0.8rem;"><?= timeAgo($r['created_at']) ?></td>
            <td>
                <?php if ($r['status'] === 'pending'): ?>
                <!-- AKSYON PARA SA PENDING: Approve o Reject -->
                <div class="action-btns">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="pet_id" value="<?= $r['pet_id'] ?>">
                        <input type="hidden" name="new_status" value="approved">
                        <button type="submit" class="btn btn-success" style="padding:0.3rem 0.65rem;font-size:0.8rem;">✅ Approve</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="pet_id" value="<?= $r['pet_id'] ?>">
                        <input type="hidden" name="new_status" value="rejected">
                        <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.65rem;font-size:0.8rem;">❌ Reject</button>
                    </form>
                </div>
                <?php elseif ($r['status'] === 'approved'): ?>
                <!-- AKSYON PARA SA APPROVED: Reset pabalik sa pending -->
                <div class="action-btns">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="pet_id" value="<?= $r['pet_id'] ?>">
                        <input type="hidden" name="new_status" value="pending">
                        <button type="submit" class="btn btn-secondary" style="padding:0.3rem 0.65rem;font-size:0.8rem;">🔄 Reset</button>
                    </form>
                </div>
                <?php else: ?>
                <!-- AKSYON PARA SA REJECTED: I-reconsider (ibalik sa pending) -->
                <div class="action-btns">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="pet_id" value="<?= $r['pet_id'] ?>">
                        <input type="hidden" name="new_status" value="pending">
                        <button type="submit" class="btn btn-secondary" style="padding:0.3rem 0.65rem;font-size:0.8rem;">🔄 Reconsider</button>
                    </form>
                </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
