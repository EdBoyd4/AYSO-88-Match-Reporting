<?php

require_once __DIR__ . '/class-view-match-report-fieldset.php';
require_once __DIR__ . '/class-view-sanctions-reports-fieldset.php';
require_once __DIR__ . '/../focused/class-view-match-notes-fieldset.php';
require_once __DIR__ . '/../focused/class-view-scores-section.php';

class MatchResultsFieldset {
    public function render(): void {
        (new ScoresSection())->render();
        (new MatchReportFieldSet())->render();
        (new SanctionsReportsFieldSet())->render();
        (new MatchNotesFieldset())->render();
    }
}