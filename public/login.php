<?php

session_start();

date_default_timezone_set('America/Los_Angeles');

$dateTime = new DateTime();
$currentDateFromSys = $dateTime->format('Y-m-d');
$currentTimeFromSys = $dateTime->format('H:i');
$sessionId = session_id();

include_once __DIR__ . '/../config-ref-match-reporting/constants-GSS-88-file-paths.php';
// handles select to identify match
include_once GSS88_CONFIG_FILES . '/constants-model-GSS-88-match-and-sanction-db.php';
include_once GSS88_MODELS_REPORTS . '/model-insert.php'; // handles inserts for report
include_once GSS88_MODELS_REPORTS . '/model-query.php'; // handles queries for report
include_once GSS88_VIEWS_REPORTS_COMPOUND . '/class-view-match-report-form.php';
include_once GSS88_CONTROLLERS_UPLOAD . '/controller-match-report-sanitize-and-enter.php';

// controller-match-report-email.php expects $rootDir (for its
// vendor/autoload.php require) to already be set by whatever includes it -
// same convention controller-match-report-photo-upload.php uses.
$rootDir = realpath($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '..');

// TEMPORARY while testing locally, between seasons: this sends real mail
// (real staff addresses, real SMTP creds, both hardcoded in the file below)
// as soon as it's included, so every recipient except the referee
// administrator is suppressed until this is flipped back to false for
// production. See TODO- GSS88MatchReports.md.
define('GSS88_EMAIL_DEV_MODE', true);
include_once GSS88_CONTROLLERS_UPDATES . '/controller-match-report-email.php';



if($_SERVER['REQUEST_METHOD'] === 'POST'){
    error_log("POST received: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);

    $headerDataItems = getHeaderDataFromPOST();

    error_log("Header Processed: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);

    // getHeaderDataFromPOST() returns null when a required field was
    // missing/invalid; the specific problem was already echoed to the
    // user, so just stop here instead of inserting incomplete data.
    if ($headerDataItems === null) {
        error_log("Validation failed: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);
        die();
    }

    // method below returns matchIdPlayed
    $matchReportId = insertMatchData($headerDataItems);

    error_log("data uploaded: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);

    // The report is already saved at this point - don't let a problem
    // sending the notification email (this code path has never actually
    // run end-to-end before) turn a successful submission into a 500.
    try {
        getMatchInfoForEvaluation($matchReportId);
    } catch (\Throwable $e) {
        error_log('Notification email failed for match report ' . $matchReportId . ': ' . $e->getMessage());
    }

        session_destroy();
        header('Location: logout.php');
        die();
}else{
    error_log("GET received: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);
    (new MatchReportForm($_SERVER['PHP_SELF']))->render();
}