<?php
/**
 * Sidebar — app_url() fixes logo and logout on domain root (e.g. InfinityFree) and in subfolders.
 */
require_once __DIR__ . '/../../helpers/functions.php';

$logo_path = app_url('pictures/logo.jpg');

$role_folder = $_SESSION['user_role'] === 'admin' ? 'admin/' : ($_SESSION['user_role'] === 'officer' ? 'officer/' : 'student/');
$dashboard_link = app_url('views/' . $role_folder . 'dashboard.php');
$report_link = app_url('views/student/report_incident.php');
$users_link = app_url('views/admin/users.php');
$settings_link = app_url('views/change_password.php');
$logout_link = app_url('logout.php');

$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_dashboard = basename($current_path) === 'dashboard.php';
$is_report = basename($current_path) === 'report_incident.php';
$is_users = basename($current_path) === 'users.php';
$is_settings = basename($current_path) === 'change_password.php';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo">
        <span>CIRS</span>
    </div>
    
    <div class="sidebar-user">
        <div class="user-role-badge role-<?php echo strtolower($_SESSION['user_role']); ?>">
            <?php echo strtoupper($_SESSION['user_role']); ?>
        </div>
        <span class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
        <span class="sidebar-user-id"><?php echo htmlspecialchars($_SESSION['user_id'] ?? ''); ?></span>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo htmlspecialchars($dashboard_link); ?>" class="nav-item <?php echo $is_dashboard ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if ($_SESSION['user_role'] === 'student'): ?>
            <a href="<?php echo htmlspecialchars($report_link); ?>" class="nav-item <?php echo $is_report ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Report Incident</span>
            </a>
        <?php endif; ?>

        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <a href="<?php echo htmlspecialchars($users_link); ?>" class="nav-item <?php echo $is_users ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
        <?php endif; ?>
        
        <div class="sidebar-nav-label">Account</div>
        
        <a href="<?php echo htmlspecialchars($settings_link); ?>" class="nav-item <?php echo $is_settings ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo htmlspecialchars($logout_link); ?>" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open'); this.classList.remove('visible')"></div>
