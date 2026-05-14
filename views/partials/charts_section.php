<section class="charts-section" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); min-height: 350px;">
        <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-chart-pie" style="color: #4f46e5;"></i> Incidents by Category
        </h3>
        <div style="height: 250px; position: relative;">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    <div class="stat-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); min-height: 350px;">
        <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-chart-bar" style="color: #10b981;"></i> Incidents by Status
        </h3>
        <div style="height: 250px; position: relative;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryData = <?php echo json_encode($stats['by_category'] ?? []); ?>;
    const statusData = <?php echo json_encode($stats['by_status'] ?? []); ?>;

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { family: 'Inter', size: 11, weight: '500' },
                    padding: 15,
                    usePointStyle: true,
                    color: getComputedStyle(document.body).getPropertyValue('--text-muted').trim()
                }
            }
        },
        animation: { duration: 1500, easing: 'easeOutQuart' }
    };

    // Category Chart
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(categoryData),
            datasets: [{
                data: Object.values(categoryData),
                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#06b6d4', '#ec4899'],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: { ...chartOptions, cutout: '70%' }
    });

    // Status Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                label: 'Incidents',
                data: Object.values(statusData),
                backgroundColor: '#4f46e5',
                borderRadius: 8,
                barThickness: 30
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#e2e8f0' } },
                x: { grid: { display: false } }
            },
            plugins: { ...chartOptions.plugins, legend: { display: false } }
        }
    });
});
</script>
