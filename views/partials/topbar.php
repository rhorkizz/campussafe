<?php
if (!function_exists('app_url')) {
    require_once dirname(__DIR__, 2) . '/helpers/functions.php';
}
if (empty($GLOBALS['_campus_safe_app_base_js'])) {
    $GLOBALS['_campus_safe_app_base_js'] = true;
    echo '<script>window.__CAMPUS_SAFE_BASE__=' . json_encode(app_base()) . ';</script>' . "\n";
}
?>
<header class="main-topbar">
    <div class="topbar-left topbar-left-cluster">
        <button type="button" class="sidebar-toggle" aria-label="Open menu">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><?php echo htmlspecialchars($page_title ?? 'Dashboard'); ?></h1>
    </div>

    <div class="topbar-right">
        <div class="topbar-actions">
            <div class="topbar-notification" id="notification-trigger">
                <i class="fas fa-bell"></i>
                <?php
                $userRole        = $_SESSION['user_role'] ?? 'student';
                $pending_count   = $stats['pending_incidents'] ?? 0;
                $attention_count = ($userRole === 'admin' || $userRole === 'officer')
                    ? ($stats['pending_incidents'] ?? 0) + ($stats['in_progress_incidents'] ?? 0)
                    : $pending_count;
                if ($attention_count > 0):
                ?>
                    <span class="notification-count"><?php echo $attention_count; ?></span>
                <?php endif; ?>

                <div class="notification-dropdown" id="notification-menu">
                    <div class="dropdown-header">
                        <h3>Notifications</h3>
                        <span class="badge"><?php echo $attention_count; ?> Alert<?php echo $attention_count !== 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="dropdown-body">
                        <?php if ($attention_count > 0 && isset($incidents) && count($incidents) > 0): ?>
                            <?php
                            $shown = 0;
                            foreach ($incidents as $incident):
                                if ($shown >= 5) break;
                                $s = strtolower($incident['status'] ?? '');
                                $show = ($s === 'pending' || $s === 'submitted');
                                if ($userRole === 'admin' || $userRole === 'officer') {
                                    $show = $show || ($s === 'in_progress');
                                }
                                if (!$show) continue;
                                $shown++;
                                $incId      = $incident['id'] ?? $incident['incident_id'];
                                $detailUrl  = app_url('views/incident_details.php?id=' . (int) $incId);
                                $iconClass  = ($s === 'in_progress') ? 'fa-clock text-warning' : 'fa-exclamation-circle';
                            ?>
                                <a href="<?php echo htmlspecialchars($detailUrl); ?>" class="notification-item">
                                    <div class="item-icon"><i class="fas <?php echo $iconClass; ?>"></i></div>
                                    <div class="item-content">
                                        <p class="item-title"><?php echo htmlspecialchars($incident['title'] ?? 'Incident #' . $incId); ?></p>
                                        <span class="item-time">
                                            <span class="status-badge status-<?php echo htmlspecialchars($s); ?>" style="font-size:10px;padding:2px 6px;">
                                                <?php echo ucfirst(str_replace('_', ' ', $s)); ?>
                                            </span>
                                            &nbsp;<?php echo date('M d, H:i', strtotime($incident['created_at'])); ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <?php if ($shown === 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-double"></i>
                                    <p>All caught up!</p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-double"></i>
                                <p>All caught up!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($attention_count > 0): ?>
                        <div class="dropdown-footer">
                            <a href="<?php echo htmlspecialchars(app_url(getDashboardPath($userRole))); ?>">
                                View all &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>
