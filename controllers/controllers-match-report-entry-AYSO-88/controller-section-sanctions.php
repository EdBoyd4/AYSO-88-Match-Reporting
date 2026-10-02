<?php
require_once __DIR__ . '/controller-shared-validators.php';

// Validates and processes the "Sanctions Issued" fieldset - mirrors
// class-view-sanctions-reports-fieldset.php and its per-sanction children
// (SanctionEntrySegment, SanctionLevelRadioSection, SanctionPartyRadioSection,
// SanctionPartySection, SanctionSummarySection).

function logAndEchoError($type, $value, $index) {
    error_log("sanction{$index}{$type} was invalid: $value");
    echo 'There was a serious error with the sanction you attempted to report.';
}

// Sanctions are optional overall, but once ANY of the four fields for a
// given sanction slot has something in it, all four become required for
// that slot - a half-filled sanction is rejected rather than silently
// dropped or silently accepted. Returns true/false; on success populates
// each entered sanction's fields plus sanctionAmount on $headerDataItems.
function processSanctionReportBasic(&$headerDataItems) {

	$sanctionsInMatch = 0;
	$isValid = true;

	// Process each sanction
	for ($i = 1; $i <= 5; $i++) {
		$sanctionLevelKey = 'sanctionLevel'. $i;
		$sanctionPartyTypeKey = 'sanctionParty' . $i;
		$sanctionPartyDescriptionKey = 'sanction-party-description-' . $i;
		$sanctionCauseSummaryKey = 'sanction' . $i . 'SummaryText';

		$levelEntered = !empty($_POST[$sanctionLevelKey]);
		$partyEntered = !empty($_POST[$sanctionPartyTypeKey]);
		$descriptionEntered = !empty($_POST[$sanctionPartyDescriptionKey]);
		$summaryEntered = !empty($_POST[$sanctionCauseSummaryKey]);

		if (!($levelEntered || $partyEntered || $descriptionEntered || $summaryEntered)) {
			// Nothing was entered for this slot at all - it's optional, skip it.
			continue;
		}

		if (!($levelEntered && $partyEntered && $descriptionEntered && $summaryEntered)) {
			error_log("sanction $i was only partially filled in");
			echo "Sanction #$i is missing some information - please fill in all of its fields (who was cautioned/sent off, what happened, and why), or remove it.";
			$isValid = false;
			continue;
		}

		$level = $_POST[$sanctionLevelKey];
		if ($level === 'yellow') {
			$headerDataItems[$sanctionLevelKey] = 0;
		} elseif ($level === 'red') {
			$headerDataItems[$sanctionLevelKey] = 1;
		} else {
			logAndEchoError('level', $level, $i);
			$isValid = false;
			continue;
		}

		$partyType = $_POST[$sanctionPartyTypeKey];
		if ($partyType === 'player') {
			$headerDataItems[$sanctionPartyTypeKey] = 0;
		} elseif ($partyType === 'coach') {
			$headerDataItems[$sanctionPartyTypeKey] = 1;
		} else {
			logAndEchoError('Party', $partyType, $i);
			$isValid = false;
			continue;
		}

		if (validateTextString($_POST[$sanctionPartyDescriptionKey])) {
			$headerDataItems[$sanctionPartyDescriptionKey] = sanitizeuserData($_POST[$sanctionPartyDescriptionKey]);
		} else {
			$isValid = false;
			continue;
		}

		if (validateTextString($_POST[$sanctionCauseSummaryKey])) {
			$headerDataItems[$sanctionCauseSummaryKey] = sanitizeuserData($_POST[$sanctionCauseSummaryKey]);
		} else {
			$isValid = false;
			continue;
		}

		$sanctionsInMatch = $i;
	}

	if($sanctionsInMatch > 0){
		$headerDataItems['sanctionAmount'] = $sanctionsInMatch;
	}

	return $isValid;
}
