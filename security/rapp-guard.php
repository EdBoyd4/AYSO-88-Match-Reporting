<?php
declare(strict_types=1);

use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Security\SessionManager;

/**
 * Page guards for the RAPP entry points.
 *
 * rapp_context()  -> the signed-in user's context array, or null.
 * rapp_require()  -> same, but redirects (and exit()s) when the user is not
 *                    signed in, is deactivated, or lacks the capability.
 *
 * Context: ['user_id','user_source','email','name','roles','division_id','is_active'].
 *
 * $userAccessPdo is specifically the user_access database connection: only
 * accounts authenticated from that store (manually-managed: RRA, board, DC,
 * ...) have a gss88_user_profiles row. A self-service otp_users account
 * (user_source === 'otp_users') has no profile to look up -- it falls
 * through to the defaults (active, name = email, no division), which is
 * correct for a referee who just self-registered.
 */

function rapp_context(PDO $userAccessPdo, AuthManager $authManager, SessionManager $sessionManager): ?array
{
    $sessionManager->startSession();
    if (!$authManager->isLoggedIn()) {
        return null;
    }
    // Idle-timeout check; may destroy the session and redirect+exit on its own.
    $sessionManager->enforceTimeout();

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $roles = $_SESSION['user_roles'] ?? [];
    $source = $authManager->currentUserSource();

    $p = [];
    if ($source === 'user_access') {
        $stmt = $userAccessPdo->prepare('SELECT full_name, division_id, is_active FROM gss88_user_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    return [
        'user_id'     => $userId,
        'user_source' => $source,
        'email'       => (string) ($_SESSION['user_name'] ?? ''),
        'name'        => (string) (($p['full_name'] ?? '') !== '' ? $p['full_name'] : ($_SESSION['user_name'] ?? '')),
        'roles'       => is_array($roles) ? $roles : [],
        'division_id' => isset($p['division_id']) && $p['division_id'] !== null ? (int) $p['division_id'] : null,
        'is_active'   => !array_key_exists('is_active', $p) || (int) $p['is_active'] === 1,
    ];
}

/**
 * $loginPath picks which sign-in screen an unauthenticated visitor is bounced
 * to: $basePath . '/login.php' (self-service OTP, otp_users) for the
 * public-facing report submission flow, or '/admin-login.php' (username +
 * password, user_access, root-absolute -- not under $basePath, moved there
 * 2026-10-02) for anything admin-only. Defaults to the self-service page
 * since report.php -- the one truly public entry point -- is the common case.
 *
 * @return array the context array (guaranteed present + active + capable)
 */
function rapp_require(
    string $capability,
    PDO $userAccessPdo,
    AuthManager $authManager,
    SessionManager $sessionManager,
    AccessPolicy $accessPolicy,
    string $basePath,
    ?string $loginPath = null
): array {
    $loginPath ??= $basePath . '/login.php';
    $ctx = rapp_context($userAccessPdo, $authManager, $sessionManager);

    if ($ctx === null) {
        $target = $_SERVER['REQUEST_URI'] ?? ($basePath . '/reports.php');
        header('Location: ' . $loginPath . '?redirect=' . rawurlencode($target));
        exit;
    }
    if (!$ctx['is_active']) {
        $authManager->logout($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        header('Location: ' . $loginPath . '?deactivated=1');
        exit;
    }
    if (!$accessPolicy->can($ctx['roles'], $capability)) {
        // Not the RAPP hub (removed) -- the general two-choice landing page.
        header('Location: /login.php?denied=1');
        exit;
    }
    return $ctx;
}
