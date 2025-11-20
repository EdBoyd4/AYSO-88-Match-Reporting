<?php

session_start();

date_default_timezone_set('America/Los_Angeles');

$dateTime = new DateTime();
$currentDateFromSys = $dateTime->format('Y-m-d');
$currentTimeFromSys = $dateTime->format('H:i');
$sessionId = session_id();

$rootDir = realpath('/home/xnbglkce');
include_once($rootDir . DIRECTORY_SEPARATOR . 'gss88SanctionReportDbInterface' . DIRECTORY_SEPARATOR . 'sanctionReportConnectionConstants.php');
include_once($rootDir.DIRECTORY_SEPARATOR.'gss88SanctionReportDbInterface'.DIRECTORY_SEPARATOR.'matchInfoEntry.php');
include_once($rootDir.DIRECTORY_SEPARATOR.'loginPageComponents'.DIRECTORY_SEPARATOR.'sanctionEntry.php');
include_once($rootDir.DIRECTORY_SEPARATOR.'loginPageComponents'.DIRECTORY_SEPARATOR.'matchDetails.php');
include_once($rootDir.DIRECTORY_SEPARATOR.'loginPageComponents'.DIRECTORY_SEPARATOR.'matchReportProcessing.php');
include_once($rootDir.DIRECTORY_SEPARATOR.'gss88SanctionReportDbInterface'.DIRECTORY_SEPARATOR.'generateEmailReport.php');

function matchReportFormHeaderView(){
    echo'<header class="form_ref_match-header">
                <img src="88_logo.png" alt="AYSO Region 88 Glendale logo" class="logo" />
                <h1 id="title">AYSO Region 88<br>Fall Core</h1>
                <h2>Referee Game Report</h2>
                <p class="tagline">Brought to You By Glendale Soccer Scores (gss.org)</p>
            </header>';
}

function matchReportFormView() {
    echo'<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>AYSO Region 88 – Referee Match Report</title>
            <script type="text/javascript" src="gameCardsPreview.js" defer></script>
            <script type="text/javascript" src="matchDetailVisibility.js" defer></script>
            <script type="text/javascript" src="sanctionEntryVisibility.js" defer></script>
            <script type="text/javascript" src="formManagement.js" defer></script>
            <link rel="stylesheet" href="styles.css">
        </head>
        <body>
            <div class="bg" aria-hidden="true"></div>
            <main>
                <form
                    id="form_ref_match_report"
                    name="gameResultsEntryForm"
                    method="post"
                    action="'.$_SERVER['PHP_SELF'].'"
                    enctype="multipart/form-data"
                    class="wrap card"
                    aria-labelledby="title"
                >';
    matchReportFormHeaderView();
    matchDescriptorFieldSetView();
    matchReportFieldSetView();
    sanctionsReportsFieldSetView();
    matchNotesAndSubmitFieldset();
    echo'</form>
        <div id="loadingMessage">Uploading your report. Please wait for confirmation. Depending on the age of your phone, and the quality of your connection, this may take up to a minute.</div>
        </main>
        </body>
        </html>';
};

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
    matchReportFormView();
}