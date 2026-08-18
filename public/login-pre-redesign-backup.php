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

function gameResultsInsertComplete() {
    echo'<!DOCTYPE html>
        <head>
            <script type="text/javascript" src="gameCardsPreview.js" defer></script>
            <script type="text/javascript" src="matchDetailVisibility.js" defer></script>
            <script type="text/javascript" src="sanctionEntryVisibility.js" defer></script>
            <script type="text/javascript" src="formManagement.js" defer></script>
            <style>
            body {
                background-repeat: no-repeat;
                background-attachment: fixed; 
                background-size: 50% 100%;
                background-position: center top;
                background-color: rgba(255,255,255,0.85);
                background-blend-mode: lighten;
                background-image: url(\'88_logo.png\'); 
            }
            img{
                display: block;
                margin-left: auto;
                margin-right: auto;
            }
            .topper1{
                float: left;
                width: 25%;
                margin: auto;
            }
            .topper2{
                width: 50%;
                text-align: center;
                margin: auto;
            }
            .topper3{
                float: right;
                width: 25%;
                margin: auto;
                text-align: right;
            }
            .buttonHolder{
                text-align: center;
            }
            h1, h2, h3, h4{
                text-align: center;
            }
            .section_sanction-entry, .section_sanction-summary, .section_sanction-info-entry, .button_sanction-entry, .section-match-entry, .section_ref_entry, .section_photo-gamecards, .p_sanction-level-detail, .submitButtonHolder, .section_sanction-detail{
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .section_sanctioned-level-detail, .section_sanctioned-party-detail{
            display: flex;
                flex-direction: row;
                align-items: center;
            }
            .sanction_level_divs{
            flex-direction: row;
            }

            /* The Modal (background) */
            .modal {
                display: none; /* Hidden by default */
                position: fixed; 
                z-index: 1; 
                left: 0;
                top: 0;
                width: 100%; 
                height: 100%; 
                overflow: auto; 
                background-color: rgb(0,0,0); 
                background-color: rgba(0,0,0,0.4); 
            }

            /* Modal Content */
            .modal-content {
                background-color: #fefefe;
                margin: 15% auto; 
                padding: 20px;
                border: 1px solid #888;
                width: 80%; 
                max-width: 500px;
            }

            /* The Close Button */
            .close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
            }

            .close:hover,
            .close:focus {
                color: black;
                text-decoration: none;
                cursor: pointer;
            }

            /* Image styling */
            .modal-content img {
                width: 100%;
                height: auto;
            }
            
            #loadingMessage {
            display: none;
            font-size: 18px;
            }

            </style>';
        echo'</head>';
        echo'<body>';
            echo'<div class="LogOn">
                    <div class="topper1">
                        <button data-nav-type="login">Log In</button>
                    </div>
                    <div class="topper3">
                        <button data-nav-type="return">Return to Match Entry</button>
                    </div>
                    <div class="topper2">
                        <h1>AYSO Region 88 Game Result Entry System</h1>
                    </div>
                </div>';
        
            echo'<form id="form_game-results-entry" name ="gameResultsEntryForm" method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data">';
            echo'<h4>Brought to You By Glendale Soccer Scores (gss.org)</h4>
                <h1>Referee Game Report</h1>';
            echo'<fieldset>';
                setUpDateAndTimeSelectors();
                refNames(0);
                refNames(1);
                refNames(2);
                refStaffingIssueGPT2();
                gameCardsPhotoEntrySegment(1);
                gameCardsPhotoEntrySegment(2);
                sanctionReportBasic(1);
                sanctionReportBasic(2);
                sanctionReportBasic(3);
                sanctionReportBasic(4);
                sanctionReportBasic(5);
                sanctionReportBasic(6);
                otherMatchIssueGPT2();
                echo'</fieldset> </br>
                <div class="submitButtonHolder">
                    <input type="submit" value="Submit the Match Results" name="submit" id="submit"/>
                </div>
            </form>
            <div id="loadingMessage">Uploading your report. Please wait for confirmation. Depending on the age of your phone, and the quality of your connection, this may take up to a minute.</div>
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
    gameResultsInsertComplete();
}