<?php

// Validates and processes the "Match Details" fieldset - mirrors
// class-view-match-descriptor-fieldset.php and its 4 selector children
// (class-view-match-date-selector.php, class-view-match-time-selector.php,
// class-view-field-selector.php, class-view-division-selector.php).

function trimFieldValueForValidation ($fieldSubmitted){
	$filteredString = str_replace("field", "", $fieldSubmitted);
	return $filteredString;
}

// Function to check if the submitted value of the location select element is a valid option
function fieldIsValidOption($submittedFieldValue) {
	$fields = selectFieldNamesAndNumbersForUserInterface();
    // Convert the $_POST array to a string format
$postData = print_r($fields, true);

// Log the $_POST data to the error log
error_log("POST Data: " . $postData);

    // If no match was found after checking all options
    error_log('Submitted select value ' . $submittedFieldValue . ' does not match any of the available field options');
    echo "Invalid Field option selected.";
    return false;
}

function trimdivisionValueForValidation($divSubmitted){
	$filteredString = str_replace("division", "", $divSubmitted);
	return $filteredString;
}

// Function to check if the submitted value of the division select element is a valid option
function ageIsValidOption($submittedDivisionValue) {
    foreach (selectDivisionNamesAndNumbersForUserInterface() as $option) {
        if ($option['division_number'] === $submittedDivisionValue) {
            return true;
        }
    }
	error_log('submitted select value '.$submittedDivisionValue.' does not match any of the available age group options');
	echo "Invalid Division option selected.";
	return false;
}

// function to check if field and division are an acceptable pair
// &$headerDataItems has to be passed by reference
// NOTE: not called yet anywhere - kept in place for future use (see TODO)
function fieldAndAgeMatchCheckAndSet(&$headerDataItems, $submittedFieldValue, $submittedDivisionValue){
	$fieldNumber = (int)str_replace('pitch', '', $submittedFieldValue);
	$divisionNumber = (int)str_replace('division', '', $submittedDivisionValue);
	switch($submittedFieldValue){
		case 'pitch0': // clark 1
		case 'pitch1': // clark 2
		case 'pitch9': // GSC2
			if ($submittedDivisionValue == 'division0' || $submittedDivisionValue == 'division1') {
				$headerDataItems['playingfield'] = $fieldNumber;
				$headerDataItems['teamDivision'] = $divisionNumber;
			}
		break; // end of 10U
		case 'pitch6': // roosevelt
		case 'pitch7':  // rosemont upper
		case 'pitch8':// GSC 1
			if ($submittedDivisionValue == 'division2' || $submittedDivisionValue == 'division3') {
				$headerDataItems['playingfield'] = $fieldNumber;
				$headerDataItems['teamDivision'] = $divisionNumber;
			}
		break; // end of 12U
		case 'pitch11': // GSC4
			if ($submittedDivisionValue == 'division4' || $submittedDivisionValue == 'division5') {
				$headerDataItems['playingfield'] = $fieldNumber;
				$headerDataItems['teamDivision'] = $divisionNumber;
			}
		break; // end of 14U
		case 'pitch12': // GSC5
			if ($submittedDivisionValue == 'division6' || $submittedDivisionValue == 'division7') {
				$headerDataItems['playingfield'] = $fieldNumber;
				$headerDataItems['teamDivision'] = $divisionNumber;
			}
		break; // end of 16U
		default:
			echo "Invalid Field and Division.";
	}
}

// check start date is properly formatted
function validateMatchStartDate($date, $format = 'Y-m-d') {
    // Create DateTime object from the provided date and format
    $d = DateTime::createFromFormat($format, $date);
    // Check if DateTime creation was successful and if the formatted date matches the input
    if ($d && $d->format($format) === $date) {
        return $d; // Return DateTime object if valid
    }

    return false; // Return false if invalid
}

function checkMatchStartDate(DateTime $userDate): bool {
    $currentDate = new DateTime(); // This gets the current system date
    if ($userDate > $currentDate) {
        // Date is in the future - invalid
        echo 'Invalid Date Selected: The match date cannot be in the future.';
        return false;
    }
    // Date is in the past or today - valid
    return true;
}

// check start time is properly formatted
function validateMatchStartTime($matchTime, $format = 'H:i') {
    $d = DateTime::createFromFormat($format, $matchTime);
    return $d && $d->format($format) === $matchTime;
}

// check start time is before current time
function checkMatchStartTime($userTime){
	// This will get the current system time
    $currentTime = new DateTime();
    // Compare the times
    if ($userTime > $currentTime) {
        // Time is in the future - invalid
        echo 'Invalid Time Selected: The match time cannot be in the future.';
        return false;
    } elseif ($userTime <= $currentTime) {
        // Time is in the past or now - valid
        return true;
    } else {
        // This case should never be reached
        echo 'Invalid Game Time Entered.';
        return false;
    }
}

// clean start time
function sanitizeMatchStartTime($userTime){
	$sanitizedUserTime = filter_var($userTime, FILTER_SANITIZE_STRING);
	return $sanitizedUserTime;
}

// validate a required <select> - rejects missing/absent submission and the
// "none" placeholder option that ships as every selector's default choice
function validateSelectionValue($valueSubmitted){
	if (!isset($valueSubmitted) || $valueSubmitted === '' || $valueSubmitted === 'none') {
		error_log('a required selection was left at its placeholder value: '.print_r($valueSubmitted, true));
		echo 'Please make a selection for the match date, time, field, and division.';
		return false;
	} else {
		return true;
	}
}

// Validates the date/time/field/division selects and resolves them to the
// scheduled match being reported on. On success, populates matchDate,
// matchStartTime, playingfield, teamDivision, and scheduled_match_id on
// $headerDataItems. Returns true/false.
function processMatchDescriptor(&$headerDataItems) {
	$isValid = true;

	// Each select still submits its "none" placeholder value if the ref
	// never touched it, so presence alone isn't enough - reject the
	// placeholder explicitly.
	if (!validateSelectionValue($_POST["match-date"] ?? null)) { $isValid = false; }
	if (!validateSelectionValue($_POST["match-time"] ?? null)) { $isValid = false; }
	if (!validateSelectionValue($_POST["field-select"] ?? null)) { $isValid = false; }
	if (!validateSelectionValue($_POST["division-select"] ?? null)) { $isValid = false; }

	if (!$isValid) {
		return false;
	}

	$fieldForValidation = trimFieldValueForValidation($_POST["field-select"]);
	$headerDataItems['playingfield'] = intval($fieldForValidation);
	$divisionForValidation = trimdivisionValueForValidation($_POST["division-select"]);
	$headerDataItems['teamDivision'] = intval($divisionForValidation);
	$headerDataItems['matchDate'] = $_POST["match-date"];
	$headerDataItems['matchStartTime'] = $_POST["match-time"];

	$matchIdForReultsEntry = getMatchIdForReporting($headerDataItems['matchDate'], $headerDataItems['matchStartTime'] , $headerDataItems['playingfield'], $headerDataItems['teamDivision']);
	error_log('take your ID and shove it = '.$matchIdForReultsEntry);

	$headerDataItems['scheduled_match_id'] = $matchIdForReultsEntry;

	if (!($matchIdForReultsEntry && $matchIdForReultsEntry > 0)) {
		error_log('no scheduled match found for the submitted date/time/field/division');
		echo 'We could not find a scheduled match for the date, time, field, and division you selected.';
		return false;
	}

	return true;
}
