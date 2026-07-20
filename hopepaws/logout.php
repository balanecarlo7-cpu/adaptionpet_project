<?php
/*
 * =============================================================================
 * FILE: admin/logout.php
 * =============================================================================
 * LAYUNIN (Purpose):
 *   Ito ang ADMIN LOGOUT handler. Sinisira nito ang session ng admin at
 *   ire-redirect sa main login page.
 *
 * PROSESO:
 *   1. I-load ang config (para ma-access ang SITE_URL constant)
 *   2. Sirain ang session (lahat ng session data ay mabubura)
 *   3. Ire-redirect sa login page
 *
 * GINAGAMIT NG:
 *   Admin na mag-click ng "🚪 Logout" sa sidebar ng admin panel
 * =============================================================================
 */

require_once 'includes/config.php';

// Sirain ang buong session — mabubura ang admin_logged_in, admin_username, atbp.
session_destroy();

// Ibalik ang admin sa main login page
header('location: ' . SITE_URL . '/login.php');
exit;
