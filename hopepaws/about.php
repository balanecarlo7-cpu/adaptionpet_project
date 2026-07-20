<?php
/*
 * =============================================================================
 * FILE: about.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang ABOUT PAGE ng HopePaws. Nagkukwento ito tungkol sa organisasyon —
 *   ang simula nito, ang misyon, ang values, at ang mga miyembro ng team.
 *
 * NILALAMAN (Contents):
 *   - Kwento kung paano nagsimula ang HopePaws (Our Story)
 *   - Apat na haligi ng misyon (Rescue, Heal, Find Homes, Educate)
 *   - Team members na may larawan at role
 *   - Dynamic na statistics (bilang ng adopted at total pets)
 *
 * AUTHENTICATION:
 *   Kailangan muna mag-login bago makita ang page na ito.
 *
 * GINAGAMIT NA FILES:
 *   - includes/config.php  (getDB, sanitize, isUserLoggedIn, isAdminLoggedIn)
 *   - includes/header.php  (navigation)
 *   - includes/footer.php  (footer)
 * =============================================================================
 */

require_once 'includes/config.php';

// Kung hindi naka-login, ibalik sa login page
if (!isUserLoggedIn() && !isAdminLoggedIn()) { header('Location: ' . SITE_URL . '/login.php'); exit; }

$db = getDB();
$pageTitle = 'About Us';

// Kunin ang statistics para sa about page (bilang ng adopted at total pets)
$adopted = $db->query("SELECT COUNT(*) as c FROM pets WHERE status='adopted'")->fetch_assoc()['c'];
$total   = $db->query("SELECT COUNT(*) as c FROM pets")->fetch_assoc()['c'];
?>
<?php include 'includes/header.php'; ?>

<!-- PAGE HERO: Header ng about page -->
<section class="page-hero">
  <span class="ey">Who We Are</span>
  <h1>About HopePaws</h1>
  <p>A passionate team dedicated to giving every animal a second chance at life.</p>
</section>

<!-- OUR STORY SECTION: Kwento kung paano nagsimula ang HopePaws -->
<div class="sec alt">
  <div class="container" style="padding-top:4rem;padding-bottom:4rem;">
    <div class="about-grid">
      <div class="about-visual">
        <div class="about-img">
          <img src='<?= SITE_URL ?>/assets/images/petshop.png' alt="mypetshop">
        </div>
      </div>
      <div>
        <span class="ey">Our Story</span>
        <h2 style="font-family:'Lora',serif;font-size:clamp(1.6rem,3vw,2.2rem);font-weight:700;color:var(--text);margin-bottom:1rem;line-height:1.3;">From the Streets<br>to a Safe Haven</h2>
        <p style="color:var(--gray-600);line-height:1.8;margin-bottom:.85rem;font-size:.95rem;">HopePaws was born in 2026 in Boac, Marinduque sparked by three volunteers who found an injured dog on the highway and decided they couldn't look away anymore.</p>
        <p style="color:var(--gray-600);line-height:1.8;margin-bottom:.85rem;font-size:.95rem;">What started as a small network of foster homes and a Facebook group grew into a registered animal welfare organization, complete with a physical shelter and a dedicated team of volunteers.</p>
        <p style="color:var(--gray-600);line-height:1.8;font-size:.95rem;">Today, HopePaws serves as a safe haven for dogs, cats, rabbits, and birds rescued from neglect and abandonment across Boac and nearby town/baranggay.</p>
      </div>
    </div>
  </div>
</div>

<!-- MISSION & VALUES SECTION: Apat na haligi ng misyon ng HopePaws -->
<div class="sec">
  <div class="container">
    <div class="sec-head center">
      <span class="ey">What We Stand For</span>
      <h2>Our Mission & Values</h2>
    </div>
    <div class="steps-grid">
      <div class="step-card"><div class="step-title">Rescue First</div><p class="step-desc">We respond quickly to animals in danger and ensure they receive immediate care.</p></div>
      <div class="step-card"><div class="step-title">Heal & Rehabilitate</div><p class="step-desc">Every rescue gets vet care, nutrition, and emotional support before adoption.</p></div>
      <div class="step-card"><div class="step-title">Find Forever Homes</div><p class="step-desc">We carefully screen adopters to ensure the perfect match for pet and family.</p></div>
      <div class="step-card"><div class="step-title">Educate & Advocate</div><p class="step-desc">We run community programs promoting responsible pet ownership and welfare.</p></div>
    </div>
  </div>
</div>

<!-- TEAM SECTION: Mga miyembro ng HopePaws team na may larawan at role -->
<!-- Ang team data ay hardcoded na array — hindi galing sa database -->
<div class="sec">
  <div class="container">
    <div class="sec-head center">
      <span class="ey">The People</span>
      <h2>Meet Our Team</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1.2rem;">
      <?php
      // Array ng team members — bawat entry ay [larawan, pangalan, posisyon]
      foreach ([
        [SITE_URL . '/assets/images/carlo.png',    'Dr. Balane, Carlo', 'Founder & Veterinarian'],
        [SITE_URL . '/assets/images/dhapic.jpg',   'Limpiada, Dharen',  'Operations Manager'],
        [SITE_URL . '/assets/images/warren.jpg',   'Malalad, Warren',   'Lead Rescuer'],
        [SITE_URL . '/assets/images/princess.jpg', 'Quinto, Princess',  'Adoption Coordinator'],
      ] as [$img, $n, $r]): ?>
      <div class="step-card" style="text-align:center;">
        <img src="<?= $img ?>" alt="<?= $n ?>"
             style="width:90px;height:90px;border-radius:50%;object-fit:cover;
                    display:block;margin:0 auto 0.8rem;border:3px solid #5b8a6b;">
        <div class="step-title"><?= $n ?></div>
        <p class="step-desc"><?= $r ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
