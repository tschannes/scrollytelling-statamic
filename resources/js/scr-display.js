function showElement(element) {
    document.getElementById(element).classList.remove("hide-element");
}
function hideElement(element) {
    document.getElementById(element).classList.add("hide-element");
}

function hideByClassName(element) {
    var elements = document.getElementsByClassName(element);
    // Convert HTMLCollection to an array
    var elementsArray = Array.from(elements);
    elementsArray.forEach(function(e) {
        e.classList.add("hide-element");
    });
}

function toggleForm(element1, element2) {
    document.getElementById(element1).style.display = "flex";
    document.getElementById(element2).style.display = "none";
}

function focusOnField(element) {
    var inputElement = document.getElementById(element);
    if (inputElement) {
        // Set selection range to the end of the text
        inputElement.setSelectionRange(inputElement.value.length, inputElement.value.length);
        // Focus on the input field
        inputElement.focus();
    }

}

function toggleDropdown(element) {
    document.getElementById(element).classList.toggle("show-element");
}

function toggleTabs(element) {
    removeClassFromElements("show-tab", element)
    document.getElementById(element).classList.toggle("show-tab");
}

function removeClassFromElements(className, exceptionId) {
    // Select all elements with the specified class
    const elements = document.querySelectorAll('.' + className);

    elements.forEach(element => {
        // If the element's ID is not the exception, remove the class
        if (element.id !== exceptionId) {
            element.classList.remove(className);
        }
    });
}


// == Overflow Menus ==

// Hide all overflow menus
function hideAllOverflowMenus() {
    document.querySelectorAll('.overflow-menu').forEach(menu => {
        menu.classList.add('hide-element');
    });
}

document.addEventListener('click', function (event) {
    const clickedMoreOptions = event.target.closest('.more-options');
    const clickedOverflowMenu = event.target.closest('.overflow-menu');

    // Clicked outside any menu -> hide all
    if (!clickedMoreOptions && !clickedOverflowMenu) {
        hideAllOverflowMenus();
        return;
    }

    // Clicked on a "more-options" button
    if (clickedMoreOptions) {
        const parent = clickedMoreOptions.parentElement;
        const menu = parent.querySelector('.overflow-menu');

        if (!menu) return;

        const isHidden = menu.classList.contains('hide-element');
        hideAllOverflowMenus(); // hide others first

        if (isHidden) {
            menu.classList.remove('hide-element'); // show current
        }
    }
});
