<?php
// Determine base path for links based on current file location
$current_path = $_SERVER['PHP_SELF'];
// Get project root by finding everything before /views/ if it exists, or use / if not
$project_root = strpos($current_path, '/views/') !== false ? substr($current_path, 0, strpos($current_path, '/views/') + 1) : '/';

// Calculate relative path to project root
$relative_from_root = ltrim(str_replace($project_root, '', $current_path), '/');
$depth = substr_count($relative_from_root, '/');
$to_root = str_repeat('../', $depth);

// Ensure $to_root isn't empty if we're inside views/
if ($depth === 0 && strpos($current_path, '/views/') !== false) {
    $to_root = '../';
}
// Special case: if we are at root level (index.php), to_root should be empty
if (strpos($current_path, '/views/') === false) {
    $to_root = '';
}

// Path for logo (from views/ is ../pictures/, from views/student/ is ../../pictures/)
$logo_path = $to_root . 'pictures/logo.jpg';

// Path for dashboard (from views/ is student/dashboard.php, from views/student/ is dashboard.php)
$role_folder = $_SESSION['user_role'] === 'admin' ? 'admin/' : ($_SESSION['user_role'] === 'officer' ? 'officer/' : 'student/');
$dashboard_link = $to_root . 'views/' . $role_folder . 'dashboard.php';
$report_link = $to_root . 'views/student/report_incident.php';
$users_link = $to_root . 'views/admin/users.php';
$settings_link = $to_root . 'views/change_password.php';
$logout_link = $to_root . 'logout.php';

// Active state detection
$is_dashboard = basename($current_path) === 'dashboard.php';
$is_report = basename($current_path) === 'report_incident.php';
$is_users = basename($current_path) === 'users.php';
$is_settings = basename($current_path) === 'change_password.php';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?php echo $logo_path; ?>" alt="Logo">
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
        <a href="<?php echo $dashboard_link; ?>" class="nav-item <?php echo $is_dashboard ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if ($_SESSION['user_role'] === 'student'): ?>
            <a href="<?php echo $report_link; ?>" class="nav-item <?php echo $is_report ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Report Incident</span>
            </a>
        <?php endif; ?>

        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <a href="<?php echo $users_link; ?>" class="nav-item <?php echo $is_users ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
        <?php endif; ?>
        
        <div class="sidebar-nav-label">Account</div>
        
        <a href="<?php echo $settings_link; ?>" class="nav-item <?php echo $is_settings ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo $logout_link; ?>" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open'); this.classList.remove('visible')"></div>
