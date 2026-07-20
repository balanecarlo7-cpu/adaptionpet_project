<?php
/*
 * =============================================================================
 * FILE: admin/messages.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang MESSAGE INBOX para sa admin. Dito makikita ng admin ang lahat ng
 *   mensaheng ipinadala ng mga user sa pamamagitan ng contact form (contact.php).
 *
 * MGA FEATURES:
 *   - Inbox na may listahan ng lahat ng mensahe
 *   - Auto-mark as read kapag binuksan ang mensahe
 *   - "Mark All Read" button para sabay-sabay na markahan lahat
 *   - Delete individual na mensahe
 *   - Filter: All, Unread, Read
 *   - Message detail panel (split view)
 *   - Reply via email button
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
$adminTitle = 'Messages';

$success = '';

// MARK AS READ: Kapag nag-view ng isang mensahe, awtomatikong nire-mark bilang "read"
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    $db->query("UPDATE messages SET is_read=1 WHERE id=$viewId");
}

// =============================================================================
// FORM PROCESSING: Delete at Mark All Read actions
// =============================================================================

// DELETE ACTION: Burahin ang isang mensahe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("DELETE FROM messages WHERE id=$id");
    $success = 'Message deleted.';
}

// MARK ALL READ: I-mark ang lahat ng mensahe bilang "read" nang sabay-sabay
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'read_all') {
    $db->query("UPDATE messages SET is_read=1");
    $success = 'All messages marked as read.';
}

// Kunin ang buong mensahe kung may napiling "view" sa URL
$viewMessage = null;
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    $viewMessage = $db->query("SELECT * FROM messages WHERE id=$viewId")->fetch_assoc();
}

// FILTER: Kunin ang mensahe batay sa selected filter
$filter = $_GET['filter'] ?? 'all';
$where = $filter === 'unread' ? "WHERE is_read=0" : ($filter === 'read' ? "WHERE is_read=1" : '');
$messages = $db->query("SELECT * FROM messages $where ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Stats para sa display
$unreadCount = $db->query("SELECT COUNT(*) as c FROM messages WHERE is_read=0")->fetch_assoc()['c'];
$totalCount  = $db->query("SELECT COUNT(*) as c FROM messages")->fetch_assoc()['c'];
?>
<?php include 'header.php'; ?>

<?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

<!-- STATS AT ACTION BUTTONS: Bilang ng mensahe at mga filter/action buttons -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:0.75rem;">
    <div style="display:flex;gap:1rem;">
        <div class="stat-card" style="padding:0.9rem 1.5rem;min-width:auto;">
            <div class="stat-icon brown">✉️</div>
            <div class="stat-info"><span class="num"><?= $totalCount ?></span><span class="label">Total</span></div>
        </div>
        <div class="stat-card" style="padding:0.9rem 1.5rem;min-width:auto;">
            <div class="stat-icon terracotta">🔴</div>
            <div class="stat-info"><span class="num"><?= $unreadCount ?></span><span class="label">Unread</span></div>
        </div>
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <!-- Filter buttons: All, Unread, Read -->
        <a href="?filter=all"    class="btn btn-secondary" style="padding:0.4rem 0.8rem;font-size:0.8rem;<?= $filter==='all'    ?'background:var(--terracotta);color:white;':'' ?>">All</a>
        <a href="?filter=unread" class="btn btn-secondary" style="padding:0.4rem 0.8rem;font-size:0.8rem;<?= $filter==='unread' ?'background:var(--terracotta);color:white;':'' ?>">Unread (<?= $unreadCount ?>)</a>
        <a href="?filter=read"   class="btn btn-secondary" style="padding:0.4rem 0.8rem;font-size:0.8rem;<?= $filter==='read'   ?'background:var(--terracotta);color:white;':'' ?>">Read</a>
        <!-- "Mark All Read" button — nagpapakita lang kung may unread na mensahe -->
        <?php if ($unreadCount > 0): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="read_all">
            <button type="submit" class="btn btn-success" style="padding:0.4rem 0.8rem;font-size:0.8rem;">✅ Mark All Read</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- SPLIT VIEW: Listahan ng mensahe sa kaliwa, detalye sa kanan (kung may piniling mensahe) -->
<div style="display:grid;grid-template-columns:<?= $viewMessage ? '1fr 1.4fr' : '1fr' ?>;gap:1.5rem;align-items:start;">

    <!-- MESSAGE LIST: Talahanayan ng lahat ng mensahe -->
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Inbox</span>
        </div>
        <?php if (empty($messages)): ?>
            <div style="padding:3rem;text-align:center;color:var(--text-light);">
                <div style="font-size:3rem;">📭</div>
                <p>No messages yet.</p>
            </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th></th><th>From</th><th>Subject</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($messages as $msg): ?>
            <!-- Unread messages ay may bahagyang highlighted na background -->
            <tr style="<?= !$msg['is_read'] ? 'background:rgba(200,96,58,0.04);font-weight:500;' : '' ?>">
                <!-- Dot indicator: pula para sa unread, puti para sa read -->
                <td><?= !$msg['is_read'] ? '<span style="color:var(--terracotta);font-size:0.75rem;">●</span>' : '<span style="color:var(--text-light);font-size:0.75rem;">○</span>' ?></td>
                <td>
                    <strong style="display:block;"><?= sanitize($msg['sender_name']) ?></strong>
                    <span style="font-size:0.78rem;color:var(--text-light);"><?= sanitize($msg['sender_email']) ?></span>
                </td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= sanitize($msg['subject'] ?: '(No subject)') ?>
                </td>
                <td style="font-size:0.78rem;color:var(--text-light);"><?= timeAgo($msg['created_at']) ?></td>
                <td>
                    <div class="action-btns">
                        <!-- VIEW button: Bubukas ang message detail panel sa kanan -->
                        <a href="?view=<?= $msg['id'] ?>&filter=<?= $filter ?>" class="btn btn-warning" style="padding:0.3rem 0.6rem;font-size:0.78rem;">👁️ View</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                            <button type="submit" class="btn btn-danger confirm-delete" style="padding:0.3rem 0.6rem;font-size:0.78rem;">🗑️</button>
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

    <!-- MESSAGE DETAIL: Ipinapakita sa kanan kapag pinindot ang "View" button -->
    <?php if ($viewMessage): ?>
    <div class="admin-card" style="position:sticky;top:80px;">
        <div class="admin-card-header">
            <span class="admin-card-title">Message Detail</span>
            <a href="messages.php?filter=<?= $filter ?>" class="btn btn-secondary" style="padding:0.35rem 0.75rem;font-size:0.8rem;">✕ Close</a>
        </div>
        <div class="admin-card-body">
            <div style="margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.5rem;">
                    <div>
                        <h3 style="font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--text-dark);">
                            <?= sanitize($viewMessage['subject'] ?: '(No Subject)') ?>
                        </h3>
                        <div style="display:flex;gap:0.5rem;margin-top:0.25rem;">
                            <span class="badge badge-<?= $viewMessage['is_read'] ? 'read' : 'unread' ?>">
                                <?= $viewMessage['is_read'] ? 'Read' : 'New' ?>
                            </span>
                        </div>
                    </div>
                    <span style="font-size:0.8rem;color:var(--text-light);"><?= date('M d, Y · h:i A', strtotime($viewMessage['created_at'])) ?></span>
                </div>
                <!-- Sender info panel -->
                <div style="background:var(--cream);border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-top:0.75rem;">
                    <strong style="font-size:0.85rem;color:var(--text-dark);"><?= sanitize($viewMessage['sender_name']) ?></strong>
                    <span style="font-size:0.82rem;color:var(--text-light);margin-left:0.5rem;">&lt;<?= sanitize($viewMessage['sender_email']) ?>&gt;</span>
                </div>
            </div>

            <!-- BODY ng mensahe -->
            <div style="line-height:1.8;color:var(--text-mid);font-size:0.95rem;white-space:pre-wrap;"><?= sanitize($viewMessage['body']) ?></div>

            <!-- ACTION BUTTONS: Reply via email o Delete -->
            <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border);display:flex;gap:0.75rem;">
                <!-- REPLY BUTTON: Nagbubukas ng email client para mag-reply sa sender -->
                <a href="mailto:<?= sanitize($viewMessage['sender_email']) ?>?subject=Re: <?= urlencode(sanitize($viewMessage['subject'])) ?>"
                   class="btn btn-primary" style="font-size:0.875rem;">
                    ✉️ Reply via Email
                </a>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $viewMessage['id'] ?>">
                    <button type="submit" class="btn btn-danger confirm-delete" style="font-size:0.875rem;">🗑️ Delete</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
