function truncateText(text, maxLength = 50) {
  return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
}

function populateFilter(element, query) {
showLoading()
    fetch("script/populateFilter.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "query=" + encodeURIComponent(query)
    })
    .then(res => res.json())
    .then(data => {
        let filter = document.getElementById(element);
        filter.innerHTML = "<option value=''>-- Select --</option>"; // reset default option

        if (data.length > 0) {
            data.forEach(row => {
                // 👇 this might break if "options" key doesn't exist
                if (!row.options) {
                    console.warn("Missing 'options' in row:", row);
                }
                let option = document.createElement("option");
                if(row.project_id){
                option.value = row.project_id || "";
                }else{
                option.value = row.options || "";
                }
                option.textContent = truncateText(row.options) || "N/A";
                filter.appendChild(option);
            });
        }
    })
    .catch(err => {
        console.error("Error while fetching:", err)
    })
    .finally(() => {
        hideLoading();
    });
}


document.getElementById("rmvFilter").addEventListener("click", function() {
    showLoading();
    let filters = document.getElementsByClassName("filter");
    //for none disabled
    let filter = document.getElementsByClassName("filters");

    Array.from(filters).forEach((element, index) => {
        element.value = "";
        if (index === 0) return; // skip first element
        element.disabled = true;
        element.value = ""; // optional reset
    });

    Array.from(filter).forEach((element, index) => {
        element.value = "";
        if (index === 0) return; // skip first element
        element.value = ""; // optional reset
    });
hideLoading();
});
