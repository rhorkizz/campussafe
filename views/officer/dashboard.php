<?php
/**
 * Officer Dashboard
 * Displays incidents assigned to the officer
 */

session_start();
require_once __DIR__ . '/../../helpers/functions.php';
requireLogin();
requireRole('officer');

// Get dashboard data
$incidents = [];
$stats = ['total_incidents' => 0, 'pending_incidents' => 0, 'resolved_incidents' => 0, 'in_progress_incidents' => 0];
$officer_name = $_SESSION['user_name'] ?? '';
try {
    require_once __DIR__ . '/../../controllers/OfficerController.php';
    $controller = new OfficerController();
    $data = $controller->getDashboardData();
    $incidents = $data['incidents'];
    $stats = $data['stats'];
    $officer_name = $data['officer_name'];
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
    <title>CIRS - Officer Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=18">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../assets/js/main.js?v=18" defer></script>
</head>
<body data-theme="light">
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
        <i class="fas fa-moon"></i>
    </button>

    <div class="dashboard-wrapper">
        <?php include '../partials/sidebar.php'; ?>

        <main class="main-content">
            <?php 
            $page_title = 'Officer Dashboard';
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
                        <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Assigned Incidents</h2>
                    </div>

                    <?php include '../partials/filter_bar.php'; ?>

                    <?php if (empty($incidents)): ?>
                        <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 16px; border: 1px dashed var(--border-color);">
                            <i class="fas fa-tasks" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p style="color: var(--text-muted); font-weight: 500;">No incidents assigned to you yet.</p>
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
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Photo</th>
                                        <th>Date Reported</th>
                                        <th>Update Status</th>
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
                                                <a href="../incident_details.php?id=<?php echo htmlspecialchars($incident['id']); ?>" style="color:var(--primary); font-weight:600; text-decoration:none; display: block;">
                                                    <?php echo htmlspecialchars($incident['title'] ?? substr($incident['description'], 0, 40) . '...'); ?>
                                                </a>
                                            </td>
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
                                            <td>
                                                <?php if (!empty($incident['attachment_path'])): ?>
                                                    <a href="#" class="view-attachment" data-src="../../<?php echo htmlspecialchars($incident['attachment_path']); ?>" style="color: var(--primary); font-weight: 700; font-size: 12px; text-decoration: none;">View</a>
                                                <?php else: ?>
                                                    <span style="color: #cbd5e1;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size: 12px; color: var(--text-muted);"><?php echo date('M d, H:i', strtotime($incident['created_at'])); ?></td>
                                            <td>
                                                <select class="status-update" data-incident-id="<?php echo $incident['id']; ?>" style="padding: 6px 12px; border-radius: 8px; font-size: 12px; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-main); font-weight: 600; cursor: pointer; outline: none;">
                                                    <option value="pending" <?php echo $incident['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="in_progress" <?php echo $incident['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="resolved" <?php echo $incident['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                </select>
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
        // Handle status updates
        document.querySelectorAll('.status-update').forEach(select => {
            select.addEventListener('change', function() {
                const incidentId = this.getAttribute('data-incident-id');
                const newStatus = this.value;
                
                if (confirm('Update incident status to ' + newStatus.replace('_', ' ') + '?')) {
                    // Using redirect as per existing logic, but UI is now enhanced
                    window.location.href = '../../handlers/update_incident_status.php?id=' + incidentId + '&status=' + newStatus;
                } else {
                    // Reset to original value if cancelled (location reload would handle it, but this is cleaner)
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>
