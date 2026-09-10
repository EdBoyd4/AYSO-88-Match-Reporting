<?php
declare(strict_types=1);

require_once __DIR__ . '/../../rapp/config/rapp-bootstrap.php';
require_once __DIR__ . '/../../rapp/src/rapp-layout.php';

$sessionManager->startSession();
$loggedIn = $authManager->isLoggedIn();
$roles = $_SESSION['user_roles'] ?? [];
$userName = $loggedIn ? ($_SESSION['user_name'] ?? null) : null;
$basePath = $rappConfig['base_path'];

$canSubmit = $accessPolicy->can($roles, 'rapp.submit');
$canReports = $accessPolicy->can($roles, 'rapp.view')
    || $accessPolicy->can($roles, 'scores.view')
    || $accessPolicy->can($roles, 'matchdata.view');
$canManageUsers = $accessPolicy->can($roles, 'users.manage');

/** Logged in -> go straight there; logged out -> via OTP login with a return path. */
$go = static function (string $page) use ($loggedIn, $basePath): string {
    if ($loggedIn) {
        return $page;
    }
    return 'login.php?redirect=' . rawurlencode($basePath . '/' . $page);
};

rapp_layout_head('Home', $userName, $basePath);
?>
<div class="card">
    <h1>Referee Abuse Prevention Program</h1>
    <p class="lead">
        Report an incident where someone behaved abusively toward a referee or
        assistant referee, or review reports and match data you're authorized to see.
    </p>

    <?php if (!$loggedIn): ?>
        <p class="muted">You'll be asked to sign in with a one-time code sent to your email.</p>
    <?php endif; ?>

    <?php if ($canSubmit || !$loggedIn): ?>
        <a class="btn block" href="<?= rapp_esc($go('report.php')) ?>">File a Referee Abuse Report</a>
    <?php endif; ?>

    <?php if ($canReports || !$loggedIn): ?>
        <a class="btn block secondary" href="<?= rapp_esc($go('reports.php')) ?>">RAPP Reports &amp; Match Data</a>
    <?php endif; ?>

    <?php if ($canManageUsers): ?>
        <a class="btn block secondary" href="users.php">Manage Users</a>
    <?php endif; ?>

    <?php if ($loggedIn && !$canSubmit && !$canReports && !$canManageUsers): ?>
        <p class="msg info">Your account is signed in but has no RAPP permissions yet.
        Contact the Regional Referee Administrator.</p>
    <?php endif; ?>
</div>
<?php
rapp_layout_foot();
