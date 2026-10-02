<?php

// Small validation/sanitization helpers shared by more than one
// section controller (referee details, match notes, sanctions, ...).
// Section-specific validators live in their own controller-section-*.php
// file alongside the section they validate.

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
