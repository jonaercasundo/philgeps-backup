
// Helper: get selected filters
function getFilters() {
    let year = document.getElementById("year").value;
    let project = document.getElementById("filterProjects").value;
    let status = document.getElementById("filterStatus").value;
    let search = document.getElementById("searchInput").value.trim();
    let params = new URLSearchParams();
    if(year) params.append("year", year);
    if(project) params.append("project_id", project);
    if(status) params.append("status", status);
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
            tbody.innerHTML = data.rows.map(row => `
                <tr>
                    <td>${truncateText(row.project_name)}</td>
                    <td>${row.school}</td>
                    <td>${row.address}</td>
                    <td>${row.remarks}</td>
                    <td>${row.dr_no}</td>
                    <td>${row.delivery_date}</td>
                    <td>${row.status}</td>
                    <td>
                        <button class="btn btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#editDeliveryModal"
                            data-id="${row.delivery_id}" data-project="${row.project_name}" data-school="${row.school}"
                            data-address="${row.address}" data-remarks="${row.remarks}" data-drno="${row.dr_no}"
                            data-date="${row.delivery_date}" data-status="${row.status}">Edit</button>
                        <a class="btn btn-sm btn-success" href="generate_qr.php?id=${row.delivery_id}" target="_blank">QR</a>
                    </td>
                </tr>`).join("");
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
document.getElementById("filterButt").addEventListener("click", () => updateTable(1));
document.getElementById("searchButton").addEventListener("click", () => updateTable(1));
document.getElementById("searchInput").addEventListener("keypress", e => { if(e.key==="Enter") updateTable(1); });

// Populate filters
document.addEventListener("DOMContentLoaded", function () {
    populateFilter("year", "SELECT DISTINCT YEAR(created_at) AS options FROM deliveries ORDER BY created_at ASC");
    hideLoading();
});

document.addEventListener("DOMContentLoaded", function () {
    // Populate Year filter
    populateFilter("year", "SELECT DISTINCT YEAR(created_at) AS options FROM deliveries ORDER BY created_at ASC");
    hideLoading();

    const yearSelect = document.getElementById("year");
    const projectSelect = document.getElementById("filterProjects");
    const statusSelect = document.getElementById("filterStatus");

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
                "filterStatus",
                `SELECT DISTINCT status AS options FROM deliveries WHERE project_id='${project_id}' ORDER BY status ASC`
            );
        } else {
            statusSelect.innerHTML = "";
        }
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
