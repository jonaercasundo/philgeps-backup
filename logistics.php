<?php 
$is_logistics_page = true;
require "template/header.php"; 
require "script/role_auth.php";
require "config/db.php";

// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Admin', 'Warehouse Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
?>

<!-- Main Full-Screen Container -->
<div class="row g-0 h-100">
    <!-- 1. LEFT SIDEBAR (3 Columns wide on medium/large screens) -->
    <div class="col-md-3 border-end d-flex flex-column">
        <div class="px-3">
                <h5 class="mb-0 text-dark opacity-75">Summary</h5>
        </div>
        
        <div class="flex-fill p-3 border-top d-flex flex-column">
            <!-- 'h-100' ensures the chart container fills the height of the flex-fill parent -->
            <div class="chart-container h-100">
                <!-- 'h-100' ensures the canvas fills the height of the chart-container -->
                <canvas id="logisticsStockLevelsChart" class="h-100"></canvas>
            </div>
        </div>

         <!-- <div class="flex-fill p-3 border-top d-flex flex-column">
            <a href="#" class="btn btn-outline-secondary w-100 mb-2">
                View Something
            </a>
        </div> -->
    </div>

    <!-- 2. RIGHT MAIN CONTENT AREA (9 Columns wide on medium/large screens) -->
    <div class="col-md-9 d-flex flex-column">
        <!-- Large Main Content/Display Area -->
        <div class="flex-grow-1">
            <div class="bg-white px-4 rounded shadow-sm h-100">
                <h5 class="mb-0 text-dark">Out to Logistics</h5>

                <table id="logisticsTable" class="table table-bordered table-striped">
                    <thead  class="table-dark text-center">
                        <tr>
                            <th>Delivery ID</th>
                            <th>Project</th>
                            <th>School</th>
                            <th>DR No.</th>
                            <th>Delivery Date</th>
                            <th>Package Type</th>
                            <th>Warehouse</th>
                            <th>Contents</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require "template/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Table Scripts -->
<script>
    $(document).ready(function() {
        const table = $('#logisticsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "script/get_logistics_deliveries.php",
                type: "GET"
            },
            columns: [
                { data: "delivery_id", className: "text-center" },
                { data: "project_name", className: "text-center" },
                { data: "school_name", className: "text-center" },
                { data: "dr_no", className: "text-center" },
                { data: "delivery_date", className: "text-center" },
                { data: "package_type", className: "text-center" },
                { data: "warehouse_name", className: "text-center" },
                { 
                    data: "items_contents", 
                    orderable: false,
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            // Check if content is long enough to need truncation
                            const needsTruncation = data.length > 100 || data.split('\n').length > 3;
                            
                            if (needsTruncation) {
                                // Create a unique ID for this row's content
                                const uniqueId = 'content-' + row.delivery_id;
                                return `
                                    <div class="content-wrapper">
                                        <div id="${uniqueId}-short" class="short-content">
                                            ${truncateContent(data)}
                                        </div>
                                        <div id="${uniqueId}-full" class="full-content" style="display: none;">
                                            ${data}
                                        </div>
                                        <button type="button" class="btn btn-link btn-sm p-0 mt-1 see-more-btn" 
                                                onclick="toggleContent('${uniqueId}')">
                                            See More
                                        </button>
                                    </div>
                                `;
                            }
                            return data;
                        }
                        return data || '';
                    }
                }
            ],
            scrollY: "53vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
        });
    });

    // Helper function to truncate content
    function truncateContent(text) {
        if (!text) return '';
        
        // Limit to 100 characters or 3 lines
        const lines = text.split('\n');
        if (lines.length > 3) {
            return lines.slice(0, 3).join('\n') + '...';
        }
        if (text.length > 100) {
            return text.substring(0, 100) + '...';
        }
        return text;
    }

    // Toggle function for See More/Less
    function toggleContent(uniqueId) {
        const shortContent = document.getElementById(uniqueId + '-short');
        const fullContent = document.getElementById(uniqueId + '-full');
        const button = shortContent.parentElement.querySelector('.see-more-btn');
        
        if (shortContent.style.display !== 'none') {
            // Show full content
            shortContent.style.display = 'none';
            fullContent.style.display = 'block';
            button.textContent = 'See Less';
        } else {
            // Show short content
            fullContent.style.display = 'none';
            shortContent.style.display = 'block';
            button.textContent = 'See More';
        }
    }
</script>

<!-- Summary Scripts -->
<script>
    $(document).ready(function () {
        $.getJSON("script/get_warehouse_summary.php", function (data) {
            $("#warehouse_count").text(data.warehouse_count);
            $("#logistics_count").text(data.logistics_count);
        }).fail(function () {
            $("#warehouse_count").text("ERROR");
            $("#logistics_count").text("ERROR");
        });
    });
</script>

<!-- Logistics Stock Levels Graph -->
<script>
    // Fetch data and render chart
    fetch('script/get_logistics_summary.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            
            if (data.accepted_by_logistics && data.accepted_by_logistics.logistics_names.length > 0) {
                renderLogisticsStockLevelsChart(data.accepted_by_logistics);
            } else {
                document.getElementById('logisticsStockLevelsChart').parentElement.innerHTML = 
                    '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-muted text-center">No delivery data available</p></div>';
            }
        })
        .catch(error => {
            console.error('Error fetching logistics delivery data:', error);
            document.getElementById('logisticsStockLevelsChart').parentElement.innerHTML = 
                '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-danger text-center">Error loading chart data</p></div>';
        });

    function renderLogisticsStockLevelsChart(chartData) {
        const ctx = document.getElementById('logisticsStockLevelsChart').getContext('2d');
        
        // Use consistent indigo color palette
        const backgroundColors = [
            'rgba(79, 70, 229, 0.8)',   // indigo-600
            'rgba(99, 102, 241, 0.8)',  // indigo-500
            'rgba(129, 140, 248, 0.8)', // indigo-400
            'rgba(67, 56, 202, 0.8)',   // indigo-700
            'rgba(165, 180, 252, 0.8)', // indigo-300
            'rgba(49, 46, 129, 0.8)'    // indigo-800
        ];
        
        const borderColors = [
            'rgba(79, 70, 229, 1)',
            'rgba(99, 102, 241, 1)',
            'rgba(129, 140, 248, 1)',
            'rgba(67, 56, 202, 1)',
            'rgba(165, 180, 252, 1)',
            'rgba(49, 46, 129, 1)'
        ];

        // Create the chart with horizontal layout for better fit in sidebar
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.logistics_names,
                datasets: [{
                    label: 'Accepted Deliveries',
                    data: chartData.delivery_counts,
                    backgroundColor: backgroundColors.slice(0, chartData.logistics_names.length),
                    borderColor: borderColors.slice(0, chartData.logistics_names.length),
                    borderWidth: 1,
                    borderRadius: 4, 
                }]
            },
            options: {
                indexAxis: 'y', 
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Logistics Stock Levels',
                        font: {
                            size: 14,
                            weight: '600'
                        },
                        color: '#1f2937',
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(31, 41, 55, 0.9)',
                        titleFont: { size: 12 },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: function(context) {
                                return `Deliveries: ${context.parsed.x}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: false,
                        },
                        ticks: {
                            font: { size: 10 },
                            precision: 0
                        },
                        grid: {
                            color: '#e5e7eb'
                        }
                    },
                    y: {
                        title: {
                            display: false,
                        },
                        ticks: {
                            font: { size: 10 }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 750,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
</script>

