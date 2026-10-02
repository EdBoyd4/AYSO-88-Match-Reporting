<?php
declare(strict_types=1);

require_once __DIR__ . '/../../controllers/rapp-bootstrap.php';
require_once __DIR__ . '/../../views/rapp-layout.php';
require_once __DIR__ . '/../../views/views-rapp-report-entry-AYSO-88/class-view-rapp-match-context.php';

$basePath = $rappConfig['base_path'];
$ctx = rapp_require('rapp.submit', $userAccessPdo, $authManager, $sessionManager, $accessPolicy, $basePath);

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$windowHours = $rappConfig['report_window_hours'];
$windowDays = (int) round($windowHours / 24);
$matches = $rappReportQueries->recentScheduledMatches($windowHours);
$error = null;
$submitted = isset($_GET['submitted']);

// A caller that already knows which match -- e.g. linked from match-report
// context -- can skip the dropdown by passing ?match=<scheduled_match_id>.
// Falls back to the normal dropdown if it's missing, unknown, or has fallen
// outside the filing window: never a hard error just for a stale link, since
// the referee can still pick the right match by hand.
$preselectedMatchId = isset($_GET['match']) ? (int) $_GET['match'] : null;
$preselectedMatch = null;
if ($preselectedMatchId !== null && $preselectedMatchId > 0
    && $rappReportQueries->isWithinReportWindow($preselectedMatchId, $windowHours)) {
    // matchRow() (not RappReportQueries' own picker query) because it
    // already includes match_issue ("Additional Notes") and the officiants --
    // everything RappMatchContextView::render() needs in one call.
    $preselectedMatch = $rappMatchData->matchRow($preselectedMatchId);
}

if (!$submitted && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfOk = $sessionManager->validateCsrfToken($_POST['csrf_token'] ?? '');
    $matchId = (int) ($_POST['scheduled_match_id'] ?? 0);
    $bodyText = trim((string) ($_POST['body_text'] ?? ''));
    $hasAudioUpload = isset($_FILES['audio']) && ($_FILES['audio']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if (!$csrfOk) {
        $error = 'Your session expired or the form was invalid. Please try again.';
    } elseif ($matchId <= 0 || !$rappReportQueries->isWithinReportWindow($matchId, $windowHours)) {
        // Covers both "no match chosen" and "chosen match is outside the
        // window" -- isWithinReportWindow() checks existence too, so an
        // unknown id lands here as well, not a separate case.
        $error = "Please choose a match from the last {$windowDays} days.";
    } elseif ($bodyText === '' && !$hasAudioUpload) {
        $error = 'Add a written account, a voice recording, or both.';
    } else {
        $audioMeta = null;
        try {
            if ($hasAudioUpload) {
                $audioMeta = $rappAudioValidator->validate($_FILES['audio']);
            }

            $reportId = $rappReportInserter->createReport($matchId, $ctx['user_id'], (string) $ctx['user_source'], $bodyText !== '' ? $bodyText : null, false);

            if ($audioMeta !== null) {
                $relPath = $rappMediaStore->store($reportId, $_FILES['audio']['tmp_name'], $audioMeta['ext']);
                $rappReportInserter->addMedia($reportId, $relPath, $audioMeta['mime'], $audioMeta['bytes'], $audioMeta['original_name']);
            }

            $auditRepository->logEvent('rapp_report_filed', $ip, $ctx['email'], 'RAPP report #' . $reportId);

            try {
                $full = $rappReportQueries->findReport($reportId);
                $recipients = $rappReportQueries->notificationRecipients(['rra', 'senior_board']);
                if ($full !== null) {
                    $rappNotifier->notifyNewReport($full, $recipients);
                }
            } catch (\Throwable $e) {
                error_log('RAPP notify failed for report ' . $reportId . ': ' . $e->getMessage());
            }

            header('Location: report.php?submitted=1');
            exit;
        } catch (RappAudioException $e) {
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            error_log('RAPP submission error: ' . $e->getMessage());
            $error = 'Something went wrong saving your report. Please try again.';
        }
    }
}

$csrf = $sessionManager->generateCsrfToken();
rapp_layout_head('File a report', $ctx['name'], $basePath);

if ($submitted): ?>
<div class="card">
    <h1>Report received</h1>
    <p class="lead">Thank you. The Regional Referee Administrator has been notified.
    Your recording and written account are stored securely and retained for
    <?= (int) $rappConfig['retention_hours'] ?> hours unless preserved for follow-up.</p>
    <a class="btn" href="/login.php">Done</a>
</div>
<?php else: ?>
<div class="card">
    <h1>File a Referee Abuse Report</h1>
    <p class="lead">For incidents where someone behaved abusively toward a referee
    or assistant referee. Please file as soon as possible after the match --
    reports can be filed for up to <?= $windowDays ?> days afterward.</p>

    <?php if ($error): ?><div class="msg error"><?= rapp_esc($error) ?></div><?php endif; ?>
    <?php if ($preselectedMatchId !== null && $preselectedMatch === null): ?>
        <div class="msg info">That link's match is more than <?= $windowDays ?> days old,
        or no longer exists — choose the correct match below.</div>
    <?php endif; ?>

    <div class="guidance">
        <strong>Before you record:</strong>
        <ul>
            <li>Record <strong>only yourself</strong>, speaking <strong>privately</strong>.
                Do not record the incident as it happens, other people, or a conversation.</li>
            <li>Refer to people the way the misconduct reports do — team, player or
                coach, jersey number, description — rather than by full name where you can.</li>
        </ul>
    </div>

    <form method="post"
          action="report.php<?= $preselectedMatch !== null ? '?match=' . (int) $preselectedMatch['_id'] : '' ?>"
          enctype="multipart/form-data" class="mt-16">
        <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrf) ?>">

        <?php if ($preselectedMatch !== null): ?>
            <input type="hidden" name="scheduled_match_id" value="<?= (int) $preselectedMatch['_id'] ?>">
            <?php (new RappMatchContextView())->render($preselectedMatch); ?>
        <?php else: ?>
            <label for="scheduled_match_id">Which match?</label>
            <select name="scheduled_match_id" id="scheduled_match_id" required>
                <option value="">— choose the match —</option>
                <?php foreach ($matches as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"><?= rapp_esc($m['label']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <label for="body_text">Written account <span class="muted">(optional if you attach audio)</span></label>
        <textarea name="body_text" id="body_text"
                  placeholder="What happened, who was involved (by team / role / number), and when."><?= rapp_esc($_POST['body_text'] ?? '') ?></textarea>

        <label for="audio">Voice recording <span class="muted">(optional if you write an account)</span></label>
        <input type="file" name="audio" id="audio" accept="audio/*" capture>
        <p class="muted">On a phone this opens your voice recorder. Up to
        <?= (int) round($rappConfig['max_audio_bytes'] / 1048576) ?> MB.</p>

        <button class="btn block" type="submit">Submit report</button>
    </form>
</div>
<?php endif;

rapp_layout_foot();
