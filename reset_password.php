<?php
/**
 * Reset Password Page
 * User arrives here via the emailed link containing a one-time token.
 */

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/models/User.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . '/' . getDashboardPath($_SESSION['user_role'] ?? 'student'));
}

$db        = getDBConnection();
$userModel = $db ? new User($db) : null;

$token     = trim($_GET['token'] ?? '');
$tokenRow  = null;
$error     = '';
$success   = false;

// ── Validate token ─────────────────────────────────────────────────────────
$invalidToken = false;
if (empty($token)) {
    $error        = 'No reset token provided. Please request a new password reset link.';
    $invalidToken = true;
} elseif (!$userModel) {
    $error        = 'Database unavailable. Please try again later.';
    $invalidToken = true;
} else {
    $tokenRow = $userModel->getValidResetToken($token);
    if (!$tokenRow) {
        $error        = 'This reset link is invalid or has expired. Please request a new one.';
        $invalidToken = true;
    }
}

// ── Handle form submission ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $tokenRow) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh and try again.';
    } else {
        $newPassword  = $_POST['new_password']  ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $error = 'Password must contain at least one letter and one number.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match. Please try again.';
        } else {
            // Re-validate token freshness on POST (prevents replay after tab refresh)
            $tokenRow = $userModel->getValidResetToken($token);
            if (!$tokenRow) {
                $error = 'This reset link is no longer valid. Please request a new one.';
            } else {
                $updated = $userModel->updatePassword($tokenRow['user_id'], $newPassword);
                if ($updated) {
                    $userModel->markTokenUsed($token);
                    $success = true;
                } else {
                    $error = 'Failed to update password. Please try again.';
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
    <title>Reset Password – UPSA CIRS</title>
    <meta name="description" content="Create a new password for your UPSA CIRS account.">
    <link rel="stylesheet" href="assets/css/style.css?v=19">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        /* Password strength bar */
        .strength-bar-wrap {
            height: 4px;
            background: var(--border-color, rgba(255,255,255,.1));
            border-radius: 4px;
            margin: 0.45rem 0 0.2rem;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: width .3s, background .3s;
        }
        .strength-label {
            font-size: 0.75rem;
            color: var(--text-muted, #94a3b8);
            margin-bottom: 0.8rem;
        }

        /* Success / error states */
        .result-state {
            text-align: center;
            padding: 1.5rem 0 0.5rem;
        }
        .result-state .result-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            animation: popIn .5s cubic-bezier(.36,.07,.19,.97) both;
        }
        .result-icon.ok  { color: #10b981; }
        .result-icon.bad { color: #ef4444; }
        @keyframes popIn {
            0%   { transform: scale(0); opacity: 0; }
            70%  { transform: scale(1.15); }
            100% { transform: scale(1);   opacity: 1; }
        }
        .result-state h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: .6rem;
            color: var(--text-primary, #f1f5f9);
        }
        .result-state p {
            font-size: 0.9rem;
            color: var(--text-muted, #94a3b8);
            line-height: 1.55;
        }
        .pw-rules {
            font-size: 0.78rem;
            color: var(--text-muted, #94a3b8);
            margin: 0.2rem 0 1rem;
            list-style: none;
            padding: 0;
        }
        .pw-rules li { margin-bottom: 0.2rem; }
        .pw-rules li.ok  { color: #10b981; }
        .pw-rules li.ok::before  { content: '✓ '; }
        .pw-rules li.bad::before { content: '✗ '; color: #ef4444; }
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

            <?php if ($success): ?>
                <!-- ✅ Success -->
                <div class="result-state">
                    <div class="result-icon ok"><i class="fas fa-circle-check"></i></div>
                    <h3>Password Updated!</h3>
                    <p>Your password has been reset successfully. You can now log in with your new password.</p>
                    <a href="index.php" class="auth-btn" style="display:block;text-align:center;margin-top:1.5rem;text-decoration:none;">
                        Go to Login &nbsp;<i class="fas fa-arrow-right"></i>
                    </a>
                </div>

            <?php elseif ($invalidToken): ?>
                <!-- ❌ Invalid / expired token -->
                <div class="result-state">
                    <div class="result-icon bad"><i class="fas fa-circle-xmark"></i></div>
                    <h3>Link Unavailable</h3>
                    <p><?php echo htmlspecialchars($error); ?></p>
                    <a href="forgot_password.php" class="auth-btn" style="display:block;text-align:center;margin-top:1.5rem;text-decoration:none;">
                        Request New Link &nbsp;<i class="fas fa-envelope"></i>
                    </a>
                </div>

            <?php else: ?>
                <!-- 🔑 Reset form -->
                <h2 class="auth-title">Set New Password</h2>
                <p class="auth-subtitle">Choose a strong password for your CIRS account.</p>

                <?php if ($error): ?>
                    <div class="flash-message flash-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="reset_password.php?token=<?php echo urlencode($token); ?>" id="resetForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="auth-input-group">
                        <input
                            type="password"
                            class="auth-input"
                            id="new_password"
                            name="new_password"
                            placeholder="New password"
                            required
                            autofocus
                            autocomplete="new-password"
                            minlength="8"
                        >
                        <span class="password-toggle" id="toggleNew"><i class="far fa-eye"></i></span>
                    </div>

                    <!-- Strength bar -->
                    <div class="strength-bar-wrap"><div class="strength-bar" id="strengthBar"></div></div>
                    <div class="strength-label" id="strengthLabel">Enter a password</div>

                    <!-- Rules checklist -->
                    <ul class="pw-rules" id="pwRules">
                        <li id="rule-length">At least 8 characters</li>
                        <li id="rule-letter">Contains a letter</li>
                        <li id="rule-number">Contains a number</li>
                    </ul>

                    <div class="auth-input-group">
                        <input
                            type="password"
                            class="auth-input"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm new password"
                            required
                            autocomplete="new-password"
                        >
                        <span class="password-toggle" id="toggleConfirm"><i class="far fa-eye"></i></span>
                    </div>

                    <button type="submit" class="auth-btn" id="submitBtn">
                        Reset Password &nbsp;<i class="fas fa-lock"></i>
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!$success): ?>
                <a href="index.php" class="fp-back">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/main.js?v=19" defer></script>
    <script>
    (function () {
        // Password visibility toggles
        function bindToggle(toggleId, inputId) {
            const btn   = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            if (!btn || !input) return;
            btn.addEventListener('click', function () {
                const isPass = input.type === 'password';
                input.type = isPass ? 'text' : 'password';
                btn.querySelector('i').classList.toggle('fa-eye', !isPass);
                btn.querySelector('i').classList.toggle('fa-eye-slash', isPass);
            });
        }
        bindToggle('toggleNew', 'new_password');
        bindToggle('toggleConfirm', 'confirm_password');

        // Strength meter
        const pwInput   = document.getElementById('new_password');
        const bar       = document.getElementById('strengthBar');
        const label     = document.getElementById('strengthLabel');
        const ruleLen   = document.getElementById('rule-length');
        const ruleLet   = document.getElementById('rule-letter');
        const ruleNum   = document.getElementById('rule-number');

        const levels = [
            { pct: 0,   color: '#ef4444', text: 'Enter a password' },
            { pct: 30,  color: '#ef4444', text: 'Weak' },
            { pct: 55,  color: '#f59e0b', text: 'Fair' },
            { pct: 80,  color: '#10b981', text: 'Strong' },
            { pct: 100, color: '#059669', text: 'Very strong' },
        ];

        function updateRule(el, ok) {
            el.classList.toggle('ok',  ok);
            el.classList.toggle('bad', !ok && el.classList.contains('bad') || pwInput.value.length > 0);
        }

        if (pwInput && bar) {
            pwInput.addEventListener('input', function () {
                const v = this.value;
                let score = 0;
                const hasLen  = v.length >= 8;
                const hasLet  = /[A-Za-z]/.test(v);
                const hasNum  = /[0-9]/.test(v);
                const hasSym  = /[^A-Za-z0-9]/.test(v);
                const hasUpp  = /[A-Z]/.test(v);

                if (v.length === 0) { score = 0; }
                else {
                    if (hasLen)  score++;
                    if (hasLet)  score++;
                    if (hasNum)  score++;
                    if (hasSym)  score++;
                    if (hasUpp)  score++;
                }

                const lvl = levels[Math.min(score, levels.length - 1)];
                bar.style.width     = lvl.pct + '%';
                bar.style.background = lvl.color;
                label.textContent   = v.length === 0 ? '' : lvl.text;
                label.style.color   = lvl.color;

                // Rule indicators
                ruleLen.className = hasLen ? 'ok' : (v.length > 0 ? 'bad' : '');
                ruleLet.className = hasLet ? 'ok' : (v.length > 0 ? 'bad' : '');
                ruleNum.className = hasNum ? 'ok' : (v.length > 0 ? 'bad' : '');
            });
        }

        // Prevent double-submit
        const form = document.getElementById('resetForm');
        const btn  = document.getElementById('submitBtn');
        if (form && btn) {
            form.addEventListener('submit', function () {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating…';
            });
        }
    })();
    </script>
</body>
</html>
