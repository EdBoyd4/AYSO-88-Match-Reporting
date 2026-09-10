<?php
declare(strict_types=1);

/**
 * RAPP retention + auth housekeeping. Run from cron, e.g. hourly:
 *
 *   15 * * * *  /usr/bin/php /home/xnbglkce/gss88/rapp/bin/rapp-retention.php >> /home/xnbglkce/gss88-rapp-retention.log 2>&1
 *
 * For every RAPP report older than the retention window that is NOT flagged
 * `retain`: delete the audio file(s), null the written account, stamp
 * content_purged_at, set status = closed. The metadata row is kept for the
 * season archive. Also prunes expired OTP codes and stale login attempts.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/rapp-bootstrap.php';

$startedAt = date('c');
$hours = (int) $rappConfig['retention_hours'];
$purged = 0;
$filesDeleted = 0;

foreach ($rappReports->reportsDueForPurge($hours) as $row) {
    $reportId = (int) $row['_id'];
    foreach ($rappReports->mediaForReport($reportId) as $media) {
        if ($media['purged_at'] === null) {
            $rappMediaStore->delete($media['filename']);
            $filesDeleted++;
        }
    }
    $rappReports->markContentPurged($reportId);
    $auditRepository->logEvent('rapp_content_purged', '0.0.0.0', null, 'RAPP report #' . $reportId . ' content purged after ' . $hours . 'h');
    $purged++;
}

// Auth housekeeping (both are safe no-ops if there's nothing to do).
try {
    $otpManager->purgeExpired();
    $attemptRepository->cleanupOldAttempts($config->getLockoutTimeSeconds());
} catch (\Throwable $e) {
    error_log('rapp-retention: auth housekeeping failed: ' . $e->getMessage());
}

echo sprintf(
    "[%s] rapp-retention: purged %d report(s), deleted %d file(s) (window %dh)\n",
    $startedAt,
    $purged,
    $filesDeleted,
    $hours
);
