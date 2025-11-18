document.addEventListener('DOMContentLoaded', function() {
    // Load saved layout BEFORE initializing charts
    loadLayout();
    
    // Initialize Sortable
    let sortable = new Sortable(document.getElementById('draggable-dashboard'), {
        animation: 150,
        ghostClass: 'blue-background-class',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        handle: '.drag-handle',
        onEnd: function(evt) {
            // Save layout to localStorage
            saveLayout();
            // Show toast notification
            showToast('Dashboard layout updated!', 'success');
        }
    });

    // Check if projectSelect exists before adding listener
    const projectSelect = document.getElementById('projectSelect');
    if (projectSelect) {
        projectSelect.addEventListener('change', function() {
            document.getElementById('projectFilterForm').submit();
        });
    }

    // Save layout function
    function saveLayout() {
        const items = document.querySelectorAll('.chart-item');
        const layout = [];
        items.forEach((item, index) => {
            layout.push({
                chartId: item.getAttribute('data-chart-id'),
                position: index
            });
        });
        localStorage.setItem('dashboardLayout', JSON.stringify(layout));
        console.log('Layout saved:', layout); // Debug log
    }

    // Load layout function
    function loadLayout() {
        const savedLayout = localStorage.getItem('dashboardLayout');
        if (savedLayout) {
            try {
                const layout = JSON.parse(savedLayout);
                const container = document.getElementById('draggable-dashboard');

                // Create a temporary array to hold elements in correct order
                const orderedElements = [];
                
                // Sort layout by position
                layout.sort((a, b) => a.position - b.position);

                // Find each element and add to ordered array
                layout.forEach(item => {
                    const chartElement = document.querySelector(`[data-chart-id="${item.chartId}"]`);
                    if (chartElement) {
                        orderedElements.push(chartElement);
                    }
                });

                // Reorder DOM elements
                orderedElements.forEach(element => {
                    container.appendChild(element);
                });
                
                console.log('Layout loaded:', layout); // Debug log
            } catch (error) {
                console.error('Error loading layout:', error);
                localStorage.removeItem('dashboardLayout');
            }
        }
    }

    // Reset layout function
    function resetLayout() {
        localStorage.removeItem('dashboardLayout');
        showToast('Layout reset! Reloading page...', 'info');
        setTimeout(() => {
            location.reload();
        }, 500);
    }

    // Toggle drag function
    function toggleDrag() {
        const dashboard = document.getElementById('draggable-dashboard');
        const toggleBtn = document.getElementById('toggleDrag');

        if (sortable.option("disabled")) {
            sortable.option("disabled", false);
            dashboard.classList.remove('drag-disabled');
            toggleBtn.innerHTML = '🔓 Disable Drag';
            showToast('Drag mode enabled', 'info');
        } else {
            sortable.option("disabled", true);
            dashboard.classList.add('drag-disabled');
            toggleBtn.innerHTML = '🔒 Enable Drag';
            showToast('Drag mode disabled', 'info');
        }
    }

    // Toast notification function
    function showToast(message, type = 'success') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(toast);

        // Auto remove after 3 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }

    // Event listeners
    document.getElementById('resetLayout').addEventListener('click', resetLayout);
    document.getElementById('toggleDrag').addEventListener('click', toggleDrag);
});