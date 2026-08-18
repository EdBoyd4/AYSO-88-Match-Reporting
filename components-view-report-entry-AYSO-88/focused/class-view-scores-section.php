<?php

class ScoresSection {
    private ?int $homeScore;
    private ?int $awayScore;

    public function __construct(?int $homeScore = null, ?int $awayScore = null) {
        $this->homeScore = $homeScore;
        $this->awayScore = $awayScore;
    }

    public function render(): void {
        echo '<fieldset class="match-score-descriptor" id="match-score-descriptor">
            <legend class="match-score-descriptor__legend">Final Score</legend>
            <div class="match-score-descriptor__row">';

        echo '<section id="section_home-score-entry" class="section-match-entry match-score-descriptor__group">
                <label for="number_home-score" id="label_number_home-score">
                    Home Team Score:
                </label>
                <input type="number"
                        name="homeScore"
                        id="number_home-score"
                        class="form-control match-score-descriptor__input"
                        min="0"
                        step="1"
                        inputmode="numeric"
                        ' . ($this->homeScore === null ? '' : 'value="' . htmlspecialchars((string) $this->homeScore, ENT_QUOTES, 'UTF-8') . '"') . ' required>
            </section>';

        echo '<section id="section_away-score-entry" class="section-match-entry match-score-descriptor__group">
                <label for="number_away-score" id="label_number_away-score">
                    Away Team Score:
                </label>
                <input type="number"
                        name="awayScore"
                        id="number_away-score"
                        class="form-control match-score-descriptor__input"
                        min="0"
                        step="1"
                        inputmode="numeric"
                        ' . ($this->awayScore === null ? '' : 'value="' . htmlspecialchars((string) $this->awayScore, ENT_QUOTES, 'UTF-8') . '"') . ' required>
            </section>';

        echo '</div>
        </fieldset>';
    }
}
