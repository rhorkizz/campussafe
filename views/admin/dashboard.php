<?php
/**
 * Admin Dashboard
 * Displays all incidents and system statistics
 */

session_start();
require_once __DIR__ . '/../../helpers/functions.php';
requireLogin();
requireRole('admin');

// Get dashboard data
$incidents = [];
$stats = ['total_incidents' => 0, 'pending_incidents' => 0, 'resolved_incidents' => 0, 'in_progress_incidents' => 0];
$admin_name = $_SESSION['user_name'] ?? '';
try {
    require_once __DIR__ . '/../../controllers/AdminController.php';
    $controller = new AdminController();
    $data = $controller->getDashboardData();
    $incidents = $data['incidents'];
    $stats = $data['stats'];
    $admin_name = $data['admin_name'];
} catch (Exception $e) {
    $error_message = $e->getMessage();
    // Set flash message for error
    setFlashMessage($error_message, 'error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIRS - Admin Dashboard</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_url('assets/css/style.css')); ?>?v=19">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            $page_title = 'Admin Dashboard';
            include '../partials/topbar.php'; 
            ?>

            <div class="main-body" style="padding: 2rem; max-width: 1400px; margin: 0 auto;">
                <?php 
                $flash = getFlashMessage();
                if ($flash): ?>
                    <div class="flash-message flash-<?php echo $flash['type']; ?>" style="margin-bottom: 2rem;">
                        <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>" style="margin-right: 12px;"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="error-message" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca;">
                        <i class="fas fa-times-circle" style="margin-right: 12px;"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <?php include '../partials/stats_section.php'; ?>

                <!-- Charts -->
                <?php include '../partials/charts_section.php'; ?>

                <!-- Incidents Section -->
                <section class="incidents-section" style="background: var(--bg-card); padding: 2rem; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--text-main);">All Incidents</h2>
                    </div>

                    <?php include '../partials/filter_bar.php'; ?>

                    <?php if (empty($incidents)): ?>
                        <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 16px; border: 1px dashed var(--border-color);">
                            <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p style="color: var(--text-muted); font-weight: 500;">No incidents reported yet.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="incidents-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category</th>
                                        <th>Student</th>
                                        <th>Location</th>
                                        <th>Title</th>
                                        <th>Officer</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Date Reported</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidents as $incident): ?>
                                        <tr>
                                            <td style="font-weight: 700; color: var(--text-muted);">#<?php echo htmlspecialchars($incident['id']); ?></td>
                                            <td><span style="font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($incident['category_name'] ?? 'N/A'); ?></span></td>
                                            <td style="font-weight: 600;"><?php echo htmlspecialchars($incident['student_name'] ?? 'Anonymous'); ?></td>
                                            <td><span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($incident['location'] ?? 'N/A'); ?></span></td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars(app_url('views/incident_details.php?id=' . (int) $incident['id'])); ?>" style="color:var(--primary); font-weight:600; text-decoration:none; display: block;">
                                                    <?php echo htmlspecialchars($incident['title'] ?? substr($incident['description'], 0, 40) . '...'); ?>
                                                </a>
                                            </td>
                                            <td><span style="font-size: 13px;"><?php echo htmlspecialchars($incident['officer_name'] ?? 'Unassigned'); ?></span></td>
                                            <td>
                                                <span class="status-badge status-<?php echo htmlspecialchars($incident['status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($incident['status']))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="priority-badge priority-<?php echo htmlspecialchars($incident['priority'] ?? 'medium'); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($incident['priority'] ?? 'medium')); ?>
                                                </span>
                                            </td>
                                            <td style="font-size: 12px; color: var(--text-muted);"><?php echo date('M d, H:i', strtotime($incident['created_at'])); ?></td>
                                            <td>
                                                <div style="display: flex; gap: 8px;">
                                                    <a href="<?php echo htmlspecialchars(app_url('views/incident_details.php?id=' . (int) $incident['id'])); ?>" class="btn-small" title="View details" style="padding: 6px 12px; border-radius: 8px; font-size: 12px; background: var(--bg-secondary); color: var(--text-main); text-decoration: none; border: 1px solid var(--border-color);"><i class="fas fa-eye"></i></a>
                                                    <button class="btn-delete" data-incident-id="<?php echo $incident['id']; ?>" title="Delete incident" style="padding: 6px 12px; border-radius: 8px; font-size: 12px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); cursor: pointer;"><i class="fas fa-trash-alt"></i></button>
                                                </div>
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

    <!-- Delete Modal Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-delete');
            const csrfToken = '<?php echo generateCSRFToken(); ?>';
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const incidentId = this.getAttribute('data-incident-id');
                    const row = this.closest('tr');
                    
                    if (confirm('Are you sure you want to delete this incident? This action cannot be undone.')) {
                        this.disabled = true;
                        const icon = this.querySelector('i');
                        const originalClass = icon.className;
                        icon.className = 'fas fa-spinner fa-spin';
                        
                        fetch((window.__CAMPUS_SAFE_BASE__ || '') + '/handlers/delete_incident.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'incident_id=' + incidentId + '&csrf_token=' + encodeURIComponent(csrfToken)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                row.style.transition = 'all 0.4s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(20px)';
                                setTimeout(() => row.remove(), 400);
                            } else {
                                alert('Error: ' + data.message);
                                this.disabled = false;
                                icon.className = originalClass;
                            }
                        })
                        .catch(error => {
                            alert('An error occurred. Please try again.');
                            this.disabled = false;
                            icon.className = originalClass;
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
