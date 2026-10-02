<?php

require_once __DIR__ . '/../focused/class-view-match-report-form-header.php';
require_once __DIR__ . '/class-view-match-descriptor-fieldset.php';
require_once __DIR__ . '/class-view-sanctions-reports-fieldset.php';
require_once __DIR__ . '/class-view-match-results-fieldset.php';
require_once __DIR__ . '/../focused/class-view-submit-fieldset.php';

class MatchReportForm {
    private string $formAction;
    private MatchDataQueries $matchDataQueries;
    private string $rappCsrfToken;

    public function __construct(string $formAction, MatchDataQueries $matchDataQueries, string $rappCsrfToken) {
        $this->formAction = $formAction;
        $this->matchDataQueries = $matchDataQueries;
        $this->rappCsrfToken = $rappCsrfToken;
    }

    // Cache-busts each local asset with its own file's mtime, so a deploy
    // that changes one script/stylesheet is picked up immediately instead of
    // referees running whatever their browser had cached from before -- this
    // page previously had no cache-busting at all on any asset tag.
    private function assetVersion(string $collateralRelativePath): string {
        $path = dirname(__DIR__, 3) . '/collateral/' . $collateralRelativePath;
        $mtime = @filemtime($path);
        return $mtime !== false ? (string) $mtime : (string) time();
    }

    public function render(): void {
        $gamePickerOptions = $this->matchDataQueries->scheduledMatchPickerOptions();
        $v = fn(string $collateralRelativePath) => $this->assetVersion($collateralRelativePath);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AYSO Region 88 – Referee Match Report</title>
    <script> const allMatches = <?=
        json_encode($gamePickerOptions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?></script>
    <script type="text/javascript" src="controller-gamecard-files.js?v=<?= $v('js/controllers/controller-gamecard-files.js') ?>" defer></script>
    <script type="text/javascript" src="controller-match-details.js?v=<?= $v('js/controllers/controller-match-details.js') ?>" defer></script>
    <script type="text/javascript" src="display-gamecard-photo.js?v=<?= $v('js/display/display-gamecard-photo.js') ?>" defer></script>
    <script type="text/javascript" src="display-match-details.js?v=<?= $v('js/display/display-match-details.js') ?>" defer></script>
    <script type="text/javascript" src="display-sanction-entry.js?v=<?= $v('js/display/display-sanction-entry.js') ?>" defer></script>
    <script type="text/javascript" src="controller-rapp-incident-section.js?v=<?= $v('js/controllers/controller-rapp-incident-section.js') ?>" defer></script>
    <script type="text/javascript" src="controller-match-report-form.js?v=<?= $v('js/controllers/controller-match-report-form.js') ?>" defer></script>
    <link rel="stylesheet" href="styles-gss88-match-report-form.css?v=<?= $v('styles/styles-gss88-match-report-form.css') ?>">
</head>
<body>
    <div class="bg" aria-hidden="true"></div>
    <main>
        <form
            id="form_ref_match_report"
            name="gameResultsEntryForm"
            method="post"
            action="<?= htmlspecialchars($this->formAction); ?>"
            enctype="multipart/form-data"
            class="wrap card"
            aria-labelledby="title"
        >
<?php
        (new MatchReportFormHeader())->render();
        (new MatchDescriptorFieldSet($this->matchDataQueries))->render();
        (new MatchResultsFieldset($this->rappCsrfToken))->render();
        (new SanctionsReportsFieldSet())->render();
        (new SubmitFieldset())->render();
?>
        </form>
        <div id="loadingMessage"
            class="loading-message"
            aria-live="polite"
            hidden
        >
            Uploading your report. Please wait for confirmation. Depending on the age
            of your device, and the quality of your connection, this may take up to a minute.
        </div>
    </main>
</body>
</html>
<?php
    }
}
