<?php
declare(strict_types=1);

// Real source lives here at the project root, not in public/rapp/ --
// public/admin-login.php is a symlink to this file. Moved 2026-10-02 so the
// admin team's sign-in URL is the short https://gss88.local/admin-login.php
// rather than .../rapp/admin-login.php. rapp-bootstrap.php's $userAccessConfig
// loginRoute, rapp-guard.php's rapp_require(), and reports.php's own login
// redirects were all updated to match (see each for why); the self-service
// referee login at /rapp/login.php was NOT moved, only this one.

require_once __DIR__ . '/controllers/rapp-bootstrap.php';
require_once __DIR__ . '/views/rapp-layout.php';

use Boyd\LoginLibrary\Controllers\LoginController;

$basePath = $rappConfig['base_path'];
$sessionManager->startSession();

// Already signed in? Bounce to the requested destination (or the reports hub).
if ($authManager->isLoggedIn()) {
    $dest = $_GET['redirect'] ?? '';
    header('Location: ' . (str_starts_with($dest, '/') ? $dest : $basePath . '/reports.php'));
    exit;
}

// Username + password only -- no self-registration. An account only exists
// here if an existing RRA created it via Manage Users (or it was seeded
// directly, e.g. the first RRA). See rapp-bootstrap.php's doc comment.
$controller = new LoginController($authManager, $sessionManager);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$requestedRedirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? null);

$result = $controller->handleLogin(
    method: $_SERVER['REQUEST_METHOD'],
    username: $_POST['username'] ?? null,
    password: $_POST['password'] ?? null,
    csrfToken: $_POST['csrf_token'] ?? null,
    requestedRedirect: $requestedRedirect,
    defaultRedirectUrl: $basePath . '/reports.php',
    ipAddress: $ip,
);

if ($result->success) {
    header('Location: ' . $result->redirectUrl);
    exit;
}

$redirectField = is_string($requestedRedirect) && str_starts_with($requestedRedirect, '/')
    ? $requestedRedirect : '';

rapp_layout_head('Admin sign in', null, $basePath);
?>
<div class="card">
    <h1>Admin sign in</h1>

    <?php if ($result->error): ?><div class="msg error"><?= rapp_esc($result->error) ?></div><?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= rapp_esc((string) $result->csrfToken) ?>">
        <input type="hidden" name="redirect" value="<?= rapp_esc($redirectField) ?>">
        <label for="username">Email address</label>
        <input type="email" name="username" id="username" autocomplete="username" required autofocus>
        <label for="password">Password</label>
        <input type="password" name="password" id="password" autocomplete="current-password" required>
        <button class="btn block" type="submit">Sign in</button>
    </form>
</div>
<?php
rapp_layout_foot();
