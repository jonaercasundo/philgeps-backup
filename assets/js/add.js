function addForm(page, url){
showLoading();
let page2 = page.charAt(0).toUpperCase() + page.slice(1);
let form = document.getElementById("addForm");
let formData = new FormData(form);
let mesg = "&"
if(page == "projects"){
    mesg ="?"
}
fetch("script/"+url, {
        method: "POST",
        body: formData,
        enctype: "multipart/form-data"
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            window.location.href += mesg+"toast="+page2+"%20Added&type=success";
            form.reset();
            // optionally close modal
        } else {
            window.location.href += mesg+"toast="+data.message+"&type=danger";
        }
    })
    .catch(err => {
        console.error("Error:", err)
    })
    .finally(() => {
        hideLoading();
    });
}