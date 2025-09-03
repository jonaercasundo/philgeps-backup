function fetchData(page, columns) {
    showLoading();
    let search = document.getElementById("search").value;

    fetch("script/search.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "page=" + page + "&columns=" + JSON.stringify(columns) + "&search=" + search
    })
    .then(res => res.json())
    .then(data => {
        let tbody = document.getElementById("resultTable");
        tbody.innerHTML = "";

        let pagination = document.getElementById("pagination");
        pagination.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center">No results found</td></tr>`;
            return;
        }

        switch(page){
            case "school":
                data.forEach(row => {
                            tbody.innerHTML += `
                                <tr>
                                    <td id="id${row.school_id}s">${row.school_id}</td>
                                    <td id="name${row.school_id}s">${row.school_name}</td>
                                    <td>
                                    <span id="address${row.school_id}s">${row.address}</span>, 
                                    <span id="municipality${row.school_id}s">${row.municipality}</span>, 
                                    <span id="division${row.school_id}s">${row.division}</span>, 
                                    <span id="region${row.school_id}s">${row.region}</span>
                                    </span></td>
                                    <td id="person${row.school_id}s">${row.contact_person}</td>
                                    <td id="contact${row.school_id}s">${row.contact}</td>
                                    <td>
                                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" onclick="updateEdit(${row.school_id})">Edit School</button>
                                    <a href="delete_lot.php?id=<?= $lot['lot_id'] ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this School?')">Delete</a></td>
                                </tr>
                            `;
                        })
            
            break;
        }
    })
    .finally(() => {
        hideLoading();
    });
}
