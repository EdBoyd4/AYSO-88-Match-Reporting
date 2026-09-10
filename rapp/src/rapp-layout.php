<?php
declare(strict_types=1);

/**
 * Minimal shared chrome for the RAPP pages. Deliberately tiny and self-contained
 * (inline CSS) to match the rest of gss88's server-rendered pages.
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
<style>
    :root { color-scheme: light; }
    * { box-sizing: border-box; }
    body { margin: 0; font: 15px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #eef1f4; color: #1c2530; }
    header.rapp-bar { background: #0b5f3b; color: #fff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
    header.rapp-bar a { color: #cdeede; text-decoration: none; }
    header.rapp-bar .who { font-size: 0.9em; opacity: 0.9; }
    main.rapp-wrap { max-width: 720px; margin: 24px auto; padding: 0 16px; }
    .card { background: #fff; border-radius: 10px; padding: 20px 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); margin-bottom: 18px; }
    h1 { font-size: 1.4rem; margin: 0 0 4px; }
    h2 { font-size: 1.1rem; margin: 0 0 10px; }
    p.lead { color: #4a5764; margin-top: 0; }
    .btn { display: inline-block; padding: 11px 18px; border-radius: 8px; background: #0b5f3b; color: #fff; border: 0; font-size: 1rem; cursor: pointer; text-decoration: none; }
    .btn:hover { background: #094c30; }
    .btn.secondary { background: #e3e8ec; color: #1c2530; }
    .btn.secondary:hover { background: #d3dade; }
    .btn.block { display: block; width: 100%; text-align: center; margin-bottom: 10px; }
    label { display: block; font-weight: 600; margin-bottom: 4px; }
    input[type=email], input[type=text], textarea, select { width: 100%; padding: 9px; border: 1px solid #b9c2cb; border-radius: 6px; font: inherit; margin-bottom: 14px; }
    textarea { min-height: 120px; resize: vertical; }
    .msg { padding: 10px 12px; border-radius: 6px; margin-bottom: 14px; }
    .msg.error { background: #fdecea; color: #b3261e; }
    .msg.ok { background: #e7f4ea; color: #1b5e20; }
    .msg.info { background: #e8f0fe; color: #174ea6; }
    .guidance { background: #fbf7e8; border: 1px solid #ecdfb0; border-radius: 8px; padding: 12px 14px; font-size: 0.92em; color: #5b4c1f; }
    table.rapp { width: 100%; border-collapse: collapse; }
    table.rapp th, table.rapp td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #e3e8ec; font-size: 0.93em; }
    .muted { color: #6b7683; font-size: 0.9em; }
</style>
</head>
<body>
<header class="rapp-bar">
    <a href="<?= rapp_esc(($basePath ?? '') . '/index.php') ?>"><strong>RAPP</strong> — Referee Abuse Prevention Program</a>
    <span class="who">
        <?php if ($userName): ?>
            <?= rapp_esc($userName) ?> · <a href="<?= rapp_esc(($basePath ?? '') . '/logout.php') ?>">Sign out</a>
        <?php endif; ?>
    </span>
</header>
<main class="rapp-wrap">
    <?php
}

function rapp_layout_foot(): void
{
    ?>
</main>
</body>
</html>
    <?php
}
