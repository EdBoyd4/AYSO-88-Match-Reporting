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
include_once GSS88_MODELS_REPORTS . '/model-report-entry.php'; // handles inserts for report
include_once GSS88_VIEWS_REPORTS . '/view-section-match-detail.php';
include_once GSS88_VIEWS_REPORTS . '/view-section-sanction-entry.php';
include_once GSS88_CONTROLLERS_UPLOAD . '/controller-match-report-sanitize-and-enter.php';
include_once GSS88_CONTROLLERS_UPLOAD . '/controller-match-report-email.php';

function matchReportFormHeaderView(){
    echo'<header class="form_ref_match-header">
                <img src="88_logo.png" alt="AYSO Region 88 Glendale logo" class="logo" />
                <h1 id="title">AYSO Region 88<br>Fall Core</h1>
                <h2>Referee Game Report</h2>
                <p class="tagline">Brought to You By Glendale Soccer Scores (gss.org)</p>
            </header>';
}

function matchReportFormView() {
    $gamePickerOptions = selectScheduledMatchOptionsFromDatabaseEXP();
    ?><!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>AYSO Region 88 – Referee Match Report</title>
            <script> const allMatches = <?= 
                json_encode($gamePickerOptions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            ?></script>
            <script type="text/javascript" src="gameCardsPreview.js" defer></script>
            <script type="text/javascript" src="matchDetailVisibility.js" defer></script>
            <script type="text/javascript" src="sanctionEntryVisibility.js" defer></script>
            <script type="text/javascript" src="formManagement.js" defer></script>
            <link rel="stylesheet" href="styles-gss88-match-report-form.css">
        </head>
        <body>
            <div class="bg" aria-hidden="true"></div>
            <main>
                <form
                    id="form_ref_match_report"
                    name="gameResultsEntryForm"
                    method="post"
                    action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                    enctype="multipart/form-data"
                    class="wrap card"
                    aria-labelledby="title"
                >
    <?php
    matchReportFormHeaderView();
    matchDescriptorFieldSetView();
    matchReportFieldSetView();
    sanctionsReportsFieldSetView();
    matchNotesAndSubmitFieldset();
    ?></form>
        <div id="loadingMessage"
            class="loading-message"
            aria-live="polite"
            hidden
            >
            Uploading your report. Please wait for confirmation. Depending on the age
            of your phone, and the quality of your connection, this may take up to a minute.
        </div>
        </main>
        </body>
        </html>
    <?php
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