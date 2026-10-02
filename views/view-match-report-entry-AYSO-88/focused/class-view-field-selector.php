<?php

class FieldSelector {
    private array $options;
    private ?string $selectedFieldNumber;

    public function __construct(array $gamePickerOptions, ?string $selectedFieldNumber = null) {
        $this->options = $this->extractUniqueFields($gamePickerOptions);
        $this->selectedFieldNumber = $selectedFieldNumber;
    }

    // Dedupes by field_number, then orders the same way the `fields` table
    // itself lists rows (by _id) rather than by first-appearance in the
    // (date-sorted) scheduled_matches query.
    private function extractUniqueFields(array $gamePickerOptions): array {
        $uniqueFields = [];
        foreach ($gamePickerOptions as $field) {
            $key = $field['field_number'];
            if (!isset($uniqueFields[$key])) {
                $uniqueFields[$key] = [
                    'field_number' => $field['field_number'],
                    'field_name' => $field['field_name'],
                    'field_row_id' => $field['field_row_id']
                ];
            }
        }
        $uniqueFields = array_values($uniqueFields);
        usort($uniqueFields, fn($a, $b) => $a['field_row_id'] <=> $b['field_row_id']);
        return $uniqueFields;
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
