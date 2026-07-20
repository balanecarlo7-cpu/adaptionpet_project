<?php
/*
 * =============================================================================
 * FILE: gallery.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang PUBLIC GALLERY PAGE ng HopePaws. Ipinapakita dito ang lahat ng
 *   larawan na na-upload ng admin mula sa admin/gallery.php.
 *
 * AUTHENTICATION:
 *   WALA — Public page ito, lahat ay makakakita kahit hindi naka-login.
 *
 * GINAGAMIT NA FILES:
 *   - includes/config.php  (getDB, sanitize, UPLOAD_URL)
 *   - includes/header.php  (navigation)
 *   - includes/footer.php  (footer)
 * =============================================================================
 */

require_once 'includes/config.php';
// Kung hindi naka-login, ibalik sa login page
if (!isUserLoggedIn() && !isAdminLoggedIn()) { header('Location: ' . SITE_URL . '/login.php'); exit; }

$db = getDB();
$pageTitle = 'Gallery';

// Kunin ang lahat ng gallery photos, pinaka-bago muna
$gallery = $db->query("SELECT * FROM gallery ORDER BY uploaded_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'includes/header.php'; ?>

<!-- PAGE HERO: Header ng gallery page -->
<section class="page-hero">
  <span class="ey">Our Gallery</span>
  <h1>Moments of Hope 📷</h1>
  <p>Happy tails, adoption days, and life at the shelter — every photo tells a story.</p>
</section>

<div class="sec">
  <div class="container">

    <?php if (empty($gallery)): ?>
      <!-- Walang photos pa sa gallery -->
      <div style="text-align:center;padding:4rem 2rem;color:var(--gray-600);">
        <div style="font-size:4rem;margin-bottom:1rem;">🖼️</div>
        <h3 style="font-family:'Lora',serif;margin-bottom:.5rem;">No Photos Yet</h3>
        <p>Check back soon — our shelter team is always capturing new moments!</p>
      </div>
    <?php else: ?>

      <!-- GALLERY GRID: Responsive grid -->
      <div style="
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
        gap:1.25rem;
      ">
        <?php foreach ($gallery as $item): ?>
        <div style="
          border-radius:12px;
          overflow:hidden;
          background:#fff;
          box-shadow:0 2px 12px rgba(0,0,0,.08);
          transition:transform .2s,box-shadow .2s;
        " onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.14)'"
           onmouseout="this.style.transform='';this.style.boxShadow='0 2px 12px rgba(0,0,0,.08)'">

          <!-- Photo -->
          <div style="height:220px;background:linear-gradient(135deg,#FDE8D8,#F5C9A8);overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:3.5rem;">
            <?php
              $imgPath = __DIR__ . '/uploads/gallery/' . $item['image'];
              if ($item['image'] && file_exists($imgPath)):
            ?>
              <img
                src="<?= UPLOAD_URL ?>gallery/<?= htmlspecialchars($item['image']) ?>"
                alt="<?= htmlspecialchars($item['caption'] ?? 'Gallery photo') ?>"
                style="width:100%;height:100%;object-fit:cover;"
                loading="lazy"
              >
            <?php else: ?>
              🐾
            <?php endif; ?>
          </div>

          <!-- Caption & Date -->
          <?php if ($item['caption'] || $item['uploaded_at']): ?>
          <div style="padding:.85rem 1rem;">
            <?php if ($item['caption']): ?>
              <p style="font-size:.88rem;color:var(--text);font-weight:500;margin-bottom:.3rem;line-height:1.45;">
                <?= htmlspecialchars($item['caption']) ?>
              </p>
            <?php endif; ?>
            <?php if ($item['uploaded_at']): ?>
              <p style="font-size:.75rem;color:var(--gray-600);">
                📅 <?= date('F j, Y', strtotime($item['uploaded_at'])) ?>
              </p>
            <?php endif; ?>
          </div>
          <?php endif; ?>

        </div>
        <?php endforeach; ?>
      </div>

      <!-- Photo count -->
      <p style="text-align:center;color:var(--gray-600);font-size:.85rem;margin-top:2rem;">
        Showing <?= count($gallery) ?> photo<?= count($gallery) !== 1 ? 's' : '' ?> 🐾
      </p>

    <?php endif; ?>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
