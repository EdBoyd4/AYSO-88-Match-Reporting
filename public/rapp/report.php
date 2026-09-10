<?php
declare(strict_types=1);

require_once __DIR__ . '/../../rapp/config/rapp-bootstrap.php';
require_once __DIR__ . '/../../rapp/src/rapp-layout.php';

$basePath = $rappConfig['base_path'];
$ctx = rapp_require('rapp.submit', $pdo, $authManager, $sessionManager, $accessPolicy, $basePath);

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$matches = $rappReports->recentScheduledMatches(28);
$error = null;
$submitted = isset($_GET['submitted']);

if (!$submitted && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfOk = $sessionManager->validateCsrfToken($_POST['csrf_token'] ?? '');
    $matchId = (int) ($_POST['scheduled_match_id'] ?? 0);
    $bodyText = trim((string) ($_POST['body_text'] ?? ''));
    $hasAudioUpload = isset($_FILES['audio']) && ($_FILES['audio']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    $validMatchIds = array_column($matches, 'id');

    if (!$csrfOk) {
        $error = 'Your session expired or the form was invalid. Please try again.';
    } elseif ($matchId <= 0 || (!in_array($matchId, $validMatchIds, true) && !$rappReports->scheduledMatchExists($matchId))) {
        $error = 'Please choose the match this incident relates to.';
    } elseif ($bodyText === '' && !$hasAudioUpload) {
        $error = 'Add a written account, a voice recording, or both.';
    } else {
        $audioMeta = null;
        try {
            if ($hasAudioUpload) {
                $audioMeta = $rappAudioValidator->validate($_FILES['audio']);
            }

            $reportId = $rappReports->createReport($matchId, $ctx['user_id'], $bodyText !== '' ? $bodyText : null, false);

            if ($audioMeta !== null) {
                $relPath = $rappMediaStore->store($reportId, $_FILES['audio']['tmp_name'], $audioMeta['ext']);
                $rappReports->addMedia($reportId, $relPath, $audioMeta['mime'], $audioMeta['bytes'], $audioMeta['original_name']);
            }

            $auditRepository->logEvent('rapp_report_filed', $ip, $ctx['email'], 'RAPP report #' . $reportId);

            try {
                $full = $rappReports->findReport($reportId);
                $recipients = $rappReports->notificationRecipients(['rra', 'senior_board']);
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
    <a class="btn" href="<?= rapp_esc($basePath) ?>/index.php">Done</a>
</div>
<?php else: ?>
<div class="card">
    <h1>File a Referee Abuse Report</h1>
    <p class="lead">For incidents where someone behaved abusively toward a referee
    or assistant referee.</p>

    <?php if ($error): ?><div class="msg error"><?= rapp_esc($error) ?></div><?php endif; ?>

    <div class="guidance">
        <strong>Before you record:</strong>
        <ul style="margin:6px 0 0 18px; padding:0;">
            <li>Record <strong>only yourself</strong>, speaking <strong>privately</strong>.
                Do not record the incident as it happens, other people, or a conversation.</li>
            <li>Refer to people the way the misconduct reports do — team, player or
                coach, jersey number, description — rather than by full name where you can.</li>
        </ul>
    </div>

    <form method="post" action="report.php" enctype="multipart/form-data" style="margin-top:16px">
        <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrf) ?>">

        <label for="scheduled_match_id">Which match?</label>
        <select name="scheduled_match_id" id="scheduled_match_id" required>
            <option value="">— choose the match —</option>
            <?php foreach ($matches as $m): ?>
                <option value="<?= (int) $m['id'] ?>"><?= rapp_esc($m['label']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="body_text">Written account <span class="muted">(optional if you attach audio)</span></label>
        <textarea name="body_text" id="body_text" placeholder="What happened, who was involved (by team / role / number), and when."></textarea>

        <label for="audio">Voice recording <span class="muted">(optional if you write an account)</span></label>
        <input type="file" name="audio" id="audio" accept="audio/*" capture>
        <p class="muted">On a phone this opens your voice recorder. Up to
        <?= (int) round($rappConfig['max_audio_bytes'] / 1048576) ?> MB.</p>

        <button class="btn block" type="submit">Submit report</button>
    </form>
</div>
<?php endif;

rapp_layout_foot();
