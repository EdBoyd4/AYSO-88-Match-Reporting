<?php
declare(strict_types=1);

/**
 * Shared bootstrap for every RAPP / auth entry point.
 *
 * Include this first. It loads the login library, reads gss88/.env, wires the
 * library services against gss88's four segregated databases, and exposes:
 *
 *   $rappConfig          array   media dir, retention window, base URL, limits
 *
 *   -- user_access: manually-managed accounts (RRA, board, DC, ...) --------
 *   Authenticates by username + password only (admin-login.php, project root;
 *   public/admin-login.php symlinks to it -- Boyd\LoginLibrary\Controllers\
 *   LoginController) -- no self-registration,
 *   no OTP. An account only exists here if an existing RRA created it via
 *   Manage Users (or it was seeded directly, e.g. the first RRA).
 *   $userAccessConfig    Boyd\LoginLibrary\Config\LoginConfig
 *   $userAccessPdo       PDO
 *   $userRepository      Boyd\LoginLibrary\Repositories\UserRepository
 *   $settingsRepository  Boyd\LoginLibrary\Repositories\SettingsRepository
 *   $sessionManager      Boyd\LoginLibrary\Security\SessionManager   (shared by both stores)
 *   $authManager         Boyd\LoginLibrary\Security\AuthManager      (the general-purpose one -- use this post-login)
 *
 *   -- otp_users: self-service accounts (referees who self-register) -----
 *   Authenticates by emailed one-time code only (public/rapp/login.php). An
 *   address already registered in user_access is refused here -- see
 *   $userLocator -- so nobody ends up registered in both stores.
 *   $otpUsersRepository        Boyd\LoginLibrary\Repositories\PasswordlessUserRepository
 *   $selfServiceOtpController  Boyd\LoginLibrary\Controllers\SelfServiceOtpController
 *   $userLocator               Boyd\LoginLibrary\Security\MultiStoreUserLocator
 *
 *   -- RAPP + match-report domain (separate databases, same server) -------
 *   $rappReportQueries   RappReportQueries  (reads; models/model-rapp-report-entry)
 *   $rappReportInserter  RappReportInserter (writes; same directory)
 *   $rappMatchData       MatchDataQueries (models/model-match-report-entry; shared with match-report.php)
 *   $rappUserAdmin       RappUserAdminRepository
 *
 *   $accessPolicy        AccessPolicy
 *
 * Every session established from here (privileged or self-service) carries a
 * 'user_source' tag ('user_access' or 'otp_users') alongside 'user_id' --
 * required because those are two disjoint identity spaces sharing one
 * numeric ID range. See AuthManager::currentUserSource() and rapp-guard.php.
 */

// This file lives at controllers/rapp-bootstrap.php -- one level below gss88
// root (it used to be two, at rapp/config/rapp-bootstrap.php), so every path
// below is one dirname() shallower than it used to be.
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/Gss88Env.php';
require_once dirname(__DIR__) . '/security/AccessPolicy.php';
require_once dirname(__DIR__) . '/contracts-rapp-reports-AYSO-88/Gss88OtpMailer.php';
require_once dirname(__DIR__) . '/models/model-rapp-report-entry/model-rapp-report-insert.php';
require_once dirname(__DIR__) . '/models/model-rapp-report-entry/model-rapp-report-query.php';
require_once dirname(__DIR__) . '/models/model-match-report-entry/model-match-data-query.php';
require_once dirname(__DIR__) . '/security/RappUserAdminRepository.php';
require_once dirname(__DIR__) . '/security/RappAudioValidator.php';
require_once __DIR__ . '/controller-rapp-audio-report-storage.php';
require_once dirname(__DIR__) . '/contracts-rapp-reports-AYSO-88/RappNotifier.php';
require_once dirname(__DIR__) . '/security/rapp-guard.php';

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Controllers\SelfServiceOtpController;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Repositories\OtpRepository;
use Boyd\LoginLibrary\Repositories\PasswordlessUserRepository;
use Boyd\LoginLibrary\Repositories\SecurityAuditRepository;
use Boyd\LoginLibrary\Repositories\SettingsRepository;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Security\IpAttemptThrottle;
use Boyd\LoginLibrary\Security\MultiStoreUserLocator;
use Boyd\LoginLibrary\Security\OtpManager;
use Boyd\LoginLibrary\Security\SessionManager;

date_default_timezone_set('America/Los_Angeles');

$gss88Root = dirname(__DIR__);
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
    // How long after a match a report about it may be filed. 120 hours (5
    // days) as of 2026-09-27 -- referees are volunteers, and a Saturday
    // match reported the following Wednesday isn't unreasonable. Not a
    // strict compliance deadline (referees are still encouraged to file
    // promptly); this just bounds the match picker
    // (RappReportQueries::recentScheduledMatches) and is re-checked at
    // submission time regardless of how the match was selected
    // (RappReportQueries::isWithinReportWindow).
    'report_window_hours' => (int) $cfgVal('GSS88_RAPP_REPORT_WINDOW_HOURS', '120'),
    'base_url'        => $baseUrl,          // absolute, for emailed links
    'base_path'       => $basePath ?: '/rapp', // path-only, for in-app redirects
];

// -- Shared server/credentials; four separate databases ----------------------
$dbHost = $cfgVal('GSS88_DB_HOST', 'localhost');
$dbUser = $cfgVal('GSS88_DB_USER');
$dbPass = $cfgVal('GSS88_DB_PASS');
// Idle timeouts: password (admin) sessions 4 hours; referee code-login sessions
// 45 minutes (a RAPP report takes up to ~30 min, so this leaves headroom
// without leaving a left-open tab signed in for hours).
$sessionTimeoutSeconds = (int) $cfgVal('GSS88_SESSION_TIMEOUT_SECONDS', '14400');
$otpSessionTimeoutSeconds = (int) $cfgVal('GSS88_OTP_SESSION_TIMEOUT_SECONDS', '2700');

$matchReportsDbName = $cfgVal('GSS88_MATCH_REPORTS_DB_NAME');
$rappReportsDbName  = $cfgVal('GSS88_RAPP_REPORTS_DB_NAME');
$userAccessDbName   = $cfgVal('GSS88_USER_ACCESS_DB_NAME');
$otpUsersDbName     = $cfgVal('GSS88_OTP_USERS_DB_NAME');

// -- user_access: manually-managed accounts (RRA, board, DC, ...) -----------
$userAccessConfig = new LoginConfig(
    dbHost: $dbHost,
    dbName: $userAccessDbName,
    dbUser: $dbUser,
    dbPass: $dbPass,
    sessionName: 'Gss88RappSession',
    sessionTimeoutSeconds: $sessionTimeoutSeconds,
    // Root-absolute, not under base_path -- admin-login.php lives at
    // /admin-login.php, not /rapp/admin-login.php (moved 2026-10-02). The
    // self-service referee login below is unaffected, still under base_path.
    loginRoute: '/admin-login.php',
    unauthorizedRoute: $rappConfig['base_path'] . '/reports.php',
    userManagerRole: 'rra',
    identitySource: 'user_access',
);
$userAccessDatabase = new Database($userAccessConfig);
$userAccessPdo      = $userAccessDatabase->getConnection();
$userRepository     = new UserRepository($userAccessDatabase, $userAccessConfig);
$attemptRepository  = new AttemptRepository($userAccessDatabase);
$auditRepository    = new SecurityAuditRepository($userAccessDatabase);
$settingsRepository = new SettingsRepository($userAccessDatabase, $userAccessConfig->getTableAuthSettings());

// Session bookkeeping (cookie, CSRF, idle timeout) is one PHP session shared
// by both identity stores -- only the runtime-adjustable timeout setting
// needs a home, and that's user_access's auth_settings (otp_users has no
// such table; see the library's minimal example schema).
$sessionManager = new SessionManager($userAccessConfig, $settingsRepository);
$authManager    = new AuthManager($userRepository, $sessionManager, $userAccessConfig, $attemptRepository, $auditRepository);

// -- otp_users: self-service accounts (referees who self-register) ----------
// Tighter than the library's generic defaults (5 sends/hr, 5 attempts/15min):
// a legitimate referee needs at most a couple of code requests per login, and
// filling out one RAPP report (up to ~30 min) caps how fast they could even
// attempt a second one -- even two back-to-back in a day can't generate much
// real volume, so slack beyond that only benefits an attacker.
$otpUsersConfig = new LoginConfig(
    dbHost: $dbHost,
    dbName: $otpUsersDbName,
    dbUser: $dbUser,
    dbPass: $dbPass,
    sessionName: 'Gss88RappSession',
    sessionTimeoutSeconds: $sessionTimeoutSeconds,
    loginRoute: $rappConfig['base_path'] . '/login.php',
    unauthorizedRoute: $rappConfig['base_path'] . '/reports.php',
    maxLoginAttempts: 3,
    lockoutTimeMinutes: 10,
    otpMaxSendsPerHour: 3,
    otpTtlSeconds: 300,
    identitySource: 'otp_users',
    otpSessionTimeoutSeconds: $otpSessionTimeoutSeconds,
    // Required: OtpManager refuses to start without it (32+ random characters,
    // kept only in .env). Never stored in the database or committed.
    otpHmacKey: $cfgVal('GSS88_OTP_HMAC_KEY'),
);
$otpUsersDatabase     = new Database($otpUsersConfig);
// No password, no roles tables here: a row is just the verified email, and
// every user this store returns carries the single fixed role 'ref'.
$otpUsersRepository   = new PasswordlessUserRepository($otpUsersDatabase, $otpUsersConfig, 'ref');
$otpUsersAttemptRepo  = new AttemptRepository($otpUsersDatabase);
$otpUsersAuditRepo    = new SecurityAuditRepository($otpUsersDatabase);
// No password store, so it can only establish (code-verified) sessions.
$otpUsersAuthManager  = new AuthManager(
    userRepository: null,
    sessionManager: $sessionManager,
    config: $otpUsersConfig,
    attemptRepository: $otpUsersAttemptRepo,
    auditRepository: $otpUsersAuditRepo,
);

// -- SMTP / OTP delivery ------------------------------------------------------
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

// user_access authenticates by password (see admin-login.php) -- no OTP
// challenges are ever issued for that store, so it gets no OtpManager here.
// $otpUsersAttemptRepo already existed (rapp-retention.php's cleanup pass)
// but nothing actually recorded/checked attempts against it until now --
// one IP could otherwise hammer OTP requests/verifies across unlimited
// different referee email addresses with no lockout at all.
$otpUsersOtpManager = OtpManager::fromConfig(
    repository: new OtpRepository($otpUsersDatabase, $otpUsersConfig->getTableOtpChallenges()),
    mailer: new Gss88OtpMailer($smtp, $mailDevLogOnly),
    config: $otpUsersConfig,
    auditLogger: $otpUsersAuditRepo,
    ipThrottle: IpAttemptThrottle::fromConfig($otpUsersAttemptRepo, $otpUsersConfig),
);

$accessPolicy = new AccessPolicy();

// -- Self-service login (otp_users only) --------------------------------------
// Auto-registers a first-time address as 'ref' on verification, so an RRA
// doesn't have to pre-add every referee before any of them can file a RAPP
// report. $userLocator is checked by login.php BEFORE calling this -- an
// address already in user_access is refused rather than handed to this
// controller, so nobody ends up registered in both stores.
$selfServiceOtpController = new SelfServiceOtpController(
    $otpUsersOtpManager, $otpUsersAuthManager, $otpUsersRepository, $sessionManager
);
$userLocator = new MultiStoreUserLocator([
    'user_access' => $userRepository,
    'otp_users' => $otpUsersRepository,
]);

// -- match_reports + rapp_reports: plain domain databases, no auth tables ---
$matchReportsPdo = (new Database(new LoginConfig(dbHost: $dbHost, dbName: $matchReportsDbName, dbUser: $dbUser, dbPass: $dbPass)))->getConnection();
$rappReportsPdo  = (new Database(new LoginConfig(dbHost: $dbHost, dbName: $rappReportsDbName, dbUser: $dbUser, dbPass: $dbPass)))->getConnection();

// -- RAPP domain services -----------------------------------------------------
$rappReportQueries  = new RappReportQueries($rappReportsPdo, $matchReportsDbName, $userAccessDbName, $otpUsersDbName);
$rappReportInserter = new RappReportInserter($rappReportsPdo);
$rappMatchData      = new MatchDataQueries($matchReportsPdo);
$rappUserAdmin      = new RappUserAdminRepository($userAccessPdo, $matchReportsDbName);
$rappAudioValidator = new RappAudioValidator($rappConfig['max_audio_bytes']);
$rappMediaStore     = new RappMediaStore($rappConfig['media_dir']);
$rappNotifier       = new RappNotifier($smtp, $mailDevLogOnly, $rappConfig['base_url']);
