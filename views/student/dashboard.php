<?php
/**
 * Student Dashboard
 * Displays student's incident reports and allows reporting new incidents
 */

session_start();
require_once __DIR__ . '/../../helpers/functions.php';
requireLogin();
requireRole('student');

// Get dashboard data
$incidents = [];
$student_name = $_SESSION['user_name'] ?? '';
$student_id = $_SESSION['user_id'] ?? '';
try {
    require_once __DIR__ . '/../../controllers/StudentController.php';
    $controller = new StudentController();
    $data = $controller->getDashboardData();
    $incidents = $data['incidents'];
    $student_name = $data['student_name'];
    $stats = $data['stats'] ?? ['pending_incidents' => 0];
} catch (Exception $e) {
    $error_message = $e->getMessage();
    setFlashMessage($error_message, 'error');
}

$flash = getFlashMessage();
$pending_count = count(array_filter($incidents, function($i) { return $i['status'] === 'submitted' || $i['status'] === 'pending'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPSA Incident - Dashboard</title>
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
            $page_title = 'Student Dashboard';
            include '../partials/topbar.php'; 
            ?>

            <div class="main-body container">
                <?php if ($flash): ?>
                    <div class="flash-message flash-<?php echo $flash['type']; ?>" style="margin-bottom: 2rem;">
                        <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>" style="margin-right: 12px;"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <div class="dashboard-actions" style="margin-bottom: 2.5rem;">
                    <a href="<?php echo htmlspecialchars(app_url('views/student/report_incident.php')); ?>" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Report New Incident
                    </a>
                </div>

                <section class="incidents-section">
                    <div class="section-header">
                        <h2>My Recent Reports</h2>
                    </div>
                    
                    <?php if (empty($incidents)): ?>
                        <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 16px; border: 1px dashed var(--border-color);">
                            <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p style="color: var(--text-muted); font-weight: 500;">You haven't reported any incidents yet.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="incidents-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Photo</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidents as $incident): ?>
                                        <tr>
                                            <td data-label="ID" style="font-weight: 700; color: var(--text-muted);">#<?php echo htmlspecialchars($incident['id'] ?? $incident['incident_id']); ?></td>
                                            <td data-label="Title">
                                                <a href="<?php echo htmlspecialchars(app_url('views/incident_details.php?id=' . (int)($incident['id'] ?? $incident['incident_id']))); ?>" style="color:var(--primary); font-weight:700; text-decoration:none; display: block;">
                                                    <?php echo htmlspecialchars($incident['title'] ?? 'N/A'); ?>
                                                </a>
                                            </td>
                                            <td data-label="Category"><span style="font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($incident['category_name'] ?? 'N/A'); ?></span></td>
                                            <td data-label="Location"><span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($incident['location'] ?? 'N/A'); ?></span></td>
                                            <td data-label="Status">
                                                <span class="status-badge status-<?php echo htmlspecialchars($incident['status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($incident['status']))); ?>
                                                </span>
                                            </td>
                                            <td data-label="Priority">
                                                <span class="priority-badge priority-<?php echo htmlspecialchars($incident['priority'] ?? 'medium'); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($incident['priority'] ?? 'medium')); ?>
                                                </span>
                                            </td>
                                            <td data-label="Photo">
                                                <?php if (!empty($incident['attachment_path'])): ?>
                                                    <a href="#" class="view-attachment" data-src="<?php echo htmlspecialchars(app_url($incident['attachment_path'])); ?>" style="color: var(--primary); font-weight: 700; font-size: 11px; text-decoration: none;">View</a>
                                                <?php else: ?>
                                                    <span style="color: #cbd5e1;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Date" style="font-size: 12px; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($incident['created_at'])); ?></td>
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
</body>
</html>
