<?php
declare(strict_types=1);

require_once __DIR__ . '/../controllers/rapp-bootstrap.php';
require_once __DIR__ . '/../views/rapp-layout.php';

$basePath = $rappConfig['base_path'];

// Any of these capabilities gets you onto the dashboard; each section re-checks.
$ctx = rapp_context($userAccessPdo, $authManager, $sessionManager);
if ($ctx === null) {
    // Root-absolute: admin-login.php lives at /admin-login.php, not under
    // $basePath (moved 2026-10-02, see rapp-bootstrap.php/rapp-guard.php).
    header('Location: /admin-login.php?redirect=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ($basePath . '/reports.php')));
    exit;
}
if (!$ctx['is_active']) {
    $authManager->logout($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    header('Location: /admin-login.php?deactivated=1');
    exit;
}

$roles = $ctx['roles'];
$canRappView  = $accessPolicy->can($roles, 'rapp.view');
$canRappRetain = $accessPolicy->can($roles, 'rapp.retain');
$canMatchData = $accessPolicy->can($roles, 'matchdata.view');
$canScores    = $accessPolicy->can($roles, 'scores.view');
$mdScope = $accessPolicy->scopeFor($roles, 'matchdata.view');
$scScope = $accessPolicy->scopeFor($roles, 'scores.view');

if (!$canRappView && !$canMatchData && !$canScores) {
    // Not the RAPP hub (removed) -- the general two-choice landing page.
    header('Location: /login.php?denied=1');
    exit;
}

$reportId = (int) ($_GET['id'] ?? 0);
$section  = (string) ($_GET['section'] ?? '');
$notice   = null;

$sanctionLevel = static fn ($v) => (int) $v === 0 ? 'Caution' : 'Send-off';
$sanctionParty = static fn ($v) => (int) $v === 0 ? 'Player' : 'Coach';

// The three match-data screens (all gated by matchdata.view), keyed by their
// ?section= value. Replaced the single combined "Match data" list.
$matchDataScreens = [
    'staffing'  => 'Staffing issues',
    'issues'    => 'Match issues',
    'sanctions' => 'Sanctions',
];

// Whose matches the match-data screens list: a division-scoped user (DC) is
// pinned to their own division; everyone else sees all divisions unless the
// ?division= filter narrows it. null = all divisions.
$matchDataDivision = $mdScope === 'division'
    ? $ctx['division_id']
    : (isset($_GET['division']) && $_GET['division'] !== '' ? (int) $_GET['division'] : null);

// ---------------------------------------------------------------------------
// POST: retain / close actions on a RAPP report
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reportId > 0) {
    if (!$canRappRetain || !$sessionManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: reports.php?id=' . $reportId);
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'retain') {
        $rappReportInserter->setRetain($reportId, true);
    } elseif ($action === 'release') {
        $rappReportInserter->setRetain($reportId, false);
    } elseif ($action === 'close') {
        $rappReportInserter->setStatus($reportId, 'closed');
    } elseif ($action === 'acknowledge') {
        $rappReportInserter->setStatus($reportId, 'acknowledged');
    }
    $auditRepository->logEvent('rapp_report_' . $action, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $ctx['email'], 'report #' . $reportId);
    header('Location: reports.php?id=' . $reportId);
    exit;
}

rapp_layout_head('RAPP dashboard', $ctx['name'], $basePath);

// ===========================================================================
// RAPP report detail
// ===========================================================================
if ($reportId > 0 && $canRappView) {
    $r = $rappReportQueries->findReport($reportId);
    if ($r === null) {
        echo '<div class="card"><p class="msg error">Report not found.</p>'
           . '<a class="btn secondary" href="reports.php">Back</a></div>';
        rapp_layout_foot();
        return;
    }
    $media = $rappReportQueries->mediaForReport($reportId);
    $csrf = $sessionManager->generateCsrfToken();
    ?>
    <div class="card">
        <p class="muted"><a href="reports.php">&larr; All RAPP reports</a></p>
        <h1>RAPP report #<?= (int) $r['_id'] ?>
            <span class="muted">— <?= rapp_esc(ucfirst($r['status'])) ?><?= (int) $r['retain'] === 1 ? ', preserved' : '' ?></span>
        </h1>
        <table class="rapp">
            <tr><th>Filed</th><td><?= rapp_esc($r['created_at']) ?></td></tr>
            <tr><th>By</th><td><?= rapp_esc($r['submitter_name'] ?: $r['submitter_email']) ?> (<?= rapp_esc($r['submitter_email']) ?>)</td></tr>
            <tr><th>Match</th><td><?= rapp_esc(($r['match_date'] ?? '?') . ' ' . ($r['match_time'] ?? '')) ?> — <?= rapp_esc($r['division_name'] ?? '?') ?>, <?= rapp_esc($r['field_name'] ?? '?') ?></td></tr>
            <tr><th>Teams</th><td><?= rapp_esc(($r['home_team'] ?? '?') . ' v ' . ($r['away_team'] ?? '?')) ?></td></tr>
        </table>

        <h2 class="mt-16">Written account</h2>
        <?php if ($r['content_purged_at'] !== null): ?>
            <p class="muted">Purged <?= rapp_esc($r['content_purged_at']) ?> (retention window elapsed).</p>
        <?php elseif (($r['body_text'] ?? '') === ''): ?>
            <p class="muted">None provided.</p>
        <?php else: ?>
            <p class="report-text"><?= rapp_esc($r['body_text']) ?></p>
        <?php endif; ?>

        <h2 class="mt-16">Voice recording</h2>
        <?php if ($media === []): ?>
            <p class="muted">None provided.</p>
        <?php else: foreach ($media as $m): ?>
            <?php if ($m['purged_at'] !== null): ?>
                <p class="muted">Recording purged <?= rapp_esc($m['purged_at']) ?>.</p>
            <?php else: ?>
                <audio controls preload="none" src="media.php?id=<?= (int) $m['_id'] ?>"></audio>
                <p class="muted"><?= rapp_esc($m['mime']) ?>, <?= number_format((int) $m['bytes'] / 1024, 0) ?> KB</p>
            <?php endif; ?>
        <?php endforeach; endif; ?>

        <?php if ($canRappRetain && $r['content_purged_at'] === null): ?>
            <form method="post" action="reports.php?id=<?= (int) $r['_id'] ?>" class="actions-row">
                <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrf) ?>">
                <?php if ((int) $r['retain'] === 1): ?>
                    <button class="btn secondary" name="action" value="release" type="submit">Release (allow purge)</button>
                <?php else: ?>
                    <button class="btn" name="action" value="retain" type="submit">Preserve past <?= (int) $rappConfig['retention_hours'] ?>h</button>
                <?php endif; ?>
                <?php if ($r['status'] === 'new'): ?>
                    <button class="btn secondary" name="action" value="acknowledge" type="submit">Mark acknowledged</button>
                <?php endif; ?>
                <button class="btn secondary" name="action" value="close" type="submit">Close</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
    rapp_layout_foot();
    return;
}

// ===========================================================================
// Match detail (reached from any of the three match-data screens)
// ===========================================================================
$matchId = (int) ($_GET['match'] ?? 0);
if ($section === 'matchdata' && $canMatchData && $matchId > 0) {
    // Back link returns to whichever list the user came from.
    $from = (string) ($_GET['from'] ?? '');
    $backHref  = isset($matchDataScreens[$from]) ? 'reports.php?section=' . $from : 'reports.php';
    $backLabel = $matchDataScreens[$from] ?? 'Dashboard';

    $row = $rappMatchData->matchRow($matchId);
    if ($row && !$accessPolicy->canForDivision($roles, 'matchdata.view', (int) $row['division_id'], $ctx['division_id'])) {
        $row = null;
    }
    ?>
    <div class="card">
        <p class="muted"><a href="<?= rapp_esc($backHref) ?>">&larr; <?= rapp_esc($backLabel) ?></a></p>
        <?php if ($row === null): ?>
            <p class="msg error">Not available.</p>
        <?php else: ?>
            <h1><?= rapp_esc($row['match_date'] . ' ' . $row['match_time']) ?></h1>
            <table class="rapp">
                <tr><th>Division / field</th><td><?= rapp_esc(($row['division_name'] ?? '?') . ' — ' . ($row['field_name'] ?? '?')) ?></td></tr>
                <tr><th>Teams</th><td><?= rapp_esc($row['home_team'] . ' v ' . $row['away_team']) ?></td></tr>
                <tr><th>Referees</th><td><?= rapp_esc(trim(implode(', ', array_filter([$row['referee_1'], $row['referee_2'], $row['referee_3']])))) ?: '—' ?></td></tr>
                <?php if ($canScores): ?>
                    <tr><th>Score</th><td><?= $row['home_score'] === null ? '—' : ((int) $row['home_score'] . ' – ' . (int) $row['away_score']) ?></td></tr>
                <?php endif; ?>
                <tr><th>Staffing issue</th><td><?= rapp_esc($row['ref_staffing_issue'] ?? '') ?: '—' ?></td></tr>
                <tr><th>Other issue</th><td><?= rapp_esc($row['match_issue'] ?? '') ?: '—' ?></td></tr>
            </table>
            <?php $sanctions = $rappMatchData->sanctionsForMatch($matchId); ?>
            <h2 class="mt-16">Sanctions (<?= count($sanctions) ?>)</h2>
            <?php foreach ($sanctions as $s): ?>
                <p><strong>#<?= (int) $s['sanction_number_in_match'] ?> <?= rapp_esc($sanctionLevel($s['sanction_level'])) ?></strong>
                    — <?= rapp_esc($sanctionParty($s['sanctioned_party'])) ?>: <?= rapp_esc($s['party_description']) ?><br>
                    <span class="muted"><?= rapp_esc($s['event_description']) ?></span></p>
            <?php endforeach; ?>
            <?php if ($sanctions === []): ?><p class="muted">None.</p><?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    rapp_layout_foot();
    return;
}

// ===========================================================================
// Sections: staffing issues / match issues / sanctions -- one table each
// ===========================================================================
if (isset($matchDataScreens[$section]) && $canMatchData) {
    // A division-scoped user with no division on their profile has
    // $matchDataDivision === null, which the queries read as "all divisions" --
    // list nothing for them instead.
    $rows = ($mdScope === 'division' && $matchDataDivision === null) ? [] : match ($section) {
        'staffing'  => $rappMatchData->staffingIssues($matchDataDivision),
        'issues'    => $rappMatchData->matchIssues($matchDataDivision),
        'sanctions' => $rappMatchData->sanctionsList($matchDataDivision),
    };
    $colspan = $section === 'sanctions' ? 6 : 5;
    ?>
    <div class="card">
        <p class="muted"><a href="reports.php">&larr; Dashboard</a></p>
        <h1><?= rapp_esc($matchDataScreens[$section]) ?></h1>
        <?php if ($mdScope === 'division'): ?>
            <p class="muted">Your division only.</p>
        <?php else: ?>
            <form method="get" action="reports.php" class="filter-form">
                <input type="hidden" name="section" value="<?= rapp_esc($section) ?>">
                <label for="division">Division</label>
                <select name="division" id="division" onchange="this.form.submit()">
                    <option value="">All divisions</option>
                    <?php foreach ($rappMatchData->activeDivisions() as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= $matchDataDivision === $d['id'] ? 'selected' : '' ?>><?= rapp_esc($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <table class="rapp">
            <?php if ($section === 'staffing'): ?>
                <tr><th>When</th><th>Division / field</th><th>Referees</th><th>Staffing issue</th><th></th></tr>
            <?php elseif ($section === 'issues'): ?>
                <tr><th>When</th><th>Division / field</th><th>Match</th><th>Issue</th><th></th></tr>
            <?php else: ?>
                <tr><th>When</th><th>Division / field</th><th>Match</th><th>Sanction</th><th>Details</th><th></th></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= rapp_esc($row['match_date'] . ' ' . $row['match_time']) ?></td>
                    <td><?= rapp_esc(($row['division_name'] ?? '?') . ' — ' . ($row['field_name'] ?? '?')) ?></td>
                    <?php if ($section === 'staffing'): ?>
                        <td><?= rapp_esc(trim(implode(', ', array_filter([$row['referee_1'], $row['referee_2'], $row['referee_3']])))) ?: '—' ?></td>
                        <td class="report-text"><?= rapp_esc($row['issue_text']) ?></td>
                    <?php elseif ($section === 'issues'): ?>
                        <td><?= rapp_esc($row['home_team'] . ' v ' . $row['away_team']) ?></td>
                        <td class="report-text"><?= rapp_esc($row['issue_text']) ?></td>
                    <?php else: ?>
                        <td><?= rapp_esc($row['home_team'] . ' v ' . $row['away_team']) ?></td>
                        <td>#<?= (int) $row['sanction_number_in_match'] ?> <?= rapp_esc($sanctionLevel($row['sanction_level'])) ?><br>
                            <span class="muted"><?= rapp_esc($sanctionParty($row['sanctioned_party'])) ?></span></td>
                        <td><?= rapp_esc($row['party_description']) ?><br>
                            <span class="muted report-text"><?= rapp_esc($row['event_description']) ?></span></td>
                    <?php endif; ?>
                    <td><a href="reports.php?section=matchdata&match=<?= (int) $row['match_id'] ?>&from=<?= rapp_esc($section) ?>">view</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="<?= $colspan ?>" class="muted">Nothing in the last 90 days.</td></tr><?php endif; ?>
        </table>
    </div>
    <?php
    rapp_layout_foot();
    return;
}

// ===========================================================================
// Section: scores + game cards
// ===========================================================================
if ($section === 'scores' && $canScores) {
    $divisionId = $scScope === 'division'
        ? $ctx['division_id']
        : (isset($_GET['division']) && $_GET['division'] !== '' ? (int) $_GET['division'] : null);
    $rows = $rappMatchData->matchesWithData($divisionId, 120, false);
    ?>
    <div class="card">
        <p class="muted"><a href="reports.php">&larr; Dashboard</a></p>
        <h1>Scores &amp; game cards</h1>
        <?php if ($scScope !== 'division'): ?>
            <form method="get" action="reports.php" class="filter-form">
                <input type="hidden" name="section" value="scores">
                <label for="division">Division</label>
                <select name="division" id="division" onchange="this.form.submit()">
                    <option value="">All divisions</option>
                    <?php foreach ($rappMatchData->activeDivisions() as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= $divisionId === $d['id'] ? 'selected' : '' ?>><?= rapp_esc($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <table class="rapp">
            <tr><th>Date</th><th>Division</th><th>Field</th><th>Match</th><th>Score</th><th>Game cards</th></tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= rapp_esc($row['match_date'] . ' ' . $row['match_time']) ?></td>
                    <td><?= rapp_esc($row['division_name'] ?? '?') ?></td>
                    <td><?= rapp_esc($row['field_name'] ?? '?') ?></td>
                    <td><?= rapp_esc($row['home_team'] . ' v ' . $row['away_team']) ?></td>
                    <td><?= $row['home_score'] === null ? '—' : ((int) $row['home_score'] . ' – ' . (int) $row['away_score']) ?></td>
                    <td><?= (int) $row['gamecard_count'] ?: '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="6" class="muted">Nothing in the last 120 days.</td></tr><?php endif; ?>
        </table>
        <p class="muted">Game-card image viewing is not wired up yet — the count above
        reflects what's recorded in the database.</p>
    </div>
    <?php
    rapp_layout_foot();
    return;
}

// ===========================================================================
// Default: hub
// ===========================================================================
?>
<div class="card">
    <h1>RAPP dashboard</h1>
    <p class="lead">Signed in as <?= rapp_esc($ctx['name']) ?>.</p>

    <?php if ($canRappView): ?>
        <h2 class="mt-14">Referee abuse reports</h2>
        <?php $list = $rappReportQueries->listReports(200); ?>
        <table class="rapp">
            <tr><th>#</th><th>Filed</th><th>Division</th><th>Match</th><th>By</th><th>Has audio</th><th>Status</th></tr>
            <?php foreach ($list as $row): ?>
                <tr>
                    <td><a href="reports.php?id=<?= (int) $row['_id'] ?>"><?= (int) $row['_id'] ?></a></td>
                    <td><?= rapp_esc($row['created_at']) ?></td>
                    <td><?= rapp_esc($row['division_name'] ?? '?') ?></td>
                    <td><?= rapp_esc(($row['match_date'] ?? '?') . ' ' . ($row['match_time'] ?? '')) ?></td>
                    <td><?= rapp_esc($row['submitter_name'] ?: $row['submitter_email']) ?></td>
                    <td><?= (int) $row['has_audio'] === 1 ? 'yes' : '—' ?></td>
                    <td><?= rapp_esc(ucfirst($row['status'])) ?><?= (int) $row['retain'] === 1 ? ' · preserved' : '' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($list === []): ?><tr><td colspan="7" class="muted">No reports.</td></tr><?php endif; ?>
        </table>
    <?php endif; ?>

    <?php if ($canMatchData): ?>
        <?php foreach (array_keys($matchDataScreens) as $i => $key): ?>
            <p<?= $i === 0 ? ' class="mt-14"' : '' ?>><a class="btn secondary" href="reports.php?section=<?= rapp_esc($key) ?>"><?= rapp_esc($matchDataScreens[$key]) ?><?= $mdScope === 'division' ? ' — your division' : '' ?></a></p>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($canScores): ?>
        <p><a class="btn secondary" href="reports.php?section=scores">Scores &amp; game cards<?= $scScope === 'division' ? ' — your division' : '' ?></a></p>
    <?php endif; ?>
</div>
<?php
rapp_layout_foot();
