<?php
declare(strict_types=1);

require_once __DIR__ . '/../../controllers/rapp-bootstrap.php';
require_once __DIR__ . '/../../views/rapp-layout.php';

use Boyd\LoginLibrary\Views\OtpLoginView;

$basePath = $rappConfig['base_path'];
$sessionManager->startSession();

// Already signed in? Bounce to the requested destination (or the reports hub).
if ($authManager->isLoggedIn()) {
    $dest = $_GET['redirect'] ?? '';
    header('Location: ' . (str_starts_with($dest, '/') ? $dest : $basePath . '/reports.php'));
    exit;
}

// Self-service, otp_users only: a first-time address is auto-registered as
// 'ref' on verification, so an RRA doesn't have to pre-add every referee
// before any of them can sign in to file a RAPP report. See the login
// library's Controllers\SelfServiceOtpController. Admin accounts sign in
// separately at /admin-login.php (username + password, no self-registration)
// -- see admin-login.php (project root; public/admin-login.php symlinks to it).
$method = $_SERVER['REQUEST_METHOD'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$step = $_POST['step'] ?? 'request';
$requestedRedirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? null);

$error = null;
$notice = null;
$stage = 'request';
$email = trim((string) ($_POST['email'] ?? ''));

// An address already registered in user_access is refused here rather than
// silently auto-registered as a second, separate 'ref' account -- that's
// what guarantees a username never ends up in both stores.
$isManagedAddress = $method === 'POST' && $email !== '' && $userLocator->locate($email) === 'user_access';

if ($isManagedAddress) {
    $error = 'That address belongs to an admin account. Use Referee Admin Log In instead.';
    $csrfToken = $sessionManager->generateCsrfToken();
} elseif ($method === 'POST' && $step === 'verify') {
    $result = $selfServiceOtpController->handleCodeVerification(
        method: $method,
        csrfToken: $_POST['csrf_token'] ?? null,
        email: $_POST['email'] ?? null,
        code: $_POST['code'] ?? null,
        requestedRedirect: $requestedRedirect,
        defaultRedirectUrl: $basePath . '/reports.php',
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
    $state = $selfServiceOtpController->handleCodeRequest(
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

// The form markup itself (messages, fields, buttons) comes from the login
// library's OtpLoginView -- only the page chrome (heading, lead text) and the
// CSS that makes it look like the rest of gss88.org (see rapp-layout.php's
// .login-form rules) are ours. 'redirect' is carried through via
// extraHiddenFields, since the library has no opinion on post-login
// destinations; $codeLabel is shortened because the lead paragraph below
// already names the address the code went to.
$otpLoginView = new OtpLoginView();

rapp_layout_head('Sign in', null, $basePath);
?>
<div class="card login-form">
    <h1>Sign in</h1>

    <?php if ($stage === 'verify'): ?>
        <p class="lead">Enter the one-time code sent to <strong><?= rapp_esc($email) ?></strong>.</p>
        <?php $otpLoginView->renderVerifyFormFragment(
            csrfToken: $csrfToken,
            email: $email,
            error: $error,
            notice: $notice,
            codeLabel: 'One-time code',
            extraHiddenFields: ['redirect' => $redirectField],
        ); ?>
    <?php else: ?>
        <p class="lead">We'll email you a one-time code.</p>
        <?php $otpLoginView->renderRequestFormFragment(
            csrfToken: $csrfToken,
            error: $error,
            prefillEmail: $email,
            extraHiddenFields: ['redirect' => $redirectField],
        ); ?>
    <?php endif; ?>
</div>
<?php
rapp_layout_foot();
