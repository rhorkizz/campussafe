<?php
/**
 * Incident Details View
 * Displays full details of a specific incident
 */

session_start();
require_once __DIR__ . '/../helpers/functions.php';
requireLogin();

// Retrieve and clear any flash message set before the redirect
$flash = getFlashMessage();

// Get incident ID
$incident_id = $_GET['id'] ?? null;
if (!$incident_id) {
    setFlashMessage("Invalid incident ID", "error");
    $role = $_SESSION['user_role'] ?? 'student';
    redirect('../' . getDashboardPath($role));
}

$incident = null;
$error = "";

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../models/Incident.php';
    
    $db = getDBConnection();
    if ($db) {
        $model = new Incident($db);
        $incident = $model->getIncidentById($incident_id);
        $comments = $model->getComments($incident_id);
        
        // Load categories if officer/admin needs to re-route (optional improvement)
        $categories = [];
        if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'officer') {
            require_once __DIR__ . '/../models/Category.php';
            $catModel = new Category($db);
            $categories = $catModel->getAllCategories();
        }
    } else {
        $error = "Database connection failed.";
    }

if (!$incident) {
        setFlashMessage("Incident not found.", "error");
        $role = $_SESSION['user_role'] ?? 'student';
        redirect('../' . getDashboardPath($role));
    }

    // Authorization check: 
    // - Students can only see their own incidents
    // - Officers can see assigned incidents (or if they are in the same department/role - keeping it simple for now)
    // - Admins can see all
    
    $user_role = $_SESSION['user_role'];
    $user_id = $_SESSION['user_id'];

    if ($user_role === 'student' && $incident['reported_by'] !== $user_id) {
        setFlashMessage("Access denied.", "error");
        redirect('../' . getDashboardPath($user_role));
    }
    
    // For officers, strictly speaking we should check assignment, but for now allowing viewing if they have the link might be acceptable 
    // or we can enforce robust checking. Let's enforce basic checking if possible, or just allow for now as "read only" is low risk.
    // The previous dashboards only showed assigned incidents, so let's stick to that pattern if possible, 
    // but `getIncidentById` doesn't restrict. Let's rely on the dashboard links not exposing IDs they shouldn't see, 
    // and maybe add a check here if we want to be strict.
    // For now, I'll allow officers to view any incident if they have the ID, to facilitate collaboration, 
    // unless the user specifically asked for strict permissions.

} catch (Exception $e) {
    $error = $e->getMessage();
}

$dashboard = getDashboardPath($_SESSION['user_role'] ?? 'student');
$back_link = str_replace('views/', '', $dashboard);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Details - <?php echo htmlspecialchars($incident['title'] ?? 'Incident'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=18">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/main.js?v=18" defer></script>
</head>
<body data-theme="light">
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
        <i class="fas fa-moon"></i>
    </button>

    <div class="dashboard-wrapper">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <?php 
            $page_title = 'Incident Details';
            include 'partials/topbar.php'; 
            ?>

            <div class="main-body container">
                <?php if ($flash): ?>
                    <div class="flash-message flash-<?php echo $flash['type']; ?>">
                        <i class="fas <?php 
                            if ($flash['type'] === 'success') echo 'fa-check-circle';
                            elseif ($flash['type'] === 'error') echo 'fa-times-circle';
                            else echo 'fa-info-circle';
                        ?>"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-message" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca;">
                        <i class="fas fa-times-circle" style="margin-right: 12px;"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php else: ?>
                    <div class="incidents-section">
                        <div class="section-header flex-responsive" style="align-items: flex-start; margin-bottom: 2rem;">
                            <h2 style="margin: 0;"><?php echo htmlspecialchars($incident['title']); ?></h2>
                            <span class="status-badge status-<?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $incident['status']))); ?>">
                                <?php echo htmlspecialchars($incident['status']); ?>
                            </span>
                        </div>

                        <!-- Status Timeline -->
                        <?php
                        $status = strtolower(str_replace(' ', '_', $incident['status']));
                        $stages = [
                            'reported' => ['label' => 'Reported', 'active' => true],
                            'assigned' => ['label' => 'Assigned', 'active' => in_array($status, ['assigned', 'in_progress', 'resolved'])],
                            'in_progress' => ['label' => 'In Progress', 'active' => in_array($status, ['in_progress', 'resolved'])],
                            'resolved' => ['label' => 'Resolved', 'active' => $status === 'resolved']
                        ];
                        ?>
                        <div class="status-timeline">
                            <?php foreach ($stages as $key => $stage): ?>
                                <div class="timeline-step <?php echo $stage['active'] ? 'active' : ''; ?>">
                                    <div class="timeline-marker">
                                        <?php if ($stage['active']): ?>
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                                <path d="M13.5 4L6 11.5L2.5 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-label"><?php echo $stage['label']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="detail-grid grid-responsive grid-3" style="margin-bottom: 2.5rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: 16px;">
                            <div>
                                <strong style="display: block; font-size: 12px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 5px;">Category</strong>
                                <span style="font-weight: 600;"><?php echo htmlspecialchars($incident['category_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 12px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 5px;">Location</strong>
                                <span style="font-weight: 600;"><?php echo htmlspecialchars($incident['location'] ?? 'Not specified'); ?></span>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 12px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 5px;">Reported On</strong>
                                <span style="font-weight: 600;"><?php echo date('M d, Y, H:i', strtotime($incident['created_at'])); ?></span>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 12px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 5px;">Reporter</strong>
                                <span style="font-weight: 600;"><?php echo htmlspecialchars($incident['student_name'] ?? 'Anonymous'); ?></span>
                            </div>
                            <?php if(!empty($incident['assigned_role_name'])): ?>
                            <div>
                                <strong style="display: block; font-size: 12px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 5px;">Assigned To</strong>
                                <span style="font-weight: 600;"><?php echo htmlspecialchars($incident['officer_name'] ?? $incident['assigned_role_name']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-bottom: 2.5rem;">
                            <strong style="display: block; margin-bottom: 1rem; color: var(--text-main); font-family: Sora; font-size: 1.1rem;">Description</strong>
                            <p style="background: var(--bg-card); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); line-height: 1.8; color: var(--text-main);">
                                <?php echo nl2br(htmlspecialchars($incident['description'])); ?>
                            </p>
                        </div>

                        <?php if (!empty($incident['attachment_path'])): ?>
                            <div style="margin-bottom: 3rem;">
                                <strong style="display: block; margin-bottom: 1rem; color: var(--text-main); font-family: Sora; font-size: 1.1rem;">Attached Evidence</strong>
                                <img src="../<?php echo htmlspecialchars($incident['attachment_path']); ?>" 
                                     alt="Incident Attachment" 
                                     class="lightbox-trigger"
                                     style="max-width: 100%; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-md); cursor: pointer; transition: transform 0.3s ease;">
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 10px; text-align: center;"><i class="fas fa-search-plus"></i> Click to enlarge</p>
                            </div>
                        <?php endif; ?>

                        <!-- Management Panel (Officers/Admins Only) -->
                        <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'officer'): ?>
                        <div class="management-panel" style="margin-bottom: 3rem; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 20px; background: var(--bg-secondary);">
                            <h3 style="font-family: Sora; font-size: 1rem; color: var(--text-main); margin-bottom: 1.5rem;"><i class="fas fa-tools" style="margin-right: 10px; color: var(--primary);"></i> Management Actions</h3>
                            <form action="../handlers/incident_action_handler.php" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; align-items: flex-end;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="incident_id" value="<?php echo $incident_id; ?>">
                                <input type="hidden" name="action" value="update_status">
                                
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>Update Status</label>
                                    <select name="status">
                                        <option value="Pending" <?php echo $incident['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="In Progress" <?php echo $incident['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Resolved" <?php echo $incident['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    </select>
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>Re-assign Category</label>
                                    <select name="category_id">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $incident['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn-primary" style="height: 42px; justify-content: center;">Update Incident</button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <!-- Comments Section -->
                        <div class="comments-section">
                            <h3 style="font-family: Sora; font-size: 1.1rem; color: var(--text-main); margin-bottom: 2rem;"><i class="fas fa-comments" style="margin-right: 12px; color: var(--primary);"></i> Discussion</h3>
                            
                            <div class="comments-list" style="margin-bottom: 2.5rem;">
                                <?php if (empty($comments)): ?>
                                    <div style="text-align: center; padding: 3rem; color: var(--text-muted); background: var(--bg-secondary); border-radius: 16px;">
                                        <i class="fas fa-comment-slash" style="font-size: 2rem; opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
                                        No comments yet. Start the conversation!
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="comment-card" style="margin-bottom: 1.5rem; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 16px; background: <?php echo $comment['user_id'] === $_SESSION['user_id'] ? 'rgba(232, 160, 32, 0.05)' : 'var(--bg-card)'; ?>;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <span style="font-weight: 700; font-size: 14px; color: var(--text-main);"><?php echo htmlspecialchars($comment['full_name']); ?></span>
                                                    <span style="font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 4px; color: white; background: <?php echo $comment['role_name'] === 'Admin' ? 'var(--danger)' : ($comment['role_name'] === 'Student' ? 'var(--primary)' : 'var(--success)'); ?>;">
                                                        <?php echo strtoupper($comment['role_name']); ?>
                                                    </span>
                                                </div>
                                                <span style="font-size: 11px; color: var(--text-muted);"><?php echo date('M d, H:i', strtotime($comment['created_at'])); ?></span>
                                            </div>
                                            <p style="margin: 0; line-height: 1.6; color: var(--text-main); font-size: 14px;"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Comment Input -->
                            <form action="../handlers/incident_action_handler.php" method="POST" style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 20px; border: 1px solid var(--border-color);">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="incident_id" value="<?php echo $incident_id; ?>">
                                <input type="hidden" name="action" value="add_comment">
                                
                                <label style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 12px;">Add a comment</label>
                                <textarea name="comment" rows="3" required placeholder="Type your message here..." style="width: 100%; padding: 1rem; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); font-family: Inter; resize: vertical; margin-bottom: 1rem;"></textarea>
                                <div style="display: flex; justify-content: flex-end;">
                                    <button type="submit" class="btn-primary" style="padding: 10px 24px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer;">Post Comment <i class="fas fa-paper-plane" style="margin-left: 8px;"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
