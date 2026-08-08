function registerFormMatchElements(form){

    // get a handle on any button with the class 'button_ref-staffing-issue'
    const buttonsRefStaffingIssues = form.querySelectorAll('.button_ref-staffing-issue');
    // get a handle on any button with the class 'button_match-issue'
    const buttonsMatchIssues = form.querySelectorAll('.button_match-issue');
    const dateSelect = document.getElementById('match-date');
    const timeSelect = document.getElementById('match-time');
    const fieldSelect = document.getElementById('select-field');
    const divisionSelect = document.getElementById('division-select');
    const gameCardPhotos = form.querySelectorAll('.photo_gamecard-pair');
    // Get all buttons that open modals with the class name 'button-userhelper-example'
    const exampleButtons = form.querySelectorAll('.button-userhelper-example');
    const modals = form.querySelectorAll('.modal');
    const closeButtons = form.querySelectorAll('.close');
    const gameDetailsData = JSON.parse(document.getElementById('game-details-data').value);

    registerRefStaffingButtons(buttonsRefStaffingIssues);
    registerMatchIssueButtons(buttonsMatchIssues);
    registerGameCardsPhotoInputs(gameCardPhotos);
    registerExampleButtons(exampleButtons, modals);
    registerModalCloseButtons(closeButtons, modals);
    registerClickOutsideToClose(modals);
    
    dateSelect.addEventListener('change', function() {
        const selectedValues = getAllSelectorValues(dateSelect, timeSelect, fieldSelect, divisionSelect);
        console.log('selectedValues= ', selectedValues);
        updateSelectorValues(selectedValues, gameDetailsData, dateSelect, timeSelect, fieldSelect, divisionSelect);
    });
    timeSelect.addEventListener('change', function() {
        const selectedValues = getAllSelectorValues(dateSelect, timeSelect, fieldSelect, divisionSelect);
        console.log('selectedValues= ', selectedValues);
        updateSelectorValues(selectedValues, gameDetailsData, dateSelect, timeSelect, fieldSelect, divisionSelect);
    });
    fieldSelect.addEventListener('change', function() {
        const selectedValues = getAllSelectorValues(dateSelect, timeSelect, fieldSelect, divisionSelect);
        console.log('selectedValues= ', selectedValues);
        updateSelectorValues(selectedValues, gameDetailsData, dateSelect, timeSelect, fieldSelect, divisionSelect);
    });
    divisionSelect.addEventListener('change', function() {
        const selectedValues = getAllSelectorValues(dateSelect, timeSelect, fieldSelect, divisionSelect);
        console.log('selectedValues= ', selectedValues);
        updateSelectorValues(selectedValues, gameDetailsData, dateSelect, timeSelect, fieldSelect, divisionSelect);
    });
}

function registerExampleButtons(exampleButtons, modals) {
    const modalsArray = Array.from(modals); // Convert NodeList to Array if necessary
    exampleButtons.forEach(exampleButton => {
        exampleButton.addEventListener('click', () => {
            const lastChar = exampleButton.id.slice(-1);
            // Find the modal with the matching ID
            const modalId = 'imageAlert' + lastChar;
            const thisModal = modalsArray.find(modal => modal.id === modalId);
            if (thisModal) {
                thisModal.style.display = "flex";
            } else {
                console.warn(`from register example buttons No modal found with ID: ${modalId}`);
            }
        });
    });
}

function registerModalCloseButtons(closeButtons, modals) {
    closeButtons.forEach(function(closeButton) {
        closeButton.addEventListener('click', function() {
            // Extract the last character from the closeButton ID
            const lastChar = closeButton.id.slice(-1);
            // Construct the modal ID to find
            const modalId = 'imageAlert' + lastChar;
            // Convert NodeList to array and find the matching modal
            let thisModal = Array.from(modals).find(modal => modal.id === modalId);
            if (thisModal) {
                thisModal.style.display = "none";
            } else {
                console.warn(`No modal found with ID: ${modalId}`);
            }
        });
    });
}


function registerClickOutsideToClose(modals) {
    window.addEventListener('click', function(event) {
        modals.forEach(function(modal) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    });
}

// make the ref staffing buttons responsive
function registerRefStaffingButtons(buttonsRefStaffingIssues){
    buttonsRefStaffingIssues.forEach(function(button) {
        button.addEventListener('click', function() {
            const controlsId = this.getAttribute('aria-controls');
            const sectionRefstaffing = document.getElementById(controlsId);
            const textRefstaffing = sectionRefstaffing.querySelector('textarea');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            if (!isExpanded) {
                this.setAttribute('aria-expanded', 'true');
                this.textContent = 'Discard this Issue';
                sectionRefstaffing.hidden = false;
                sectionRefstaffing.style.display = 'flex';
                textRefstaffing.required = true;
            } else {
                this.setAttribute('aria-expanded', 'false');
                this.textContent = 'Report a Referee Staffing Issue';
                sectionRefstaffing.hidden = true;
                sectionRefstaffing.style.display = 'none';
                textRefstaffing.required = false;
                textRefstaffing.value = ''; // Clear textarea if collapsed
            }
        });
    });
}

// make the match buttons responsive
function registerMatchIssueButtons(buttonsMatchIssues){
    buttonsMatchIssues.forEach(function(button) {
        button.addEventListener('click', function() {
            const controlsId = this.getAttribute('aria-controls');
            const sectionMatchIssue = document.getElementById(controlsId);
            const textMatchIssue = sectionMatchIssue.querySelector('textarea');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            if (!isExpanded) {
                this.setAttribute('aria-expanded', 'true');
                sectionMatchIssue.hidden = false;
                sectionMatchIssue.style.display = 'flex';
                textMatchIssue.required = true;
            } else {
                this.setAttribute('aria-expanded', 'false');
                sectionMatchIssue.hidden = true;
                sectionMatchIssue.style.display = 'none';
                textMatchIssue.required = false;
                textMatchIssue.value = ''; // Clear textarea if collapsed
            }
        });
    });
}

function registerGameCardsPhotoInputs(gameCardPhotos){
    gameCardPhotos.forEach(function(input) {
        input.addEventListener('change', function() {
            displayPhoto.call(this); // Ensure 'this' refers to the file input directly, which passing as a parameter would not
        });
    });
}

