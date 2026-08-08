<?php

class FieldSelector {
    private array $options;
    private ?string $selectedFieldNumber;

    public function __construct(array $gamePickerOptions, ?string $selectedFieldNumber = null) {
        $this->options = $this->extractUniqueFields($gamePickerOptions);
        $this->selectedFieldNumber = $selectedFieldNumber;
    }

    private function extractUniqueFields(array $gamePickerOptions): array {
        $uniqueFields = [];
        foreach ($gamePickerOptions as $field) {
            $key = $field['field_number'] . '|' . $field['field_name'];
            if (!isset($uniqueFields[$key])) {
                $uniqueFields[$key] = [
                    'field_number' => $field['field_number'],
                    'field_name' => $field['field_name']
                ];
            }
        }
        return array_values($uniqueFields);
    }

    public function render(): void {
        echo '<section class="match-descriptor__group match-descriptor__group--field">
        <label for="select-field" class="match-descriptor__label">Field:</label>
        <select id="select-field" name="field-select" class="form-control match-descriptor__input match-descriptor__select" required>';
        
        echo '<option value="none" ' . (is_null($this->selectedFieldNumber) ? 'selected' : '') . '>Select a field</option>';
        
        foreach ($this->options as $field) {
            $fieldNumber = htmlspecialchars((string)$field['field_number'], ENT_QUOTES, 'UTF-8');
            $fieldName = htmlspecialchars((string)$field['field_name'], ENT_QUOTES, 'UTF-8');
            $isSelected = ($fieldNumber == $this->selectedFieldNumber) ? 'selected' : '';
            $value = htmlspecialchars('field' . $fieldNumber, ENT_QUOTES, 'UTF-8');
            
            echo '<option value="' . $value . '" ' . $isSelected . '>' . $fieldName . '</option>';
        }
        
        echo '</select>
    </section>';
    }
}
