// Access the global phpData object created in the main PHP file.
const {
  // placesDelivered,
  deliveryStatusOverview,
  monthlyDeliveryTrend,
  // todayUserActivity,
  selectedProject,
  inventoryData,
  stockLevelData,
  inventoryByWarehouse
} = phpData;

// Modern Professional Color Scheme
const statusColors = {
    'Warehouse': 'rgba(59, 130, 246, 0.9)',   // Blue-600
    'Delivered': 'rgba(16, 185, 129, 0.9)',     // Emerald-500
    'Accepted': 'rgba(139, 92, 246, 0.9)',   // Violet-500
    'Pending': 'rgba(245, 158, 11, 0.9)'      // Amber-500
};

const primaryColors = [
    'rgba(59, 130, 246, 0.8)',   // Blue
    'rgba(16, 185, 129, 0.8)',   // Emerald
    'rgba(139, 92, 246, 0.8)',   // Violet
    'rgba(245, 158, 11, 0.8)',   // Amber
    'rgba(99, 102, 241, 0.8)',   // Indigo
    'rgba(14, 165, 233, 0.8)',   // Sky
    'rgba(100, 116, 139, 0.8)',  // Slate
    'rgba(168, 85, 247, 0.8)'    // Purple
];

// Professional color variants
const colorVariants = {
    light: {
        'Warehouse': 'rgba(59, 130, 246, 0.15)',
        'Delivered': 'rgba(16, 185, 129, 0.15)',
        'Accepted': 'rgba(139, 92, 246, 0.15)',
        'Pending': 'rgba(245, 158, 11, 0.15)'
    },
    border: {
        'Warehouse': 'rgba(59, 130, 246, 1)',
        'Delivered': 'rgba(16, 185, 129, 1)',
        'Accepted': 'rgba(139, 92, 246, 1)',
        'Pending': 'rgba(245, 158, 11, 1)'
    }
};

// Group inventory data
const itemGroups = {};
inventoryData.forEach(row => {
  const { item_name, qty, warehouse_name } = row;
  if (!itemGroups[item_name]) itemGroups[item_name] = { total: 0, warehouses: {} };
  itemGroups[item_name].total += Number(qty);
  itemGroups[item_name].warehouses[warehouse_name] = 
      (itemGroups[item_name].warehouses[warehouse_name] || 0) + Number(qty);
});

// Prepare arrays for chart
const labels = Object.keys(itemGroups);
const totals = labels.map(name => itemGroups[name].total);

// to prevent page jump
document.addEventListener('DOMContentLoaded', function() {
    // Add anchor to form submission
    const dateFilterForm = document.getElementById('dateFilterForm');
    if (dateFilterForm) {
        dateFilterForm.addEventListener('submit', function(e) {
            // Add hash to URL to maintain scroll position
            window.location.hash = 'inventory-warehouse';
        });
    }
    
    // Scroll to inventory section if hash exists
    if (window.location.hash === '#inventory-warehouse') {
        const inventorySection = document.querySelector('[data-chart-id="inventory-warehouse"]');
        if (inventorySection) {
            setTimeout(() => {
                inventorySection.scrollIntoView({ behavior: 'smooth' });
                // Remove hash from URL
                history.replaceState(null, null, ' ');
            }, 100);
        }
    }
});

// Document ready function to ensure the DOM is loaded before running scripts
document.addEventListener('DOMContentLoaded', function() {

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

  // 1. Delivery Status Overview (Doughnut)
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
          backgroundColor: deliveryStatusOverview.map(r => statusColors[r.status] || primaryColors[0]),
          borderColor: deliveryStatusOverview.map(r => colorVariants.border[r.status] || primaryColors[0]),
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
      borderColor: statusColors[status] || primaryColors[idx % primaryColors.length],
      backgroundColor: colorVariants.light[status] || primaryColors[idx % primaryColors.length].replace('0.8', '0.2'),
      borderWidth: 3,
      tension: 0.4,
      fill: true,
      pointBackgroundColor: statusColors[status] || primaryColors[idx % primaryColors.length],
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 5,
      pointHoverRadius: 7
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
      document.getElementById('monthlyDeliveryTrendChart'), 'No monthly trend data available');
  }

  // 3. Today's User Activity (Doughnut)
  // if (todayUserActivity.length > 0) {
  //     const activityTypes = [...new Set(todayUserActivity.map(r => r.activity_type))];
  //     const timeLabels = [...new Set(todayUserActivity.map(r => r.time_label))].sort();
      
  //     const activityDetails = {};
  //     todayUserActivity.forEach(r => {
  //         activityDetails[`${r.time_label}-${r.activity_type}`] = r.activity_list?.split('|||') || [];
  //     });
      
  //     const datasets = activityTypes.map((type, i) => {
  //         const color = statusColors[type] || primaryColors[i % primaryColors.length];
  //         const bgColor = colorVariants.light[type] || color.replace('0.8', '0.1');
          
  //         return {
  //             label: type,
  //             data: timeLabels.map(t => todayUserActivity.find(r => r.time_label === t && r.activity_type === type)?.total_activities || 0),
  //             borderColor: colorVariants.border[type] || color,
  //             backgroundColor: bgColor,
  //             borderWidth: 3,
  //             tension: 0.4,
  //             fill: true,
  //             pointBackgroundColor: color,
  //             pointBorderColor: '#ffffff',
  //             pointBorderWidth: 2,
  //             pointRadius: 4,
  //             pointHoverRadius: 6
  //         };
  //     });

  //     new Chart(document.getElementById('todayActivityChart'), {
  //         type: 'line',
  //         data: { labels: timeLabels, datasets },
  //         options: {
  //             responsive: true,
  //             maintainAspectRatio: false,
  //             interaction: { mode: 'nearest', intersect: true },
  //             plugins: {
  //                 legend: { 
  //                   position: 'top',
  //                   labels: {
  //                       usePointStyle: true,
  //                       padding: 15
  //                   }
  //               },
  //                 tooltip: {
  //                     backgroundColor: '#fff',
  //                     titleColor: '#333',
  //                     bodyColor: '#666',
  //                     borderColor: '#ddd',
  //                     borderWidth: 2,
  //                     padding: 12,
  //                     displayColors: true,
  //                     titleFont: { size: 13, weight: 'bold' },
  //                     bodyFont: { size: 12 },
  //                     callbacks: {
  //                         title: ctx => ctx[0].dataset.label + ' Activity',
  //                         label: ctx => {
  //                             const key = `${timeLabels[ctx.dataIndex]}-${ctx.dataset.label}`;
  //                             const activities = activityDetails[key] || [];
  //                             return activities.length > 0 ? activities.slice(0, 5) : `${ctx.parsed.y} Activities at ${ctx.label}`;
  //                         },
  //                         afterLabel: ctx => {
  //                             const key = `${timeLabels[ctx.dataIndex]}-${ctx.dataset.label}`;
  //                             const activities = activityDetails[key] || [];
  //                             return activities.length > 5 ? `... and ${activities.length - 5} more` : null;
  //                         }
  //                     }
  //                 }
  //             },
  //             scales: {
  //                 y: { beginAtZero: true, ticks: { stepSize: 1 } },
  //                 x: { 
  //                     grid: { display: false },
  //                     ticks: { maxRotation: 45, minRotation: 45, autoSkip: true, maxTicksLimit: 20 }
  //                 }
  //             }
  //         }
  //     });
  // } else {
  //     createEmptyChart(document.getElementById('todayActivityChart'), 'No activity today');
  // }

  // 4. Places Delivered (Horizontal Bar) - by schools reached
  // if (placesDelivered.length > 0) {
  //   new Chart(document.getElementById('placesDeliveredChart'), {
  //     type: 'bar',
  //     data: {
  //       labels: placesDelivered.map(r => r.project_name + ' (' + r.region + ')'),
  //       datasets: [{
  //         label: 'Schools per Region',
  //         data: placesDelivered.map(r => r.total_schools),
  //         backgroundColor: placesDelivered.map((_, i) => primaryColors[i % primaryColors.length]),
  //         borderColor: placesDelivered.map((_, i) => primaryColors[i % primaryColors.length].replace('0.8', '1')),
  //         borderWidth: 2,
  //         borderRadius: 4,
  //         borderSkipped: false
  //       }]
  //     },
  //     options: {
  //       indexAxis: 'y',
  //       responsive: true,
  //       maintainAspectRatio: false,
  //       plugins: {
  //               legend: {
  //                   display: false
  //               },
  //               tooltip: {
  //                   callbacks: {
  //                       afterLabel: function(context) {
  //                           const region = placesDelivered[context.dataIndex];
  //                           return `Delivered: ${region.delivered_count} schools`;
  //                       }
  //                   }
  //               }
  //           },
  //           scales: {
  //               x: {
  //                   beginAtZero: true,
  //                   title: {
  //                       display: true,
  //                       text: 'Number of Schools'
  //                   }
  //               },
  //               y: {
  //                   grid: {
  //                       display: false
  //                   },
  //                   ticks: {
  //                       autoSkip: false
  //                   }
  //               }
  //           }
  //       }
  //   });
  // } else {
  //   createEmptyChart(document.getElementById('placesDeliveredChart'), 'No places delivered data available');
  // }

  // 5. Inventory Quantities per Warehouse (Vertical Bar)
  if (inventoryData.length > 0) {
    // Group by item and calculate totals
    const itemTotals = {};
    inventoryData.forEach(item => {
      itemTotals[item.item_name] = (itemTotals[item.item_name] || 0) + parseInt(item.total_qty);
    });

    const labels = Object.keys(itemTotals);
    const totals = Object.values(itemTotals);

    const ctx = document.getElementById('inventoryChart');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Total Quantity',
          data: totals,
          backgroundColor: labels.map((_, i) => primaryColors[i % primaryColors.length]),
          borderColor: labels.map((_, i) => primaryColors[i % primaryColors.length].replace('0.8', '1')),
          borderWidth: 2,
          borderRadius: 4,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          tooltip: {
            backgroundColor: '#fff',
            titleColor: '#333',
            bodyColor: '#666',
            borderColor: '#ddd',
            borderWidth: 2,
            padding: 12,
            titleFont: { size: 13, weight: 'bold' },
            bodyFont: { size: 12 },
            callbacks: {
              label: function(context) {
                return `Total: ${context.parsed.y}`;
              }
            }
          },
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: { display: true, text: 'Quantity' }
          },
          x: {
            title: { display: true, text: 'Item' }
          }
        }
      }
    });
  } else {
    createEmptyChart(document.getElementById('inventoryChart'), 'No inventory data available');
  }

  // Inventory by Warehouse - Separate Charts
  if (phpData.inventoryByWarehouse && phpData.inventoryByWarehouse.length > 0) {
      const container = document.getElementById('warehouseChartsContainer');
      
      // Group items by warehouse
      const warehouseGroups = {};
      phpData.inventoryByWarehouse.forEach(r => {
          if (!warehouseGroups[r.warehouse_name]) {
              warehouseGroups[r.warehouse_name] = [];
          }
          warehouseGroups[r.warehouse_name].push(r);
      });
      
      // Create chart for each warehouse
      Object.keys(warehouseGroups).forEach((warehouseName, index) => {
          const items = warehouseGroups[warehouseName];
          const itemCount = items.length; // COUNT(*) equivalent
          const totalQty = items.reduce((sum, item) => sum + parseInt(item.qty), 0);
          
          const col = document.createElement('div');
          col.className = 'col-lg-4 col-md-6 col-sm-12 mb-3';
          col.innerHTML = `
              <div class="card h-100">
                  <div class="card-header bg-light">
                      <h6 class="mb-1">${warehouseName}</h6>
                      <small class="text-muted">${itemCount} items | Total: ${totalQty} units</small>
                  </div>
                  <div class="card-body">
                      <canvas id="warehouseChart_${index}" height="250"></canvas>
                  </div>
              </div>
          `;
          
          container.appendChild(col);
          
          // Create pie chart for this warehouse
          new Chart(document.getElementById(`warehouseChart_${index}`), {
              type: 'pie',
              data: {
                  labels: items.map(item => item.item_name),
                  datasets: [{
                      data: items.map(item => parseInt(item.qty)),
                      backgroundColor: items.map((_, i) => primaryColors[i % primaryColors.length]),
                      borderColor: items.map((_, i) => primaryColors[i % primaryColors.length].replace('0.8', '1')),
                      borderWidth: 2
                  }]
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                      legend: {
                        display: false
                      },
                      tooltip: {
                          callbacks: {
                              label: function(context) {
                                  const item = items[context.dataIndex];
                                  const percentage = ((item.qty / totalQty) * 100).toFixed(1);
                                  return `${item.item_name}: ${item.qty} ${item.unit} (${percentage}%)`;
                              }
                          }
                      }
                  }
              }
          });
      });
  } else {
      const container = document.getElementById('warehouseChartsContainer');
      container.innerHTML = '<div class="col-12 text-center text-muted py-5"><p>No inventory data available</p></div>';
  }

});
