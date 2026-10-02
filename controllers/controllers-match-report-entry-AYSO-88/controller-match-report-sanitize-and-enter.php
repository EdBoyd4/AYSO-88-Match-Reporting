<?php
// Orchestrates the per-section controllers below - each one validates and
// extracts the $_POST/$_FILES data for one view-section of the match
// report form, mirroring that section's view component:
//   controller-section-match-descriptor.php  <-> class-view-match-descriptor-fieldset.php
//   controller-section-scores.php            <-> class-view-scores-section.php
//   controller-section-referee-details.php   <-> class-view-match-report-fieldset.php (minus photos)
//   controller-match-report-photo-upload.php <-> class-view-game-cards-photo-entry-segment.php
//   controller-section-sanctions.php         <-> class-view-sanctions-reports-fieldset.php
//   controller-section-match-notes.php       <-> class-view-match-notes-fieldset.php
require_once __DIR__ . '/controller-shared-validators.php';
require_once __DIR__ . '/controller-section-match-descriptor.php';
require_once __DIR__ . '/controller-section-scores.php';
require_once __DIR__ . '/controller-section-referee-details.php';
require_once __DIR__ . '/controller-match-report-photo-upload.php';
require_once __DIR__ . '/controller-section-sanctions.php';
require_once __DIR__ . '/controller-section-match-notes.php';

// Reads and validates the whole match report form out of $_POST/$_FILES,
// section by section. Returns the assembled $headerDataItems array on
// success, or null if any required field across any section was
// missing/invalid (the specific problem was already echoed to the user).
function getHeaderDataFromPOST(){
	$headerDataItems = array();
	$isValid = true;

	// The match descriptor resolves which scheduled match is being
	// reported on - every other section's DB writes hang off that match
	// id, so stop immediately if it can't be resolved.
	if (!processMatchDescriptor($headerDataItems)) {
		return null;
	}

	if (!processMatchScores($headerDataItems)) {
		$isValid = false;
	}

	if (!processRefereeDetails($headerDataItems)) {
		$isValid = false;
	}

	// Game Card Photos 1 & 2
	if (!isset($_FILES['gameCardsPhoto1']) || $_FILES['gameCardsPhoto1']['error'] !== UPLOAD_ERR_OK) {
		$isValid = false;
	}
	processImageFileFromDATA($headerDataItems, 1);

	if (!isset($_FILES['gameCardsPhoto2']) || $_FILES['gameCardsPhoto2']['error'] !== UPLOAD_ERR_OK) {
		$isValid = false;
	}
	processImageFileFromDATA($headerDataItems, 2);

	if (!processSanctionReportBasic($headerDataItems)) {
		$isValid = false;
	}

	if (!processMatchNotes($headerDataItems)) {
		$isValid = false;
	}

	if (!$isValid) {
		return null;
	}

	return $headerDataItems;
}
