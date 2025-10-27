// Access the global phpData object created in the main PHP file.
const {
    deliveryStatusOverview,
    monthlyDeliveryTrend,
    selectedProject,
    inventoryData,
    stockLevelData,
    inventoryHistoryTrend,
    projectStatusOverview,
    opportunity,
    progressPerLot,
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

    // 📊 Project Status Overview (Pie Chart)
    if (projectStatusOverview.length > 0) {
    const totalOverall = projectStatusOverview.reduce((sum, r) => sum + parseFloat(r.total || 0), 0);

    new Chart(document.getElementById('projectStatusChart'), {
        type: 'pie',
        data: {
        labels: projectStatusOverview.map(r => 
            `${r.status} (${((r.total / totalOverall) * 100).toFixed(1)}%)`
        ),
        datasets: [{
            data: projectStatusOverview.map(r => r.total),
            backgroundColor: projectStatusOverview.map(r => 
                projectStatusColors[r.status]
            ),
            borderColor: projectStatusOverview.map(r => 
                projectStatusColors[r.status]
            ),
            borderWidth: 2
        }]
        },
        options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
            position: 'bottom',
            labels: {
                boxWidth: 14,
                font: { size: 12 }
            }
            }
        }
        }
    });
    } else {
    createEmptyChart(document.getElementById('projectStatusChart'), 'No project data available');
    }

    // 📊 BUDGET VARIANCE
    if (phpData.opportunity && phpData.opportunity.length > 0) {
        const projects = phpData.opportunity.map(p => p.project_name);
        const contractData = phpData.opportunity.map(p => parseFloat(p.contract_amount) || 0);
        const abcData = phpData.opportunity.map(p => parseFloat(p.ABC) || 0);

        new Chart(document.getElementById('opportunityChart'), {
            type: 'bar',
            data: {
                labels: projects,
                datasets: [
                    {
                        label: 'Contract Amount',
                        data: contractData,
                        backgroundColor: '#198754',
                        borderColor: '#146c43',
                        borderWidth: 1
                    },
                    {
                        label: 'ABC',
                        data: abcData,
                        backgroundColor: '#fbc02d',
                        borderColor: '#f9a825',
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
                            text: 'Projects'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount (₱)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                return `${context.dataset.label}: ₱${value.toLocaleString()}`;
                            },
                            afterLabel: function(context) {
                                const projectData = phpData.opportunity[context.dataIndex];
                                const contractAmount = parseFloat(projectData.contract_amount) || 0;
                                const abc = parseFloat(projectData.ABC) || 0;
                                const total = contractAmount + abc;
                                
                                if (total > 0) {
                                    const percentage = context.dataset.label === 'Contract Amount' 
                                        ? Math.round((contractAmount / total) * 100)
                                        : Math.round((abc / total) * 100);
                                    return `(${percentage}%)`;
                                }
                                return '';
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
                                const projectData = phpData.opportunity[index];
                                const contractAmount = parseFloat(projectData.contract_amount) || 0;
                                const abc = parseFloat(projectData.ABC) || 0;
                                const total = contractAmount + abc;
                                
                                if (total > 0) {
                                    const percentage = i === 0 
                                        ? Math.round((contractAmount / total) * 100)
                                        : Math.round((abc / total) * 100);
                                    
                                    ctx.fillStyle = '#fff';
                                    ctx.font = 'bold 12px Arial';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(percentage + '%', bar.x, bar.y + (bar.height / 2));
                                }
                            }
                        });
                    });
                }
            }]
        });
    } else {
        createEmptyChart(document.getElementById('opportunityChart'), 'No project financial data available');
    }

    // 📊 ITEM PRICE VARIANCE CHART
    if (phpData.itemVariance && phpData.itemVariance.length > 0) {
        const items = phpData.itemVariance.map(p => p.item_name);
        const ourPriceData = phpData.itemVariance.map(p => parseFloat(p.our_price) || 0);
        const factoryPriceData = phpData.itemVariance.map(p => parseFloat(p.factory_price) || 0);

        new Chart(document.getElementById('itemPriceVarianceChart'), {
            type: 'bar',
            data: {
                labels: items,
                datasets: [
                    {
                        label: 'Price',
                        data: ourPriceData,
                        backgroundColor: '#198754', 
                        borderColor: '#146c43',
                        borderWidth: 1
                    },
                    {
                        label: 'Factory Price',
                        data: factoryPriceData,
                        backgroundColor: '#fbc02d',
                        borderColor: '#f9a825',
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
                            text: 'Items'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Price (₱)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                return `${context.dataset.label}: ₱${value.toLocaleString()}`;
                            },
                            afterLabel: function(context) {
                                const itemData = phpData.itemVariance[context.dataIndex];
                                const ourPrice = parseFloat(itemData.our_price) || 0;
                                const factoryPrice = parseFloat(itemData.factory_price) || 0;
                                const total = ourPrice + factoryPrice;
                                
                                if (total > 0) {
                                    const percentage = context.dataset.label === 'Our Price' 
                                        ? Math.round((ourPrice / total) * 100)
                                        : Math.round((factoryPrice / total) * 100);
                                    return `(${percentage}%)`;
                                }
                                return '';
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
                                const itemData = phpData.itemVariance[index];
                                const ourPrice = parseFloat(itemData.our_price) || 0;
                                const factoryPrice = parseFloat(itemData.factory_price) || 0;
                                const total = ourPrice + factoryPrice;
                                
                                if (total > 0) {
                                    const percentage = i === 0 
                                        ? Math.round((ourPrice / total) * 100)
                                        : Math.round((factoryPrice / total) * 100);
                                    
                                    ctx.fillStyle = '#fff';
                                    ctx.font = 'bold 12px Arial';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(percentage + '%', bar.x, bar.y + (bar.height / 2));
                                }
                            }
                        });
                    });
                }
            }]
        });
    } else {
        createEmptyChart(document.getElementById('itemPriceVarianceChart'), 'No item price variance data available');
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


});
