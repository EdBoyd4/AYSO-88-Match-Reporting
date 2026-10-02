<?php

class SanctionPartyDescriptionSection {
    private int $containerId;
    private ?string $sanctionedPartyDescriptionText;

    public function __construct(int $containerId, ?string $sanctionedPartyDescriptionText = null) {
        $this->containerId = $containerId;
        $this->sanctionedPartyDescriptionText = $sanctionedPartyDescriptionText;
    }

    public function render(): void {
        echo '<textarea 
        name="sanction-party-description-' . $this->containerId . '" 
        id="textarea_sanction-party-description-' . $this->containerId . '" 
        class="form-control textarea_sanction-party-description" 
        rows = "1" 
        cols = "60">';
        
        if ($this->sanctionedPartyDescriptionText != null) {
            echo htmlspecialchars($this->sanctionedPartyDescriptionText, ENT_QUOTES, 'UTF-8');
        }
        
        echo '</textarea>';
    }
}
