<?php

class DivisionSelector {
    private array $options;
    private ?string $selectedDivisionNumber;

    public function __construct(array $gamePickerOptions, ?string $selectedDivisionNumber = null) {
        $this->options = $this->extractUniqueDivisions($gamePickerOptions);
        $this->selectedDivisionNumber = $selectedDivisionNumber;
    }

    private function extractUniqueDivisions(array $gamePickerOptions): array {
        $uniqueDivisions = [];
        foreach ($gamePickerOptions as $division) {
            $key = $division['division_number'] . '|' . $division['division_name'];
            if (!isset($uniqueDivisions[$key])) {
                $uniqueDivisions[$key] = [
                    'division_number' => $division['division_number'],
                    'division_name' => $division['division_name']
                ];
            }
        }
        return array_values($uniqueDivisions);
    }

    public function render(): void {
        echo '<section class="match-descriptor__group match-descriptor__group--division">
        <label for="division-select" class="match-descriptor__label">Division:</label>
        <select id="division-select" name="division-select" class="form-control match-descriptor__input match-descriptor__select" required>';
        
        echo '<option value="none" ' . (is_null($this->selectedDivisionNumber) ? 'selected' : '') . '>Select a Division</option>';
        
        foreach ($this->options as $division) {
            $divisionNumber = htmlspecialchars((string)$division['division_number'], ENT_QUOTES, 'UTF-8');
            $divisionName = htmlspecialchars((string)$division['division_name'], ENT_QUOTES, 'UTF-8');
            $isSelected = ($divisionNumber == $this->selectedDivisionNumber) ? 'selected' : '';
            $value = htmlspecialchars('division' . $divisionNumber, ENT_QUOTES, 'UTF-8');
            
            echo '<option value="' . $value . '" ' . $isSelected . '>' . $divisionName . '</option>';
        }
        
        echo '</select>
    </section>';
    }
}
