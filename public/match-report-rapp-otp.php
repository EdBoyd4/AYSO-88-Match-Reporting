<?php
declare(strict_types=1);

/**
 * AJAX endpoint for match-report.php's inline "also report abuse" section.
 * Two actions, both POST, both returning JSON:
 *
 *   action=request  email=...                -> sends a one-time code
 *   action=verify   email=... code=...       -> checks it
 *
 * Deliberately NOT a login: on a verified code this does not call
 * AuthManager::establishPasswordlessSession() at all -- match-report.php
 * stays anonymous. It only records, in the session match-report.php already
 * shares (via rapp-bootstrap.php's SessionManager), which otp_users identity
 * just proved control of which address, for match-report.php's own submit
 * handler to attribute an inline RAPP report to -- a lighter-weight fact than
 * "this browser is logged in."
 *
 * Reuses the exact same OtpManager/PasswordlessUserRepository the standalone
 * RAPP login (public/rapp/login.php) uses, via rapp-bootstrap.php -- same
 * throttling, same HMAC-keyed codes, same auto-registration into otp_users.
 * No new security surface, just a second entry point to existing logic.
 */

require_once __DIR__ . '/../controllers/rapp-bootstrap.php';

header('Content-Type: application/json');

// SessionManager's CSRF token is one-time-use (validateCsrfToken() unsets it
// on every check) -- fine for a form that submits once, but this section
// makes two AJAX round-trips (request, then verify) before the real
// match-report.php form POST that follows. Every response here hands back a
// fresh token for the *next* step, and the JS swaps it into the hidden
// rapp_csrf_token field, so each step spends a token nobody has spent yet
// and the field is left holding a still-valid one for the eventual submit.
function respond(array $data, int $status = 200): never
{
    global $sessionManager;
    $data['csrf_token'] = $sessionManager->generateCsrfToken();
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Method not allowed.'], 405);
}

$sessionManager->startSession();

if (!$sessionManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
    respond(['error' => 'Your session expired. Please reload the page and try again.'], 400);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$action = $_POST['action'] ?? '';
$email = trim((string) ($_POST['email'] ?? ''));

if ($action === 'request') {
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['error' => 'Please enter a valid email address.']);
    }
    try {
        $otpUsersOtpManager->startChallenge($email, $ip);
    } catch (\Throwable $e) {
        // Same as SelfServiceOtpController: don't leak delivery problems to
        // the form, the neutral notice below still shows either way.
        error_log('match-report-rapp-otp.php: startChallenge failed: ' . $e->getMessage());
    }
    // Same neutral message regardless of outcome -- a response can't be used
    // to tell whether an address is already registered or rate-limited.
    respond(['notice' => 'A one-time code is on its way to that address. It expires shortly.']);
}

if ($action === 'verify') {
    $code = trim((string) ($_POST['code'] ?? ''));
    if ($email === '' || $code === '') {
        respond(['error' => 'Please enter the code we emailed you.']);
    }

    $outcome = $otpUsersOtpManager->verifyChallenge($email, $code, $ip);

    if ($outcome->isLockedOut()) {
        respond(['error' => 'Too many incorrect attempts. Request a new code.']);
    }
    if (!$outcome->isVerified()) {
        // Same message for "wrong code" and "no such challenge" as every
        // other OTP surface in this app, so a response can't distinguish them.
        respond(['error' => 'That code is invalid or has expired. Request a new one.']);
    }

    try {
        $user = $otpUsersRepository->findOrRegister($email);
    } catch (\Throwable $e) {
        error_log('match-report-rapp-otp.php: findOrRegister failed: ' . $e->getMessage());
        respond(['error' => 'Something went wrong verifying that address. Please try again.']);
    }

    $_SESSION['rapp_inline_verified_user_id'] = $user->getId();
    $_SESSION['rapp_inline_verified_email'] = $email;

    respond(['verified' => true]);
}

respond(['error' => 'Unknown action.'], 400);
