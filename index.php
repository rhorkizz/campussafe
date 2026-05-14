<?php
/**
 * Login Page
 * Entry point for the CampusSafe application
 */

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    redirect(app_url(getDashboardPath($_SESSION['user_role'])));
}

$demoMode = defined('BYPASS_LOGIN_DEMO') && BYPASS_LOGIN_DEMO;

// Demo mode: no login form, just "Enter without database"
if ($demoMode) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusSafe - Demo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
    <div id="splash-screen">
        <h1 class="logo-animate">CampusSafe</h1>
    </div>

    <div class="login-container">
        <div class="login-box">
            <h1 class="logo-animate">CampusSafe</h1>
            <h2>Incident Reporting System</h2>
            <p style="color:#666;margin-bottom:1.5rem;">Login is disabled. Enter without database to explore the system.</p>
            <a href="demo_enter.php" class="btn-primary" style="display:inline-block;text-align:center;text-decoration:none;padding:12px 24px;">Enter without database (demo)</a>
            <p style="margin-top:1.5rem;font-size:0.9em;color:#888;">View as: <a href="demo_enter.php?role=admin">Admin</a> · <a href="demo_enter.php?role=officer">Officer</a> · <a href="demo_enter.php?role=student">Student</a></p>
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}

// Handle login form submission (when demo bypass is off)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . '/controllers/StudentController.php';
        $controller = new StudentController();
        $result = $controller->login();
        
        if (!$result['success']) {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIRS - Login</title>
    <link rel="stylesheet" href="assets/css/style.css?v=18">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/main.js?v=18" defer></script>
</head>
<body>
    <!-- Splash Screen -->
    <div id="splash-screen">
        <div class="splash-icon"><i class="fas fa-shield-alt"></i></div>
        <h1>CampusSafe</h1>
        <p class="splash-subtitle">UPSA Incident Reporting System</p>
    </div>

    <!-- Auth Background Elements -->
    <div class="auth-bg-pattern"></div>
    
    <div class="login-container">
        <div class="auth-box hidden-initially">
            <div class="auth-logo">
                <div class="auth-logo-icon" style="background:transparent;padding:2px;">
                    <img src="<?php echo htmlspecialchars(app_url('pictures/logo.jpg')); ?>" style="width:44px;height:44px;object-fit:contain;border-radius:8px;" alt="UPSA Logo">
                </div>
                <div class="auth-logo-text">
                    <h1>UPSA CIRS</h1>
                    <p>Incident Reporting System</p>
                </div>
            </div>

            <h2 class="auth-title">Welcome Back</h2>
            <p class="auth-subtitle">Sign in to access the incident portal</p>
            
            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="auth-input-group">
                    <input type="text" class="auth-input" id="user_id" name="user_id" placeholder="SEC-007" required autofocus autocomplete="username">
                </div>
                
                <div class="auth-input-group">
                    <input type="password" class="auth-input" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                    <span class="password-toggle" id="togglePassword">
                        <i class="far fa-eye"></i>
                    </span>
                </div>
                
                <button type="submit" class="auth-btn">Sign In &rarr;</button>
            </form>

            <div style="text-align:center;margin-top:1.2rem;">
                <a href="forgot_password.php" style="font-size:0.88rem;color:var(--text-muted,#94a3b8);text-decoration:none;transition:color .2s;"
                   onmouseover="this.style.color='var(--accent,#6366f1)'"
                   onmouseout="this.style.color='var(--text-muted,#94a3b8)'">
                    <i class="fas fa-key" style="margin-right:0.3rem;"></i>Forgot your password?
                </a>
            </div>
        </div>
    </div>
</body>
</html>
