<?php

class MatchTimeSelector {
    private array $options;
    private ?string $selectedTime;

    public function __construct(array $gamePickerOptions, ?string $selectedTime = null) {
        $this->options = $this->extractUniqueTimes($gamePickerOptions);
        $this->selectedTime = $selectedTime;
    }

    // Dedupes and orders ascending. formatted_match_time is zero-padded
    // 24-hour H:i (from DATE_FORMAT(..., '%H:%i')), so a plain ascending
    // sort is also chronological order.
    private function extractUniqueTimes(array $gamePickerOptions): array {
        $uniqueTimes = array_values(array_unique(array_column($gamePickerOptions, 'formatted_match_time')));
        sort($uniqueTimes);
        return $uniqueTimes;
    }

    public function render(): void {
        echo '<section class="match-descriptor__group match-descriptor__group--time">
        <label for="match-time" class="match-descriptor__label">Time:</label>
        <select id="match-time" name="match-time" class="form-control match-descriptor__input match-descriptor__input--time" required>';    
        
        echo '<option value="none" ' . (is_null($this->selectedTime) ? 'selected' : '') . '>Select a Time</option>';
        
        foreach ($this->options as $time) {
            // The value stays 24-hour (it's what sorts correctly and what
            // the rest of the app/DB matching expects); only the label
            // shown to the ref is converted to 12-hour AM/PM.
            $formattedTime = htmlspecialchars((string)$time, ENT_QUOTES, 'UTF-8');
            $displayTime = htmlspecialchars(DateTime::createFromFormat('H:i', $time)->format('g:i A'), ENT_QUOTES, 'UTF-8');
            $isSelected = ($formattedTime == $this->selectedTime) ? 'selected' : '';

            echo '<option value="' . $formattedTime . '" ' . $isSelected . '>' . $displayTime . '</option>';
        }
        
        echo '</select>
    </section >';
    }
}
