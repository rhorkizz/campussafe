<header class="main-topbar">
    <div class="topbar-left flex-responsive" style="gap: 16px; flex-direction: row; align-items: center;">
        <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><?php echo $page_title ?? 'Dashboard'; ?></h1>
    </div>
    
    <div class="topbar-right">
        <div class="topbar-actions">
            <div class="topbar-notification" id="notification-trigger">
                <i class="fas fa-bell"></i>
                <?php
                // For admins/officers: count pending + in_progress as "needing attention"
                // For students: count only their own pending
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
                                // Students see only their own pending; officers/admins see pending + in_progress
                                $show = ($s === 'pending' || $s === 'submitted');
                                if ($userRole === 'admin' || $userRole === 'officer') {
                                    $show = $show || ($s === 'in_progress');
                                }
                                if (!$show) continue;
                                $shown++;
                                $incId      = $incident['id'] ?? $incident['incident_id'];
                                $detailUrl  = BASE_URL . '/views/incident_details.php?id=' . (int)$incId;
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
                            <a href="<?php echo BASE_URL . '/' . getDashboardPath($userRole); ?>">
                                View all &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>
