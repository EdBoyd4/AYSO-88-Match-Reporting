<?php

class MatchDateSelector {
    private array $options;
    private ?string $selectedDate;

    public function __construct(array $gamePickerOptions, ?string $selectedDate = null) {
        $this->options = $this->extractUniqueDates($gamePickerOptions);
        $this->selectedDate = $selectedDate;
    }

    private function extractUniqueDates(array $gamePickerOptions): array {
        return array_unique(array_column($gamePickerOptions, 'match_date'));
    }

    public function render(): void {
        echo '<section class="match-descriptor__group match-descriptor__group--date">
        <label for="match-date" class="match-descriptor__label">Date:</label>
        <select id="match-date" name="match-date" class="form-control match-descriptor__input match-descriptor__input--date" required>';
        
        echo '<option value="none" ' . (is_null($this->selectedDate) ? 'selected' : '') . '>Select a Date</option>';

        foreach ($this->options as $date) {
            $dateForUser = new DateTime($date);
            $formattedDateValue = htmlspecialchars($dateForUser->format('Y-m-d'), ENT_QUOTES, 'UTF-8');
            $formattedDateDisplay = htmlspecialchars($dateForUser->format('m-d-Y'), ENT_QUOTES, 'UTF-8');
            $isSelected = ($formattedDateValue == $this->selectedDate) ? 'selected' : '';
            
            echo '<option value="' . $formattedDateValue . '" ' . $isSelected . '>' . $formattedDateDisplay . '</option>';
        }
        
        echo '</select>
    </section>';
    }
}
