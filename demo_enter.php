<?php
/**
 * Demo entry – enter without database.
 * Sets session as demo user and redirects to the chosen dashboard.
 * ?role=admin|student|officer (default: admin)
 */

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/functions.php';

if (!defined('BYPASS_LOGIN_DEMO') || !BYPASS_LOGIN_DEMO) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$role = isset($_GET['role']) && in_array($_GET['role'], ['admin', 'student', 'officer'], true)
    ? $_GET['role']
    : 'admin';

$_SESSION['user_id'] = 'DEMO';
$_SESSION['user_role'] = $role;
$_SESSION['user_name'] = 'Demo User';

redirect(app_url(getDashboardPath($role)));
