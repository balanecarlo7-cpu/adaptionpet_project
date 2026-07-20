<?php
/*
 * =============================================================================
 * FILE: admin/footer.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang SHARED FOOTER para sa lahat ng admin pages. Isinasara nito ang
 *   mga HTML tags na binuksan sa admin/header.php at naglo-load ng mga scripts.
 *
 * NILALAMAN:
 *   - Closing HTML tags para sa admin-content, admin-main, at body
 *   - main.js script (shared sa public at admin)
 *   - JavaScript para sa "Confirm Delete" dialog
 *
 * GINAGAMIT SA:
 *   Lahat ng admin pages gamit ang: include 'footer.php';
 * =============================================================================
 */
?>
    </div><!-- /admin-content — isinasara ang div na binuksan sa header.php -->
</main><!-- /admin-main — isinasara ang main content wrapper -->

<!-- Shared JavaScript file na ginagamit ng parehong admin at public pages -->
<script src="<?= SITE_URL ?>/assets/main.js"></script>

<script>
/*
 * CONFIRM DELETE SCRIPT
 * Lahat ng button na may class na 'confirm-delete' ay magpapakita ng
 * confirmation dialog bago i-submit ang delete action.
 * Kung pipiliin ng user ang "Cancel", hindi matutuloy ang delete.
 *
 * GINAGAMIT SA: admin/pets.php, admin/gallery.php, admin/messages.php
 */
document.querySelectorAll('.confirm-delete').forEach(btn => {
    btn.addEventListener('click', e => {
        // Kung pinindot ng user ang "Cancel" sa confirm dialog, pigilan ang form submission
        if (!confirm('Are you sure you want to delete this? This cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>
