/**
 * CampusSafe — Main JavaScript
 * Fixed: mobile sidebar toggle, splash screen, theme init
 */

document.addEventListener('DOMContentLoaded', function () {

    //  1. THEME
    function applyTheme(theme) {
        // Set on both <html> and <body> so CSS vars cascade correctly
        // even if <body data-theme="light"> is hardcoded in the HTML.
        document.documentElement.setAttribute('data-theme', theme);
        document.body.setAttribute('data-theme', theme);
        updateThemeIcon(theme);
    }

    function updateThemeIcon(theme) {
        const btn = document.querySelector('.theme-toggle i');
        if (btn) btn.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    }

    // Apply saved theme immediately (overrides any hardcoded body attribute)
    const savedTheme = localStorage.getItem('cs-theme') || 'light';
    applyTheme(savedTheme);

    window.toggleTheme = function () {
        const current = document.documentElement.getAttribute('data-theme');
        const next    = current === 'light' ? 'dark' : 'light';
        localStorage.setItem('cs-theme', next);
        applyTheme(next);
    };

    //   2. SPLASH SCREEN  
    const splash = document.getElementById('splash-screen');
    if (splash) {
        // inject shield icon if not present
        if (!splash.querySelector('.splash-icon')) {
            const icon = document.createElement('div');
            icon.className = 'splash-icon';
            icon.innerHTML = '<i class="fas fa-shield-alt"></i>';
            splash.insertBefore(icon, splash.firstChild);
        }

        setTimeout(() => {
            splash.classList.add('fade-out');
            const box = document.querySelector('.auth-box, .login-box');
            if (box) {
                box.classList.remove('hidden-initially');
                box.classList.add('fade-in-delayed');
            }
        }, 2200);
    }

    // ─── 3. PAGE LOADER ───────────────────────────────────────
    const isDashboard = document.querySelector('.dashboard-wrapper');
    const skeletons = document.querySelectorAll('.stat-card, .incidents-table tbody tr, .chart-card');
    
    // Apply skeleton immediately on dashboards
    if (isDashboard) {
        skeletons.forEach(s => s.classList.add('skeleton'));
    }

    if (isDashboard && !document.querySelector('.page-loader')) {
        const loader = document.createElement('div');
        loader.className = 'page-loader';
        loader.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loader);

        Promise.all([
            new Promise(r => setTimeout(r, 800)), // Slightly longer for "premium" feel
            new Promise(r => {
                if (document.readyState === 'complete') r();
                else window.addEventListener('load', r);
            })
        ]).then(() => {
            loader.classList.add('hidden');
            
            setTimeout(() => {
                // Remove skeleton and apply reveal animations
                skeletons.forEach(s => s.classList.remove('skeleton'));
                const containers = document.querySelectorAll('.stats-grid, .incidents-table-container, .dashboard-stats, .analysis-grid');
                containers.forEach(c => c.classList.add('reveal-stagger', 'active'));
                
                setTimeout(() => {
                    if (loader.parentNode) loader.remove();
                }, 500);
            }, 200);
        });
    }

    // ─── 4. AUTO-RELOAD (Idle-based Refresh) ──────────────────
    if (isDashboard) {
        let lastActivity = Date.now();
        const reloadInterval = 60000; // 60 seconds

        // Update last activity on any user interaction
        ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => {
            document.addEventListener(evt, () => {
                lastActivity = Date.now();
            }, { passive: true });
        });

        setInterval(() => {
            const timeSinceActivity = Date.now() - lastActivity;
            
            // Only reload if user has been idle for at least the interval
            // AND is not currently focused on an input
            const activeEl = document.activeElement;
            const isTyping = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA');

            if (timeSinceActivity >= reloadInterval && !isTyping) {
                console.log('Idle detected. Auto-reloading for fresh data...');
                window.location.reload();
            }
        }, 10000); // Check every 10 seconds
    }

    // ─── 5. MOBILE SIDEBAR ────────────────────────────────────
    const sidebar  = document.getElementById('sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');

    // Create overlay if not present
    let overlay = document.getElementById('sidebar-overlay');
    if (!overlay && sidebar) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.id = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    function openSidebar() {
        sidebar?.classList.add('open');
        overlay?.classList.add('visible');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('visible');
        document.body.style.overflow = '';
    }

    toggleBtn?.addEventListener('click', () => {
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    overlay?.addEventListener('click', closeSidebar);

    // Close on nav item click (mobile)
    sidebar?.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 900) closeSidebar();
        });
    });

    // Reset on resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) closeSidebar();
    });

    // 6. TABLE ROW ANIMATIONS 
    // Re-trigger animation for dynamically-loaded tables
    document.querySelectorAll('.incidents-table tbody tr').forEach((row, i) => {
        row.style.animationDelay = `${i * 0.04}s`;
    });

    // 7. FORM SUBMIT LOADING STATE 
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function () {
            if (form.checkValidity()) {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:7px;border-top-color:#fff;"></span> Processing...';
                    btn.disabled = true;
                }
            }
        });
    });

    // 8. PASSWORD TOGGLE 
    const pwToggle = document.getElementById('togglePassword');
    const pwInput  = document.getElementById('password');

    if (pwToggle && pwInput) {
        pwToggle.addEventListener('click', () => {
            const isText = pwInput.type === 'text';
            pwInput.type = isText ? 'password' : 'text';
            pwToggle.querySelector('i').className = isText ? 'far fa-eye' : 'far fa-eye-slash';
        });
    }

    //  8. NOTIFICATION DROPDOWN 
    const notifTrigger = document.getElementById('notification-trigger');
    const notifMenu    = document.getElementById('notification-menu');

    if (notifTrigger && notifMenu) {
        notifTrigger.addEventListener('click', e => {
            e.stopPropagation();
            notifMenu.classList.toggle('show');
        });

        document.addEventListener('click', e => {
            if (!notifTrigger.contains(e.target)) {
                notifMenu.classList.remove('show');
            }
        });
    }

    // 9. ATTACHMENT LIGHTBOX 
    document.body.addEventListener('click', e => {
        const trigger = e.target.closest('.view-attachment');
        if (trigger) {
            e.preventDefault();
            const src = trigger.getAttribute('href') !== '#'
                ? trigger.getAttribute('href')
                : trigger.dataset.src;
            if (src) openLightbox(src);
        }
    });

    // 10. ANIMATED COUNTERS 
    document.querySelectorAll('.stat-value').forEach(el => {
        const target = parseInt(el.textContent);
        if (!isNaN(target) && target > 0) {
            el.textContent = '0';
            animateCounter(el, target, 1000);
        }
    });
});

// HELPERS  

function animateCounter(el, target, duration) {
    const step = target / (duration / 16);
    let current = 0;
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = Math.floor(current);
        if (current >= target) clearInterval(timer);
    }, 16);
}

function openLightbox(src) {
    let modal = document.getElementById('imageLightbox');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageLightbox';
        modal.className = 'report-modal';
        modal.innerHTML = `
            <span class="report-modal-close" onclick="closeLightbox()">&times;</span>
            <img class="report-modal-content" id="lightboxImage" alt="Incident attachment">
        `;
        document.body.appendChild(modal);
        modal.addEventListener('click', e => { if (e.target === modal) closeLightbox(); });
    }
    document.getElementById('lightboxImage').src = src;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const modal = document.getElementById('imageLightbox');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}
