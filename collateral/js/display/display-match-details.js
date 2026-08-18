// Formats a "YYYY-MM-DD" value as "MM-DD-YYYY" for display, matching the
// PHP-rendered option text. Done with plain string splitting rather than
// Date parsing so it can't drift a day due to timezone conversion.
function formatMatchDateForDisplay(isoDate){
    const [year, month, day] = isoDate.split('-');
    return `${month}-${day}-${year}`;
}

// Formats a zero-padded 24-hour "H:i" value (e.g. "17:00") as 12-hour
// AM/PM (e.g. "5:00 PM") for display, matching the PHP-rendered option
// text. The select's value/sort key stay 24-hour - only the label changes.
function formatMatchTimeForDisplay(time24){
    const [hourStr, minute] = time24.split(':');
    let hour = parseInt(hourStr, 10);
    const period = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    if (hour === 0) { hour = 12; }
    return `${hour}:${minute} ${period}`;
}

// Drives the four "Match Details" selects (date/time/field/division) as one
// cascading, mutually-filtering picker over the match rows embedded in
// #game-details-data:
//   - each select's own option list is recomputed from whichever rows are
//     still consistent with the OTHER three selects' current choices
//     (never its own choice, so a select can always be changed without
//     first resetting it back to its placeholder)
//   - resetting a select back to its placeholder removes that constraint,
//     so the other selects' option lists expand back out
//   - if a select is still unset and only one option remains for it, it's
//     auto-selected
//   - options are always deduped and ordered per DIMENSIONS below (field
//     and division in their table's row order, date/time ascending)
function registerMatchDetailSelectorCascade(dateSelect, timeSelect, fieldSelect, divisionSelect, gameDetailsData){
    const NONE = 'none';

    const DIMENSIONS = [
        {
            key: 'match_date',
            select: dateSelect,
            placeholder: 'Select a Date',
            getValue: row => row.match_date,
            getLabel: row => formatMatchDateForDisplay(row.match_date),
            getSortKey: row => row.match_date
        },
        {
            key: 'formatted_match_time',
            select: timeSelect,
            placeholder: 'Select a Time',
            getValue: row => row.formatted_match_time,
            getLabel: row => formatMatchTimeForDisplay(row.formatted_match_time),
            getSortKey: row => row.formatted_match_time
        },
        {
            key: 'field_number',
            select: fieldSelect,
            placeholder: 'Select a field',
            getValue: row => 'field' + row.field_number,
            getLabel: row => row.field_name,
            getSortKey: row => Number(row.field_row_id)
        },
        {
            key: 'division_number',
            select: divisionSelect,
            placeholder: 'Select a Division',
            getValue: row => 'division' + row.division_number,
            getLabel: row => row.division_name,
            getSortKey: row => Number(row.division_row_id)
        }
    ];

    function currentValue(dim){
        const value = dim.select.value;
        return (!value || value === NONE) ? null : value;
    }

    function rowMatchesOtherDimensions(row, exceptKey){
        return DIMENSIONS.every(dim => {
            if (dim.key === exceptKey) { return true; }
            const selected = currentValue(dim);
            return selected === null || dim.getValue(row) === selected;
        });
    }

    // Distinct, ordered {value, label} options for one dimension, given
    // the other three dimensions' current selections.
    function computeAvailableOptions(dim){
        const seen = new Map();
        gameDetailsData.forEach(row => {
            if (!rowMatchesOtherDimensions(row, dim.key)) { return; }
            const value = dim.getValue(row);
            if (!seen.has(value)) {
                seen.set(value, { value: value, label: dim.getLabel(row), sortKey: dim.getSortKey(row) });
            }
        });
        return Array.from(seen.values()).sort((a, b) => {
            if (a.sortKey < b.sortKey) { return -1; }
            if (a.sortKey > b.sortKey) { return 1; }
            return 0;
        });
    }

    // Rebuilds a select's <option> list, keeping its current value selected
    // if it's still available, otherwise resetting it to the placeholder.
    function rebuildOptions(dim, options){
        const previousValue = dim.select.value;

        dim.select.innerHTML = '';
        const placeholderOption = document.createElement('option');
        placeholderOption.value = NONE;
        placeholderOption.textContent = dim.placeholder;
        dim.select.appendChild(placeholderOption);

        options.forEach(opt => {
            const optionEl = document.createElement('option');
            optionEl.value = opt.value;
            optionEl.textContent = opt.label;
            dim.select.appendChild(optionEl);
        });

        dim.select.value = options.some(opt => opt.value === previousValue) ? previousValue : NONE;
    }

    // These 4 selects are required, but they always submit a value - even
    // before the ref picks anything - because the placeholder option's
    // value is "none" rather than an empty string. Native HTML5
    // required-checking only catches an empty-string placeholder, so track
    // an explicit custom validity alongside the placeholder ourselves.
    function updatePlaceholderValidity(dim){
        dim.select.setCustomValidity(dim.select.value === NONE ? 'Please make a selection.' : '');
    }

    // Recomputes every dimension's options against the others' current
    // selections, auto-selects any dimension left with exactly one
    // possible option, and repeats until nothing changes (bounded, since
    // there are only 4 dimensions to settle).
    function recompute(){
        for (let pass = 0; pass < DIMENSIONS.length + 1; pass++) {
            let changedThisPass = false;

            DIMENSIONS.forEach(dim => {
                const valueBefore = dim.select.value;
                const options = computeAvailableOptions(dim);
                rebuildOptions(dim, options);

                if (dim.select.value === NONE && options.length === 1) {
                    dim.select.value = options[0].value;
                }

                updatePlaceholderValidity(dim);

                if (dim.select.value !== valueBefore) {
                    changedThisPass = true;
                }
            });

            if (!changedThisPass) {
                break;
            }
        }
    }

    DIMENSIONS.forEach(dim => {
        dim.select.addEventListener('change', function(){
            recompute();
            // Let the rest of the form (submit-button state, required-field
            // border colors) react to whatever the cascade just changed.
            dim.select.form.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    recompute();
}

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

    registerMatchDetailSelectorCascade(dateSelect, timeSelect, fieldSelect, divisionSelect, gameDetailsData);
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

