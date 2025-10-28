// Access the global phpData object created in the main PHP file.
const {
    progressPerRegion,
    progressPerLot,

    inventoryByWarehouse,
    inventoryData,
    selectedProject
} = phpData;

// Delivery Status Colors (Green-Yellow-Red)
const deliveryStatusColors = {
    'Delivered': '#198754',   // Green
    'Accepted': '#fbc02d',    // Yellow
    'Pending': '#dc3545',     // Red
    'Cancelled': '#b02a37',   // Darker Red
    'Not': '#a52834'          // Even Darker Red
};

// Project Status Colors (Green-Yellow-Red)  
const projectStatusColors = {
    'Pending Evaluation': '#dc3545',     // Red
    'For Award': '#fbc02d',              // Yellow  
    'For Implementation': '#e6b422',     // Darker Yellow
    'Ongoing': '#d4a81e',                // Even Darker Yellow
    'Delivered': '#198754',              // Green
    'Completed': '#157347'               // Darker Green
};

const primaryColors = [
    '#198754',   // Green
    '#157347',   // Darker Green
    '#fbc02d',   // Yellow  
    '#e6b422',   // Darker Yellow
    '#dc3545',   // Red
    '#b02a37',   // Darker Red
    '#22c55e',   // Bright Green
    '#facc15'    // Bright Yellow
];

// Color variants
const colorVariants = {
    light: {
        'Delivered': 'rgba(25, 135, 84, 0.15)',
        'Accepted': 'rgba(251, 192, 45, 0.15)',
        'Pending': 'rgba(220, 53, 69, 0.15)',
        'Cancelled': 'rgba(176, 42, 55, 0.15)',
        'Pending Evaluation': 'rgba(220, 53, 69, 0.15)',
        'For Award': 'rgba(251, 192, 45, 0.15)',
        'For Implementation': 'rgba(230, 180, 34, 0.15)',
        'Ongoing': 'rgba(212, 168, 30, 0.15)',
        'Completed': 'rgba(21, 115, 71, 0.15)'
    },
    border: {
        'Delivered': '#198754',
        'Accepted': '#fbc02d',
        'Pending': '#dc3545',
        'Cancelled': '#b02a37',
        'Pending Evaluation': '#dc3545',
        'For Award': '#fbc02d',
        'For Implementation': '#e6b422',
        'Ongoing': '#d4a81e',
        'Completed': '#157347'
    }
};

// Function to get color based on quantity 
function getQuantityColor(quantity, maxQuantity) {
    if (maxQuantity === 0) return deliveryStatusColors.Pending; // Red for zero
    
    const percentage = (quantity / maxQuantity) * 100;
    
    if (percentage <= 25) {
        // Red (0-25%)
        return deliveryStatusColors.Pending;
    } else if (percentage <= 50) {
        // Orange/Yellow (26-50%)
        return projectStatusColors['For Implementation'];
    } else if (percentage <= 75) {
        // Yellow (51-75%)
        return deliveryStatusColors.Accepted;
    } else {
        // Green (76-100%)
        return deliveryStatusColors.Delivered;
    }
}

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
                legend: { display: false }
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

// Document ready function to ensure the DOM is loaded before running scripts
document.addEventListener('DOMContentLoaded', function() {
 // Inventory by Warehouse - Separate Charts
    if (inventoryByWarehouse && inventoryByWarehouse.length > 0) {
        const container = document.getElementById('warehouseChartsContainer');
        
        // Group items by warehouse
        const warehouseGroups = {};
        inventoryByWarehouse.forEach(r => {
            if (!warehouseGroups[r.warehouse_name]) {
                warehouseGroups[r.warehouse_name] = [];
            }
            warehouseGroups[r.warehouse_name].push(r);
        });
        
        container.innerHTML = ''; // clear before generating

        let row = null;
        const warehouseNames = Object.keys(warehouseGroups);

        warehouseNames.forEach((warehouseName, index) => {
            const items = warehouseGroups[warehouseName];
            const itemCount = items.length;
            const totalQty = items.reduce((sum, item) => sum + parseInt(item.qty), 0);

            // Sort items by quantity (descending)
            items.sort((a, b) => parseInt(b.qty) - parseInt(a.qty));
            
            // Find max quantity for this warehouse for color scaling
            const maxQuantity = Math.max(...items.map(item => parseInt(item.qty)));

            // Create a new row for every 2 cards
            if (index % 2 === 0) {
                row = document.createElement('div');
                row.className = 'row';
                container.appendChild(row);
            }

            const col = document.createElement('div');
            col.className = 'col-lg-6 col-md-6 mb-3';
            col.innerHTML = `
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-1">${warehouseName}</h6>
                        <small class="text-muted">${itemCount} items | Total: ${totalQty} units</small>
                    </div>
                    <div class="card-body" style="height: 400px; overflow-y: auto;">
                        <canvas id="warehouseChart_${index}" width="600" height="${Math.max(400, items.length * 20)}"></canvas>
                    </div>
                </div>
            `;

            row.appendChild(col);

            // Initialize chart with quantity-based colors using existing variables
            new Chart(document.getElementById(`warehouseChart_${index}`), {
                type: 'bar',
                data: {
                    labels: items.map(item => item.item_name),
                    datasets: [{
                        label: 'Quantity',
                        data: items.map(item => parseInt(item.qty)),
                        backgroundColor: items.map(item => getQuantityColor(parseInt(item.qty), maxQuantity)),
                        borderColor: items.map(item => getQuantityColor(parseInt(item.qty), maxQuantity)),
                        borderWidth: 1.5,
                        borderRadius: 4,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: false,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
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
                                    const item = items[context.dataIndex];
                                    const percentage = totalQty > 0 ? ((item.qty / totalQty) * 100).toFixed(1) : 0;
                                    return `${parseInt(item.qty).toLocaleString()} ${item.unit} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: { display: true, text: 'Quantity' },
                            grid: { color: '#eee' },
                            ticks: { precision: 0 }
                        },
                        y: {
                            title: { display: false },
                            grid: { display: false },
                            ticks: {
                                autoSkip: false,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        });

        // ADD INVENTORY QUANTITY CHART AT THE END
        const chartIndex = warehouseNames.length;
        
        // Create a new row if the last warehouse chart was the 2nd in a row (even index)
        if (chartIndex % 2 === 0) {
            row = document.createElement('div');
            row.className = 'row';
            container.appendChild(row);
        }

        // Group by item and calculate totals for overall inventory
        const itemTotals = {};
        inventoryByWarehouse.forEach(item => {
            itemTotals[item.item_name] = (itemTotals[item.item_name] || 0) + parseInt(item.qty);
        });

        const labels = Object.keys(itemTotals);
        const totals = Object.values(itemTotals);
        
        // Find max quantity for overall inventory for color scaling
        const maxOverallQuantity = Math.max(...totals);

        const col = document.createElement('div');
        col.className = 'col-lg-6 col-md-6 mb-3';
        col.innerHTML = `
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-1">📦 Overall Inventory Quantity</h6>
                    <small class="text-muted">${labels.length} items total</small>
                </div>
                <div class="card-body" style="height: 400px; overflow-y: auto;">
                    <canvas id="overallInventoryChart" width="600" height="${Math.max(400, labels.length * 20)}"></canvas>
                </div>
            </div>
        `;

        row.appendChild(col);

        // Initialize overall inventory chart with quantity-based colors using existing variables
        new Chart(document.getElementById('overallInventoryChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Quantity',
                    data: totals,
                    backgroundColor: totals.map(quantity => getQuantityColor(quantity, maxOverallQuantity)),
                    borderColor: totals.map(quantity => getQuantityColor(quantity, maxOverallQuantity)),
                    borderWidth: 1.5,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: false,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Total: ${context.parsed.x.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: { display: true, text: 'Total Quantity' },
                        grid: { color: '#eee' },
                        ticks: { precision: 0 }
                    },
                    y: {
                        title: { display: false },
                        grid: { display: false },
                        ticks: {
                            autoSkip: false,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });

    } else {
        const container = document.getElementById('warehouseChartsContainer');
        container.innerHTML = '<div class="col-12 text-center text-muted py-5"><p>No inventory data available</p></div>';
    }

    // Progress by Region - Accepted Percentage
    if (phpData.progressPerRegion && phpData.progressPerRegion.length > 0) {
        const regions = phpData.progressPerRegion.map(r => r.region);
        const acceptedData = phpData.progressPerRegion.map(r => {
            const total = r.total || 1;
            return Math.round((r.accepted / total) * 100);
        });
        const notAcceptedData = phpData.progressPerRegion.map(r => {
            const total = r.total || 1;
            return 100 - Math.round((r.accepted / total) * 100);
        });

        new Chart(document.getElementById('acceptedPerRegionChart'), {
            type: 'bar',
            data: {
                labels: regions,
                datasets: [
                    {
                        label: 'Accepted',
                        data: acceptedData,
                        backgroundColor: deliveryStatusColors.Accepted,
                        borderColor: colorVariants.border.Accepted,
                        borderWidth: 1
                    },
                    {
                        label: 'Not Accepted',
                        data: notAcceptedData,
                        backgroundColor: deliveryStatusColors.Not,
                        borderColor: colorVariants.border.Not,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Regions'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const regionData = phpData.progressPerRegion[context.dataIndex];
                                if (context.dataset.label === 'Accepted') {
                                    return `Accepted: ${regionData.accepted}/${regionData.total} (${context.parsed.y}%)`;
                                } else {
                                    return `Not Accepted: ${regionData.total - regionData.accepted}/${regionData.total} (${context.parsed.y}%)`;
                                }
                            }
                        }
                    }
                }
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const data = dataset.data[index];
                            if (data > 0) {
                                ctx.fillStyle = '#fff';
                                ctx.font = 'bold 12px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(data + '%', bar.x, bar.y + (bar.height / 2));
                            }
                        });
                    });
                }
            }]
        });
    } else {
        createEmptyChart(document.getElementById('acceptedPerRegionChart'), 'No region data available');
    }

    // Progress by Region - Delivered Percentage
    if (phpData.progressPerRegion && phpData.progressPerRegion.length > 0) {
        const regions = phpData.progressPerRegion.map(r => r.region);
        const deliveredData = phpData.progressPerRegion.map(r => {
            const total = r.total || 1;
            return Math.round((r.delivered / total) * 100);
        });
        const notDeliveredData = phpData.progressPerRegion.map(r => {
            const total = r.total || 1;
            return 100 - Math.round((r.delivered / total) * 100);
        });

        new Chart(document.getElementById('deliveredPerRegionChart'), {
            type: 'bar',
            data: {
                labels: regions,
                datasets: [
                    {
                        label: 'Delivered',
                        data: deliveredData,
                        backgroundColor: deliveryStatusColors.Delivered,
                        borderColor: colorVariants.border.Delivered,
                        borderWidth: 1
                    },
                    {
                        label: 'Not Delivered',
                        data: notDeliveredData,
                        backgroundColor: deliveryStatusColors.Not,
                        borderColor: colorVariants.border.Not,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Regions'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const regionData = phpData.progressPerRegion[context.dataIndex];
                                if (context.dataset.label === 'Delivered') {
                                    return `Delivered: ${regionData.delivered}/${regionData.total} (${context.parsed.y}%)`;
                                } else {
                                    return `Not Delivered: ${regionData.total - regionData.delivered}/${regionData.total} (${context.parsed.y}%)`;
                                }
                            }
                        }
                    }
                }
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const data = dataset.data[index];
                            if (data > 0) {
                                ctx.fillStyle = '#fff';
                                ctx.font = 'bold 12px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(data + '%', bar.x, bar.y + (bar.height / 2));
                            }
                        });
                    });
                }
            }]
        });
    } else {
        createEmptyChart(document.getElementById('deliveredPerRegionChart'), 'No region data available');
    }

    // Progress by Lot - Accepted Percentage
    if (phpData.progressPerLot && phpData.progressPerLot.length > 0) {
        const lots = phpData.progressPerLot.map(l => l.lot_name);
        const acceptedData = phpData.progressPerLot.map(l => {
            const total = l.total || 1;
            return Math.round((l.accepted / total) * 100);
        });
        const notAcceptedData = phpData.progressPerLot.map(l => {
            const total = l.total || 1;
            return 100 - Math.round((l.accepted / total) * 100);
        });

        new Chart(document.getElementById('acceptedPerLotChart'), {
            type: 'bar',
            data: {
                labels: lots,
                datasets: [
                    {
                        label: 'Accepted',
                        data: acceptedData,
                        backgroundColor: deliveryStatusColors.Accepted,
                        borderColor: colorVariants.border.Accepted,
                        borderWidth: 1
                    },
                    {
                        label: 'Not Accepted',
                        data: notAcceptedData,
                        backgroundColor: deliveryStatusColors.Not,
                        borderColor: colorVariants.border.Not,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Lots'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const lotData = phpData.progressPerLot[context.dataIndex];
                                if (context.dataset.label === 'Accepted') {
                                    return `Accepted: ${lotData.accepted}/${lotData.total} (${context.parsed.y}%)`;
                                } else {
                                    return `Not Accepted: ${lotData.total - lotData.accepted}/${lotData.total} (${context.parsed.y}%)`;
                                }
                            }
                        }
                    }
                }
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const data = dataset.data[index];
                            if (data > 0) {
                                ctx.fillStyle = '#fff';
                                ctx.font = 'bold 12px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(data + '%', bar.x, bar.y + (bar.height / 2));
                            }
                        });
                    });
                }
            }]
        });
    } else {
        createEmptyChart(document.getElementById('acceptedPerLotChart'), 'No lot data available');
    }

    // Progress by Lot - Delivered Percentage
    if (phpData.progressPerLot && phpData.progressPerLot.length > 0) {
        const lots = phpData.progressPerLot.map(l => l.lot_name);
        const deliveredData = phpData.progressPerLot.map(l => {
            const total = l.total || 1;
            return Math.round((l.delivered / total) * 100);
        });
        const notDeliveredData = phpData.progressPerLot.map(l => {
            const total = l.total || 1;
            return 100 - Math.round((l.delivered / total) * 100);
        });

        new Chart(document.getElementById('deliveredPerLotChart'), {
            type: 'bar',
            data: {
                labels: lots,
                datasets: [
                    {
                        label: 'Delivered',
                        data: deliveredData,
                        backgroundColor: deliveryStatusColors.Delivered,
                        borderColor: colorVariants.border.Delivered,
                        borderWidth: 1
                    },
                    {
                        label: 'Not Delivered',
                        data: notDeliveredData,
                        backgroundColor: deliveryStatusColors.Not,
                        borderColor: colorVariants.border.Not,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Lots'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const lotData = phpData.progressPerLot[context.dataIndex];
                                if (context.dataset.label === 'Delivered') {
                                    return `Delivered: ${lotData.delivered}/${lotData.total} (${context.parsed.y}%)`;
                                } else {
                                    return `Not Delivered: ${lotData.total - lotData.delivered}/${lotData.total} (${context.parsed.y}%)`;
                                }
                            }
                        }
                    }
                }
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const data = dataset.data[index];
                            if (data > 0) {
                                ctx.fillStyle = '#fff';
                                ctx.font = 'bold 12px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(data + '%', bar.x, bar.y + (bar.height / 2));
                            }
                        });
                    });
                }
            }]
        });
    } else {
        createEmptyChart(document.getElementById('deliveredPerLotChart'), 'No lot data available');
    }

    // Not USED BELOW - KEEP FOR FUTURE REFERENCE
    // Changes per Warehouse
    // new Chart(document.getElementById('changesPerWarehouseChart'), {
    //     type: 'bar',
    //     data: {
    //     labels: changesPerWarehouse.map(r => r.warehouse_name),
    //     datasets: [{
    //         label: 'Changes',
    //         data: changesPerWarehouse.map(r => r.total_changes),
    //         backgroundColor: '#ffc107'
    //         }]
    //     },
    //     options: {
    //     plugins: { legend: { display: false } },
    //     scales: {
    //         x: { title: { display: true, text: 'Warehouse' } },
    //         y: { beginAtZero: true, title: { display: true, text: 'Changes' } }
    //         }
    //     }
    // });

        // 🟩 Top Updated Items
    // new Chart(document.getElementById('topUpdatedItemsChart'), {
    //   type: 'bar',
    //   data: {
    //     labels: topUpdatedItems.map(r => r.item_name),
    //     datasets: [{
    //       label: 'Updates',
    //       data: topUpdatedItems.map(r => r.update_count),
    //       backgroundColor: '#28a745'
    //     }]
    //   },
    //   options: {
    //     plugins: { legend: { display: false } },
    //     scales: {
    //       x: { title: { display: true, text: 'Item' } },
    //       y: { beginAtZero: true, title: { display: true, text: 'Updates' } }
    //     }
    //   }
    // });
    // 3. Today's User Activity (Doughnut)
    // if (todayUserActivity.length > 0) {
    //     const activityTypes = [...new Set(todayUserActivity.map(r => r.activity_type))];
    //     const timeLabels = [...new Set(todayUserActivity.map(r => r.time_label))].sort();
        
    //     const activityDetails = {};
    //     todayUserActivity.forEach(r => {
    //         activityDetails[`${r.time_label}-${r.activity_type}`] = r.activity_list?.split('|||') || [];
    //     });
        
    //     const datasets = activityTypes.map((type, i) => {
    //         const color = deliveryStatusColors[type] || primaryColors[i % primaryColors.length];
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


});