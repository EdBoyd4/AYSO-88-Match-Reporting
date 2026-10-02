<?php

require_once __DIR__ . '/../focused/class-view-field-selector.php';
require_once __DIR__ . '/../focused/class-view-division-selector.php';
require_once __DIR__ . '/../focused/class-view-match-date-selector.php';
require_once __DIR__ . '/../focused/class-view-match-time-selector.php';
require_once __DIR__ . '/../focused/class-view-scores-section.php';

class MatchDescriptorFieldSet {
    private MatchDataQueries $matchDataQueries;
    private ?string $matchDate;
    private ?string $matchTime;
    private ?int $fieldNumber;
    private ?int $teamDivisionNumber;
    private ?int $homeScore;
    private ?int $awayScore;

    public function __construct(MatchDataQueries $matchDataQueries, ?string $matchDate = null, ?string $matchTime = null, ?int $fieldNumber = null, ?int $teamDivisionNumber = null, ?int $homeScore = null, ?int $awayScore = null) {
        $this->matchDataQueries = $matchDataQueries;
        $this->matchDate = $matchDate;
        $this->matchTime = $matchTime;
        $this->fieldNumber = $fieldNumber;
        $this->teamDivisionNumber = $teamDivisionNumber;
        $this->homeScore = $homeScore;
        $this->awayScore = $awayScore;
    }

    public function render(): void {
        $gamePickerOptions = $this->matchDataQueries->scheduledMatchPickerOptions();
        echo'<fieldset class="match-descriptor" id="match-descriptor">
            <legend class="match-descriptor__legend">Match Details</legend>';

        (new MatchDateSelector($gamePickerOptions, $this->matchDate))->render();
        (new MatchTimeSelector($gamePickerOptions, $this->matchTime))->render();
        (new FieldSelector($gamePickerOptions, $this->fieldNumber))->render();
        (new DivisionSelector($gamePickerOptions, $this->teamDivisionNumber))->render();

        echo '<input type="hidden" id="game-details-data" value="' . htmlspecialchars(json_encode($gamePickerOptions), ENT_QUOTES, 'UTF-8') . '" />';

        (new ScoresSection($this->homeScore, $this->awayScore))->render();

        echo '</fieldset>';
    }
}
