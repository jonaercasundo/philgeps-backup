// Access the global phpData object created in the main PHP file.
const {
    cashFlow,
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

// Cash Flow - Revenue by Month (Line Chart)
if (phpData.cashFlow && phpData.cashFlow.length > 0) {
    const months = phpData.cashFlow.map(r => r.month);
    const revenueData = phpData.cashFlow.map(r => parseFloat(r.total_revenue));
    const profitData = phpData.cashFlow.map(r => parseFloat(r.total_profit));

    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Total Revenue',
                    data: revenueData,
                    borderColor: '#28a745', // Green for revenue
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Total Profit',
                    data: profitData,
                    borderColor: '#007bff', // Blue for profit
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
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
                        text: 'Months'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₱)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const monthData = phpData.cashFlow[context.dataIndex];
                            if (context.dataset.label === 'Total Revenue') {
                                return `Revenue: ₱${parseFloat(monthData.total_revenue).toLocaleString()} (${monthData.total_deliveries} deliveries)`;
                            } else {
                                return `Profit: ₱${parseFloat(monthData.total_profit).toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        }
    });
} else {
    createEmptyChart(document.getElementById('revenueChart'), 'No cash flow data available');
}
});