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
include_once GSS88_CONTROLLERS_UPLOAD . '/controller-match-report-email.php';



if($_SERVER['REQUEST_METHOD'] === 'POST'){
    error_log("POST received: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);

    $headerDataItems = getHeaderDataFromPOST();
    
    error_log("Header Processed: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);
    
    // method below returns matchIdPlayed
    $matchReportId = insertMatchData($headerDataItems);
    
    error_log("data uploaded: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);
    
    getMatchInfoForEvaluation($matchReportId);
    
    error_log("email sent: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);
    
        session_destroy();
        header('Location: logout.php');
        die();
}else{
    error_log("GET received: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);
    (new MatchReportForm($_SERVER['PHP_SELF']))->render();
}