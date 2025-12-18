<?php
include_once('gameCardsUpload.php');

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

// validate the size of the ref / AR1 / AR2 name
function validateNameString($stringSubmitted){
	if (strlen($stringSubmitted) >= 100) {
		error_log('the officiant name was too long: '.$stringSubmitted);
		echo 'There was a problem with one of the names.';
		return false;
		// die('Text is too long.');
	} else{
		return true;
	}
}

// validate the size of the ref stafffing issue, party description, sanction event description, other incident description
function validateTextString($stringSubmitted){
	if (strlen($stringSubmitted) >= 255) {
		error_log('a problem report was too long: '.$stringSubmitted);
		echo 'There was a problem with the text you entered.';
		return false;
		// die('Text is too long.');
	} else{
		return true;
	}
}

// scrub ref / AR1 / AR2 name, ref stafffing issue, party description, sanction event description, other incident description
// size doesn't matter - :-)
function sanitizeuserData($dirtyPlayer){
	$sanitizedText = filter_var($dirtyPlayer, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	return $sanitizedText;
}

function processSanctionReportBasic(&$headerDataItems) {

	$sanctionsInMatch = 0;

    // Process each sanction
    for ($i = 1; $i <= 5; $i++) {
		error_log('in the sanctions loop');
        $sanctionLevelKey = 'sanctionLevel'. $i;
        $sanctionPartyTypeKey = 'sanctionParty' . $i;
        $sanctionPartyDescriptionKey = 'sanction-party-description-' . $i;
        $sanctionCauseSummaryKey = 'sanction' . $i . 'SummaryText';

		if(isset($_POST[$sanctionLevelKey]) && isset($_POST[$sanctionPartyTypeKey]) && isset($_POST[$sanctionPartyDescriptionKey]) && isset($_POST[$sanctionCauseSummaryKey])){
			error_log('in the sanctions if');
			$sanctionsInMatch = $i;

			if (isset($_POST[$sanctionLevelKey])) {
				$level = $_POST[$sanctionLevelKey];
				if ($level === 'yellow') {
					$headerDataItems[$sanctionLevelKey] = 0;
				} elseif ($level === 'red') {
					$headerDataItems[$sanctionLevelKey] = 1;
				} else  {
					logAndEchoError('level', $level, $i);
				}
			} else {
				logAndEchoMissing('level', $i);
			}

			if (isset($_POST[$sanctionPartyTypeKey])) {
				$partyType = $_POST[$sanctionPartyTypeKey];
				if ($partyType === 'player') {
					$headerDataItems[$sanctionPartyTypeKey] = 0;
				} elseif ($partyType === 'coach') {
					$headerDataItems[$sanctionPartyTypeKey] = 1;
				} else  {
					logAndEchoError('Party', $partyType, $i);
				}
			} else {
				logAndEchoMissing('Party', $i);
			}

			if (isset($_POST[$sanctionPartyDescriptionKey])) {
				validateTextString($_POST[$sanctionPartyDescriptionKey]);
				$headerDataItems[$sanctionPartyDescriptionKey] = sanitizeuserData($_POST[$sanctionPartyDescriptionKey]);
			} else {
				logAndEchoMissing('party description', $i);
			}

			if (isset($_POST[$sanctionCauseSummaryKey])) {
				validateTextString($_POST[$sanctionCauseSummaryKey]);
				$headerDataItems[$sanctionCauseSummaryKey] = sanitizeuserData($_POST[$sanctionCauseSummaryKey]);
			} else {
				logAndEchoMissing('event description', $i);
			}
		}

	}
    
	if($sanctionsInMatch > 0){
		$headerDataItems['sanctionAmount'] = $sanctionsInMatch;
	}
    
    return $headerDataItems;
}

function logAndEchoError($type, $value, $index) {
    error_log("sanction{$index}{$type} was invalid: $value");
    echo 'There was a serious error with the sanction you attempted to report.';
}

function logAndEchoMissing($type, $index) {
    error_log("sanction{$index}{$type} was empty.");
    echo "We did not receive information about the $type of the sanction you attempted to report.";
}


function getHeaderDataFromPOST(){
	// logs details of the photos that wwere submitted
	// error_log('we are inside. - '.print_r($_FILES, true));

    $headerDataItems = array();

	$fieldForValidation = trimFieldValueForValidation ($_POST["matchSelection"]);
	$headerDataItems['playingfield'] = intval($fieldForValidation);
	$divisionForValidation = trimdivisionValueForValidation($_POST["teamDivision"]);
	$headerDataItems['teamDivision'] = intval($divisionForValidation);
	$headerDataItems['matchDate'] = $_POST["matchDate"];
	$headerDataItems['matchStartTime'] = $_POST["matchTime"];

	$matchIdForReultsEntry = getMatchIdForReporting($headerDataItems['matchDate'], $headerDataItems['matchStartTime'] , $headerDataItems['playingfield'], $headerDataItems['teamDivision']);
	error_log('take your ID and shove it = '.$matchIdForReultsEntry);

	$headerDataItems['scheduled_match_id'] = $matchIdForReultsEntry;
	
	if($matchIdForReultsEntry && $matchIdForReultsEntry>0){
		if(isset($_POST["center"])){
		if(validateNameString($_POST["center"])){
			$sanitizedName = sanitizeuserData($_POST["center"]);
    		$headerDataItems['Ref1'] = $sanitizedName;
		}		
		}else{
			error_log('no ref name entered');
			echo 'The Center Ref\'s name was too long';
		}
		
		if(isset($_POST["AR1"])){
			if(validateNameString($_POST["AR1"])){
				$sanitizedName = sanitizeuserData($_POST["AR1"]);
				$headerDataItems['Ref2'] = $sanitizedName;
			}		
		}else{
			error_log('no ref name entered');
			echo 'The  name of AR 1 was too long';
		}
		
		
		if(isset($_POST["AR2"])){
			if(validateNameString($_POST["AR2"])){
				$sanitizedName = sanitizeuserData($_POST["AR2"]);
				$headerDataItems['Ref3'] = $sanitizedName;
			}		
		}else{
			error_log('no ref name entered');
			echo 'The name of AR 2 was too long';
		}

		$isTextSet = isset($_POST["refStaffingIssueText"]) && !empty($_POST["refStaffingIssueText"]);
		$isCheckboxSet = isset($_POST["refStaffingIssueCheckBox"]);

		// check to ensure ref staffing checkbox is set
		if(!empty($_POST["refStaffingIssueText"])){
			if(isset($_POST["refStaffingIssueCheckBox"])){
				if(validateTextString($_POST["refStaffingIssueText"])){
				$refIssueDescription = sanitizeuserData($_POST["refStaffingIssueText"]);
				$headerDataItems['refStaffingIssueText'] = $refIssueDescription;
				}else{
					error_log('problem with the reffing issue description');
					echo 'There was an issue with your description of the ref staffing.';
				}
			}else{
				error_log('problem with the reffing issue checkbox - issue entered but no checkbox');
				echo 'There was an issue with the way your description of the ref staffing issue was entered.';
			}		
		}

		// check to ensure match issue checkbox is set
		if(!empty($_POST["matchIssueText"])){
			if(isset($_POST["matchIssueCheckbox"])){
				if(validateTextString($_POST["matchIssueText"])){
					$matchIssueDescription = sanitizeuserData($_POST["matchIssueText"]);
					$headerDataItems['matchIssueText'] = $matchIssueDescription;
				}else{
					error_log('problem with the match issue description');
					echo 'There was an issue with your description of the match incident.';
				}
			}else{
				error_log('problem with the match issue checkbox - issue entered but no checkbox');
				echo 'There was an issue with the way your description of the match issue was entered.';
			}	
		}

		// File name - Game Card Photo 1 
		processImageFileFromDATA($headerDataItems, 1);

		// File name - Game Card Photo 2 
		processImageFileFromDATA($headerDataItems, 2);

		//$headerDataItems['sanctionAmount'] = $_POST["sanctionAmount"];
		
		//error_log('sanctionAmount = '.$headerDataItems['sanctionAmount']);

		// if there was a sanction, or more than one, prep those for entry
		processSanctionReportBasic($headerDataItems);
		
		return $headerDataItems;
	}
}