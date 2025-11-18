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
const { deliveriesByWarehouse, 
        cashflowData,
        selectedProject
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

      // Cashflow Chart - Actual Expense vs Income (Timeline)
    if (cashflowData && cashflowData.length > 0) {
        const months = cashflowData.map(r => r.month);
        const incomeValues = cashflowData.map(r => parseFloat(r.total_income) || 0);
        const expenseValues = cashflowData.map(r => parseFloat(r.total_expense) || 0);
        const netCashflowValues = cashflowData.map(r => parseFloat(r.net_cashflow) || 0);

        new Chart(document.getElementById('cashflowChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Income',
                        data: incomeValues,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true,
                        order: 2
                    },
                    {
                        label: 'Expense',
                        data: expenseValues,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true,
                        order: 2
                    },
                    {
                        label: 'Profit',
                        data: netCashflowValues,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.15)',
                        borderWidth: 4,
                        tension: 0.3,
                        fill: true,
                        order: 1,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Timeline (Month)',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount (₱)',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
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
                            title: function(context) {
                                return 'Month: ' + context[0].label;
                            },
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = parseFloat(context.parsed.y);
                                return label + ': ₱' + value.toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            },
                            footer: function(context) {
                                const monthData = cashflowData[context[0].dataIndex];
                                const income = parseFloat(monthData.total_income);
                                const expense = parseFloat(monthData.total_expense);
                                const net = parseFloat(monthData.net_cashflow);
                                
                                let status = '';
                                if (net > 0) {
                                    status = '✓ Positive Cashflow';
                                } else if (net < 0) {
                                    status = '✗ Negative Cashflow';
                                } else {
                                    status = '◎ Break Even';
                                }
                                
                                return [
                                    '',
                                    status,
                                    'Margin: ' + (income > 0 ? ((net / income) * 100).toFixed(1) + '%' : 'N/A')
                                ];
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        footerFont: {
                            size: 12,
                            weight: 'normal'
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    } else {
        createEmptyChart(document.getElementById('cashflowChart'), 'No cashflow data available');
    }

        // Expected vs Actual Deliveries by Warehouse
    if (deliveriesByWarehouse && deliveriesByWarehouse.length > 0) {
        const warehouses = deliveriesByWarehouse.map(w => w.warehouse_name);
        const expectedData = deliveriesByWarehouse.map(w => parseInt(w.expected_deliveries));
        const actualData = deliveriesByWarehouse.map(w => parseInt(w.actual_deliveries));

        new Chart(document.getElementById('deliveriesByWarehouseChart'), {
            type: 'bar',
            data: {
                labels: warehouses,
                datasets: [
                    {
                        label: 'Expected Deliveries',
                        data: expectedData,
                        backgroundColor: deliveryStatusColors.Pending, // #dc3545 - Red
                        borderColor: colorVariants.border.Pending,     // #dc3545
                        borderWidth: 1
                    },
                    {
                        label: 'Actual Deliveries',
                        data: actualData,
                        backgroundColor: deliveryStatusColors.Delivered, // #198754 - Green
                        borderColor: colorVariants.border.Delivered,     // #198754
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Warehouses'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Deliveries'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const warehouseData = deliveriesByWarehouse[context.dataIndex];
                                if (context.dataset.label === 'Expected Deliveries') {
                                    return `Expected: ${context.parsed.y} deliveries`;
                                } else {
                                    const variance = warehouseData.expected_deliveries - warehouseData.actual_deliveries;
                                    const status = variance <= 0 ? 'Met Target' : 'Behind Target';
                                    const completionRate = warehouseData.expected_deliveries > 0 
                                        ? Math.round((warehouseData.actual_deliveries / warehouseData.expected_deliveries) * 100) 
                                        : 0;
                                    return `Actual: ${context.parsed.y} deliveries | ${completionRate}% Complete | ${status}`;
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'top',
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
                                ctx.font = 'bold 10px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(data, bar.x, bar.y - 10);
                            }
                        });
                    });
                }
            }]
        });
    } else {
        createEmptyChart(document.getElementById('deliveriesByWarehouseChart'), 'No deliveries data available for selected project');
    }

});

setInterval(() => location.reload(), 30000);