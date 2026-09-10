<?php
declare(strict_types=1);

require_once __DIR__ . '/../../rapp/config/rapp-bootstrap.php';

$authManager->logout($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
header('Location: ' . $rappConfig['base_path'] . '/index.php');
exit;
