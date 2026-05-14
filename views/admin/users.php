<?php
/**
 * Admin User Management
 * Add, remove, and bulk import users (students & officers)
 */

session_start();
require_once __DIR__ . '/../../helpers/functions.php';
requireLogin();
requireRole('admin');

$error = '';
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . '/../../controllers/AdminController.php';
        $controller = new AdminController();

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $result = $controller->addUser();
                    break;
                case 'deactivate':
                    $result = $controller->deactivateUser($_POST['user_id'] ?? '');
                    break;
                case 'bulk_import':
                    $result = $controller->bulkImportUsers();
                    break;
                default:
                    $result = ['success' => false, 'message' => 'Invalid action.'];
            }
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Load page data
try {
    require_once __DIR__ . '/../../controllers/AdminController.php';
    $controller = new AdminController();
    $roleFilter = isset($_GET['role']) ? (int)$_GET['role'] : null;
    $data = $controller->getUsersPageData($roleFilter);
    $users = $data['users'];
    $departments = $data['departments'];
    $roles = $data['roles'];
    $admin_name = $data['admin_name'];
} catch (Exception $e) {
    $users = [];
    $departments = [];
    $roles = [['role_id' => 1, 'role_name' => 'Student'], ['role_id' => 2, 'role_name' => 'Campus Officer'], ['role_id' => 3, 'role_name' => 'Hostel Officer']];
    $admin_name = $_SESSION['user_name'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIRS - Manage Users</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_url('assets/css/style.css')); ?>?v=19">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="<?php echo htmlspecialchars(app_url('assets/js/main.js')); ?>?v=19" defer></script>
</head>
<body data-theme="light">
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
        <i class="fas fa-moon"></i>
    </button>

    <div class="dashboard-wrapper">
        <?php include '../partials/sidebar.php'; ?>

        <main class="main-content">
            <?php 
            $page_title = 'User Management';
            include '../partials/topbar.php'; 
            ?>

            <div class="main-body" style="padding: 2rem; max-width: 1400px; margin: 0 auto;">
                <?php if ($success): ?>
                    <div class="flash-message flash-success" style="margin-bottom: 2rem;">
                        <i class="fas fa-check-circle" style="margin-right: 12px;"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="flash-message flash-error" style="margin-bottom: 2rem;">
                        <i class="fas fa-exclamation-circle" style="margin-right: 12px;"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="user-mgmt-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
                    <div class="action-card" style="background: var(--bg-card); padding: 2rem; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                        <h3 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-user-plus" style="color: var(--primary);"></i> Add Single User
                        </h3>
                        <form method="POST" class="add-user-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="add">
                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 13px; font-weight: 600; color: var(--text-muted);">User ID *</label>
                                    <input type="text" name="user_id" required maxlength="20" placeholder="e.g. UPSA001" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); font-family: Inter;">
                                </div>
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 13px; font-weight: 600; color: var(--text-muted);">Full Name *</label>
                                    <input type="text" name="full_name" required placeholder="Kwame Mensah" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); font-family: Inter;">
                                </div>
                            </div>
                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 13px; font-weight: 600; color: var(--text-muted);">Role *</label>
                                    <select name="role_id" id="role_select" required style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); font-family: Inter; cursor: pointer;">
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?php echo $r['role_id']; ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" id="dept_group">
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 13px; font-weight: 600; color: var(--text-muted);">Department</label>
                                    <select name="department_id" id="dept_select" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); font-family: Inter; cursor: pointer;">
                                        <option value="">— Select —</option>
                                        <?php foreach ($departments as $d): ?>
                                            <option value="<?php echo $d['department_id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-size: 13px; font-weight: 600; color: var(--text-muted);">Initial Password *</label>
                                <input type="text" name="password" required placeholder="e.g. staff123" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); font-family: Inter;">
                            </div>
                            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 0.85rem; border-radius: 12px; font-weight: 700;">
                                <i class="fas fa-plus"></i> Add User
                            </button>
                        </form>
                    </div>

                    <div class="action-card" style="background: var(--bg-card); padding: 2rem; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                        <h3 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-file-import" style="color: var(--info);"></i> Bulk Import (CSV)
                        </h3>
                        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 1rem;">Upload a CSV to add multiple users at once.</p>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="bulk_import">
                            <div class="form-group" style="margin-bottom: 1.5rem; border: 2px dashed var(--border-color); padding: 2rem; border-radius: 16px; text-align: center; background: var(--bg-secondary);">
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required style="display: none;">
                                <label for="csv_file" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                    <span style="font-weight: 600; color: var(--text-main);">Choose CSV file or drag here</span>
                                    <span style="font-size: 11px; color: var(--text-muted);">Max file size: 2MB</span>
                                </label>
                            </div>
                            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 0.85rem; border-radius: 12px; font-weight: 700; background: var(--info); border-color: var(--info);">
                                <i class="fas fa-upload"></i> Import Users
                            </button>
                        </form>
                        <div style="margin-top: 1.5rem; text-align: center;">
                            <a href="<?php echo htmlspecialchars(app_url('assets/sample_users.csv')); ?>" download style="color: var(--primary); font-size: 13px; font-weight: 600; text-decoration: none;">
                                <i class="fas fa-download"></i> Download Sample CSV
                            </a>
                        </div>
                    </div>
                </div>

                <section class="incidents-section" style="background: var(--bg-card); padding: 2rem; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Current Users</h2>
                    </div>

                    <div class="filter-bar" style="display: flex; gap: 0.75rem; margin-bottom: 2rem; flex-wrap: wrap;">
                        <a href="users.php" class="btn-small <?php echo !isset($_GET['role']) ? 'active' : ''; ?>" style="padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 13px; <?php echo !isset($_GET['role']) ? 'background: var(--primary); color: white;' : 'background: var(--bg-secondary); color: var(--text-muted); border: 1px solid var(--border-color);'; ?>">All</a>
                        <?php foreach ($roles as $r): ?>
                            <a href="users.php?role=<?php echo $r['role_id']; ?>" class="btn-small <?php echo (isset($_GET['role']) && (int)$_GET['role'] === $r['role_id']) ? 'active' : ''; ?>" style="padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 13px; <?php echo (isset($_GET['role']) && (int)$_GET['role'] === $r['role_id']) ? 'background: var(--primary); color: white;' : 'background: var(--bg-secondary); color: var(--text-muted); border: 1px solid var(--border-color);'; ?>"><?php echo htmlspecialchars($r['role_name']); ?></a>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($users)): ?>
                        <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 16px; border: 1px dashed var(--border-color);">
                            <i class="fas fa-users-slash" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p style="color: var(--text-muted); font-weight: 500;">No users found.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="incidents-table">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Full Name</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td style="font-weight: 700; color: var(--text-muted);"><?php echo htmlspecialchars($u['user_id']); ?></td>
                                            <td style="font-weight: 600;"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                            <td>
                                                <span style="font-size: 12px; font-weight: 700; background: <?php echo ($u['role_id'] == 1 ? 'rgba(79, 70, 229, 0.1)' : ($u['role_id'] == 4 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)')); ?>; color: <?php echo ($u['role_id'] == 1 ? '#4f46e5' : ($u['role_id'] == 4 ? '#ef4444' : '#10b981')); ?>; padding: 4px 10px; border-radius: 6px;">
                                                    <?php echo htmlspecialchars($u['role_name'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td><span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($u['department_name'] ?? '—'); ?></span></td>
                                            <td>
                                                <?php if ($u['user_id'] !== ($_SESSION['user_id'] ?? '') && ($u['role_id'] ?? 0) != 4): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove <?php echo htmlspecialchars($u['full_name']); ?>? They will no longer be able to log in.');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['user_id']); ?>">
                                                        <button type="submit" class="btn-small" style="padding: 6px 12px; border-radius: 8px; font-size: 12px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); cursor: pointer; font-weight: 600;">
                                                            <i class="fas fa-user-minus"></i> Remove
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: #cbd5e1; font-size: 12px;">Protected</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script>
        var roleSelect = document.getElementById('role_select');
        var deptSelect = document.getElementById('dept_select');
        var deptGroup = document.getElementById('dept_group');
        
        function toggleDept() {
            var v = parseInt(roleSelect.value, 10);
            if (v === 2 || v === 3) {
                deptSelect.required = true;
                deptGroup.style.opacity = '1';
                deptGroup.style.pointerEvents = 'auto';
            } else {
                deptSelect.required = false;
                deptGroup.style.opacity = '0.5';
                deptGroup.style.pointerEvents = 'none';
                deptSelect.value = "";
            }
        }
        
        roleSelect.addEventListener('change', toggleDept);
        toggleDept();

        // Update file name on selection
        document.getElementById('csv_file').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : 'Choose CSV file';
            this.nextElementSibling.querySelector('span:nth-child(2)').textContent = fileName;
        });
    </script>
</body>
</html>
