// update Background
function uploadBackground(element) {
	var form = document.getElementById(element);
	var formData = new FormData(form);

	var xhr = new XMLHttpRequest();
	xhr.open("POST", "functions/fun-add-background.php", true);

	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4 && xhr.status === 200) {
			// After successful upload, reset the form and refresh the image list
			form.reset();
			location.reload();
		}
	};

	xhr.send(formData);
}


function updateImage(element) {
    var form = document.getElementById(element);
    var formData = new FormData(form);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "functions/fun-update-image.php", true);

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // After successful upload, reset the form and refresh the image list
            form.reset();
            // Parse the response from PHP
            var response = xhr.responseText;
            // Check if the response contains the redirect URL
            if (response.startsWith("redirect:")) {
                // Extract the URL from the response
                var redirectUrl = response.split("redirect:")[1];
                // Redirect the user to the specified URL
                window.location.href = redirectUrl;
            } else {
                // Handle other responses here if needed
                console.log("Unexpected response: " + response);
            }
        }
    };

    xhr.send(formData);
}


