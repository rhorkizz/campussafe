<?php
/**
 * Change Password Page
 * Forces user to change password on first login or when required
 */

session_start();
require_once __DIR__ . '/../helpers/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect(app_url('index.php'));
}

// Check if there is a flash message
$flash = getFlashMessage();
$error = '';
$success = '';
if ($flash) {
    if ($flash['type'] === 'error') {
        $error = $flash['message'];
    } else {
        $success = $flash['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIRS - Change Password</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_url('assets/css/style.css')); ?>?v=19">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .change-password-container {
            max-width: 480px;
            width: 90%;
            padding: 3rem;
            background: var(--bg-card);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        .cp-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .cp-header .logo {
            font-family: 'Sora', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            display: inline-block;
        }
        .cp-header h2 {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.75rem;
            font-size: 1.5rem;
        }
        .cp-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-muted);
        }
        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }
    </style>
</head>
<body>

    <div class="change-password-container">
        <div class="cp-header">
            <div class="logo">CIRS</div>
            <h2>Secure Your Account</h2>
            <p>Please update your password to continue using the Campus Incident Reporting System.</p>
        </div>

        <?php if ($error): ?>
            <div class="flash-message flash-error" style="margin-bottom: 2rem; border-radius: 12px;">
                <i class="fas fa-exclamation-circle" style="margin-right: 12px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash-message flash-success" style="margin-bottom: 2rem; border-radius: 12px;">
                <i class="fas fa-check-circle" style="margin-right: 12px;"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars(app_url('handlers/change_password_handler.php')); ?>" method="POST">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="At least 6 characters">
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Repeat your new password">
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-shield-alt"></i> Update Password
            </button>
        </form>
    </div>

</body>
</html>
