<?php 
$is_warehouse_page = true;
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
        
        <div class="p-3">
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body p-3">
                    <h6 class="card-title text-primary mb-1">Total Deliveries In Warehouse</h6>
                    <p id="warehouse_count" class="card-text fw-bold fs-5">Loading...</p>
                </div>
            </div>
            
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body p-3">
                    <h6 class="card-title text-primary mb-1">Total Deliveries Send to Logistics</h6>
                    <p id="logistics_count" class="card-text fw-bold fs-5">Loading...</p>
                </div>
            </div>

            <!-- <div class="d-flex align-items-center mb-3 bg-white rounded p-3 shadow-sm border">
                <div class="flex-shrink-0 me-3">
                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 30px; height: 30px;">
                        <i class="bi bi-person"></i> </div>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-0">New Users</h6>
                    <small class="text-muted">Last 7 days</small>
                </div>
                <span class="badge bg-success">+12%</span>
            </div> -->


            <a href="#" class="btn btn-outline-secondary w-100 mb-2">
                View Something
            </a>
        </div>

        <div class="flex-fill p-3 border-top d-flex flex-column">
            <!-- 'h-100' ensures the chart container fills the height of the flex-fill parent -->
            <div class="chart-container h-100">
                <!-- 'h-100' ensures the canvas fills the height of the chart-container -->
                <canvas id="warehouseBarChart" class="h-100"></canvas>
            </div>
        </div>
    </div>

    <!-- 2. RIGHT MAIN CONTENT AREA (9 Columns wide on medium/large screens) -->
    <div class="col-md-9 d-flex flex-column">
        <!-- Large Main Content/Display Area -->
        <div class="flex-grow-1">
            <div class="bg-white px-4 rounded shadow-sm h-100">
                <h5 class="mb-0 text-dark">Deliveries Status Logistics </h5>

                <table id="logisticsTable" class="table product-category-table w-100">
                    <thead>
                        <tr>
                            <th>Delivery ID</th>
                            <th>Project</th>
                            <th>School</th>
                            <th>DR No.</th>
                            <th>Delivery Date</th>
                            <th>Package Type</th>
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
                { data: "delivery_id" },
                { data: "project_name" },
                { data: "school_name" },
                { data: "dr_no" },
                { data: "delivery_date" },
                { data: "package_type" },
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

<!-- // SAMPLE DATA FOR TESTING PURPOSES OF CHART -->
<script>
    // Define data for the chart
    const warehouseLabels = ['Warehouse A', 'Warehouse B', 'Warehouse C', 'Warehouse D', 'Warehouse E'];
    const itemData = [1500, 2200, 950, 3100, 1800];
    
    // Function to initialize the Chart.js instance
    function initChart() {
        const ctx = document.getElementById('warehouseBarChart').getContext('2d');

        const chartConfig = {
            type: 'bar',
            data: {
                labels: warehouseLabels,
                datasets: [{
                    label: 'Total Items in Stock',
                    data: itemData,
                    // Define bar colors using an appealing indigo palette
                    backgroundColor: [
                        'rgba(79, 70, 229, 0.8)', // indigo-600
                        'rgba(99, 102, 241, 0.8)', // indigo-500
                        'rgba(129, 140, 248, 0.8)', // indigo-400
                        'rgba(67, 56, 202, 0.8)', // indigo-700
                        'rgba(165, 180, 252, 0.8)' // indigo-300
                    ],
                    // Define border color
                    borderColor: [
                        'rgba(79, 70, 229, 1)',
                        'rgba(99, 102, 241, 1)',
                        'rgba(129, 140, 248, 1)',
                        'rgba(67, 56, 202, 1)',
                        'rgba(165, 180, 252, 1)'
                    ],
                    borderWidth: 1,
                    borderRadius: 4, // Slightly smaller rounded tops for smaller bars
                }]
            },
            options: {
                indexAxis: 'y', // KEY CHANGE: Switched to horizontal bars for better fit in a narrow sidebar
                responsive: true,
                maintainAspectRatio: false, // Allows the chart to adapt to the container's size
                plugins: {
                    legend: {
                        display: false // Hide the legend since there's only one dataset
                    },
                    title: {
                        display: true,
                        text: 'Warehouse Stock Levels', // Simplified title for sidebar
                        font: {
                            size: 14, // Smaller font size
                            weight: '600'
                        },
                        color: '#1f2937' // dark gray
                    },
                    tooltip: {
                        backgroundColor: 'rgba(31, 41, 55, 0.9)',
                        titleFont: { size: 12 },
                        bodyFont: { size: 12 }
                    }
                },
                scales: {
                    x: { // X-axis is now the value axis (Items)
                        beginAtZero: true,
                        title: {
                            display: false, // Hide title for brevity in sidebar
                        },
                        ticks: {
                            font: { size: 10 } // Smaller font for ticks
                        },
                        grid: {
                            color: '#e5e7eb' // Light gray grid lines
                        }
                    },
                    y: { // Y-axis is now the category axis (Warehouses)
                        title: {
                            display: false, // Hide title for brevity in sidebar
                        },
                        ticks: {
                            font: { size: 10 } // Smaller font for ticks
                        },
                        grid: {
                            display: false // Hide vertical grid lines
                        }
                    }
                }
            }
        };
        
        // Create the chart instance
        new Chart(ctx, chartConfig);
    }

    // Initialize chart once the window loads
    window.onload = initChart;

</script>

