<?php
declare(strict_types=1);

/**
 * Minimal shared chrome for the RAPP pages. Styling is an external
 * stylesheet (collateral/styles/styles-gss88-rapp.css, symlinked into
 * public/ the same way styles-gss88-match-report-form.css is) -- gss88
 * doesn't do inline styling, so nothing here should ever grow a <style>
 * block or a style="..." attribute again.
 */

function rapp_esc(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function rapp_layout_head(string $title, ?string $userName = null, ?string $basePath = null): void
{
    $title = rapp_esc($title);
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $title ?> · AYSO Region 88</title>
<link rel="stylesheet" href="/styles-gss88-rapp.css">
</head>
<body>
<header class="rapp-bar">
    <a href="<?= rapp_esc(($basePath ?? '') . '/reports.php') ?>"><strong>RAPP</strong> — Referee Abuse Prevention Program</a>
    <span class="who">
        <?php if ($userName): ?>
            <?= rapp_esc($userName) ?> · <a href="<?= rapp_esc(($basePath ?? '') . '/logout.php') ?>">Sign out</a>
        <?php endif; ?>
    </span>
</header>
<main class="rapp-wrap">
    <?php
}

// The report-specific "what was already recorded for this match" block
// (Match Details / Additional Notes, shown on report.php?match=) moved to
// RappMatchContextView -- views/views-rapp-report-entry-AYSO-88/
// class-view-rapp-match-context.php. Everything else in this file is
// shared chrome used by every RAPP page (login, admin-login, users, reports,
// report), not report-specific, so it stayed here. Its CSS (.match-context
// and friends) stayed in styles-gss88-rapp.css too -- there's no
// per-component stylesheet in this module, every RAPP page shares the one
// external file linked in rapp_layout_head().

function rapp_layout_foot(): void
{
    ?>
</main>
</body>
</html>
    <?php
}
