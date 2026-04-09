// Function to delete a comic via AJAX
function deleteComic(comicDirectory) {
    if (confirm("Sind Sie sicher, dass Sie diesen Comic löschen möchten?")) {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = xhr.responseText;
                if (response === "success") {
                    window.location.href = "list.php";
                } else {
                    alert("Fehler beim Löschen des Comics.");
                }
            }
        };
        
        xhr.open("POST", "functions/fun-delete-comic.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.send("deleteComicDirectory=" + comicDirectory); // Ensure the parameter name matches
    }
}

// Function to delete a branch via AJAX
function deleteBranch(comicName, branchId) {
    if (confirm("Are you sure you want to delete this branch?")) {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    var response = xhr.responseText;
                    if (response === "Branch successfully deleted.") {
                        alert("Branch was successfully deleted.");
                        // Redirect to the list.php page after successful deletion
                        window.location.reload();
                    } else {
                        alert("Error deleting the branch.");
                    }
                } else {
                    alert("Error: " + xhr.status);
                }
            }
        };

        xhr.open("POST", "functions/fun-delete-branch.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.send("comicName=" + encodeURIComponent(comicName) + "&branchId=" + encodeURIComponent(branchId));
    }
}

// function to delete an image
function deleteImage(comicName, imageName, branchId) {
    if (confirm("Are you sure you want to delete this image?")) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "functions/fun-delete-image.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                window.open("edit.php?comic=" + comicName + "&branch=" + branchId, "_self");
            }
        };

        var params = "comicName=" + comicName + "&imageName=" + imageName + "&branchId=" + branchId;

        console.log(params);

        xhr.send(params);
    }
}