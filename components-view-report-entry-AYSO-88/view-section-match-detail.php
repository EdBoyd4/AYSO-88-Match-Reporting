<?php

function fieldSelectorDisplay($gamePickerOptions, $dbFieldNumber = null) {
    // Extract unique field_number and field_name pairs
    $uniqueFields = array_unique(array_map(function($field) {
        return $field['field_number'] . '|' . $field['field_name'];
    }, $gamePickerOptions));

    // Convert back to an associative array
    $uniqueFieldOptions = array_map(function($uniqueField) {
        list($fieldNumber, $fieldName) = explode('|', $uniqueField);
        return ['field_number' => $fieldNumber, 'field_name' => $fieldName];
    }, $uniqueFields);

    echo '<section class="match-descriptor__group match-descriptor__group--field">
        <label for="select-field" class="match-descriptor__label">Field:</label>
        <select id="select-field" name="field-select" class="form-control match-descriptor__input match-descriptor__select" required>';
    
    // Add an empty option for "Select a field"
    echo '<option value="none" ' . (is_null($dbFieldNumber) ? 'selected' : '') . '>Select a field</option>';
    
    // Iterate over the unique options and create <option> elements
    foreach ($uniqueFieldOptions as $field) {
        $fieldNumber = htmlspecialchars($field['field_number'], ENT_QUOTES, 'UTF-8');
        $fieldName = htmlspecialchars($field['field_name'], ENT_QUOTES, 'UTF-8');
        echo '<option value="' . htmlspecialchars('field' . $fieldNumber, ENT_QUOTES, 'UTF-8') . '" ' . ($fieldNumber == $dbFieldNumber ? 'selected' : '') . '>' . $fieldName . '</option>';
    }
    
    echo '</select>
    </section>';
}

// for future possible use. relies on change to db structure and addition of dev environment identifier.
/* function fieldSelectorDisplay($gamePickerOptions, $dbFieldNumber = null)
{
    // Safety checks
    if (!is_array($gamePickerOptions)) {
        if (defined('IS_DEV') && IS_DEV) {
            throw new InvalidArgumentException("fieldSelectorDisplay(): gamePickerOptions must be an array.");
        }
        error_log("fieldSelectorDisplay(): invalid gamePickerOptions input.");
        return;
    }

    // Build unique list: keyed by field_id for stable ordering
    $uniqueFields = [];
    foreach ($gamePickerOptions as $field) {

        if (!isset($field['field_id'], $field['field_name'])) {
            continue; // Skip malformed records
        }

        $num = $field['field_id'];
        $name = $field['field_name'];

        $uniqueFields[$num] = $name; 
    }

    echo '<section class="match-descriptor__group match-descriptor__group--field">
            <label for="select-field" class="match-descriptor__label">Field:</label>
            <select id="select-field" name="field-select" class="match-descriptor__input match-descriptor__select" required>';

    // Default “Select a field”
    echo '<option value="none" ' . (is_null($dbFieldNumber) ? 'selected' : '') . '>Select a field</option>';

    // Output unique fields
    foreach ($uniqueFields as $fieldNumber => $fieldName) {
        $fieldNumberEsc = htmlspecialchars($fieldNumber, ENT_QUOTES, 'UTF-8');
        $fieldNameEsc   = htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8');

        echo '<option value="' . $fieldNumberEsc . '" ' 
            . (($fieldNumber == $dbFieldNumber) ? 'selected' : '') 
            . '>' . $fieldNameEsc . '</option>';
    }

    echo '</select></section>';
} */

function divisionSelectorDisplay($gamePickerOptions, $teamDivisionNumber = null) {
    // Extract the unique divisionNumber and divisionName pairs
    $uniqueDivisions = array_unique(array_map(function($division) {
        return $division['division_number'] . '|' . $division['division_name'];
    }, $gamePickerOptions));
    // Convert back to an associative array
    $uniqueDivisionOptions = array_map(function($uniqueDivision) {
        list($divisionNumber, $divisionName) = explode('|', $uniqueDivision);
        return ['division_number' => $divisionNumber, 'division_name' => $divisionName];
    }, $uniqueDivisions);

    echo'<section class="match-descriptor__group match-descriptor__group--division">
        <label for="division-select" class="match-descriptor__label">Division:</label>
        <select id="division-select" name="division-select" class="form-control match-descriptor__input match-descriptor__select" required>';

    // Add an empty option for "Select a division"
    echo '<option value="none" ' . (is_null($teamDivisionNumber) ? 'selected' : '') . '>Select a Division</option>';

    // Iterate over the unique options and create <option> elements
    foreach ($uniqueDivisionOptions as $division) {
        $divisionNumber = htmlspecialchars($division['division_number'], ENT_QUOTES, 'UTF-8');
        $divisionName = htmlspecialchars($division['division_name'], ENT_QUOTES, 'UTF-8');
        echo '<option value="division' . $divisionNumber . '" ' . ($divisionNumber == $teamDivisionNumber ? 'selected' : '') . '>' . $divisionName . '</option>';
    }

    echo '</select>
    </section>';
}

function matchDateSelectorDisplay($gamePickerOptions, $matchDate = null){
    $dates = array_unique(array_column($gamePickerOptions, 'match_date'));

    echo '<section class="match-descriptor__group match-descriptor__group--date">
        <label for="match-date" class="match-descriptor__label">Date:</label>
        <select id="match-date" name="match-date" class="form-control match-descriptor__input match-descriptor__input--date" required>';
    
    // Default option
    echo '<option value="none" ' . (is_null($matchDate) ? 'selected' : '') . '>Select a Date</option>';

    // Iterate over the options and create <option> elements
    foreach ($dates as $date) {
        // Create a DateTime object from the string
        $dateForUser = new DateTime($date);
        // Format the date to Y-m-d for value and m-d-Y for display
        $formattedDateValue = htmlspecialchars($dateForUser->format('Y-m-d'), ENT_QUOTES, 'UTF-8');
        $formattedDateDisplay = htmlspecialchars($dateForUser->format('m-d-Y'), ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $formattedDateValue . '" ' . ($formattedDateValue == $matchDate ? 'selected' : '') . '>' . $formattedDateDisplay . '</option>';
    }
    
    echo '</select>
    </section>';
}

function matchTimeSelectorDisplay($gamePickerOptions, $matchTime = null) {
    $times = array_unique(array_column($gamePickerOptions, 'formatted_match_time'));

    echo '<section class="match-descriptor__group match-descriptor__group--time">
        <label for="match-time" class="match-descriptor__label">Time:</label>
        <select id="match-time" name="match-time" class="form-control match-descriptor__input match-descriptor__input--time" required>';    
        
    // Default option
    echo '<option value="none">Select a Time</option>';
    // Iterate over the options and create <option> elements
    foreach ($times as $time) {
        $formattedTime = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $formattedTime . '" ' . ($formattedTime == $matchTime ? 'selected' : '') . '>' . $formattedTime . '</option>';
    }
    echo '</select>
    </section >';
}

function matchDescriptorFieldSetView($matchDate = null, $matchTime = null, $fieldNumber = null, $teamDivisionNumber = null){
    $gamePickerOptions = selectScheduledMatchOptionsFromDatabaseEXP();
    echo'<fieldset class="match-descriptor" id="match-descriptor">
        <legend class="match-descriptor__legend">Match Details</legend>';
    matchDateSelectorDisplay($gamePickerOptions, $matchDate);
    matchTimeSelectorDisplay($gamePickerOptions, $matchTime);
    fieldSelectorDisplay($gamePickerOptions, $fieldNumber);
    divisionSelectorDisplay($gamePickerOptions, $teamDivisionNumber);
    // not sure what the hell this is for : echo '<input type="hidden" id="game-details-data" value=\'' . json_encode($gamePickerOptions) . '\' />
    echo '</fieldset>';
}

// gamecards - front of #1 and back of #2, then vice versa
function gameCardsPhotoEntrySegment($pairNumber, $matchNumber = null){
    // Front of Card 1, and Back of Card 2
    // Front of Card 2, and Back of Card 1
    // '.($matchNumber === null ? '' : 'match-'.$matchNumber).'
    $pairNumberText='';
    switch ($pairNumber){
        case 1:
            $pairNumberText='Photo of Front of Card 1, and Back of Card 2';
            break;
        case 2:
            $pairNumberText='Photo of Front of Card 2, and Back of Card 1';
            break;
        default:
        $pairNumberText='ERROR';
    }
    echo '<section id="section_'.($matchNumber === null ? '' : 'match-'.$matchNumber.'-').'gamecard-pair-'.$pairNumber.'-photo-entry" class="section_photo-gamecards">
            <label for="photo_'.($matchNumber === null ? '' : 'match-'.$matchNumber.'-').'gamecard-pair-'.$pairNumber.'" class="label_gamecards_photo">'.$pairNumberText.':
            <input type="file" accept="image/*" capture="camera"
                name="game'.($matchNumber === null ? '' : $matchNumber).'CardsPhoto'.$pairNumber.'" 
                id="photo_'.($matchNumber === null ? '' : 'match-'.$matchNumber.'-').'gamecard-pair-'.$pairNumber.'" 
                class="photo_gamecard-pair" required></label>
                <button 
                type="button" 
                id="button_gamecard-photo-example-'.$pairNumber.'" 
                class="button-userhelper-example"
                >
                See Example
                </button>
                <!-- Modal -->
                <div 
                id="imageAlert'.$pairNumber.'" 
                class="modal"
                >
                    <div 
                    class="modal-content"
                    >
                        <span id="closeExample'.$pairNumber.'" class="close">&times;</span>
                        <img src="/image_example-'.$pairNumber.'.jpg" alt="Alert Image" />
                    </div>
                </div>
        </section>
        <section id="photo_'.($matchNumber === null ? '' : 'match-'.$matchNumber.'-').'gamecard-pair-'.$pairNumber.'-preview"></section>';
};

// officials name reporting - informal titles
function refNames($refNumber, $refName = null){
    $refRole = '';
    $refRoleText = '';
    $entryRequired = '';

    switch ($refNumber){
        case 0:
            $refRole = 'center';
            $refRoleText = 'Your';
            $entryRequired = ' required';
            break;

        case 1:
            $refRole = 'ar1';
            $refRoleText = 'AR 1\'s';
            break;

        case 2:
            $refRole = 'ar2';
            $refRoleText = 'AR 2\'s';
            break;

        default:
            // for modification - requires includeOnce : environmentConfig.php with : define('IS_DEV', true); // or false on the A2 server

            /* if (IS_DEV) {
                throw new InvalidArgumentException("Invalid referee number: $refNumber");
            } else {
                error_log("Invalid referee number in refNames(): $refNumber");
                return;
            } */
            return;
    }

    echo '<section id="section-'.$refRole.'-ref-entry"
                 class="match-report-descriptor__ref match-report-descriptor__ref--'.$refRole.'">

            <label for="text-ref-'.$refRole.'"
                   id="label-text-ref-'.$refRole.'">
                   '.$refRoleText.' Name:
            </label>

            <input type="text"
                    name="'.$refRole.'"
                    id="text-ref-'.$refRole.'"
                    class="form-control"
                    '.($refName === null ? '' : 'value="'.$refName.'"').$entryRequired.'>
        </section>';
}


function refStaffingIssueGPT2($staffingIssueNumber = null, $staffingIssue = null) {
    $staffingIssueNumber = $staffingIssueNumber ?? ''; // Handle null case
    echo '<fieldset id="section_ref-staffing-issue'.$staffingIssueNumber.'" class="section-match-entry">
            <legend>Referee Staffing Issue</legend>';
        echo'<button 
                type="button"
                id="button_ref-staffing-issue'.$staffingIssueNumber.'"
                class="button_ref-staffing-issue"
                aria-controls="section_ref-staffing-issue-detail'.$staffingIssueNumber.'"
                aria-expanded="false">
                Report a Referee Staffing Issue
            </button>';
        echo '<section id="section_ref-staffing-issue-detail'.$staffingIssueNumber.'" class="section_match-issue" hidden>';
            echo '<label for="textarea_ref-staffing-issue'.$staffingIssueNumber.'" 
                id="label_ref-staffing-issue'.$staffingIssueNumber.'">Please Provide A Brief Description of the Issue(s):</label>
                <textarea
                    id="textarea_ref-staffing-issue'.$staffingIssueNumber.'" 
                    name="refStaffingIssueText'.$staffingIssueNumber.'" 
                    class="form-control" 
                    rows="1" 
                    cols="60">'
                    .($staffingIssue === null ? '' : htmlspecialchars($staffingIssue)).'
                </textarea>';
        echo '</section>';
    echo '</fieldset>';
}

function matchNotesFieldset($matchIssueNumber = null, $matchIssue = null) {
    $matchIssueNumber = $matchIssueNumber ?? ''; // Handle null case
    echo '<fieldset class="match-notes-descriptor" id="match-notes-descriptor">
            <legend class="match-notes-descriptor__legend">
                Additional Notes
            </legend>
            <section id="section_match-issue-entry'.$matchIssueNumber.'" class="section-match-entry">';
        echo '<button 
                type="button"
                id="button_match-issue'.$matchIssueNumber.'"
                class="button_match-issue"
                aria-controls="section_match-issue-detail'.$matchIssueNumber.'"
                aria-expanded="false">
                Please Check Here If There was an Issue with Something Else (Spectator(s), Field, Etc.)
            </button>';
        echo '<section id="section_match-issue-detail'.$matchIssueNumber.'" class="section_match-issue" hidden>';
            echo '<label for="textarea_match-issue'.$matchIssueNumber.'" 
                id="label_match-issue'.$matchIssueNumber.'">Please Provide A Brief Description of the Issue(s):</label>
                <textarea id="textarea_match-issue'.$matchIssueNumber.'" 
                name="matchIssueText'.$matchIssueNumber.'" 
                class="form-control" 
                rows="1" 
                cols="60">'
                .($matchIssue === null ? '' : htmlspecialchars($matchIssue)).'</textarea>';
        echo '</section>';
    echo '</section>
    </fieldset>';
}

function matchReportFieldSetView($refName = null, $ar1Name = null, $ar2Name = null){
    // placeholder for version that includes Vartan/ Aldo page $gamePickerOptions = selectScheduledMatchOptionsFromDatabase();
    echo'<fieldset class="match-report-descriptor" id="match-report-descriptor">
        <legend class="match-report-descriptor__legend">Referee Details</legend>';
    refNames(0);
    refNames(1);
    refNames(2);
    refStaffingIssueGPT2();
    gameCardsPhotoEntrySegment(1);
    gameCardsPhotoEntrySegment(2);
    echo '</fieldset>';
}

function submitFieldset(){
    echo '
    <fieldset class="match-submit-descriptor" id="match-submit-descriptor">
        <legend class="match-submit-descriptor__legend">
            Report Submission
        </legend>
        <div class="submit-row">
            <input
                type="submit"
                value="Submit the Match Results"
                name="submit"
                id="submit"
                class="btn"
            />
        </div>
    </fieldset>';
}
