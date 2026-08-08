// Define your functions outside the DOMContentLoaded event listener
function getAllSelectorValues(dateSelect, timeSelect, fieldSelect, divisionSelect) {
    const matchdata = [];

    if (dateSelect.value !== 'none') {
        matchdata.push({ key: 'match_date', value: dateSelect.value });
    }
    if (timeSelect.value !== 'none') {
        matchdata.push({ key: 'formatted_match_time', value: timeSelect.value });
    }
    if (fieldSelect.value !== 'none') {
        const fieldNumString = fieldSelect.value.split('field')[1];
        console.log('fieldNumString: ', fieldNumString);
        const fieldNumber = parseInt(fieldNumString, 10);
        console.log('fieldNumber:', fieldNumber);
        // Get the selected option
        const selectedFieldName = fieldSelect.options[fieldSelect.selectedIndex];
        console.log('selectedFieldName:', selectedFieldName);
        // Get the text of the selected option
        const selectedFieldText = selectedFieldName.text;
        matchdata.push({ key: 'field_number', value: fieldNumber });
    }
    if (divisionSelect.value !== 'none') {
        const divisionNumber = parseInt(divisionSelect.value.split('division')[1], 10);
        matchdata.push({ key: 'division_number', value: divisionNumber });
    }
    console.log('Selected values set:', matchdata);
    return matchdata;
}

/* function eliminateDuplicatesByKeys(workingSet, keyOrder) {
    let refinedSet = workingSet;
    console.log('keyOrder:', keyOrder);
    console.log('key Order length:', keyOrder.length);
    // Iterate over each key in the specified order
    keyOrder.forEach(key => {
        console.log('key is: ', key);
        const uniqueEntriesMap = new Map();

        refinedSet.forEach(item => {
            console.log('krefinedSet: ', refinedSet);
            console.log('going through this too');
            // Create a unique key based on the current key
            const uniqueKey = item[key];

            // If this uniqueKey is not already in the map, add it
            if (!uniqueEntriesMap.has(uniqueKey)) {
                uniqueEntriesMap.set(uniqueKey, item);
            }
        });

        // Update refinedSet to only include the unique entries so far
        refinedSet = [...uniqueEntriesMap.values()];
    });
    console.log('refinedSet: ', refinedSet);
    return refinedSet;
} */

function eliminateDuplicatesByKeyValue(workingSet, selectedValues) {

    // Create a Set of unique entries based on the remaining keys
    const uniqueEntries = filteredSet.reduce((acc, item) => {
        // Generate a unique key based on the filteredSelectOptionKeys
        const keyForProperFilter = filteredSelectOptionKeys.map(key => item[key]).join('-');

        if (!acc.has(keyForProperFilter)) {
            acc.set(keyForProperFilter, item);
        }
        return acc;
    }, new Map()).values();

    const result = Array.from(uniqueEntries);

    console.log(result);

    // Convert the map values to an array of objects
    return result;
}

function filterUniqueItems(items, uniqueKey) {
    // Create a Map to store unique items based on the specified key
    const uniqueItemsMap = new Map();

    items.forEach(item => {
        // Generate a unique key based on the specified key
        const keyForFilter = item[uniqueKey];

        // Add the item to the Map if it's not already present
        if (!uniqueItemsMap.has(keyForFilter)) {
            uniqueItemsMap.set(keyForFilter, item);
        }
    });

    // Convert the Map values to an array of objects
    return Array.from(uniqueItemsMap.values());
}

function getUniqueValuesByKey(workingSet, key) {
    const uniqueValues = workingSet
        .map((item) => item[key])
        .filter((value, index, currentValues) => currentValues.indexOf(value) === index);
        console.log('uniqueValues for selectupdates '+key+' = ', uniqueValues);
    return uniqueValues;
}

function getUniqueDoubleValues(workingSet, filter1, filter2) {
const uniqueValues = workingSet

// Create a Set to store unique key-value combinations
const uniqueItems = new Set();

// Iterate over each item in the array
workingSet.forEach(item => {
    // Create a unique key by combining filter1 and filter2 values
    const keyValuePair = `${item[filter1]}-${item[filter2]}`;

    // Add the keyValuePair to the Set (duplicates will be ignored)
    uniqueItems.add(keyValuePair);
});

console.log('doublevalues for selectupdates '+filter1+' & '+filter2+' = ', uniqueValues);
// Convert the Set back to an array of objects with filter1 and filter2
return Array.from(uniqueItems).map(pair => {
    const [key, value] = pair.split('-');
    return {
        [filter1]: key,
        [filter2]: value
    };
});
}

function fieldSelectorDisplay(gamePickerOptions, dbFieldNumber = null) {
// Extract unique field_number and field_name pairs
const uniqueFields = [...new Set(gamePickerOptions.map(field => 
    `${field.field_number}|${field.field_name}`
))];

// Convert back to an array of objects
const uniqueFieldOptions = uniqueFields.map(uniqueField => {
    const [fieldNumber, fieldName] = uniqueField.split('|');
    return { field_number: fieldNumber, field_name: fieldName };
});

// Create the section and select elements
const sectionElement = document.createElement('section');
sectionElement.id = 'section_location-entry';
sectionElement.className = 'section-match-entry';

const labelElement = document.createElement('label');
labelElement.setAttribute('for', 'select_location');
labelElement.id = 'label_location-select';
labelElement.textContent = 'Field:';
sectionElement.appendChild(labelElement);

const selectElement = document.createElement('select');
selectElement.name = 'matchSelection';
selectElement.id = 'select_location';

// Add an empty option for "Select a field"
const emptyOption = document.createElement('option');
emptyOption.value = 'none';
emptyOption.textContent = 'Select a Field';
if (dbFieldNumber === null) {
    emptyOption.selected = true;
}
selectElement.appendChild(emptyOption);

// Iterate over the unique options and create <option> elements
uniqueFieldOptions.forEach(field => {
    const fieldNumber = field.field_number.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    const fieldName = field.field_name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    const optionElement = document.createElement('option');
    optionElement.value = `field${fieldNumber}`;
    optionElement.textContent = fieldName;
    if (fieldNumber === dbFieldNumber) {
        optionElement.selected = true;
    }
    selectElement.appendChild(optionElement);
});

// Append the select element to the section
sectionElement.appendChild(selectElement);

// Append the section to the body or desired container
document.body.appendChild(sectionElement);
}

function updateSelectorValues(selectedValues, gameDetailsData, dateSelect, timeSelect, fieldSelect, divisionSelect) {
console.log('Game Details Data:', gameDetailsData);
console.log('Selected values sent:', selectedValues);

let workingSet = [...gameDetailsData];

if(workingSet.length > 1){
    // Filter workingSet based on selected values
    selectedValues.forEach(selection => {
        if (selection.key === 'match_date') {
            workingSet = workingSet.filter(item => item.match_date === selection.value);
            console.log('workingSet 1: ', workingSet);
        }
        if (selection.key === 'formatted_match_time') {
            workingSet = workingSet.filter(item => item.formatted_match_time === selection.value);
            console.log('workingSet 2: ', workingSet);
        }
        if (selection.key === 'division_number') {
            workingSet = workingSet.filter(item => Number(item.division_number) === selection.value);
            console.log('workingSet 3: ', workingSet);
        }
        if (selection.key === 'field_number') {
            workingSet = workingSet.filter(item => Number(item.field_number) === selection.value);
            console.log('workingSet 4: ', workingSet);
        }
    });
}
console.log('workingSet done: ', workingSet);    

updateSelectOptions(dateSelect, getUniqueValuesByKey(workingSet, 'match_date'), selectedValues.find(sel => sel.key === 'match_date')?.value, 'Select a Date');
updateSelectOptions(timeSelect, getUniqueValuesByKey(workingSet, 'formatted_match_time'), selectedValues.find(sel => sel.key === 'formatted_match_time')?.value, 'Select a Time');
updateSelectOptions(divisionSelect, getUniqueDoubleValues(workingSet, 'division_number', 'division_name'), selectedValues.find(sel => sel.key === 'division_number')?.value, 'Select a Division');       
updateSelectOptions(fieldSelect, getUniqueDoubleValues(workingSet, 'field_number', 'field_name'), selectedValues.find(sel => sel.key === 'field_number')?.value, 'Select a Field');        
}

function updateSelectOptions(selectElement, uniqueValues, selectedValue, defaultText) {
if(selectedValue){
    console.log(' unique values sent to update: ', uniqueValues);
    console.log('selectedValue: ', selectedValue);
    console.log('selectElement: ', selectElement);
}

selectElement.innerHTML = '';

const defaultOption = document.createElement('option');
defaultOption.value = 'none';
defaultOption.text = defaultText;
selectElement.appendChild(defaultOption);

if (uniqueValues.length === 0) {
    console.log('no unique values');
    const option = document.createElement('option');
    option.value = '';
    option.text = 'No options available';
    selectElement.appendChild(option);
} else {
    if(selectElement === document.getElementById('select_team-division')){
        uniqueValues.forEach(({ division_number, division_name }) => {
            const option = document.createElement('option');
            console.log('div number = ',division_number);
            console.log('div name = ',division_name);
            option.value = 'division'+division_number;
            option.textContent = division_name; // Display the name in the dropdown
            if (division_number === selectedValue||uniqueValues.length === 1) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    }
    if(selectElement === document.getElementById('select_location')){
        uniqueValues.forEach(({ field_number, field_name }) => {
            const option = document.createElement('option');
            console.log('field number = ',field_number);
            console.log('field name = ',field_name);
            option.value = 'field'+field_number;
            option.textContent = field_name; // Display the name in the dropdown
            if (field_number === selectedValue||uniqueValues.length === 1) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    }
    if(selectElement === document.getElementById('select_date-match-played')){
        console.log('match_date = ', uniqueValues);
        uniqueValues.forEach(match_date => {
        const option = document.createElement('option');
            option.value = match_date;
            option.textContent = match_date; // Use the correct display text
        if (match_date === selectedValue||uniqueValues.length === 1) {
            option.selected = true;
        }
        selectElement.appendChild(option);
        });
    }
    if(selectElement === document.getElementById('select_time-match-played')){
        console.log('match_time = ', uniqueValues);
        uniqueValues.forEach(formatted_match_time => {
        const option = document.createElement('option');
            option.value = formatted_match_time;
            option.textContent = formatted_match_time; // Use the correct display text
        if (formatted_match_time === selectedValue||uniqueValues.length === 1) {
            option.selected = true;
        }
        selectElement.appendChild(option);
        });
    }
}
}
