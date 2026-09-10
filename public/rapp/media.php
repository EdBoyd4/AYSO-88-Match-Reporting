<?php
declare(strict_types=1);

/**
 * Authenticated audio streaming. The only way to reach a RAPP recording -- the
 * files live outside the web root. Requires rapp.view; every access is logged
 * to rapp_media_access_log.
 *
 *   /rapp/media.php?id=<rapp_media._id>
 */

require_once __DIR__ . '/../../rapp/config/rapp-bootstrap.php';

$basePath = $rappConfig['base_path'];
$ctx = rapp_require('rapp.view', $pdo, $authManager, $sessionManager, $accessPolicy, $basePath);

$mediaId = (int) ($_GET['id'] ?? 0);
$media = $mediaId > 0 ? $rappReports->findMedia($mediaId) : null;

if ($media === null || $media['purged_at'] !== null) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Not available.';
    exit;
}

$path = $rappMediaStore->resolve($media['filename']);
if ($path === null || !is_file($path)) {
    error_log('RAPP media row ' . $mediaId . ' has no file on disk: ' . $media['filename']);
    http_response_code(410);
    header('Content-Type: text/plain');
    echo 'The recording is no longer stored.';
    exit;
}

$rappReports->logMediaAccess((int) $media['rapp_report_id'], $ctx['user_id']);
$auditRepository->logEvent('rapp_media_played', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $ctx['email'], 'media #' . $mediaId . ' report #' . $media['rapp_report_id']);

$size = filesize($path);
header('Content-Type: ' . ($media['mime'] ?: 'application/octet-stream'));
header('Content-Length: ' . $size);
header('Content-Disposition: inline; filename="rapp-' . (int) $media['rapp_report_id'] . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
header('Accept-Ranges: none');

// Files are small (a short voice memo); a plain readfile is fine.
readfile($path);
