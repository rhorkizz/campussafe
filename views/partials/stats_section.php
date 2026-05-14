<section class="stats-card-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <span style="color: var(--text-muted); font-size: 14px; font-weight: 600;">Total Incidents</span>
            <div style="width: 40px; height: 40px; background: rgba(79, 70, 229, 0.1); color: #4f46e5; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-clipboard-list"></i>
            </div>
        </div>
        <p style="font-size: 28px; font-weight: 800; color: var(--text-main); margin: 0;"><?php echo $stats['total_incidents']; ?></p>
    </div>

    <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <span style="color: var(--text-muted); font-size: 14px; font-weight: 600;">Pending</span>
            <div style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div style="display: flex; align-items: baseline; gap: 10px;">
            <p style="font-size: 28px; font-weight: 800; color: var(--text-main); margin: 0;"><?php echo $stats['pending_incidents']; ?></p>
            <?php if ($stats['pending_incidents'] > 0): ?>
                <span style="background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px;">New</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <span style="color: var(--text-muted); font-size: 14px; font-weight: 600;">In Progress</span>
            <div style="width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
        <p style="font-size: 28px; font-weight: 800; color: var(--text-main); margin: 0;"><?php echo $stats['in_progress_incidents']; ?></p>
    </div>

    <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <span style="color: var(--text-muted); font-size: 14px; font-weight: 600;">Resolved</span>
            <div style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <p style="font-size: 28px; font-weight: 800; color: var(--text-main); margin: 0;"><?php echo $stats['resolved_incidents']; ?></p>
    </div>
</section>
