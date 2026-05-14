<?php
/**
 * Forgot Password Page
 * User enters their UPSA email address to receive a reset link.
 */

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/EmailService.php';
require_once __DIR__ . '/models/User.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect(app_url(getDashboardPath($_SESSION['user_role'] ?? 'student')));
}

$message   = '';
$msgType   = '';
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Session expired. Please refresh and try again.';
        $msgType = 'error';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));

        // Basic email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $msgType = 'error';
        } elseif (substr(strtolower($email), -16) !== '@upsamail.edu.gh') {
            $message = 'Only UPSA institutional emails (@upsamail.edu.gh) are accepted.';
            $msgType = 'error';
        } else {
            $db        = getDBConnection();
            $userModel = new User($db);
            $user      = $userModel->getUserByDerivedEmail($email);

            // Always show a generic success message to prevent user enumeration
            $submitted = true;
            $message   = "If that email is registered, a reset link has been sent. Check your inbox (and spam folder).";
            $msgType   = 'success';

            if ($user) {
                $token    = $userModel->createResetToken($user['user_id']);
                if ($token) {
                    // Build absolute reset URL
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host     = $_SERVER['HTTP_HOST'];
                    $resetUrl = $protocol . '://' . $host . BASE_URL . '/reset_password.php?token=' . urlencode($token);
                    EmailService::sendPasswordReset($email, $user['full_name'], $resetUrl);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password – UPSA CIRS</title>
    <meta name="description" content="Reset your UPSA Campus Incident Reporting System password via your institutional email.">
    <link rel="stylesheet" href="assets/css/style.css?v=19">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Forgot-password specific overrides ── */
        .fp-hint {
            font-size: 0.82rem;
            color: var(--text-muted, #94a3b8);
            margin-top: 0.35rem;
            line-height: 1.4;
        }

        .fp-examples {
            background: var(--card-bg, rgba(255,255,255,.06));
            border: 1px solid var(--border-color, rgba(255,255,255,.12));
            border-radius: 10px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.4rem;
            font-size: 0.82rem;
            color: var(--text-muted, #94a3b8);
        }
        .fp-examples span {
            display: block;
            margin-bottom: 0.3rem;
            font-family: 'Courier New', monospace;
            color: var(--accent, #6366f1);
        }
        .fp-examples span:last-child { margin-bottom: 0; }

        .fp-back {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            justify-content: center;
            margin-top: 1.4rem;
            font-size: 0.9rem;
            color: var(--text-muted, #94a3b8);
            text-decoration: none;
            transition: color .2s;
        }
        .fp-back:hover { color: var(--accent, #6366f1); }
        .success-state {
            text-align: center;
            padding: 1.5rem 0 0.5rem;
        }
        .success-state .success-icon {
            font-size: 3.5rem;
            color: #10b981;
            margin-bottom: 1rem;
            animation: popIn .5s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes popIn {
            0%   { transform: scale(0); opacity: 0; }
            70%  { transform: scale(1.15); }
            100% { transform: scale(1);   opacity: 1; }
        }
        .success-state h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: .6rem;
            color: var(--text-primary, #f1f5f9);
        }
        .success-state p {
            font-size: 0.9rem;
            color: var(--text-muted, #94a3b8);
            line-height: 1.55;
        }
    </style>
</head>
<body>
    <!-- Splash Screen -->
    <div id="splash-screen">
        <div class="splash-icon"><i class="fas fa-shield-alt"></i></div>
        <h1>CampusSafe</h1>
        <p class="splash-subtitle">UPSA Incident Reporting System</p>
    </div>

    <div class="auth-bg-pattern"></div>

    <div class="login-container">
        <div class="auth-box hidden-initially">

            <!-- Logo -->
            <div class="auth-logo">
                <div class="auth-logo-icon" style="background:transparent;padding:2px;">
                    <img src="<?php echo htmlspecialchars(app_url('pictures/logo.jpg')); ?>" style="width:44px;height:44px;object-fit:contain;border-radius:8px;" alt="UPSA Logo">
                </div>
                <div class="auth-logo-text">
                    <h1>UPSA CIRS</h1>
                    <p>Incident Reporting System</p>
                </div>
            </div>

            <?php if ($submitted): ?>
                <!-- Success state -->
                <div class="success-state">
                    <div class="success-icon"><i class="fas fa-envelope-circle-check"></i></div>
                    <h3>Check Your Email</h3>
                    <p><?php echo htmlspecialchars($message); ?></p>
                    <p style="margin-top:0.8rem;">The link expires in <strong>1 hour</strong>.</p>
                </div>
            <?php else: ?>
                <h2 class="auth-title">Forgot Password</h2>
                <p class="auth-subtitle">Enter your UPSA institutional email to receive a reset link.</p>

                <?php if ($message): ?>
                    <div class="flash-message flash-<?php echo $msgType === 'error' ? 'error' : 'success'; ?>">
                        <i class="fas fa-<?php echo $msgType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>


                <!-- Email format hint -->
                <div class="fp-examples">
                    <strong style="color:var(--text-primary,#f1f5f9);font-size:0.83rem;">Your UPSA email format:</strong>
                    <span>indexnumber@upsamail.edu.gh</span>
                    <span>lastname.firstname@upsamail.edu.gh</span>
                </div>

                <form method="POST" action="forgot_password.php" id="forgotForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="auth-input-group">
                        <input
                            type="email"
                            class="auth-input"
                            id="email"
                            name="email"
                            placeholder="e.g. lastname.firstname@upsamail.edu.gh"
                            required
                            autofocus
                            autocomplete="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>
                    <p class="fp-hint">
                        <i class="fas fa-info-circle"></i>
                        Students: <em>indexnumber@upsamail.edu.gh</em> &nbsp;·&nbsp; Lecturers &amp; Officers: <em>lastname.firstname@upsamail.edu.gh</em>
                    </p>

                    <button type="submit" class="auth-btn" id="submitBtn">
                        Send Reset Link &nbsp;<i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            <?php endif; ?>

            <a href="index.php" class="fp-back">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <script src="assets/js/main.js?v=19" defer></script>
    <script>
        // Prevent double-submit
        (function () {
            const form = document.getElementById('forgotForm');
            const btn  = document.getElementById('submitBtn');
            if (form && btn) {
                form.addEventListener('submit', function () {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
                });
            }
        })();
    </script>
</body>
</html>
