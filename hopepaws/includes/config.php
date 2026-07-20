<?php
// =============================================================================
// SESSION START (must be first — before any redirect/auth checks)
// =============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * =============================================================================
 * FILE: includes/config.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang "utak" o puso ng buong HopePaws system. Dito nakalagay lahat ng
 *   global na settings, database connection, at helper functions na ginagamit
 *   sa bawat page ng website.
 *
 * MGA NILALAMAN (Contents):
 *   1. Database constants    - Credentials para makakonekta sa MySQL database
 *   2. Site URL builder      - Auto-detect kung saan nakahost ang site
 *   3. getDB()               - Gumagawa ng database connection
 *   4. sanitize()            - Naglilinis ng user input para iwas XSS attacks
 *   5. isAdminLoggedIn()     - Tinitingnan kung may naka-login na admin
 *   6. requireAdmin()        - Nagre-redirect kung hindi admin ang naka-login
 *   7. isUserLoggedIn()      - Tinitingnan kung may naka-login na regular user
 *   8. requireUser()         - Nagre-redirect kung hindi user ang naka-login
 *   9. getPetPlaceholderUrl()- Nagbabalik ng default placeholder image ng pet
 *  10. getPetImageUrl()      - Nagbabalik ng actual o placeholder na larawan ng pet
 *  11. getStatusBadge()      - Gumagawa ng HTML badge (Available/Adopted)
 *  12. timeAgo()             - Kino-convert ang timestamp sa "2 hrs ago" format
 *
 * GINAGAMIT SA (Used in):
 *   Lahat ng PHP files gamit ang: require_once 'includes/config.php';
 * =============================================================================
 */

// =============================================================================
// 1. DATABASE CONFIGURATION CONSTANTS
// Dito idineklara ang mga constants para sa MySQL database connection.
// =============================================================================
define('DB_HOST', 'localhost');   // Pangalan ng server ng database (karaniwan 'localhost')
define('DB_USER', 'root');        // Username ng MySQL (karaniwan 'root' sa local)
define('DB_PASS', '');            // Password ng MySQL (walang password sa local dev)
define('DB_NAME', 'hopepaws');    // Pangalan ng database na gagamitin
define('SITE_NAME', 'HopePaws'); // Pangalan ng website

// =============================================================================
// 2. SITE URL AUTO-BUILDER
// Awtomatikong nalalaman ng code na ito kung saan nakalagay ang website —
// kahit localhost, kahit may subfolder. Hindi na kailangan i-hardcode ang URL.
// =============================================================================
$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

// __FILE__ always points to the physical path of config.php
// dirname(__FILE__)       = .../hopepaws/includes
// dirname(dirname(__FILE__)) = .../hopepaws  <-- project root
$_projectRoot = str_replace('\\', '/', rtrim(dirname(dirname(__FILE__)), '/\\'));

// DOCUMENT_ROOT = the web server's htdocs root (e.g. C:/xampp/htdocs)
$_docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));

// Subtract docroot from project path to get the web-relative URL path
if ($_docRoot !== '' && strpos($_projectRoot, $_docRoot) === 0) {
    $_dir = substr($_projectRoot, strlen($_docRoot));
} else {
    $_dir = '/hopepaws'; // fallback for standard XAMPP setup
}
$_dir = '/' . trim($_dir, '/');

// I-define ang SITE_URL at UPLOAD paths gamit ang nahanap na base directory
define('SITE_URL', $_protocol . '://' . $_host . $_dir);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// =============================================================================
// 3. FUNCTION: getDB()
// PURPOSE: Gumagawa ng koneksyon sa MySQL database.
//          Kapag may error sa koneksyon, magpapakita ng malinaw na mensahe
//          para malaman ng developer kung ano ang problema.
// RETURNS: MySQLi connection object
// GINAGAMIT SA: Halos lahat ng PHP files (index, pets, adopt, admin, etc.)
// =============================================================================
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // Kapag hindi makakonekta, ipakita ang error message na may instruksyon
        die("<div style='font-family:sans-serif;padding:2rem;color:#c0392b;'>
            <h2>⚠️ Database Connection Failed</h2>
            <p>Please import <strong>database.sql</strong> into MySQL and check config in <code>includes/config.php</code></p>
            <pre>" . $conn->connect_error . "</pre>
        </div>");
    }
    // I-set ang character encoding sa utf8mb4 para suportahan ang lahat ng characters
    $conn->set_charset('utf8mb4');
    return $conn;
}

// =============================================================================
// 4. (Session is started at the top of this file — see above)
// =============================================================================

// =============================================================================
// 5. FUNCTION: sanitize($data)
// PURPOSE: Naglilinis ng user input bago gamitin o ipakita sa webpage.
//          Pinoprotektahan ang site laban sa XSS (Cross-Site Scripting) attacks
//          at nagtatanggal ng extra spaces sa simula at dulo ng text.
// PARAMETER: $data - Ang raw na text na galing sa user (form input, etc.)
// RETURNS: Malinis na string na ligtas ipakita sa HTML
// GINAGAMIT SA: Lahat ng pages na nagpapakita ng user-submitted data
// =============================================================================
function sanitize($data) {
    // strip_tags()        - Tinatanggal ang HTML tags (e.g. <script>, <b>)
    // trim()              - Tinatanggal ang extra whitespace sa simula/dulo
    // htmlspecialchars()  - Kino-convert ang special chars tulad ng < > " ' &
    return htmlspecialchars(strip_tags(trim($data)));
}

// =============================================================================
// 6. FUNCTION: isAdminLoggedIn()
// PURPOSE: Tinitingnan kung may admin na currently naka-login sa session.
//          Ginagamit ito bago ipakita ang admin-only na pages o features.
// RETURNS: true kung naka-login ang admin, false kung hindi
// GINAGAMIT SA: header.php, lahat ng admin at public pages
// =============================================================================
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// =============================================================================
// 7. FUNCTION: requireAdmin()
// PURPOSE: "Guardia" para sa admin pages — kung hindi admin ang naka-login,
//          awtomatikong ire-redirect sa admin login page.
//          Ilalagay ito sa simula ng bawat admin PHP file.
// GINAGAMIT SA: admin/index.php, admin/pets.php, admin/requests.php, etc.
// =============================================================================
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        // Kung hindi admin, ibabalik sa login page ng admin
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

// =============================================================================
// 8. FUNCTION: isUserLoggedIn()
// PURPOSE: Tinitingnan kung may regular user na currently naka-login.
//          Ginagamit ito para malaman kung ipapakita ang user-specific na UI.
// RETURNS: true kung naka-login ang user, false kung hindi
// GINAGAMIT SA: header.php, index.php, pets.php, at lahat ng public pages
// =============================================================================
function isUserLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

// =============================================================================
// 9. FUNCTION: requireUser()
// PURPOSE: "Guardia" para sa user-only pages — kung hindi user ang naka-login,
//          awtomatikong ire-redirect sa user login page.
//          Ilalagay ito sa simula ng pages na para lang sa registered users.
// GINAGAMIT SA: user/dashboard.php at iba pang user-restricted pages
// =============================================================================
function requireUser() {
    if (!isUserLoggedIn()) {
        // Kung hindi naka-login, ibabalik sa login page ng user
        header('Location: ' . SITE_URL . '/user/login.php');
        exit;
    }
}

// =============================================================================
// 10. FUNCTION: getPetPlaceholderUrl($petId, $species)
// PURPOSE: Nagbabalik ng default placeholder image URL para sa mga pet
//          na wala pang na-upload na larawan.
//          Ginagamit ang pet ID para consistent ang placeholder per pet.
// PARAMETERS:
//   $petId   - ID ng pet sa database (ginagamit bilang unique seed)
//   $species - Uri ng hayop: 'dog' | 'cat' | 'rabbit' | 'bird' | 'other'
// RETURNS: URL string ng placeholder image (SVG file)
// GINAGAMIT SA: admin/pets.php kapag nagdadagdag ng bagong pet
// =============================================================================
function getPetPlaceholderUrl($petId = 1, $species = 'dog') {
    // Nagbabalik ng default SVG placeholder image na nakalagay sa assets folder
    return SITE_URL . '/assets/default-pet.svg';
}

// =============================================================================
// 11. FUNCTION: getPetImageUrl($filename, $species, $petId)
// PURPOSE: Nagbabalik ng tamang image URL ng isang pet.
//          Kung may uploaded photo ang pet, iyon ang ibabalik.
//          Kung wala, ang default placeholder SVG ang ipapakita.
// PARAMETERS:
//   $filename - Filename ng larawan na nakalagay sa database (e.g. "pet_abc.jpg")
//   $species  - Uri ng hayop para sa placeholder
//   $petId    - ID ng pet para sa unique placeholder
// RETURNS: Full URL string ng larawan o placeholder
// GINAGAMIT SA: index.php, pets.php, user/dashboard.php, admin/pets.php
// =============================================================================
function getPetImageUrl($filename, $species = 'dog', $petId = 1) {
    // Tingnan kung nandoon ang actual na file sa uploads folder
    $path = __DIR__ . '/../uploads/pets/' . $filename;
    if ($filename && file_exists($path)) {
        // Kung mayroon, ibalik ang URL ng uploaded photo
        return UPLOAD_URL . 'pets/' . $filename;
    }
    // Kung wala, ibalik ang default placeholder image
    return SITE_URL . '/assets/default-pet.svg';
}

// =============================================================================
// 12. FUNCTION: getStatusBadge($status)
// PURPOSE: Gumagawa ng HTML badge/label para ipakita ang status ng isang pet.
//          Nagbibigay ng kulay at icon depende sa status (available o adopted).
// PARAMETER: $status - 'available' o 'adopted'
// RETURNS: HTML string ng colored badge na may icon
// GINAGAMIT SA: index.php, pets.php, admin/pets.php, admin/requests.php
// =============================================================================
function getStatusBadge($status) {
    if ($status === 'available') {
        // Berdeng badge para sa mga pet na pwedeng i-adopt
        return '<span class="badge badge-available">🐾 Available</span>';
    } elseif ($status === 'adopted') {
        // Pulang badge para sa mga pet na may bagong pamilya na
        return '<span class="badge badge-adopted">❤️ Adopted</span>';
    }
    // Default badge para sa ibang status values
    return '<span class="badge">' . $status . '</span>';
}

// =============================================================================
// 13. FUNCTION: timeAgo($datetime)
// PURPOSE: Kino-convert ang database timestamp sa human-readable na format.
//          Halimbawa: "2025-01-01 10:00:00" → "3 hrs ago" o "Jan 01, 2025"
//          Mas maganda at madaling basahin kaysa sa raw na timestamp.
// PARAMETER: $datetime - Timestamp string mula sa database
// RETURNS: String tulad ng "just now", "5 min ago", "2 hrs ago", o date
// GINAGAMIT SA: admin/index.php, admin/requests.php, admin/messages.php
// =============================================================================
function timeAgo($datetime) {
    $time = strtotime($datetime); // I-convert ang string sa Unix timestamp
    $diff = time() - $time;       // Kalkulahin kung ilang segundo na ang lumipas

    // Depende sa dami ng oras na lumipas, magbabalik ng naaangkop na format
    if ($diff < 60)     return 'just now';                        // Wala pang isang minuto
    if ($diff < 3600)   return floor($diff/60) . ' min ago';      // Mga minuto
    if ($diff < 86400)  return floor($diff/3600) . ' hrs ago';    // Mga oras
    return date('M d, Y', $time);                                 // Mga araw na lumipas
}
?>
