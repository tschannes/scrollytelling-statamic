function simulateClickOnEnter(elementId) {
    var element = document.getElementById(elementId);
    if (element) {
        element.click();
    }
}

// Function to simulate click on the currently focused element when Enter key is pressed
function simulateClickOnTabEnter(event) {
    // Check if the Enter key is pressed
    if (event.key === 'Enter') {
        // Find the currently focused element
        var focusedElement = document.activeElement;
        // Check if the focused element has tabindex="0" and is clickable (e.g., <a>, <button>, <img>, etc.)
        if (focusedElement.tabIndex === 0 && typeof focusedElement.click === 'function') {
            // Simulate click on the focused element
            focusedElement.click();
        }
    }
}

function clickOnElement(element) {
    document.getElementById(element).click();
}

function updateInputValue(element, content) {
    document.getElementById(element).value = content;
}

// Attach event listener for keydown event on the document
document.addEventListener('keydown', simulateClickOnTabEnter);


// Download Comic
function downloadComic(comicName) {
    console.log(comicName);

    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                // Create a blob from the response
                var blob = new Blob([xhr.response], { type: 'application/zip' });
                var url = window.URL.createObjectURL(blob);
                
                // Create a link element to download the file
                var link = document.createElement('a');
                link.href = url;
                link.download = comicName + '.zip'; // Set the filename
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url); // Clean up
                console.log("Comic downloaded successfully.");
            } else {
                alert("Error while downloading comic.");
            }
        }
    };

    xhr.open("POST", "functions/fun-export-comic.php", true); // Replace with the path to your PHP script
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.responseType = 'blob'; // Expect a binary response
    xhr.send("comicName=" + encodeURIComponent(comicName));
}


// Toggle public state of comic 
function togglePublicState(comicName, publicState) {
    var setPublicState = (publicState === "public") ? true : false; // Determine the public state
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = xhr.responseText;
            if (response === "success") {
                console.log("Comic updated successfully.");
                location.reload();
            } else {
                console.log("Error while updating comic.");
            }
        }
    };
    
    xhr.open("POST", "functions/fun-update-public-state.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.send("comicName=" + encodeURIComponent(comicName) + "&publicswitch=" + setPublicState);
}


// Duplicate comic 
function duplicateComic(comicName) {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = xhr.responseText;
            if (response === "success") {
                console.log("Comic updated successfully.");
                location.reload();
            } else {
                console.log("Error while updating comic.");
            }
        }
    };
    
    xhr.open("POST", "functions/fun-duplicate-comic.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.send("comicName=" + encodeURIComponent(comicName));
}