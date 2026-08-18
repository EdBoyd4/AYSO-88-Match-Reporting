<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once $rootDir.DIRECTORY_SEPARATOR. '/vendor/autoload.php';
//require '../vendor/autoload.php';
//require '/home3/noxzbfte/vendor/autoload.php';

function divisionMatcher($dataForEmail){
    $stringForEmail = 'division'.$dataForEmail['division'];
    // Search through the array
    foreach (R88_TEAM_DIVISION_ITEMS as $item) {
        if ($item['label'] === $stringForEmail) {
            $divisionForEmail = $item['text'];
            break; // Exit the loop once the match is found
        }
    }
    // Output the result
    return $divisionForEmail;
}

function pitchMatcher($dataForEmail){
    $stringForEmail = 'pitch'.$dataForEmail['field'];
    // Search through the array
    foreach (R88_FIELD_ITEMS as $item) {
        if ($item['label'] === $stringForEmail) {
            $fieldForEmail = $item['text'];
            break; // Exit the loop once the match is found
        }
    }
    // Output the result
    return $fieldForEmail;
}

function sanctionAmountChecker($dataForEmail){
    $numberOfSanctions = 0;
    foreach ($dataForEmail as $key => $value) {
        if (preg_match('/^sanction_(\d+)$/', $key) && is_numeric($value) && $value >= 0 && $value <= 5) {
            error_log('key is '.$key);
            $value = (int)$value; // Convert to integer for comparison
            if ($numberOfSanctions === 0 || $value > $numberOfSanctions) {
                $numberOfSanctions = $value;
            error_log('number of sanctions is '.$value);
            }
        }
    }
    return $numberOfSanctions;
}

function refAmountChecker($dataForEmail){
    $numberOfRefsShort = 0;
    
    if(!isset($dataForEmail['referee_2']) || empty($dataForEmail['referee_2'])){
        ++$numberOfRefsShort;
    }
    
    if(!isset($dataForEmail['referee_3']) || empty($dataForEmail['referee_3'])){
        ++$numberOfRefsShort;
    }
    
    return $numberOfRefsShort;
}


// subject line format
// NOTICE - # SANCTIONS – # REFS SHORT - REF ISSUE – MATCH EVENT – AYSO R88 - Date – Division – Location – Time
function generateSubjectString($dataForEmail){
    // begin the string
    $subjectString = 'NOTICE'.' - ';
    
    if($dataForEmail['sanctions_issued_in_match']){
    $subjectString.= $dataForEmail['sanctions_issued_in_match'].' SANCTION(S) - ';
    }
    
    
    // check for full ref crew
    $refsMissingNumber = refAmountChecker($dataForEmail);
    if($refsMissingNumber > 0){
        $subjectString.= $refsMissingNumber.' REF(S) SHORT - ';
    }
    if($dataForEmail['ref_staffing_issue']){
        $subjectString.='REF ISSUE'.' - ';
    }
    if($dataForEmail['match_issue']){
        $subjectString.='MATCH EVENT'.' - ';
    }
    $subjectString.='AYSO R88'.' - ';
    $subjectString.= $dataForEmail['match_date'].' - ';
    $subjectString.= $dataForEmail['division_name'].' - ';
    $subjectString.= $dataForEmail['field_name'].' - ';
    $subjectString.= $dataForEmail['match_time'];
    error_log('subject string - '.$subjectString);
    return $subjectString;
}

function matchInfo($dataForEmail){
    $matchInfoString ='<table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 10px; border: 1px solid #ccc;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="font-weight: bold; font-size: 16px; padding-bottom: 5px;">
                            Game Information
                        </td>
                    </tr>
                    <tr>
                        <td>';
                            $matchInfoString.='<p><strong>Date Game Was Played:</strong> '.$dataForEmail['match_date'].'</p>';
                            $matchInfoString.='<p><strong>Scheduled Start Time:</strong> '.$dataForEmail['match_time'].'</p>';
                            $matchInfoString.='<p><strong>Field:</strong> '.$dataForEmail['field_name'].'</p>';
                            $matchInfoString.='<p><strong>Division:</strong> '.$dataForEmail['division_name'].'</p>';
                            $matchInfoString.='<p><strong>Reporting Referee:</strong> '.$dataForEmail['referee_1'].'</p>';
                            $matchInfoString.='<p><strong>Assistant Referee #1:</strong> '.$dataForEmail['referee_2'].'</p>';
                            $matchInfoString.='<p><strong>Assistant Referee #2:</strong> '.$dataForEmail['referee_3'].'</p>';
                        $matchInfoString.='</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';
    return $matchInfoString;
}

function sanctionsInfo($dataForEmail){
    error_log('in the sanctions method');
    //$dataForEmail[sanction_level_1]
    $matchInfoString='';
    if($dataForEmail['sanctions_issued_in_match'] > 0){
        
    error_log('found the counter');
        for ($i = 1; $i <= $dataForEmail['sanctions_issued_in_match']; $i++) {

    error_log('found loop '.$i);

            $sanctionLevelKey = 'sanction_level_' . $i;
            $sanctionPartyTypeKey = 'sanctioned_party_' . $i;
            $sanctionPartyDescriptionKey = 'sanction-party-description-' . $i;
            $sanctionCauseSummaryKey = 'sanction' . $i . 'SummaryText';

            // Sanction level
            $sanctionLevelKey = 'sanction_level_' . $i;
            $sanctionPartyTypeKey = 'sanctioned_party_' . $i;
            $sanctionPartyDescriptionKey = 'party_description_' . $i;
            $sanctionCauseSummaryKey = 'event_description_' . $i;
            $sanctionNumber = $i;
            $sanction_level = $dataForEmail[$sanctionLevelKey];
            $sanctioned_party = $dataForEmail[$sanctionPartyTypeKey];
            $party_description = $dataForEmail[$sanctionPartyDescriptionKey];
            $event_description = $dataForEmail[$sanctionCauseSummaryKey ];
            $matchInfoString.='<table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="padding: 10px; border: 1px solid #ccc;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td style="font-weight: bold; font-size: 16px; padding-bottom: 5px;">
                                    <strong>Sanction Number '.$sanctionNumber.' of '.$dataForEmail['sanctions_issued_in_match'].'</strong>
                                </td>
                            </tr>
                            <tr>
                                <td>';
                                $matchInfoString.='<p><strong>Sanction Issued:</strong> '.($sanction_level === 0 ? 'Caution' : 'Sending-Off').'</p>';                        
                                $matchInfoString.='<p><strong>Sanctioned Party Type:</strong> '.($sanctioned_party === 0 ? 'Player' : 'Coach').'</p>';
                                $matchInfoString.='<p><strong>Sanctioned Party Description:</strong> '.$party_description.'</p>';
                                $matchInfoString.='<p><strong>Reason for Sanction:</strong> '.$event_description.'</p>';
            $matchInfoString.='</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>';
                }
            }
    return $matchInfoString;
}

function refAmountInfo($dataForEmail){
    $matchInfoString ='<table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 10px; border: 1px solid #ccc;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="font-weight: bold; font-size: 16px; padding-bottom: 5px;">
                            <strong>Names of Participating Referees</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>';
                            $matchInfoString.='<p><strong>Name of Referee:</strong> '.$dataForEmail['referee_1'].'</p>';
                            $trackingNumberForARs=0;
                            if($dataForEmail['referee_2']){
                                ++$trackingNumberForARs;
                                $matchInfoString.='<p><strong>Name of Assistant Referee '.$trackingNumberForARs.':</strong> '.$dataForEmail['referee_2'].'</p>';
                            }
                            if($dataForEmail['referee_3']){
                                ++$trackingNumberForARs;
                                $matchInfoString.='<p><strong>Name of Assistant Referee '.$trackingNumberForARs.':</strong> '.$dataForEmail['referee_3'].'</p>';
                            }
                        $matchInfoString.='</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';
    return $matchInfoString;
}

function staffingInfo($dataForEmail){
    $matchInfoString ='<table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 10px; border: 1px solid #ccc;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="font-weight: bold; font-size: 16px; padding-bottom: 5px;">
                            <strong>Referee Staffing Issue:</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>';
                        $matchInfoString.='<p>'.$dataForEmail['ref_staffing_issue'].'</p>';
                        $matchInfoString.='</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';
    return $matchInfoString;
}

function otherInfo($dataForEmail){
    $matchInfoString ='<table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 10px; border: 1px solid #ccc;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="font-weight: bold; font-size: 16px; padding-bottom: 5px;">
                            Misc Match Issue:
                        </td>
                    </tr>
                    <tr>
                        <td>';
                        $matchInfoString.='<p>'.$dataForEmail['match_issue'].'</p>';
                        $matchInfoString.='</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';
    return $matchInfoString;
}


function generateBodyText($dataForEmail){
    $stringForBodyText = '';
    $stringForBodyText.= matchInfo($dataForEmail);
    
    if($dataForEmail['sanctions_issued_in_match']){
        $stringForBodyText.= sanctionsInfo($dataForEmail);
    }
    
    // check for full ref crew
    $refsMissingNumber = refAmountChecker($dataForEmail);
    if($refsMissingNumber > 0){
        $stringForBodyText.= refAmountInfo($dataForEmail);
    }
    if($dataForEmail['ref_staffing_issue']){
        $stringForBodyText.= staffingInfo($dataForEmail);
    }
    if($dataForEmail['match_issue']){
        $stringForBodyText.= otherInfo($dataForEmail);
    }
    
    error_log('got the match info in the body');
    
    return $stringForBodyText;
}

function generateGameCardLink($dataForEmail) {
    
    error_log('got to the game cards');
    
    $gamecardFileNames = [];
    
    // Define the root directory and game cards directory
    $rootDir = realpath($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '..');
    $gameCardsDir = $rootDir . DIRECTORY_SEPARATOR . 'AYSORegion88GameCards';
    
    // Construct the full directory path - matches how
    // controller-match-report-photo-upload.php actually saves these:
    // <date>/<division name>/<field name>/<time>, names sanitized the
    // same way (sanitizeForFilesystem() comes from that file).
    $fullDirPath = $gameCardsDir . DIRECTORY_SEPARATOR .
                   $dataForEmail['match_date'] . DIRECTORY_SEPARATOR .
                   sanitizeForFilesystem($dataForEmail['division_name']) . DIRECTORY_SEPARATOR .
                   sanitizeForFilesystem($dataForEmail['field_name']) . DIRECTORY_SEPARATOR .
                   $dataForEmail['file_time'];
    
    // Check if the full directory path exists
    if (!is_dir($fullDirPath)) {
        // Handle the error, maybe log it or throw an exception
        // For now, return an empty array
        return [];
    }
    
    // Iterate over dataForEmail to find and construct file paths
    foreach ($dataForEmail as $key => $value) {
        if (preg_match('/^gamecard_filename_(\d+)$/', $key) && !empty($value)) {
            // Sanitize file name if necessary
            $gamecardFileNames[] = $fullDirPath . DIRECTORY_SEPARATOR . htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }
    
    error_log('got the game cards in the body');
    
    return $gamecardFileNames;
}

function getSanctionsIssuedInMatch($dataForEmail){
    // Initialize the highest sanction number to a very low value or NULL
    $highestSanctionNumber = null;
    // Loop through each possible sanction number column
    for ($i = 1; $i <= 5; $i++) {
        $key = "sanction_number_" . $i;
        if (isset($dataForEmail[$key]) && $dataForEmail[$key] !== NULL) {
            if ($highestSanctionNumber === NULL || $dataForEmail[$key] > $highestSanctionNumber) {
                $highestSanctionNumber = $dataForEmail[$key];
            }
        }
    }
    return $highestSanctionNumber;
}
    
function getMatchInfoForEvaluation($matchId){
    error_log('key sent for report: '.$matchId);
    
error_log('Initial memory usage: ' . memory_get_usage() . ' bytes');
error_log('Initial peak memory usage: ' . memory_get_peak_usage() . ' bytes');

    $dataForEmail = getMatchDetailsForEmail2($matchId);
    $dataForEmail['sanctions_issued_in_match'] = getSanctionsIssuedInMatch($dataForEmail);
    //$dataForEmail = $dataToUse[0];
    error_log('queried Data: ' . print_r($dataForEmail, true));
    // error_log('queried Data: ' . print_r($dataForEmail, true));
    // if any of the triggering conditions are true, then the varialbe will be true.
    /* $emailNeeded = (
        is_null($dataForEmail['referee_2']) || is_null($dataForEmail['referee_3']) || 
        !is_null($dataForEmail['sanction_level_1']) || !is_null($dataForEmail['ref_staffing_issue']) || !is_null($dataForEmail['match_issue'])
    );     */
    // if not null
    if(true){
        // initiate email formation
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
        //$mail->Host       = $smtpConfig['host'];
        $mail->Host       = 'gss88.org';
        $mail->SMTPAuth   = true;
        //$mail->Username   = $smtpConfig['username'];
        $mail->Username   = 'sanctionReports@gss88.org';
        //$mail->Password   = $smtpConfig['password'];
        $mail->Password   = 'thank god that was a bad password!';
        //$mail->SMTPSecure = $smtpConfig['secure'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        //$mail->Port       = $smtpConfig['port'];
        $mail->Port       = 465; 

            // Recipients
            $mail->setFrom('sanctionReports@gss88.org', 'Region 88 - AYSO - Match Report System - RAMaReS');
            // set up recipient list
            // each type of issue is sent to the relevant people
            $mail->addAddress('edwin.b@ayso88.org'); // referee administrator
            if (!GSS88_EMAIL_DEV_MODE) {
            // RRA gets all the emails - every game
            $mail->addAddress('craig.d@ayso88.org');
            $mail->addAddress($dataForEmail['dc_email']); // division coordinator
            // sanctions are also sent to player behavior tracker
            if($dataForEmail['sanction_level_1']){
                $mail->addAddress('scottnord@me.com');
                //$mail->addAddress('cvpa@ayso88.org'); // and match issues
            }
            // match issues are also sent to Field / Equipment Manager
            if($dataForEmail['match_issue']){
                $mail->addAddress('scottnord@me.com');
                $mail->addAddress('fields@ayso88.org');
            }
            }
            //$mail->addAddress();

            // Content
            $mail->isHTML(true);
            /* $numberOfSanctions = sanctionAmountChecker($dataForEmail);
            $divisionForEmail = divisionMatcher($dataForEmail);
            $fieldForEmail = pitchMatcher($dataForEmail); */
            
            //error_log('$numberOfSanctions of '.$numberOfSanctions);
            //error_log('$divisionForEmail of '.$divisionForEmail);
            //error_log('$fieldForEmail of '.$fieldForEmail);

            // Generate subject line string
            $mail->Subject = generateSubjectString($dataForEmail);

            // generate array for body string
            $mail->Body = generateBodyText($dataForEmail);
            // generate and verify path to images of gamecards
            $gameCardsPhotosFoldersAndFiles = generateGameCardLink($dataForEmail);
    
    error_log('home stretch');
            
            foreach ($gameCardsPhotosFoldersAndFiles as $file) {
                error_log('File size for ' . $file . ' is ' . filesize($file) . ' bytes.');
                $mail->addAttachment($file);
            }
    
    error_log('ready to send');
    
    error_log('Memory usage before sending email: ' . memory_get_usage() . ' bytes');
error_log('Peak memory usage before sending email: ' . memory_get_peak_usage() . ' bytes');
    
            // Send the email
            $mail->send();
            
            error_log('Memory usage after sending email: ' . memory_get_usage() . ' bytes');
error_log('Peak memory usage after sending email: ' . memory_get_peak_usage() . ' bytes');
            
        // good for dev, not for production
        //    echo 'Message has been sent';
        } catch (Exception $e) {
            error_log('Notification email failed to send: ' . $mail->ErrorInfo);
        //    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}