<?php
/**
 * POST /auth/logout.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

auth_logout();
header('Location: ' . SITE_URL . '/login.php');
exit;
