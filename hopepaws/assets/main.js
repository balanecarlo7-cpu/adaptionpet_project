/*
 * =============================================================================
 * FILE: assets/main.js
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang SHARED JAVASCRIPT FILE ng HopePaws. Ginagamit ito ng parehong
 *   public website at admin panel. Naglalaman ito ng lahat ng interactive
 *   na features ng site na nangangailangan ng JavaScript.
 *
 * MGA NILALAMAN (Contents):
 *   1. Scroll Animations    - Fade-in effect habang nag-i-scroll ang user
 *   2. Modal Functions      - Pagbubukas at pagsasara ng adoption pop-up forms
 *   3. Keyboard Support     - ESC key para isara ang modal
 *   4. Auto-dismiss Alerts  - Awtomatikong nawawala ang alerts pagkatapos ng 4 segundo
 *   5. Filter Buttons       - Client-side filtering ng mga cards (hindi ginagamit sa server)
 *   6. Image Preview        - Real-time preview ng larawan bago i-upload
 *
 * GINAGAMIT SA:
 *   - Lahat ng public pages (sa pamamagitan ng includes/footer.php)
 *   - Lahat ng admin pages (sa pamamagitan ng admin/footer.php)
 * =============================================================================
 */

// =============================================================================
// 1. SCROLL ANIMATIONS (Intersection Observer)
// Nagdadagdag ng 'visible' class sa mga elemento kapag lumabas na sila
// sa viewport habang nag-i-scroll. Ginagamit ng CSS para gumawa ng fade-in effect.
// Ang threshold: 0.1 ay ibig sabihin, kapag 10% ng elemento ay nakikita na,
// magti-trigger na ang animation.
// =============================================================================
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            // Idagdag ang 'visible' class kapag nakita na ng browser ang element
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1 });

// I-observe ang lahat ng elemento na may 'fade-up' class
document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

// =============================================================================
// 2. FUNCTION: openModal(id)
// PURPOSE: Nagbubukas ng adoption pop-up modal.
//          Hinahanap ang modal element gamit ang ID at idinaragdag ang 'open' class.
//          Pinipigilan din ang pag-scroll ng background habang bukas ang modal.
// PARAMETER: id — Ang ID ng modal element (hal. "adopt-5" para sa pet ID 5)
// GINAGAMIT SA: index.php, pets.php — kapag pinindot ang "Adopt Me" button
// =============================================================================
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('open');           // Ipakita ang modal
        document.body.style.overflow = 'hidden'; // Pigilan ang pag-scroll ng background
    }
}

// =============================================================================
// 3. FUNCTION: closeModal(id)
// PURPOSE: Nagsasara ng adoption pop-up modal.
//          Inaalis ang 'open' class at pinipigilan ang pag-scroll ng background.
// PARAMETER: id — Ang ID ng modal element na isasara
// GINAGAMIT SA: Modal close button (×) sa index.php at pets.php
// =============================================================================
function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('open');       // Itago ang modal
        document.body.style.overflow = '';    // Ibalik ang normal na scroll
    }
}

// =============================================================================
// 4. OVERLAY CLICK TO CLOSE
// Kapag nag-click ang user sa maitim na background (hindi sa modal mismo),
// awtomatikong nasasara ang modal.
// Ginagamit ang e.target === this para matiyak na ang overlay mismo ang na-click,
// hindi ang content ng modal.
// =============================================================================
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id); // Isara lang kung ang overlay mismo ang na-click
    });
});

// =============================================================================
// 5. ESC KEY TO CLOSE MODAL
// Nagdadagdag ng keyboard support — kapag pinindot ang ESC key,
// lahat ng bukas na modals ay magsasara.
// =============================================================================
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        // Hanapin at isara ang lahat ng bukas na modals
        document.querySelectorAll('.modal-overlay.open').forEach(m => {
            m.classList.remove('open');
            document.body.style.overflow = '';
        });
    }
});

// =============================================================================
// 6. AUTO-DISMISS ALERTS
// Pagkatapos ng 4 segundo (4000ms), awtomatikong nawawala ang lahat ng alerts.
// Gumagamit ng CSS transition para sa maayos na fade-out effect bago alisin
// ang element mula sa DOM.
// GINAGAMIT SA: Success at error alerts sa lahat ng pages
// =============================================================================
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity 0.5s'; // Smooth na fade-out sa loob ng 0.5 segundo
        alert.style.opacity = '0';               // Simulan ang fade-out
        // Pagkatapos ng fade-out, tanggalin na ang element mula sa DOM
        setTimeout(() => alert.remove(), 500);
    });
}, 4000); // 4 segundo bago mag-fade out

// =============================================================================
// 7. FILTER BUTTONS (Client-side)
// Para sa mga filter button na may data-filter at data-group attributes.
// Itinatago o ipinapakita ang mga card batay sa napiling filter.
// TANDAAN: Hindi ito ginagamit sa kasalukuyan sa server-side filtering ng pets.php —
//          ang pets.php ay gumagamit ng URL parameters para i-reload ang page.
// =============================================================================
document.querySelectorAll('.filter-btn[data-filter]').forEach(btn => {
    btn.addEventListener('click', function() {
        const filter = this.dataset.filter;
        const group  = this.dataset.group;

        // I-update ang active state ng filter buttons sa parehong group
        document.querySelectorAll(`.filter-btn[data-group="${group}"]`).forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        // Ipakita o itago ang mga card batay sa selected filter
        document.querySelectorAll('[data-card-filter]').forEach(card => {
            if (filter === 'all' || card.dataset.cardFilter === filter) {
                card.style.display = '';    // Ipakita ang card
            } else {
                card.style.display = 'none'; // Itago ang card
            }
        });
    });
});

// =============================================================================
// 8. IMAGE PREVIEW ON FILE INPUT
// Kapag pumili ng larawan sa file input, nagpapakita ng preview agad
// bago pa man i-upload sa server. Gumagamit ng FileReader API para
// mabasa ang larawan bilang data URL at ipakita bilang <img> tag.
//
// PAANO GAMITIN SA HTML:
//   <input type="file" data-preview="preview-div-id">
//   <div id="preview-div-id"></div>
//
// GINAGAMIT SA: admin/pets.php, admin/gallery.php
// =============================================================================
document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
    input.addEventListener('change', function() {
        const preview = document.getElementById(this.dataset.preview);
        if (preview && this.files[0]) {
            const reader = new FileReader(); // Lilikha ng FileReader object para mabasa ang file
            reader.onload = e => {
                // Kapag natapos na ang pagbabasa, ipakita ang larawan sa preview div
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            };
            reader.readAsDataURL(this.files[0]); // Basahin ang file bilang data URL (base64)
        }
    });
});
