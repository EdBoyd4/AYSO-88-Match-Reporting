<?php

require_once __DIR__ . '/../focused/class-view-game-cards-photo-entry-segment.php';
require_once __DIR__ . '/../focused/class-view-ref-names.php';

class MatchReportFieldSet {
    private ?string $refName;
    private ?string $ar1Name;
    private ?string $ar2Name;

    public function __construct(?string $refName = null, ?string $ar1Name = null, ?string $ar2Name = null) {
        $this->refName = $refName;
        $this->ar1Name = $ar1Name;
        $this->ar2Name = $ar2Name;
    }

    public function render(): void {
        // placeholder for version that includes Vartan/ Aldo page $gamePickerOptions = selectScheduledMatchOptionsFromDatabase();
        // The old redirect-to-a-separate-page RAPP link used to live here.
        // Removed: RappIncidentFieldset (rendered further down this same
        // form) now covers filing a RAPP report inline, without a redirect.
        echo'<fieldset class="match-report-descriptor" id="match-report-descriptor">
            <legend class="match-report-descriptor__legend">Referee Details</legend>';
        (new RefNames(0, $this->refName))->render();
        (new RefNames(1, $this->ar1Name))->render();
        (new RefNames(2, $this->ar2Name))->render();
        (new GameCardsPhotoEntrySegment(1))->render();
        (new GameCardsPhotoEntrySegment(2))->render();
        echo '</fieldset>';
    }
}
