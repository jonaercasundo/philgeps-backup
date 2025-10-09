// Collect selected filters into query string
function getFilters() {
    const filterIds = {
        year: "year",
        project_id: "filterProjects",
        status: "filterStatus",
        region: "filterRegion",
        division: "filterDivision",
        municipality: "filterMunicipality",
        search: "searchInput"
    };

    const params = new URLSearchParams();
    for (const [key, id] of Object.entries(filterIds)) {
        const el = document.getElementById(id);
        const value = el?.value?.trim();
        if (value) params.append(key, value);
    }
    return params.toString();
}

// Update table via AJAX
async function updateTable(page = 1) {
    const tbody = document.getElementById("resultTable");
    tbody.innerHTML = "";
    showLoading();

    try {
        const res = await fetch("script/filterDeliveries.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `${getFilters()}&page=${page}&limit=10`
        });
        const data = await res.json();

        if (data.rows?.length) {
            tbody.innerHTML = `
                <thead class="table-dark">
                    <tr>
                        <th>School</th>
                        <th>Address</th>
                        <th>Items</th>
                        <th>DR No</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.rows.map(row => `
                        <tr>
                            <td>${row.school_name}</td>
                            <td>${row.address}</td>
                            <td>${row.items_contents}</td>
                            <td>${row.dr_no}</td>
                            <td>${row.delivery_date}</td>
                            <td class="text-center">
                                <button class="btn btn-warning mb-1" 
                                    data-bs-toggle="modal" data-bs-target="#editDeliveryModal"
                                    data-id="${row.delivery_id}" 
                                    data-project="${row.project_name}"
                                    data-school="${row.school_name}" 
                                    data-address="${row.address}"
                                    data-items_contents="${row.items_contents}" 
                                    data-remarks="${row.items_contents}"
                                    data-drno="${row.dr_no}"
                                    data-date="${row.delivery_date}" 
                                    data-status="${row.status}">
                                    <i class="bi bi-pencil-square fs-4"></i>
                                </button> <br>
                                <a class="btn btn-secondary mb-1" href="generate_qr.php?id=${row.dr_no}" target="_blank"><i class="bi bi-qr-code fs-4"></i></a><br>
                                ${row.has_photos ? `<a class="btn btn-primary mb-1" href="deliveries_details.php?id=${row.dr_no}" target="_blank"><i class="bi bi-eye fs-4"></i></a>` : ""}
                            </td>
                        </tr>
                    `).join("")}
                </tbody>
            `;
        }
        renderPagination(data, page);
    } finally {
        hideLoading();
    }
}

// Pagination rendering
function renderPagination(data, currentPage) {
    const pagination = document.getElementById("pagination");
    pagination.innerHTML = "";
    if (!data.total_pages || data.total_pages <= 1) return;

    const createPage = (i, text = i) => `
        <li class="page-item ${i === currentPage ? "active" : ""}">
            <a class="page-link" href="#" onclick="updateTable(${i})">${text}</a>
        </li>`;

    if (currentPage > 1) pagination.innerHTML += createPage(currentPage - 1, "«");

    const start = Math.max(1, currentPage - 4);
    const end = Math.min(data.total_pages, start + 7);
    for (let i = start; i <= end; i++) pagination.innerHTML += createPage(i);

    if (currentPage < data.total_pages) pagination.innerHTML += createPage(currentPage + 1, "»");
}

// DOM Ready
document.addEventListener("DOMContentLoaded", () => {
    // Populate initial filters
    populateFilter("year", "SELECT DISTINCT YEAR(created_at) AS options FROM deliveries");
    populateFilter("importproject", "SELECT project_id, project_name AS options FROM projects");
    hideLoading();

    // Helper for chained selects
    const bindDependentFilter = (triggerId, targetId, queryBuilder) => {
        const trigger = document.getElementById(triggerId);
        const target = document.getElementById(targetId);
        trigger.addEventListener("change", () => {
            const value = trigger.value;
            target.disabled = !value;
            if (value) {
                populateFilter(targetId, queryBuilder(value));
            } else {
                target.innerHTML = "";
            }
        });
    };

    // Region → Division
    bindDependentFilter("filterRegion", "filterDivision", region => `
        SELECT DISTINCT s.division AS options 
        FROM schools_project sp 
        JOIN school s ON sp.school_id = s.school_id 
        WHERE s.region='${region}'`);

    // Division → Municipality
    bindDependentFilter("filterDivision", "filterMunicipality", division => `
        SELECT DISTINCT s.municipality AS options 
        FROM schools_project sp 
        JOIN school s ON sp.school_id = s.school_id 
        WHERE s.division='${division}'`);

    // Year → Projects
    bindDependentFilter("year", "filterProjects", year => `
        SELECT DISTINCT p.project_id, p.project_name AS options 
        FROM deliveries d 
        JOIN projects p ON d.project_id = p.project_id 
        WHERE YEAR(d.created_at)='${year}' ORDER BY p.project_id ASC`);

    // Project → Status, Lot, Region
    document.getElementById("filterProjects").addEventListener("change", e => {
        const project_id = e.target.value;
        const statusSelect = document.getElementById("filterStatus");
        statusSelect.disabled = !project_id;

        if (project_id) {
            populateFilter("importlot", `SELECT lot_id as project_id, CONCAT('Lot ', lot_name) as options FROM lot WHERE project_id='${project_id}'`);
            populateFilter("filterStatus", `SELECT DISTINCT CONCAT(UCASE(LEFT(status, 1)), LCASE(SUBSTRING(status, 2))) AS options FROM deliveries WHERE project_id='${project_id}' ORDER BY status ASC`);
            populateFilter("filterRegion", `SELECT DISTINCT s.region AS options FROM schools_project sp JOIN school s ON sp.school_id = s.school_id WHERE project_id='${project_id}'`);
            handleProjectChange(project_id);
        } else {
            statusSelect.innerHTML = "";
        }
    });

    async function handleProjectChange(projectId) {
    const depedDeliveriesDiv = document.getElementById("depedDeliveries");
    const locationFiltersDiv = document.getElementById("locationFilters");

    if (!projectId) {
        if (depedDeliveriesDiv) depedDeliveriesDiv.classList.add("visually-hidden");
        if (locationFiltersDiv) locationFiltersDiv.classList.add("visually-hidden");
        return;
    }

    try {
        const response = await fetch(`script/get_project.php?projectid=${projectId}`);
        const data = await response.json();

        if (data.lots && data.lots.length > 0) {
            const agency = data.lots[0].agency;
            
            if (!agency) {
                if (depedDeliveriesDiv) depedDeliveriesDiv.classList.add("visually-hidden");
                if (locationFiltersDiv) locationFiltersDiv.classList.remove("visually-hidden");
                return;
            }
            
            // Populate lot filter
            const lotSelect = document.getElementById("importlot");
            if (lotSelect) {
                lotSelect.innerHTML = '<option value="">Select Lot</option>';
                data.lots.forEach(lot => {
                    lotSelect.innerHTML += `<option value="${lot.id}">${lot.name}</option>`;
                });
            }

            // Show/hide filters based on agency
            const agencyLower = agency.toLowerCase().trim();
            
            switch (agencyLower) {
                case 'deped':
                case 'department of education':
                    if (depedDeliveriesDiv) depedDeliveriesDiv.classList.remove("visually-hidden");
                    if (locationFiltersDiv) locationFiltersDiv.classList.remove("visually-hidden");
                    break;
                    
                case 'dswd':
                case 'department of social welfare and development':
                    if (depedDeliveriesDiv) depedDeliveriesDiv.classList.add("visually-hidden");
                    if (locationFiltersDiv) locationFiltersDiv.classList.remove("visually-hidden");
                    break;
                    
                default:
                    if (depedDeliveriesDiv) depedDeliveriesDiv.classList.add("visually-hidden");
                    if (locationFiltersDiv) locationFiltersDiv.classList.remove("visually-hidden");
                    break;
            }
            
            // Reset keystage filter
            const keystageSelect = document.getElementById("importkeystage");
            if (keystageSelect) {
                keystageSelect.innerHTML = '<option value="">Select Keystage</option>';
                keystageSelect.disabled = true;
            }
        } else {
            if (depedDeliveriesDiv) depedDeliveriesDiv.classList.add("visually-hidden");
            if (locationFiltersDiv) locationFiltersDiv.classList.remove("visually-hidden");
        }
    } catch (error) {
        console.error('Error fetching project details:', error);
        if (depedDeliveriesDiv) depedDeliveriesDiv.classList.add("visually-hidden");
        if (locationFiltersDiv) locationFiltersDiv.classList.add("visually-hidden");
    }
}


    
    // Import project → Lot → Keystage → File Upload
    bindDependentFilter("importproject", "file_upload_import", project => `
        SELECT lot_id as project_id, lot_name as options FROM lot WHERE project_id='${project}'`);
    bindDependentFilter("importlot", "importkeystage", lot_id => `
        SELECT keystage_id as project_id, CONCAT('Keystage ', keystage_num,' ', description) AS options 
        FROM keystage WHERE lot_id='${lot_id}'`);
    bindDependentFilter("importkeystage", "file_upload_import", () => "");
    

    // Search & filter buttons
    const applyFilters = () => updateTable(1);
    ["searchButton"].forEach(id =>
        document.getElementById(id).addEventListener("click", applyFilters)
    );
    document.getElementById("searchInput").addEventListener("keypress", e => {
        if (e.key === "Enter") applyFilters();
    });
});

