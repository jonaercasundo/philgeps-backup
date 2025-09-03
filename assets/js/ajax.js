//to search school WHERE project_id
document.getElementById("schoolSearch").addEventListener("keyup", function() {
    
    let query = this.value.trim();
    let resultBox = document.getElementById("schoolResults");
    let project_id = document.getElementById("project").value
    if (query.length < 2) {
        resultBox.innerHTML = "";
        return;
    }
    fetch("script/get_school.php?id="+project_id+"&q=" + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            showLoading();
            resultBox.innerHTML = "";
            if (data.length === 0) {
                resultBox.innerHTML = `<li class="list-group-item">No results found</li>`;
                return;
            }

            data.forEach(school => {
                let li = document.createElement("li");
                li.className = "list-group-item list-group-item-action";
                li.textContent = school.school_id+" "+school.school_name;
                li.dataset.value = school.school_id;
                
                li.onclick = function() {
                    document.getElementById("schoolSearch").value = school.school_id+" "+school.school_name;
                    document.getElementById("address").value = school.address;
                    resultBox.innerHTML = "";
                };
                
                resultBox.appendChild(li);
            });
        })
        .catch(err => {
    console.error("Fetch error:", err);
    resultBox.innerHTML = `<li class="list-group-item text-danger">Error loading schools</li>`;
  })
  .finally(() => {
    hideLoading();
  });
});

function getKeystage(lot){
  showLoading();
     fetch("script/get_keystage.php?lotid=" + encodeURIComponent(lot))
      .then(res => res.json())
      .then(data => {
        populateSelect("keystageSelect", data.keystages);
      })
      .catch(err => console.error("Error:", err));
      hideLoading()
}

    // Helper to fill select with options
  function populateSelect(selectId, items) {
    showLoading();
    let select = document.getElementById(selectId);
    select.innerHTML = ""; // clear old options

    if (!items || items.length === 0) {
      select.innerHTML = "<option value=''>No data available</option>";
      return;
    }

    items.forEach(item => {
      // item should have `id` and `name`
      let option = document.createElement("option");
      option.value = item.id;
      option.textContent = item.name;
      select.appendChild(option);
    });
    hideLoading()
  }