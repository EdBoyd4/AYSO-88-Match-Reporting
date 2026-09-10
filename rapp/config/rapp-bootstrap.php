<?php
declare(strict_types=1);

/**
 * Shared bootstrap for every RAPP / auth entry point.
 *
 * Include this first. It loads the login library, reads gss88/.env, wires the
 * library services against gss88's own auth database, and exposes:
 *
 *   $rappConfig         array   media dir, retention window, base URL, limits
 *   $config             Boyd\LoginLibrary\Config\LoginConfig
 *   $database           Boyd\LoginLibrary\Database\Database
 *   $pdo                PDO     (same connection, for the rapp_* repositories)
 *   $userRepository     Boyd\LoginLibrary\Repositories\UserRepository
 *   $settingsRepository Boyd\LoginLibrary\Repositories\SettingsRepository
 *   $sessionManager     Boyd\LoginLibrary\Security\SessionManager
 *   $authManager        Boyd\LoginLibrary\Security\AuthManager
 *   $otpManager         Boyd\LoginLibrary\Security\OtpManager
 *   $accessPolicy       AccessPolicy
 */

require_once '/home/xnbglkce/boyds-little-login-library-for-php/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php'; // gss88 vendor (PHPMailer)
require_once __DIR__ . '/../src/Gss88Env.php';
require_once __DIR__ . '/../src/AccessPolicy.php';
require_once __DIR__ . '/../src/Gss88OtpMailer.php';
require_once __DIR__ . '/../src/RappReportRepository.php';
require_once __DIR__ . '/../src/RappMatchDataRepository.php';
require_once __DIR__ . '/../src/RappUserAdminRepository.php';
require_once __DIR__ . '/../src/RappAudioValidator.php';
require_once __DIR__ . '/../src/RappMediaStore.php';
require_once __DIR__ . '/../src/RappNotifier.php';
require_once __DIR__ . '/../src/rapp-guard.php';

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Repositories\OtpRepository;
use Boyd\LoginLibrary\Repositories\SecurityAuditRepository;
use Boyd\LoginLibrary\Repositories\SettingsRepository;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Security\OtpManager;
use Boyd\LoginLibrary\Security\SessionManager;

date_default_timezone_set('America/Los_Angeles');

$gss88Root = dirname(__DIR__, 2);
$env = Gss88Env::load($gss88Root . '/.env');

/** Read a config value: .env first, then real environment, then default. */
$cfgVal = static function (string $key, string $default = '') use ($env): string {
    if (array_key_exists($key, $env) && $env[$key] !== '') {
        return $env[$key];
    }
    $fromEnv = getenv($key);
    return $fromEnv !== false && $fromEnv !== '' ? $fromEnv : $default;
};

$baseUrl = rtrim($cfgVal('GSS88_RAPP_BASE_URL', '/rapp'), '/');
$basePath = parse_url($baseUrl, PHP_URL_PATH) ?: $baseUrl;

$rappConfig = [
    'media_dir'       => $cfgVal('GSS88_RAPP_MEDIA_DIR', $gss88Root . '/RAPPReports'),
    'retention_hours' => (int) $cfgVal('GSS88_RAPP_RETENTION_HOURS', '72'),
    'max_audio_bytes' => (int) $cfgVal('GSS88_RAPP_MAX_AUDIO_BYTES', (string) (25 * 1024 * 1024)),
    'base_url'        => $baseUrl,          // absolute, for emailed links
    'base_path'       => $basePath ?: '/rapp', // path-only, for in-app redirects
];

$config = new LoginConfig(
    dbHost: $cfgVal('GSS88_AUTH_DB_HOST', 'localhost'),
    dbName: $cfgVal('GSS88_AUTH_DB_NAME'),
    dbUser: $cfgVal('GSS88_AUTH_DB_USER'),
    dbPass: $cfgVal('GSS88_AUTH_DB_PASS'),
    sessionName: 'Gss88RappSession',
    sessionTimeoutSeconds: (int) $cfgVal('GSS88_SESSION_TIMEOUT_SECONDS', '14400'),
    loginRoute: $rappConfig['base_path'] . '/login.php',
    unauthorizedRoute: $rappConfig['base_path'] . '/index.php',
    userManagerRole: 'rra',
);

$database           = new Database($config);
$pdo                = $database->getConnection();
$userRepository     = new UserRepository($database, $config);
$attemptRepository  = new AttemptRepository($database);
$auditRepository    = new SecurityAuditRepository($database);
$settingsRepository = new SettingsRepository($database, $config->getTableAuthSettings());
$sessionManager     = new SessionManager($config, $settingsRepository);
$authManager        = new AuthManager($userRepository, $sessionManager, $config, $attemptRepository, $auditRepository);

$smtp = [
    'host'       => $cfgVal('GSS88_SMTP_HOST', 'localhost'),
    'port'       => $cfgVal('GSS88_SMTP_PORT', '465'),
    'secure'     => $cfgVal('GSS88_SMTP_SECURE', 'smtps'),
    'user'       => $cfgVal('GSS88_SMTP_USER'),
    'pass'       => $cfgVal('GSS88_SMTP_PASS'),
    'from_email' => $cfgVal('GSS88_SMTP_FROM_EMAIL', 'no-reply@gss88.org'),
    'from_name'  => $cfgVal('GSS88_SMTP_FROM_NAME', 'AYSO Region 88'),
];
$mailDevLogOnly = filter_var($cfgVal('GSS88_OTP_DEV_LOG_ONLY', 'false'), FILTER_VALIDATE_BOOL);

$otpManager = OtpManager::fromConfig(
    repository: new OtpRepository($database, $config->getTableOtpChallenges()),
    mailer: new Gss88OtpMailer($smtp, $mailDevLogOnly),
    config: $config,
    auditLogger: $auditRepository,
);

$accessPolicy = new AccessPolicy();

// -- RAPP domain services -----------------------------------------------------
$rappReports        = new RappReportRepository($pdo);
$rappMatchData      = new RappMatchDataRepository($pdo);
$rappUserAdmin      = new RappUserAdminRepository($pdo);
$rappAudioValidator = new RappAudioValidator($rappConfig['max_audio_bytes']);
$rappMediaStore     = new RappMediaStore($rappConfig['media_dir']);
$rappNotifier       = new RappNotifier($smtp, $mailDevLogOnly, $rappConfig['base_url']);
