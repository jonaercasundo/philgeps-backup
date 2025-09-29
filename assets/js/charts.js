// Access the global phpData object created in the main PHP file.
const {
  projectsPerYear,
  projectsByAgency,
  amountPerYear,
  projectProgress,
  placesDelivered,
  deliveryStatusOverview,
  monthlyDeliveryTrend,
  topPackageTypes,
  deliveriesPerProject,
  activityLogActions,
  selectedProject
} = phpData;


// Color palettes
const statusColors = {
  'Warehouse': 'rgba(255, 193, 7, 0.8)',
  'Schools': 'rgba(40, 167, 69, 0.8)',
  'Logistics': 'rgba(23, 162, 184, 0.8)',
  'Factory': 'rgba(220, 53, 69, 0.8)'
};

const primaryColors = [
  'rgba(54, 162, 235, 0.8)',
  'rgba(255, 99, 132, 0.8)',
  'rgba(255, 206, 86, 0.8)',
  'rgba(75, 192, 192, 0.8)',
  'rgba(153, 102, 255, 0.8)',
  'rgba(255, 159, 64, 0.8)',
  'rgba(199, 199, 199, 0.8)',
  'rgba(83, 102, 255, 0.8)'
];

// Document ready function to ensure the DOM is loaded before running scripts
document.addEventListener('DOMContentLoaded', function() {

  // Initialize Sortable
  let sortable = new Sortable(document.getElementById('draggable-dashboard'), {
    animation: 150,
    ghostClass: 'sortable-ghost',
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

  // Auto-submit form on select change
  document.getElementById('projectSelect').addEventListener('change', function() {
    document.getElementById('projectFilterForm').submit();
  });

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
  }

  // Load layout function
  function loadLayout() {
    const savedLayout = localStorage.getItem('dashboardLayout');
    if (savedLayout) {
      const layout = JSON.parse(savedLayout);
      const container = document.getElementById('draggable-dashboard');

      // Sort items according to saved layout
      layout.sort((a, b) => a.position - b.position);

      layout.forEach(item => {
        const chartElement = document.querySelector(`[data-chart-id="${item.chartId}"]`);
        if (chartElement) {
          container.appendChild(chartElement);
        }
      });
    }
  }

  // Reset layout function
  function resetLayout() {
    localStorage.removeItem('dashboardLayout');
    location.reload();
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
      toggleBtn.innerHTML = '🔓 Enable Drag';
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

  // Load saved layout on page load
  loadLayout();


  // Function to handle empty data and create a placeholder chart
  function createEmptyChart(ctx, message) {
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['No Data'],
        datasets: [{
          label: message,
          data: [0],
          backgroundColor: 'rgba(128, 128, 128, 0.3)',
          borderColor: 'rgba(128, 128, 128, 0.8)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 1
          }
        }
      }
    });
  }

  // Chart initialization functions
  if (deliveryStatusOverview.length > 0) {
  const totalOverall = deliveryStatusOverview.reduce((sum, r) => sum + r.total, 0);

  new Chart(document.getElementById('deliveryStatusChart'), {
    type: 'doughnut',
    data: {
      labels: deliveryStatusOverview.map(r => 
        `${r.status} (${((r.total / totalOverall) * 100).toFixed(1)}%)`
      ),
      datasets: [{
        data: deliveryStatusOverview.map(r => r.total),
        backgroundColor: deliveryStatusOverview.map(r => statusColors[r.status] || 'rgba(128, 128, 128, 0.8)'),
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
} else {
  createEmptyChart(document.getElementById('deliveryStatusChart'), 'No delivery data available');
}


  // 2. Monthly Delivery Trend (Line)
if (monthlyDeliveryTrend.length > 0) {
  // Extract unique months and statuses
  const months = [...new Set(monthlyDeliveryTrend.map(r => r.month))];
  const statuses = [...new Set(monthlyDeliveryTrend.map(r => r.status))];

  // Build datasets for each status
  const datasets = statuses.map((status, idx) => ({
    label: status,
    data: months.map(month => {
      const row = monthlyDeliveryTrend.find(r => r.month === month && r.status === status);
      return row ? row.total : 0; // 0 if no data for that month+status
    }),
    borderColor: [
      'rgba(54, 162, 235, 1)',   // warehouse
      'rgba(255, 206, 86, 1)',   // accepted
      'rgba(75, 192, 192, 1)'    // delivered
    ][idx % 4], // fallback color rotation
    backgroundColor: 'transparent',
    tension: 0.4,
    fill: false
  }));

  new Chart(document.getElementById('monthlyDeliveryTrendChart'), {
    type: 'line',
    data: {
      labels: months,
      datasets: datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
} else {
  createEmptyChart(
    document.getElementById('monthlyDeliveryTrendChart'),
    'No monthly trend data available'
  );
}


  // 5. Activity Log Actions (Bar)
  if (activityLogActions.length > 0) {
    new Chart(document.getElementById('activityLogActionsChart'), {
      type: 'bar',
      data: {
        labels: activityLogActions.map(r => r.action),
        datasets: [{
          label: 'Count',
          data: activityLogActions.map(r => r.total),
          backgroundColor: 'rgba(153, 102, 255, 0.7)',
          borderColor: 'rgba(153, 102, 255, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  } else {
    createEmptyChart(document.getElementById('activityLogActionsChart'), 'No activity log data available');
  }

  // Places Delivered (Horizontal Bar) - by schools reached
  if (placesDelivered.length > 0) {
    new Chart(document.getElementById('placesDeliveredChart'), {
      type: 'bar',
      data: {
        labels: placesDelivered.map(r => r.project_name + ' (' + r.region + ')'),
        datasets: [{
          label: 'Schools Reached',
          data: placesDelivered.map(r => r.total_schools),
          backgroundColor: 'rgba(0,123,255,0.7)',
          borderColor: 'rgba(0,123,255,1)',
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            beginAtZero: true
          }
        }
      }
    });
  } else {
    createEmptyChart(document.getElementById('placesDeliveredChart'), 'No places delivered data available');
  }


});
