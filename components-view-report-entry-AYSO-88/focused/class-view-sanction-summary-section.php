<?php

class SanctionSummarySection {
    private int $containerId;
    private ?string $sanctionReasonText;

    public function __construct(int $containerId, ?string $sanctionReasonText = null) {
        $this->containerId = $containerId;
        $this->sanctionReasonText = $sanctionReasonText;
    }

    public function render(): void {
        echo '<section 
        id="section_sanction-summary-' . $this->containerId . '" 
        class="section_sanction-summary" 
        style="display:none;">
        <label 
        for="textarea_sanction-summary-' . $this->containerId . '"
        class="sanctionEntry_labels">
        Please Provide A Brief Description of What Happened:
        </label>
        <textarea 
        name="sanction' . $this->containerId . 'SummaryText" 
        id="textarea_sanction-summary-' . $this->containerId . '" 
        class="form-control textarea_sanction-summary" 
        rows="1" 
        cols="60">';
              
        if ($this->sanctionReasonText != null) {
            echo htmlspecialchars($this->sanctionReasonText, ENT_QUOTES, 'UTF-8');
        }
        
        echo '</textarea>
        </section>';
    }
}
