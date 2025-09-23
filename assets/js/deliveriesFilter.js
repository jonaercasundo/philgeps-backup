
// Helper: get selected filters
function getFilters() {
    let year = document.getElementById("year").value;
    let project = document.getElementById("filterProjects").value;
    let status = document.getElementById("filterStatus").value;
    let region = document.getElementById("filterRegion").value;
    let division = document.getElementById("filterDivision").value;
    let municipality = document.getElementById("filterMunicipality").value;
    let lot = document.getElementById("importlot").value;
    let keystage = document.getElementById("importkeystage").value;
    let search = document.getElementById("searchInput").value.trim();

    let params = new URLSearchParams();
    if(year) params.append("year", year);
    if(project) params.append("project_id", project);
    if(status) params.append("status", status);
    if(region) params.append("region", region);
    if(division) params.append("division", division);
    if(municipality) params.append("municipality", municipality);
    if(lot) params.append("lot_id", lot);
    if(keystage) params.append("keystage_id", keystage);
    if(search) params.append("search", search);

    return params.toString();
}


// Update table via AJAX
function updateTable(page = 1) {
    let tbody = document.getElementById("resultTable");
    tbody.innerHTML = "";
    showLoading();
    fetch("script/filterDeliveries.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: getFilters() + "&page=" + page + "&limit=10"
    })
    .then(res => res.json())
    .then(data => {
        if(data.rows && data.rows.length){
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
            `; 
            tbody.innerHTML += data.rows.map(row => `
                
                <tbody>
                    <tr>
                        <td>${row.school_name}</td>
                        <td>${row.address}</td>
                        <td>${row.items_contents}</td>
                        <td>${row.dr_no}</td>
                        <td>${row.delivery_date}</td>
                        <td>
                            <button class="btn btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#editDeliveryModal"
                                data-id="${row.delivery_id}" data-project="${row.project_name}" data-school_name="${row.school_name}"
                                data-address="${row.address}" data-items_contents="${row.items_contents}" data-drno="${row.dr_no}"
                                data-date="${row.delivery_date}" data-status="${row.status}">Edit</button>
                            <a class="btn btn-sm btn-success" href="generate_qr.php?id=${row.delivery_id}" target="_blank">
                                QR
                            </a>
                        </td>
                    </tr>
                </tbody>
                `).join("");
        }
        renderPagination(data, page);
    })
    .finally(() => hideLoading());
}

// Pagination rendering
function renderPagination(data, currentPage) {
    let pagination = document.getElementById("pagination");
    pagination.innerHTML = "";
    if(!data.total_pages || data.total_pages <= 1) return;

    const createPage = (i, text = i) => `
        <li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="updateTable(${i})">${text}</a>
        </li>`;

    pagination.innerHTML += createPage(currentPage-1, "«");
    let start = Math.max(1, currentPage-4);
    let end = Math.min(data.total_pages, start+7);
    for(let i=start; i<=end; i++) pagination.innerHTML += createPage(i);
    pagination.innerHTML += createPage(currentPage+1, "»");
}

// Event listeners
document.getElementById("searchButton").addEventListener("click", () => updateTable(1));
document.getElementById("searchInput").addEventListener("keypress", e => { if(e.key==="Enter") updateTable(1); });


document.addEventListener("DOMContentLoaded", function () {
    // Populate Year filter
    populateFilter("year", "SELECT DISTINCT YEAR(created_at) AS options FROM deliveries ORDER BY created_at ASC");
    populateFilter("importproject",
                `SELECT project_id, project_name AS options 
                 FROM projects`);
    hideLoading();

    const yearSelect = document.getElementById("year");
    const projectSelect = document.getElementById("filterProjects");
    const statusSelect = document.getElementById("filterStatus");
    const filterRegion = document.getElementById("filterRegion");
    const filterDivision = document.getElementById("filterDivision");
    const filterMunicipality = document.getElementById("filterMunicipality");
    const importproject = document.getElementById("importproject");
    const importlot = document.getElementById("importlot");
    const importkeystage = document.getElementById("importkeystage");
    const file_upload_import = document.getElementById("file_upload_import");
    
    // When Year changes → enable & populate Project
    filterRegion.addEventListener("change", () => {
        let region = filterRegion.value;
        filterDivision.disabled = !year;
        filterMunicipality.disabled = true;
        if (region) {
            populateFilter(
                "filterDivision",
                `SELECT DISTINCT s.division AS options 
                 FROM schools_project sp 
                 JOIN school s ON sp.school_id = s.school_id 
                 WHERE s.region='${region}'`
            );
        } else {
            projectSelect.innerHTML = ""; 
            statusSelect.innerHTML = ""; 
        }
    });

    // When Year changes → enable & populate Project
    filterDivision.addEventListener("change", () => {
        let division = filterDivision.value;
        filterMunicipality.disabled = !year;
        if (division) {
            populateFilter(
                "filterMunicipality",
                `SELECT DISTINCT s.municipality AS options 
                 FROM schools_project sp 
                 JOIN school s ON sp.school_id = s.school_id 
                 WHERE s.division='${division}'`
            );
        } else {
            projectSelect.innerHTML = ""; 
            statusSelect.innerHTML = ""; 
        }
    });

    // When Year changes → enable & populate Project
    yearSelect.addEventListener("change", () => {
        let year = yearSelect.value;
        projectSelect.disabled = !year;
        statusSelect.disabled = true;
        if (year) {
            populateFilter(
                "filterProjects",
                `SELECT DISTINCT p.project_id, p.project_name AS options 
                 FROM deliveries d 
                 JOIN projects p ON d.project_id = p.project_id 
                 WHERE YEAR(d.created_at)='${year}' ORDER BY p.project_id ASC`
            );
        } else {
            projectSelect.innerHTML = ""; 
            statusSelect.innerHTML = ""; 
        }
    });

    // When Project changes → enable & populate Status
    projectSelect.addEventListener("change", () => {
        let project_id = projectSelect.value;
        statusSelect.disabled = !project_id;
        if (project_id) {
            populateFilter(
                "importlot",
                `SELECT lot_id as project_id, CONCAT('Lot ', lot_name) as options FROM lot WHERE project_id='${project_id}'`
            );
            populateFilter(
                "filterStatus",
                `SELECT DISTINCT status AS options FROM deliveries WHERE project_id='${project_id}' ORDER BY status ASC`
            );
            populateFilter(
                "filterRegion",
                `SELECT DISTINCT s.region AS options FROM schools_project sp JOIN school s ON sp.school_id = s.school_id WHERE project_id='${project_id}'`
            );
        } else {
            statusSelect.innerHTML = "";
        }
    });


    // When importproject changes → enable & populate importlot
    importproject.addEventListener("change", () => {
        let project = importproject.value;
        importlot.disabled = !project;
        importkeystage.disabled = true;
        file_upload_import.disabled = true;
        if (project) {
            populateFilter(
                "importlot",
                `SELECT lot_id as project_id, lot_name as options
                 FROM lot
                 WHERE project_id='${project}'`
            );
        } else {
            importlot.innerHTML = ""; 
            importkeystage.innerHTML = ""; 
        }
    });

    // When importlot changes → enable & populate importkeystage
    importlot.addEventListener("change", () => {
        let lot_id = importlot.value;
        importkeystage.disabled = !lot_id;
        file_upload_import.disabled = true;
        if (lot_id) {
            populateFilter(
                "importkeystage",
                `SELECT keystage_id as project_id, CONCAT('Keystage ', keystage_num,' ', description) AS options FROM keystage WHERE lot_id='${lot_id}'`
            );
        } else {
            importkeystage.innerHTML = "";
        }
    });

        // When importkeystage changes → enable Upload of File
    importkeystage.addEventListener("change", () => {
        let keystage_id = importkeystage.value;
        file_upload_import.disabled = !keystage_id;
    });
    // Filter & search triggers
    const filterButton = document.getElementById("filterButt");
    const searchButton = document.getElementById("searchButton");
    const searchInput = document.getElementById("searchInput");

    const applyFilters = () => updateTable(1);

    filterButton.addEventListener("click", applyFilters);
    searchButton.addEventListener("click", applyFilters);
    searchInput.addEventListener("keypress", e => { if(e.key==="Enter") applyFilters(); });
});
