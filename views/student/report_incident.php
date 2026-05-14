<?php
/**
 * Report Incident Page
 * Form for students to report new incidents
 */

session_start();
require_once __DIR__ . '/../../helpers/functions.php';
requireLogin();
requireRole('student');

$student_name = $_SESSION['user_name'] ?? '';
$student_id   = $_SESSION['user_id']   ?? '';

// Get categories for dropdown
$categories = [];
$error = '';
try {
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../models/Category.php';
    $db = getDBConnection();
    if ($db === null) {
        $error = "Database connection failed. Please ensure the database 'campus_incident_system' exists and MySQL is running.";
    } else {
        $categoryModel = new Category($db);
        $categories = $categoryModel->getAllCategories();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . '/../../controllers/StudentController.php';
        $controller = new StudentController();
        $result = $controller->submitIncident();
        
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
    <title>UPSA SafeReport - Report Incident</title>
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

        <!-- Main Content -->
        <main class="main-content">
            <?php 
            $page_title = 'Report Incident';
            include '../partials/topbar.php'; 
            ?>

            <div class="main-body" style="padding: 2rem; max-width: 1000px; margin: 0 auto;">
                <?php if ($error): ?>
                    <div class="flash-message flash-error" style="margin-bottom: 2rem;">
                        <i class="fas fa-exclamation-circle" style="margin-right: 12px;"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars(app_url('views/student/report_incident.php')); ?>" class="incident-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Section: Incident Details -->
                    <div class="dash-section" style="background: var(--bg-card); padding: 2.5rem; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                        <h2 style="font-family: 'Sora', sans-serif; font-size: 1.1rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 32px;">
                            <i class="fas fa-file-alt" style="margin-right: 12px;"></i> Incident Information
                        </h2>
                        
                        <div class="form-group" style="margin-bottom: 32px;">
                            <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 10px;">Incident Title <span style="color: var(--danger);">*</span></label>
                            <input type="text" id="title" name="title" required placeholder="e.g. Broken streetlight in front of Hall B" style="width: 100%; padding: 1rem; border-radius: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); font-family: 'Inter', sans-serif;">
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-bottom: 32px;" class="report-form-grid-3">
                            <div class="form-group">
                                <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 10px;">Location Area <span style="color: var(--danger);">*</span></label>
                                <select id="location_type" name="location_type" required style="width: 100%; padding: 1rem; border-radius: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main);">
                                    <option value="">-- Select Area --</option>
                                    <option value="Campus">Campus</option>
                                    <option value="Hostel">Hostel</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 10px;">Category <span style="color: var(--danger);">*</span></label>
                                <select id="category_id" name="category_id" required style="width: 100%; padding: 1rem; border-radius: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main);">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 10px;">Priority Level</label>
                                <select name="priority" style="width: 100%; padding: 1rem; border-radius: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main);">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 32px;">
                            <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 10px;">Specific Location <span style="color: var(--danger);">*</span></label>
                            <input type="text" id="specific_location" name="specific_location" required placeholder="e.g. Block A Room 203, Main Gate" style="width: 100%; padding: 1rem; border-radius: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main);">
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 10px;">Description <span style="color: var(--danger);">*</span></label>
                            <textarea id="description" name="description" rows="5" required placeholder="Describe what happened in detail..." style="width: 100%; padding: 1rem; border-radius: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); font-family: 'Inter', sans-serif; resize: vertical;"></textarea>
                        </div>
                    </div>

                    <!-- Section: Additional Details -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;" class="report-form-grid-2">
                        <!-- Section: Evidence -->
                        <div class="dash-section" style="background: var(--bg-card); padding: 2.5rem; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                            <h2 style="font-family: 'Sora', sans-serif; font-size: 1.1rem; color: var(--text-main); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 32px;">
                                <i class="fas fa-camera" style="color: var(--primary); margin-right: 12px;"></i> Evidence
                            </h2>
                            
                            <div class="form-group" style="text-align: center; border: 2px dashed var(--border-color); padding: 40px; border-radius: 16px; background: var(--bg-secondary); cursor: pointer; transition: all 0.3s ease;" onclick="document.getElementById('attachment').click()">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: var(--text-muted); margin-bottom: 16px; opacity: 0.5;"></i>
                                <p style="font-weight: 700; color: var(--text-main); font-size: 0.95rem; margin-bottom: 4px;">Upload Media</p>
                                <p style="font-size: 11px; color: var(--text-muted);">(Max 5MB)</p>
                                <input type="file" id="attachment" name="attachment" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                                <div id="file-name" style="margin-top: 10px; font-weight: 700; color: var(--primary); font-size: 12px; display: none;"></div>
                            </div>
                        </div>

                        <!-- Section: Privacy -->
                        <div class="dash-section" style="background: var(--bg-card); padding: 2.5rem; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                            <h2 style="font-family: 'Sora', sans-serif; font-size: 1.1rem; color: var(--text-main); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 32px;">
                                <i class="fas fa-shield-alt" style="color: var(--primary); margin-right: 12px;"></i> Privacy
                            </h2>
                            
                            <div class="form-group">
                                <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 10px;">Report Anonymously?</label>
                                <select name="is_anonymous" style="width: 100%; padding: 1rem; border-radius: 12px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main);">
                                    <option value="0">No – Show ID</option>
                                    <option value="1">Yes – Hide ID</option>
                                </select>
                            </div>
                            <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; margin-top: 1.5rem;">
                                <i class="fas fa-info-circle" style="margin-right: 6px; color: var(--info);"></i> Anonymous reports are handled with extra confidentiality but may take longer to verify.
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 4rem; flex-wrap: wrap;">
                        <button type="submit" class="btn-primary" style="padding: 1.25rem 3rem; border-radius: 14px; font-weight: 700; font-size: 1rem; border: none; cursor: pointer;">
                            Submit Incident <i class="fas fa-arrow-right" style="margin-left: 10px;"></i>
                        </button>
                        <a href="<?php echo htmlspecialchars(app_url('views/student/dashboard.php')); ?>" class="btn-secondary" style="padding: 1.25rem 2.5rem; border-radius: 14px; font-weight: 600; text-decoration: none; color: var(--text-main); background: var(--bg-secondary); border: 1px solid var(--border-color);">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('attachment').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : '';
            const display = document.getElementById('file-name');
            if (fileName) {
                display.innerText = 'Selected file: ' + fileName;
                display.style.display = 'block';
            } else {
                display.style.display = 'none';
            }
        });
    </script>
</body>
</html>
