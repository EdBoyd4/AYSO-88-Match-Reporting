<?php
declare(strict_types=1);

require_once __DIR__ . '/../../rapp/config/rapp-bootstrap.php';
require_once __DIR__ . '/../../rapp/src/rapp-layout.php';

use Boyd\LoginLibrary\Controllers\OtpController;

$basePath = $rappConfig['base_path'];
$sessionManager->startSession();

// Already signed in? Bounce to the requested destination (or home).
if ($authManager->isLoggedIn()) {
    $dest = $_GET['redirect'] ?? '';
    header('Location: ' . (str_starts_with($dest, '/') ? $dest : $basePath . '/index.php'));
    exit;
}

$controller = new OtpController($otpManager, $authManager, $userRepository, $sessionManager);
$method = $_SERVER['REQUEST_METHOD'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$step = $_POST['step'] ?? 'request';
$requestedRedirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? null);

$error = null;
$notice = null;
$stage = 'request';
$email = trim((string) ($_POST['email'] ?? ''));

if ($method === 'POST' && $step === 'verify') {
    $result = $controller->handleCodeVerification(
        method: $method,
        csrfToken: $_POST['csrf_token'] ?? null,
        email: $_POST['email'] ?? null,
        code: $_POST['code'] ?? null,
        requestedRedirect: $requestedRedirect,
        defaultRedirectUrl: $basePath . '/index.php',
        ipAddress: $ip,
    );
    if ($result->success) {
        header('Location: ' . $result->redirectUrl);
        exit;
    }
    $stage = 'verify';
    $error = $result->error;
    $csrfToken = $result->csrfToken;
} else {
    // step 'request' (initial GET, submit of the email form, or "send a new code")
    $state = $controller->handleCodeRequest(
        method: $method,
        csrfToken: $_POST['csrf_token'] ?? null,
        email: $_POST['email'] ?? null,
        ipAddress: $ip,
    );
    $error = $state['error'];
    $notice = $state['notice'];
    $email = $state['email'];
    $csrfToken = $state['csrfToken'];
    $stage = $state['advance'] ? 'verify' : 'request';
}

$redirectField = is_string($requestedRedirect) && str_starts_with($requestedRedirect, '/')
    ? $requestedRedirect : '';

rapp_layout_head('Sign in', null, $basePath);
?>
<div class="card">
    <h1>Sign in</h1>

    <?php if ($error): ?><div class="msg error"><?= rapp_esc($error) ?></div><?php endif; ?>
    <?php if ($notice): ?><div class="msg info"><?= rapp_esc($notice) ?></div><?php endif; ?>

    <?php if ($stage === 'verify'): ?>
        <p class="lead">Enter the one-time code sent to <strong><?= rapp_esc($email) ?></strong>.</p>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrfToken) ?>">
            <input type="hidden" name="step" value="verify">
            <input type="hidden" name="email" value="<?= rapp_esc($email) ?>">
            <input type="hidden" name="redirect" value="<?= rapp_esc($redirectField) ?>">
            <label for="code">One-time code</label>
            <input type="text" name="code" id="code" inputmode="numeric" autocomplete="one-time-code"
                   pattern="[0-9]*" maxlength="10" required autofocus>
            <button class="btn block" type="submit">Sign in</button>
        </form>
        <form method="post" action="" style="margin-top:10px">
            <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrfToken) ?>">
            <input type="hidden" name="step" value="request">
            <input type="hidden" name="email" value="<?= rapp_esc($email) ?>">
            <input type="hidden" name="redirect" value="<?= rapp_esc($redirectField) ?>">
            <button class="btn block secondary" type="submit">Send a new code</button>
        </form>
    <?php else: ?>
        <p class="lead">We'll email you a one-time code. Only registered referees and
        authorized officials can sign in.</p>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= rapp_esc($csrfToken) ?>">
            <input type="hidden" name="step" value="request">
            <input type="hidden" name="redirect" value="<?= rapp_esc($redirectField) ?>">
            <label for="email">Email address</label>
            <input type="email" name="email" id="email" autocomplete="email" required
                   value="<?= rapp_esc($email) ?>">
            <button class="btn block" type="submit">Email me a code</button>
        </form>
    <?php endif; ?>
</div>
<?php
rapp_layout_foot();
