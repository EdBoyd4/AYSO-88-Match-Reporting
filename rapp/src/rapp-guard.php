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
 * Context: ['user_id','email','name','roles','division_id','is_active'].
 */

function rapp_context(PDO $pdo, AuthManager $authManager, SessionManager $sessionManager): ?array
{
    $sessionManager->startSession();
    if (!$authManager->isLoggedIn()) {
        return null;
    }
    // Idle-timeout check; may destroy the session and redirect+exit on its own.
    $sessionManager->enforceTimeout();

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $roles = $_SESSION['user_roles'] ?? [];

    $stmt = $pdo->prepare('SELECT full_name, division_id, is_active FROM gss88_user_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'user_id'     => $userId,
        'email'       => (string) ($_SESSION['user_name'] ?? ''),
        'name'        => (string) (($p['full_name'] ?? '') !== '' ? $p['full_name'] : ($_SESSION['user_name'] ?? '')),
        'roles'       => is_array($roles) ? $roles : [],
        'division_id' => isset($p['division_id']) && $p['division_id'] !== null ? (int) $p['division_id'] : null,
        'is_active'   => !array_key_exists('is_active', $p) || (int) $p['is_active'] === 1,
    ];
}

/**
 * @return array the context array (guaranteed present + active + capable)
 */
function rapp_require(
    string $capability,
    PDO $pdo,
    AuthManager $authManager,
    SessionManager $sessionManager,
    AccessPolicy $accessPolicy,
    string $basePath
): array {
    $ctx = rapp_context($pdo, $authManager, $sessionManager);

    if ($ctx === null) {
        $target = $_SERVER['REQUEST_URI'] ?? ($basePath . '/index.php');
        header('Location: ' . $basePath . '/login.php?redirect=' . rawurlencode($target));
        exit;
    }
    if (!$ctx['is_active']) {
        $authManager->logout($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        header('Location: ' . $basePath . '/login.php?deactivated=1');
        exit;
    }
    if (!$accessPolicy->can($ctx['roles'], $capability)) {
        header('Location: ' . $basePath . '/index.php?denied=1');
        exit;
    }
    return $ctx;
}
