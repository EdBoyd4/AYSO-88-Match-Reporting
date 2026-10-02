<?php

require_once __DIR__ . '/class-view-sanction-party-caption-section.php';
require_once __DIR__ . '/class-view-sanction-party-description-section.php';

class SanctionPartySection {
    private int $containerId;
    private ?string $sanctionedPartyDescriptionText;

    public function __construct(int $containerId, ?string $sanctionedPartyDescriptionText = null) {
        $this->containerId = $containerId;
        $this->sanctionedPartyDescriptionText = $sanctionedPartyDescriptionText;
    }

    public function render(): void {
        echo '<section 
        id="section_sanction-party-description-' . $this->containerId . '" 
        class="section_sanction-detail" 
        style="display:none;">';
        
        (new SanctionPartyCaptionSection($this->containerId))->render();
        (new SanctionPartyDescriptionSection($this->containerId, $this->sanctionedPartyDescriptionText))->render();
        
        echo '</section>';
    }
}
