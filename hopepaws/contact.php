<?php
/*
 * =============================================================================
 * FILE: contact.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang CONTACT PAGE ng HopePaws. Dito makapagpadala ng mensahe ang
 *   mga bisita papunta sa admin/shelter team. Ang mga mensahe ay naka-save
 *   sa database at makikita ng admin sa admin/messages.php.
 *
 * FEATURES:
 *   - Contact information ng shelter (address, phone, email, hours)
 *   - Contact form (name, email, subject, message)
 *   - Nag-se-save ng mensahe sa 'messages' table ng database
 *   - Nagpapakita ng success o error message pagkatapos mag-submit
 *
 * AUTHENTICATION:
 *   WALA — Public page ito, lahat ay makakakita at makapagpadala ng mensahe.
 *
 * GINAGAMIT NA FILES:
 *   - includes/config.php  (getDB, sanitize functions)
 *   - includes/header.php  (navigation)
 *   - includes/footer.php  (footer)
 * =============================================================================
 */

require_once 'includes/config.php';

// Kung hindi naka-login, ibalik sa login page
if (!isUserLoggedIn() && !isAdminLoggedIn()) { header('Location: ' . SITE_URL . '/login.php'); exit; }

$db = getDB();
$pageTitle = 'Contact';
$success = $error = '';

// =============================================================================
// FORM PROCESSING: Pinoproseso ang contact form kapag nai-submit
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kunin at linisin ang form data
    $name    = sanitize($_POST['sender_name']  ?? '');
    $email   = filter_var($_POST['sender_email'] ?? '', FILTER_VALIDATE_EMAIL); // Validate email format
    $subject = sanitize($_POST['subject'] ?? '');
    $body    = sanitize($_POST['body']    ?? '');

    // VALIDATION: Tingnan kung kumpleto ang required fields
    if (!$name || !$email || !$body) {
        $error = 'Please fill in all required fields.';
    } else {
        // INSERT: I-save ang mensahe sa 'messages' table ng database
        // Ginagamit ang prepared statement para maiwasan ang SQL injection
        $stmt = $db->prepare("INSERT INTO messages (sender_name, sender_email, subject, body) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $email, $subject, $body);
        // Magpakita ng success o error message depende sa resulta
        $success = $stmt->execute() ? 'Message sent! We\'ll get back to you within 1–2 days. 🐾' : 'Something went wrong. Please try again.';
    }
}
?>
<?php include 'includes/header.php'; ?>

<!-- PAGE HERO: Header ng contact page -->
<section class="page-hero">
  <span class="ey">Get in Touch</span>
  <h1>Contact Us</h1>
  <p>Questions about adoption or volunteering? We'd love to hear from you.</p>
</section>

<div class="sec">
  <div class="container">
    <div class="contact-grid">

      <!-- LEFT SIDE: Contact details ng shelter -->
      <div>
        <span class="ey">Our Details</span>
        <h2 style="font-family:'Lora',serif;font-size:1.9rem;font-weight:700;color:var(--text);margin-bottom:.6rem;">Reach Out Anytime</h2>
        <p style="color:var(--gray-600);font-size:.95rem;margin-bottom:0;">Whether you want to adopt, volunteer, or just say hi — our team is here for you.</p>
        <!-- CONTACT INFO CARDS: Address, Phone, Email, Hours -->
        <div class="contact-cards">
          <div class="ccard"><div class="ccard-icon">📍</div><div><strong>Address</strong><span>Tanza, Boac, Marinduque</span></div></div>
          <div class="ccard"><div class="ccard-icon">📞</div><div><strong>Phone</strong><span>(049) 555-0192 · 0948-183-6308</span></div></div>
          <div class="ccard"><div class="ccard-icon">✉️</div><div><strong>Email</strong><span>hello@hopepaws.ph · adopt@hopepaws.ph</span></div></div>
          <div class="ccard"><div class="ccard-icon">🕐</div><div><strong>Shelter Hours</strong><span>Mon–Sat 8AM–5PM · Sun 10AM–3PM</span></div></div>
        </div>
      </div>

      <!-- RIGHT SIDE: Contact form -->
      <div class="form-card">
        <h3>Send Us a Message</h3>
        <!-- Ipakita ang success o error message pagkatapos mag-submit -->
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <!-- CONTACT FORM: Ang data ay mapupunta sa database 'messages' table -->
        <form method="POST">
          <div class="form-row">
            <div class="form-group"><label>Your Name *</label><input type="text" name="sender_name" required placeholder="Warren Malalad"></div>
            <div class="form-group"><label>Email *</label><input type="email" name="sender_email" required placeholder="you@email.com"></div>
          </div>
          <div class="form-group"><label>Subject</label><input type="text" name="subject" placeholder="e.g. Adoption inquiry about Mochi"></div>
          <div class="form-group"><label>Message *</label><textarea name="body" required placeholder="Write your message here..." style="min-height:135px;"></textarea></div>
          <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Send Message ✉️</button>
        </form>
      </div>

    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
