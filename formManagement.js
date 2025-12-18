document.addEventListener('DOMContentLoaded', function() {
    // get a handle on the form
    const form = document.getElementById('form_game-results-entry');
    const loadingMessage = document.getElementById('loadingMessage');
    if (form) {
        registerFormMatchElements(form);
        registerFormSanctionElements(form);
        form.addEventListener('submit', function(event) {
            // Display the loading message
            loadingMessage.hidden = false;
            form.style.display = 'none';
        });
    } else {
        console.warn('Form with ID "form_game-results-entry" not found.');
    }
});