<?php

class SanctionLevelRadioSection {
    private int $containerId;
    private ?string $sanctionLevel;

    public function __construct(int $containerId, ?string $sanctionLevel = null) {
        $this->containerId = $containerId;
        $this->sanctionLevel = $sanctionLevel;
    }

    public function render(): void {
        echo '<section id="section_sanction-level-' . $this->containerId . '" 
            class="section_sanction-level">
            <p 
            id="p_sanction-level-detail-' . $this->containerId . '" 
            class="p_sanction-level-detail">
            I Issued:
            </p>
            <section 
            id="section_sanction-level-detail-' . $this->containerId . '" 
            class="section_sanctioned-level-detail">';
        
        foreach (SANCTION_LEVEL_OPTIONS as $item) {
            echo '
            <label><input 
            type="radio" 
            id="radio_button_sanction-' . $item['label'] . '-' . $this->containerId . '" 
            name="sanctionLevel' . $this->containerId . '" 
            value="' . $item['label'] . '" 
            class="radio_button_sanction-level"';
            
            if ($item['label'] == $this->sanctionLevel) {
                echo ' checked';
            }
            
            echo '>' . $item['text'] . '</label>';
        }
        
        echo '</section>
        </section>';
    }
}
