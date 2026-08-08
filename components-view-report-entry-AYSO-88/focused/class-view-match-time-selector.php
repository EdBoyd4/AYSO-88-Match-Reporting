<?php

class MatchTimeSelector {
    private array $options;
    private ?string $selectedTime;

    public function __construct(array $gamePickerOptions, ?string $selectedTime = null) {
        $this->options = $this->extractUniqueTimes($gamePickerOptions);
        $this->selectedTime = $selectedTime;
    }

    private function extractUniqueTimes(array $gamePickerOptions): array {
        return array_unique(array_column($gamePickerOptions, 'formatted_match_time'));
    }

    public function render(): void {
        echo '<section class="match-descriptor__group match-descriptor__group--time">
        <label for="match-time" class="match-descriptor__label">Time:</label>
        <select id="match-time" name="match-time" class="form-control match-descriptor__input match-descriptor__input--time" required>';    
        
        echo '<option value="none" ' . (is_null($this->selectedTime) ? 'selected' : '') . '>Select a Time</option>';
        
        foreach ($this->options as $time) {
            $formattedTime = htmlspecialchars((string)$time, ENT_QUOTES, 'UTF-8');
            $isSelected = ($formattedTime == $this->selectedTime) ? 'selected' : '';
            
            echo '<option value="' . $formattedTime . '" ' . $isSelected . '>' . $formattedTime . '</option>';
        }
        
        echo '</select>
    </section >';
    }
}
