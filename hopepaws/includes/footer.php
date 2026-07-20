<?php
/*
 * =============================================================================
 * FILE: includes/footer.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang SHARED FOOTER para sa lahat ng public pages ng HopePaws.
 *   Nagbibigay ito ng:
 *   - Footer section na may branding, links, at contact info
 *   - Closing HTML tags para sa body
 *   - main.js script loading
 *
 * NILALAMAN NG FOOTER:
 *   - Brand section (logo at tagline)
 *   - Quick links sa lahat ng public pages
 *   - Contact information ng shelter
 *   - Copyright notice na may dynamic na taon
 *
 * GINAGAMIT SA:
 *   Lahat ng public pages gamit ang: include 'includes/footer.php';
 *   (index.php, pets.php, gallery.php, about.php, contact.php, user/dashboard.php)
 * =============================================================================
 */
?>

<!-- FOOTER SECTION: Global footer na nagpapakita sa lahat ng public pages -->
<footer class="footer">
    <div class="footer-inner">

        <!-- BRAND SECTION: Logo at tagline ng HopePaws -->
        <div class="footer-brand">
            <span class="logo">🐾 HopePaws</span>
            <p>Rescuing, rehabilitating, and rehoming animals across Marinduque since 2026.</p>
        </div>

        <!-- QUICK LINKS: Mga mabilis na link sa lahat ng pahina -->
        <div class="footer-links">
            <h4>Pages</h4>
            <ul>
                <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                <li><a href="<?= SITE_URL ?>/pets.php">Adopt a Pet</a></li>
                <li><a href="<?= SITE_URL ?>/gallery.php">Gallery</a></li>
                <li><a href="<?= SITE_URL ?>/about.php">About Us</a></li>
                <li><a href="<?= SITE_URL ?>/contact.php">Contact</a></li>
            </ul>
        </div>

        <!-- CONTACT INFO: Impormasyon para makipag-ugnayan sa shelter -->
        <div class="footer-contact">
            <h4>Contact</h4>
            <p>📍 Boac, Marinduque</p>
            <p>📞 (049) 555-0192</p>
            <p>✉️ hello@hopepaws.ph</p>
            <p>🕐 Mon–Sat 8AM–5PM</p>
        </div>
    </div>

    <!-- COPYRIGHT: Awtomatikong nag-a-update ang taon gamit ang date('Y') -->
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> HopePaws Animal Rescue · Made with ❤️ for every paw.
    </div>
</footer>

<!-- Shared JavaScript file — naglo-load ito sa dulo ng page para hindi mapabagal ang load time -->
<script src="<?= SITE_URL ?>/assets/main.js"></script>
</body>
</html>
