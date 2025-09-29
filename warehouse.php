<?php 
require "template/header.php"; 
require "config/db.php";
?>



<!-- Main Full-Screen Container -->

    <div class="row g-0 h-100">

        <!-- 1. LEFT SIDEBAR (3 Columns wide on medium/large screens) -->
        <div class="col-md-3 border-end bg-light d-flex flex-column">
            <!-- Sidebar Header/Title Placeholder -->
            <div class="p-3">
                <div class="bg-secondary bg-opacity-25 rounded p-2" style="width: 60%; height: 25px;"></div>
            </div>

            <!-- Stacked Navigation/List Items Placeholder -->
            <div class="p-3">
                <div class="bg-secondary bg-opacity-25 mb-3 rounded" style="height: 40px;"></div>
                <div class="bg-secondary bg-opacity-25 mb-3 rounded" style="height: 40px;"></div>
                <div class="bg-secondary bg-opacity-25 mb-3 rounded" style="height: 40px;"></div>
                <div class="bg-secondary bg-opacity-25 mb-3 rounded" style="height: 40px;"></div>
            </div>

            <!-- Bar Chart Placeholder (Stuck to the bottom) -->
            <div class="mt-auto p-3 border-top">
                <h6 class="text-muted mb-3">Data Visualization</h6>
                <div class="d-flex justify-content-between align-items-end chart-container">
                    <!-- Blue Bars (using primary color) -->
                    <div class="bg-primary rounded-top" style="width: 16%; height: 50%;"></div>
                    <div class="bg-primary rounded-top" style="width: 16%; height: 80%;"></div>
                    <div class="bg-primary rounded-top" style="width: 16%; height: 35%;"></div>
                    <div class="bg-primary rounded-top" style="width: 16%; height: 95%;"></div>
                    <div class="bg-primary rounded-top" style="width: 16%; height: 70%;"></div>
                </div>
            </div>
        </div>

        <!-- 2. RIGHT MAIN CONTENT AREA (9 Columns wide on medium/large screens) -->
        <div class="col-md-9 d-flex flex-column">

            <div class="d-flex align-items-center p-3 border-bottom bg-white">
                <!-- Search (New Content) -->
                <div class="d-flex" style="max-width: 300px;">
                    <input id="searchInput" class="form-control" placeholder="Search items...">
                    <button class="btn btn-outline-primary ms-2" id="searchButton">Search</button>
                </div>

                <!-- Primary Action Button (Bright Blue) - Pushed to the far right using ms-auto -->
                <button class="btn btn-primary rounded shadow-sm ms-auto">
                    <span class="fw-bold">New Report</span>
                </button>
            </div>


            <!-- Large Main Content/Display Area -->
            <div class="flex-grow-1 p-3">
                <!-- Large light gray box, using bg-secondary with higher opacity -->
                <div class="bg-secondary bg-opacity-10 h-100 rounded p-5 text-center text-muted">
                    <p class="h4">Main Content Area</p>
                    <p>This large block fills the available space.</p>
                </div>
            </div>

            <!-- Bottom Action Blocks/Buttons -->
            <div class="d-flex justify-content-start p-3 border-top bg-white">
                <!-- Darker Blue-Gray Block 1 -->
                <button class="btn btn-primary ms-2" id="">Generate Report</button>
                <button class="btn btn-primary ms-2" id="">Et</button>
            </div>
        </div>
    </div>


