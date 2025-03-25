document.addEventListener('DOMContentLoaded', function() {
    // Sync single product
    document.querySelectorAll('.sync-btn').forEach(button => {
        button.addEventListener('click', function() {
            const sku = this.getAttribute('data-sku');
            syncProduct(sku, this.closest('tr'));
        });
    });
    
    // Sync all products
    document.getElementById('syncAll').addEventListener('click', function() {
        syncAllProducts();
    });
});

function syncProduct(sku, row) {
    const statusCell = row.querySelector('td:nth-child(5)');
    const button = row.querySelector('.sync-btn');
    
    // Update UI
    button.disabled = true;
    button.textContent = 'Syncing...';
    statusCell.textContent = 'Pending';
    statusCell.className = 'status-pending';
    
    // Make AJAX request
    fetch('sync.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=sync_single&sku=${encodeURIComponent(sku)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusCell.textContent = 'Success';
            statusCell.className = 'status-success';
            
            // Update last updated time if available
            if (data.last_updated) {
                const date = new Date(data.last_updated);
                row.querySelector('td:nth-child(4)').textContent = 
                    date.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            }
        } else {
            statusCell.textContent = 'Failed';
            statusCell.className = 'status-failed';
        }
    })
    .catch(error => {
        statusCell.textContent = 'Failed';
        statusCell.className = 'status-failed';
        console.error('Error:', error);
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = 'Sync Now';
    });
}

function syncAllProducts() {
    const statusElement = document.getElementById('syncStatus');
    const syncAllBtn = document.getElementById('syncAll');
    
    statusElement.textContent = 'Starting full sync...';
    syncAllBtn.disabled = true;
    
    fetch('sync.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=sync_all'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusElement.textContent = 'Sync completed successfully! Reloading...';
            setTimeout(() => location.reload(), 2000);
        } else {
            statusElement.textContent = 'Sync failed: ' + (data.message || 'Unknown error');
        }
    })
    .catch(error => {
        statusElement.textContent = 'Sync failed: ' + error.message;
    })
    .finally(() => {
        syncAllBtn.disabled = false;
    });
}