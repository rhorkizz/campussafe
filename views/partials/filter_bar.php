<div class="filter-bar filter-bar-app" style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; align-items: stretch; background: var(--bg-card); padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
    <div style="flex: 1 1 200px; min-width: 0; position: relative;">
        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px;"></i>
        <input type="text" id="searchInput" placeholder="Search incidents by title, location..." 
               style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.8rem; border: 1px solid var(--border-color); border-radius: 12px; font-family: Inter; background: var(--input-bg); color: var(--text-main); font-size: 14px; outline: none; transition: border-color 0.2s;">
    </div>
    
    <select id="categoryFilter" style="padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 12px; font-family: Inter; min-width: 0; flex: 1 1 140px; max-width: 100%; background: var(--input-bg); color: var(--text-main); font-size: 14px; outline: none; cursor: pointer;">
        <option value="">All Categories</option>
        <?php
        $categories = array_unique(array_column($incidents, 'category_name'));
        foreach ($categories as $cat): ?>
            <?php if (!empty($cat)): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>
    
    <select id="statusFilter" style="padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 12px; font-family: Inter; min-width: 0; flex: 1 1 140px; max-width: 100%; background: var(--input-bg); color: var(--text-main); font-size: 14px; outline: none; cursor: pointer;">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="resolved">Resolved</option>
    </select>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.querySelector('.incidents-table tbody');
    
    if (!tableBody) return;
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryValue = categoryFilter.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        
        const rows = tableBody.querySelectorAll('tr:not(#noResultsMessage)');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const categoryText = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
            const statusBadge = row.querySelector('.status-badge');
            const statusText = statusBadge?.textContent.toLowerCase().replace(/\s+/g, '_') || '';
            
            const matchesSearch = rowText.includes(searchTerm);
            const matchesCategory = !categoryValue || categoryText.includes(categoryValue);
            const matchesStatus = !statusValue || statusText.includes(statusValue);
            
            if (matchesSearch && matchesCategory && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        let noResults = document.getElementById('noResultsMessage');
        if (visibleCount === 0) {
            if (!noResults) {
                noResults = document.createElement('tr');
                noResults.id = 'noResultsMessage';
                noResults.innerHTML = `<td colspan="12" style="text-align: center; padding: 4rem 2rem; color: var(--text-muted);">
                    <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.3;"></i>
                    No incidents match your current filters.
                </td>`;
                tableBody.appendChild(noResults);
            }
        } else if (noResults) {
            noResults.remove();
        }
    }
    
    searchInput?.addEventListener('input', filterTable);
    categoryFilter?.addEventListener('change', filterTable);
    statusFilter?.addEventListener('change', filterTable);
});
</script>
