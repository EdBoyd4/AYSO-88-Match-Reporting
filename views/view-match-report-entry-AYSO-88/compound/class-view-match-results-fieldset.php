<?php

require_once __DIR__ . '/class-view-match-report-fieldset.php';
require_once __DIR__ . '/../focused/class-view-match-notes-fieldset.php';
require_once __DIR__ . '/../focused/class-view-ref-staffing-issue-gpt2.php';
require_once __DIR__ . '/../focused/class-view-rapp-incident-fieldset.php';

class MatchResultsFieldset {
    public function __construct(private string $rappCsrfToken) {}

    public function render(): void {
        (new MatchReportFieldSet())->render();
        (new RefStaffingIssueGPT2())->render();
        (new RappIncidentFieldset($this->rappCsrfToken))->render();
        (new MatchNotesFieldset())->render();
    }
}