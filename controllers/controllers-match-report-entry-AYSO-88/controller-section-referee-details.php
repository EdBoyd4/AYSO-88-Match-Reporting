<?php
require_once __DIR__ . '/controller-shared-validators.php';

// Validates and processes the "Referee Details" fieldset - mirrors
// class-view-match-report-fieldset.php's RefNames (center/AR1/AR2) and
// RefStaffingIssueGPT2 children. GameCardsPhotoEntrySegment, the fieldset's
// other child, already has its own controller (controller-match-report-photo-upload.php).

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

// Validates and extracts the center ref / AR1 / AR2 names and the optional
// referee staffing issue note. Only the center ref's name is required.
// Returns true/false; on success populates Ref1/Ref2/Ref3 and (if present)
// refStaffingIssueText on $headerDataItems.
function processRefereeDetails(&$headerDataItems) {
	$isValid = true;

	if(isset($_POST["center"]) && $_POST["center"] !== ''){
		if(validateNameString($_POST["center"])){
			$sanitizedName = sanitizeuserData($_POST["center"]);
			$headerDataItems['Ref1'] = $sanitizedName;
		}else{
			$isValid = false;
		}
	}else{
		error_log('no center ref name entered');
		echo 'The Center Ref\'s name is required.';
		$isValid = false;
	}

	// optional - AR1's name. The view's <input> is named "ar1" (lowercase,
	// matching RefNames' $refRole), not "AR1" - $_POST is case-sensitive,
	// so checking "AR1" here always missed it and (incorrectly) reported
	// the name as "too long" on every single submission.
	if(isset($_POST["ar1"])){
		if(validateNameString($_POST["ar1"])){
			$sanitizedName = sanitizeuserData($_POST["ar1"]);
			$headerDataItems['Ref2'] = $sanitizedName;
		}
	}else{
		// AR1 is optional and its <input> is always rendered, so this
		// shouldn't normally happen - just log it, no need to alarm the user.
		error_log('no AR1 field present in submission');
	}

	// optional - AR2's name, same "ar2" (lowercase) field-name situation as AR1.
	if(isset($_POST["ar2"])){
		if(validateNameString($_POST["ar2"])){
			$sanitizedName = sanitizeuserData($_POST["ar2"]);
			$headerDataItems['Ref3'] = $sanitizedName;
		}
	}else{
		error_log('no AR2 field present in submission');
	}

	// optional - referee staffing issue note (the view toggles this open
	// with a button, not a checkbox, so presence of text is the only signal)
	if(!empty($_POST["refStaffingIssueText"])){
		if(validateTextString($_POST["refStaffingIssueText"])){
			$refIssueDescription = sanitizeuserData($_POST["refStaffingIssueText"]);
			$headerDataItems['refStaffingIssueText'] = $refIssueDescription;
		}else{
			error_log('problem with the reffing issue description');
			echo 'There was an issue with your description of the ref staffing.';
		}
	}

	return $isValid;
}
