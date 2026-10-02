<?php

// Real source lives here at the project root, not in public/ -- public/login.php
// is a symlink to this file (the QR code on the physical lineup cards points at
// /login.php, and needs to keep working without a redirect hop). The name
// mismatch (file is match-report.php, URL is login.php) is deliberate and
// temporary: both names get a proper rename/refactor after this season, once
// the physical cards can be reprinted with whatever URL we land on.
//
// Because this is reached via a symlink, __DIR__ resolves to this file's real
// location (this directory), not public/ -- confirmed empirically, PHP follows
// the symlink for __FILE__/__DIR__. $_SERVER['PHP_SELF']/DOCUMENT_ROOT below
// are unaffected either way, since those reflect the requested URL and the
// webserver's configured docroot, not this file's own path.

require_once __DIR__ . '/controllers/rapp-bootstrap.php';

$sessionManager->startSession();

date_default_timezone_set('America/Los_Angeles');

$dateTime = new DateTime();
$currentDateFromSys = $dateTime->format('Y-m-d');
$currentTimeFromSys = $dateTime->format('H:i');
$sessionId = session_id();

include_once __DIR__ . '/config-ref-match-reporting/constants-GSS-88-file-paths.php';
// handles select to identify match
include_once GSS88_CONFIG_FILES . '/constants-model-GSS-88-match-and-sanction-db.php';
include_once GSS88_MODELS_REPORTS . '/model-match-data-insert.php'; // handles inserts for report (PDO)
include_once GSS88_MODELS_REPORTS . '/model-match-data-query.php'; // all match-data reads (+ the notification email's own query)
include_once GSS88_VIEWS_REPORTS_COMPOUND . '/class-view-match-report-form.php';
include_once GSS88_CONTROLLERS_UPLOAD . '/controller-match-report-sanitize-and-enter.php';

// $dbhost/$dbname/$dbuser/$dbpass come from constants-model-GSS-88-match-and-
// sanction-db.php above. One PDO connection, shared by both MatchDataQueries
// (reads) and MatchDataInserter (writes) -- also what the RAPP dashboard
// uses to read this same database.
$matchDataPdo = new PDO(
    "mysql:host={$dbhost};dbname={$dbname};charset=utf8mb4",
    $dbuser,
    $dbpass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
$matchDataQueries = new MatchDataQueries($matchDataPdo);
$matchDataInserter = new MatchDataInserter($matchDataPdo);

// controller-match-report-email.php expects $rootDir (for its
// vendor/autoload.php require) to already be set by whatever includes it -
// same convention controller-match-report-photo-upload.php uses. Both derive
// it from DOCUMENT_ROOT, not this file's own path, so this is unaffected by
// being reached through the login.php symlink.
$rootDir = realpath($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '..');

// TEMPORARY while testing locally, between seasons: this sends real mail
// (real staff addresses, real SMTP creds, both hardcoded in the file below)
// as soon as it's included, so every recipient except the referee
// administrator is suppressed until this is flipped back to false for
// production. See TODO- GSS88MatchReports.md.
define('GSS88_EMAIL_DEV_MODE', true);
include_once GSS88_CONTROLLERS_UPDATES . '/controller-match-report-email.php';

/**
 * Optional, inline "also file a RAPP report" step -- see
 * views/view-match-report-entry-AYSO-88/focused/class-view-rapp-incident-
 * fieldset.php and public/match-report-rapp-otp.php for the request/verify
 * half of this. Filing a RAPP report is IN ADDITION to filing the match
 * report, never a substitute for it: this only ever runs AFTER
 * insertMatchData() has already succeeded, and any problem here is logged
 * and swallowed rather than turning a successful match report into a
 * failure. $matchReportId is match_reports._id (not scheduled_matches._id).
 */
function maybeCreateInlineRappReport(int $scheduledMatchId, string $ip): void
{
    global $rappReportQueries, $rappReportInserter, $rappAudioValidator, $rappMediaStore,
           $rappNotifier, $rappConfig, $auditRepository;

    $verifiedUserId = $_SESSION['rapp_inline_verified_user_id'] ?? null;
    $verifiedEmail = $_SESSION['rapp_inline_verified_email'] ?? null;
    // Single-use regardless of outcome below, so a stale verification from
    // this match can never carry over and get silently attached to a
    // different, later match report in the same browser session.
    unset($_SESSION['rapp_inline_verified_user_id'], $_SESSION['rapp_inline_verified_email']);

    if ($verifiedUserId === null) {
        return; // Section never used, or never completed verification.
    }

    $writtenAccount = trim((string) ($_POST['rapp_written_account'] ?? ''));
    $hasAudioUpload = isset($_FILES['rapp_audio']) && ($_FILES['rapp_audio']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($writtenAccount === '' && !$hasAudioUpload) {
        return; // Verified, but never actually described anything -- nothing to file.
    }

    if (!$rappReportQueries->isWithinReportWindow($scheduledMatchId, $rappConfig['report_window_hours'])) {
        // Same policy report.php enforces; match-report.php has no
        // equivalent of report.php's inline notice for this edge case (this
        // page redirects to a static thank-you page on success), so this is
        // logged rather than surfaced. Expected to be rare in practice: a
        // match report is normally filed close to the match itself.
        error_log("Inline RAPP report skipped for scheduled match {$scheduledMatchId}: outside the {$rappConfig['report_window_hours']}h reporting window");
        return;
    }

    try {
        $audioMeta = null;
        if ($hasAudioUpload) {
            $audioMeta = $rappAudioValidator->validate($_FILES['rapp_audio']);
        }

        // has_audio starts false regardless -- addMedia() below is what flips
        // it to true once the file is actually stored, same as report.php.
        $reportId = $rappReportInserter->createReport(
            $scheduledMatchId,
            (int) $verifiedUserId,
            'otp_users',
            $writtenAccount !== '' ? $writtenAccount : null,
            false
        );

        if ($audioMeta !== null) {
            $relPath = $rappMediaStore->store($reportId, $_FILES['rapp_audio']['tmp_name'], $audioMeta['ext']);
            $rappReportInserter->addMedia($reportId, $relPath, $audioMeta['mime'], $audioMeta['bytes'], $audioMeta['original_name']);
        }

        $auditRepository->logEvent('rapp_report_filed', $ip, (string) $verifiedEmail, 'RAPP report #' . $reportId . ' (via login.php)');

        $full = $rappReportQueries->findReport($reportId);
        $recipients = $rappReportQueries->notificationRecipients(['rra', 'senior_board']);
        if ($full !== null) {
            $rappNotifier->notifyNewReport($full, $recipients);
        }
    } catch (\Throwable $e) {
        error_log('Inline RAPP report failed for scheduled match ' . $scheduledMatchId . ': ' . $e->getMessage());
    }
}

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
    $matchReportId = $matchDataInserter->insertMatchData($headerDataItems);

    error_log("data uploaded: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);

    // The match report is already saved at this point -- nothing below this
    // line may turn that success into a failure.
    try {
        getMatchInfoForEvaluation($matchReportId);
    } catch (\Throwable $e) {
        error_log('Notification email failed for match report ' . $matchReportId . ': ' . $e->getMessage());
    }

    maybeCreateInlineRappReport((int) $headerDataItems['scheduled_match_id'], $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    header('Location: logout.php');
    exit;
}else{
    error_log("GET received: Session ID: " . $sessionId. " date: ".$currentDateFromSys." time: ".$currentTimeFromSys);
    $rappCsrfToken = $sessionManager->generateCsrfToken();
    (new MatchReportForm($_SERVER['PHP_SELF'], $matchDataQueries, $rappCsrfToken))->render();
}
