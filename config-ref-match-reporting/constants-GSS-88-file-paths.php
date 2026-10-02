<?php
declare(strict_types=1);

define('GSS88_ROOT', dirname(__DIR__));                 // /home/xnbglkce/gss88
define('GSS88_PUBLIC', GSS88_ROOT . '/public');

define('GSS88_CONFIG_FILES', GSS88_ROOT . '/config-ref-match-reporting');
/* constants-file-paths.php
constants-model-match-and-sanction-db.php
constants-sanction-detail-menus.php */

define('GSS88_CONTROLLERS_UPLOAD', GSS88_ROOT . '/controllers/controllers-match-report-entry-AYSO-88');
/* controller-match-report-photo-upload.php
controller-match-report-sanitize-and-enter.php */

// NOTE: was 'GSS88_CONTROLLERS-UPDATES' (hyphen) - a hyphen in a bareword
// constant name reads as subtraction wherever it's referenced, so this was
// never actually usable and nothing could reference it correctly.
define('GSS88_CONTROLLERS_UPDATES', GSS88_ROOT . '/controllers/controllers-match-issue-reports-AYSO-88');
// controller-match-report-email.php
// cronTest.php

// Split 2026-10-02 into model-match-report-entry/ (match-data only) and
// model-rapp-report-entry/ (RAPP only, used directly by rapp-bootstrap.php,
// not via a constant here) -- was one combined model-match-report-entry-
// AYSO-88/ directory holding both.
define('GSS88_MODELS_REPORTS', GSS88_ROOT . '/models/model-match-report-entry');
/* model-match-data-insert.php  -- match-data writes (match-report.php's only write path)
model-match-data-query.php   -- match-data reads, shared with the RAPP dashboard;
                                 also the notification email's one query */

define('GSS88_VIEWS_REPORTS', GSS88_ROOT . '/views/view-match-report-entry-AYSO-88');
/*
class-view-sanctions-reports-fieldset.php
*/

define('GSS88_VIEWS_REPORTS_FOCUSED', GSS88_VIEWS_REPORTS . '/focused');
/*
class-view-division-selector.php
class-view-field-selector.php
class-view-game-cards-photo-entry-segment.php
class-view-match-date-selector.php
class-view-match-notes-fieldset.php
class-view-match-report-form-header.php
class-view-match-time-selector.php
class-view-ref-names.php
class-view-ref-staffing-issue-gpt2.php
class-view-submit-fieldset.php
class-view-sanction-number-reminder.php
*/

define('GSS88_VIEWS_REPORTS_COMPOUND', GSS88_VIEWS_REPORTS . '/compound');
/*
class-view-match-descriptor-fieldset.php
class-view-match-report-fieldset.php
class-view-match-report-form.php
*/

define('GSS88_COLLATERAL_JS_DISPLAY', GSS88_ROOT . '/collateral/js/display');
/* 
display-gamecard-photo.js
display-match-details.js
display-sanction-entry.js 
*/

define('GSS88_COLLATERAL_STYLES', GSS88_ROOT . '/collateral/styles');
/* 
styles-gss88-match-report-form.css 
*/

define('GSS88_COLLATERAL_JS_CONTROLLERS', GSS88_ROOT . '/collateral/js/controllers');
/*
controller-gamecard-files.js
controller-match-details.js
controller-match-report-form.js
*/

define('GSS88_COLLATERAL_IMAGES', GSS88_ROOT . '/collateral/images');
/* 
5home.webp
88_logo.png
favicon.ico
goal-grass.jpg
image_example-1.jpg
image_example-2.jpg
*/

define('GSS88_ASSETS_REPORT_PHOTOS', GSS88_ROOT . '/assets-match-report-photos');
