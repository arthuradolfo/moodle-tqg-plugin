function tqg_checkall(element) {
    $(element).parent()
        .parent()
        .parent()
        .find('input[type="checkbox"]').each( function(id, child) {
            if(element == child) return;
            if(child.checked) {
                child.checked = false;
            }
            else {
                child.checked = true;
            }
        }
    );
}