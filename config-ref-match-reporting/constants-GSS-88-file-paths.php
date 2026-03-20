<?php
declare(strict_types=1);

define('GSS88_ROOT', dirname(__DIR__));                 // /home/xnbglkce/gss88
define('GSS88_PUBLIC', GSS88_ROOT . '/public');

define('GSS88_CONFIG_FILES', GSS88_ROOT . '/config-ref-match-reporting');
/* constants-file-paths.php
constants-model-match-and-sanction-db.php
constants-sanction-detail-menus.php */

define('GSS88_CONTROLLERS_UPLOAD', GSS88_ROOT . '/components-controllers-report-entry-AYSO-88');
/* controller-match-report-photo-upload.php
controller-match-report-sanitize-and-enter.php */

define('GSS88_CONTROLLERS-UPDATES', GSS88_ROOT . '/components-controllers-reporting-updates-AYSO-88');
// controller-match-report-email.php
// cronTest.php

define('GSS88_MODELS_REPORTS', GSS88_ROOT . '/components-model-report-entry-AYSO-88');
/* model-report-entry.php*/

define('GSS88_VIEWS_REPORTS', GSS88_ROOT . '/components-view-report-entry-AYSO-88');
/*
view-section-match-detail.php
view-section-sanction-entry.php 
*/

define('GSS88_ASSETS_REPORT_PHOTOS', GSS88_ROOT . '/assets-match-report-photos');
