<?php
require_once __DIR__ . '/controller-shared-validators.php';

// Validates and processes the "Additional Notes" fieldset - mirrors
// class-view-match-notes-fieldset.php.

// Optional - additional match notes (the view toggles this open with a
// button, not a checkbox, so presence of text is the only signal). Always
// returns true since this section is entirely optional; the failure path
// here is a description that's too long, which just gets dropped.
function processMatchNotes(&$headerDataItems) {
	if(!empty($_POST["matchIssueText"])){
		if(validateTextString($_POST["matchIssueText"])){
			$matchIssueDescription = sanitizeuserData($_POST["matchIssueText"]);
			$headerDataItems['matchIssueText'] = $matchIssueDescription;
		}else{
			error_log('problem with the match issue description');
			echo 'There was an issue with your description of the match incident.';
		}
	}

	return true;
}
