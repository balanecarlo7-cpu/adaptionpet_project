<?php
// user/login.php — redirects to main login with user role pre-selected
require_once '../includes/config.php';
header('Location: ' . SITE_URL . '/login.php?role=user');
exit;
