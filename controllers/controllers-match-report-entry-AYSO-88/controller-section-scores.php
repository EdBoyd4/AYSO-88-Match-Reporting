<?php

// Validates and processes the "Final Score" section - mirrors
// class-view-scores-section.php.

// validate a submitted score - required, must be a non-negative whole number
function validateScoreValue($scoreSubmitted){
	if (!ctype_digit((string)$scoreSubmitted)) {
		error_log('an invalid score value was submitted: '.$scoreSubmitted);
		echo 'There was a problem with one of the scores you entered.';
		return false;
	} else {
		return true;
	}
}

// Validates and extracts the home/away score. Both are required. Returns
// true/false; on success populates homeScore/awayScore on $headerDataItems.
function processMatchScores(&$headerDataItems) {
	$isValid = true;

	if(isset($_POST["homeScore"])){
		if(validateScoreValue($_POST["homeScore"])){
			$headerDataItems['homeScore'] = intval($_POST["homeScore"]);
		}else{
			$isValid = false;
		}
	}else{
		error_log('no home score entered');
		echo 'The home team score is required.';
		$isValid = false;
	}

	if(isset($_POST["awayScore"])){
		if(validateScoreValue($_POST["awayScore"])){
			$headerDataItems['awayScore'] = intval($_POST["awayScore"]);
		}else{
			$isValid = false;
		}
	}else{
		error_log('no away score entered');
		echo 'The away team score is required.';
		$isValid = false;
	}

	return $isValid;
}
