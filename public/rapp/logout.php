<?php
declare(strict_types=1);

require_once __DIR__ . '/../../controllers/rapp-bootstrap.php';

$authManager->logout($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
// Not the RAPP hub (removed) -- the general two-choice landing page.
header('Location: /login.php');
exit;
