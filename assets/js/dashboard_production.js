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

// Access data from global phpData object
const { deliveryStatusOverview, 
        monthlyDeliveryTrend, 
        inventoryData, 
        selectedProject, 
        progressPerLot, 
        inventoryHistoryTrend 
} = phpData;

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

document.addEventListener('DOMContentLoaded', function() {
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
            backgroundColor: deliveryStatusOverview.map(r => deliveryStatusColors[r.status] || primaryColors[0]),
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
        const months = [...new Set(monthlyDeliveryTrend.map(r => r.month))];
        
        // Filter out the generic "Logistics" status, keep only specific logistics companies
        const statuses = [...new Set(monthlyDeliveryTrend.map(r => r.status))].filter(status => 
            status !== 'Logistics'
        );

        // Colors without green - use yellow, red, and other colors from primaryColors
        const nonGreenColors = primaryColors.filter(color => 
            !color.includes('198754') && !color.includes('157347') && !color.includes('22c55e')
        );

        const datasets = statuses.map((status, index) => {
            let borderColor, backgroundColor;
            
            if (status === 'Warehouse') {
                borderColor = deliveryStatusColors.Pending;
                backgroundColor = colorVariants.light.Pending;
            } else if (status === 'Schools') {
                borderColor = deliveryStatusColors.Delivered; // Schools still green
                backgroundColor = colorVariants.light.Delivered;
            } else {
                // Logistics companies get colors from nonGreenColors
                const colorIndex = index % nonGreenColors.length;
                borderColor = nonGreenColors[colorIndex];
                backgroundColor = colorVariants.light.Accepted;
            }
            
            return {
                label: status,
                data: months.map(month => {
                    const row = monthlyDeliveryTrend.find(r => r.month === month && r.status === status);
                    return row ? parseInt(row.total) : 0;
                }),
                borderColor: borderColor,
                backgroundColor: backgroundColor,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: borderColor,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            };
        });

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

    // Group inventory data for Inventory by Warehouse chart
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

    // Combined Progress by Lot - Accepted & Delivered Percentage
    if (phpData.progressPerLot && phpData.progressPerLot.length > 0) {
        const lots = phpData.progressPerLot.map(l => l.lot_name);
        
        const acceptedData = phpData.progressPerLot.map(l => {
            const total = l.total || 1;
            return Math.round((l.accepted / total) * 100);
        });
        
        const deliveredData = phpData.progressPerLot.map(l => {
            const total = l.total || 1;
            return Math.round((l.delivered / total) * 100);
        });
        
        const pendingData = phpData.progressPerLot.map(l => {
            const total = l.total || 1;
            const acceptedPercent = Math.round((l.accepted / total) * 100);
            const deliveredPercent = Math.round((l.delivered / total) * 100);
            return 100 - acceptedPercent - deliveredPercent;
        });

        new Chart(document.getElementById('deliveryStatusPerLotChart'), {
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
                        label: 'Accepted',
                        data: acceptedData,
                        backgroundColor: deliveryStatusColors.Accepted,
                        borderColor: colorVariants.border.Accepted,
                        borderWidth: 1
                    },
                    {
                        label: 'Pending',
                        data: pendingData,
                        backgroundColor: deliveryStatusColors.Pending,
                        borderColor: colorVariants.border.Pending,
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
                                const total = lotData.total || 1;
                                
                                switch(context.dataset.label) {
                                    case 'Delivered':
                                        return `Delivered: ${lotData.delivered}/${total} (${context.parsed.y}%)`;
                                    case 'Accepted':
                                        return `Accepted: ${lotData.accepted}/${total} (${context.parsed.y}%)`;
                                    case 'Pending':
                                        const pending = total - lotData.accepted - lotData.delivered;
                                        return `Pending: ${pending}/${total} (${context.parsed.y}%)`;
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
        createEmptyChart(document.getElementById('deliveryStatusPerLotChart'), 'No lot data available');
    }

    // 🎯 Inventory History (Daily Changes)
    new Chart(document.getElementById('inventoryHistoryTrendChart'), {
        type: 'line',
        data: {
            labels: inventoryHistoryTrend.map(r => r.change_date), // e.g. ['2025-01-01', '2025-01-02', ...]
            datasets: [{
            label: 'Inventory Changes',
            data: inventoryHistoryTrend.map(r => r.total_changes),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.2)',
            borderWidth: 2,
            fill: true,
            tension: 0.3, // smooth curve
            pointRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'Inventory History (Daily Changes)'
            }
            },
            scales: {
            x: {
                title: { display: true, text: 'Date' },
                ticks: { maxRotation: 45, minRotation: 45 }
            },
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Total Changes' }
            }
            }
        }
    });
});

setInterval(() => location.reload(), 30000);