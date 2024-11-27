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

    echo '<section id="section_location-entry" class="section-match-entry">
        <label for="select_location" id="label_location-select">Field:</label>
        <select name="matchSelection" id="select_location" required>';
    
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
    echo '<section id="section_team_division_entry" class="section-match-entry">
        <label for="select_team-division" id="label_select_team-division">Division:</label>
        <select name="teamDivision" id="select_team-division" required>';
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
    echo '<section id="section_date-match-played" class="section-match-entry">
        <label for="select_date-match-played" id="label_date-match-played">Date:</label>
        <select name="matchDate" id="select_date-match-played" required>';
    
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
    echo '<section id="section_time-match-played" class="section-match-entry">
        <label for="select_time-match-played" id="label_time-match-played">Time:</label>
        <select name="matchTime" id="select_time-match-played" required>';    
    // Default option
    echo '<option value="none">Select a Time</option>';
    // Iterate over the options and create <option> elements
    foreach ($times as $time) {
        $formattedTime = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $formattedTime . '" ' . ($formattedTime == $matchTime ? 'selected' : '') . '>' . $formattedTime . '</option>';
    }
    echo '</select>
    </section>';
}

function setUpDateAndTimeSelectors($matchDate = null, $matchTime = null, $fieldNumber = null, $teamDivisionNumber = null){
    $gamePickerOptions = selectScheduledMatchOptionsFromDatabase();
    fieldSelectorDisplay($gamePickerOptions, $fieldNumber);
    divisionSelectorDisplay($gamePickerOptions, $teamDivisionNumber);
    matchDateSelectorDisplay($gamePickerOptions, $matchDate);
    matchTimeSelectorDisplay($gamePickerOptions, $matchTime);
    echo '<input type="hidden" id="game-details-data" value=\'' . json_encode($gamePickerOptions) . '\' />';
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
                <button type="button" id="button_gamecard-photo-example-'.$pairNumber.'" class="button-userhelper-example">See Example</button>
                <!-- Modal -->
                <div id="imageAlert'.$pairNumber.'" class="modal">
                    <div class="modal-content">
                        <span id="closeExample'.$pairNumber.'" class="close">&times;</span>
                        <img src="/image_example-'.$pairNumber.'.jpg" alt="Alert Image" />
                    </div>
                </div>
        </section>
        <section id="photo_'.($matchNumber === null ? '' : 'match-'.$matchNumber.'-').'gamecard-pair-'.$pairNumber.'-preview"></section>';
};

// officials name reporting - informal titles
function refNames($refNumber, $refName = null){
    $refRole='';
    $refRoleText='';
    switch ($refNumber){
        case 0:
            $refRole='center';
            $refRoleText='Your';
            $entryRequired=' required';
            break;
        case 1:
            $refRole='AR1';
            $refRoleText='AR 1\'s';
            $entryRequired='';
            break;
        case 2:
            $refRole='AR2';
            $refRoleText='AR 2\'s';
            $entryRequired='';
            break;
        default:
        $refRole;
        $refRoleText;
    }
    echo '<section id="section_'.$refRole.'-ref-entry" class="section_ref_entry">
        <label for="text_'.$refRole.'" id="label_text_'.$refRole.'">'.$refRoleText.' Name:</label>
        <input type="text" name="'.$refRole.'" id="text_'.$refRole.'" '.($refName === null ? '' : 'value="'.$refName.'"').$entryRequired.'>
    </section>';
};

function refStaffingIssueGPT2($staffingIssueNumber = null, $staffingIssue = null) {
    $staffingIssueNumber = $staffingIssueNumber ?? ''; // Handle null case
    echo '<section id="section_ref-staffing-issue'.$staffingIssueNumber.'" class="section-match-entry">';
        echo '<section id="section_ref-staffing-issue-checkbox'.$staffingIssueNumber.'" class="section_ref-staffing">';
            echo '<input type="checkbox" id="checkbox_ref-staffing-issue'.$staffingIssueNumber.'" 
                    name="refStaffingIssueCheckBox'.$staffingIssueNumber.'" value="refStaffingIssue" 
                    class="checkbox_ref-staffing-issues">';
            echo '<label for="textarea_ref-staffing-issue">Please Check Here If There was an Issue with the Referee Staffing</label>';
        echo '</section>';
        echo '<section id="section_ref-staffing-issue-detail'.$staffingIssueNumber.'" class="section_match-issue" style="display:none;">';
            echo '<label for="textarea_ref-staffing-issue'.$staffingIssueNumber.'" 
                id="label_ref-staffing-issue'.$staffingIssueNumber.'">Please Provide A Brief Description of the Issue(s):</label>
                <textarea id="textarea_ref-staffing-issue'.$staffingIssueNumber.'" 
                name="refStaffingIssueText'.$staffingIssueNumber.'" rows="1" cols="60">'
                .($staffingIssue === null ? '' : htmlspecialchars($staffingIssue)).'</textarea>';
        echo '</section>';
    echo '</section>';
}

function otherMatchIssueGPT2($matchIssueNumber = null, $matchIssue = null) {
    $matchIssueNumber = $matchIssueNumber ?? ''; // Handle null case
    echo '<section id="section_match-issue-entry'.$matchIssueNumber.'" class="section-match-entry">';
        echo '<section id="section_match-issue-checkbox'.$matchIssueNumber.'" class="section_match-issue">';
            echo '<input type="checkbox" id="checkbox_match-issue'.$matchIssueNumber.'" 
                    name="matchIssueCheckbox'.$matchIssueNumber.'" value="matchIssue" 
                    class="checkbox_match-issues">';
            echo '<label for="textarea_match-issue">Please Check Here If There was an Issue with Something Else (Spectator(s), Field, Etc.)</label>';
        echo '</section>';
        echo '<section id="section_match-issue-detail'.$matchIssueNumber.'" class="section_match-issue" style="display:none;">';
            echo '<label for="textarea_match-issue'.$matchIssueNumber.'" 
                id="label_match-issue'.$matchIssueNumber.'">Please Provide A Brief Description of the Issue(s):</label>
                <textarea id="textarea_match-issue'.$matchIssueNumber.'" 
                name="matchIssueText'.$matchIssueNumber.'" rows="1" cols="60">'
                .($matchIssue === null ? '' : htmlspecialchars($matchIssue)).'</textarea>';
        echo '</section>';
    echo '</section>';
}