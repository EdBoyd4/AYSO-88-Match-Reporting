<?php

require_once __DIR__ . '/class-view-sanction-number-reminder.php';
require_once __DIR__ . '/class-view-sanction-level-radio-section.php';
require_once __DIR__ . '/class-view-sanction-party-radio-section.php';
require_once __DIR__ . '/class-view-sanction-party-section.php';
require_once __DIR__ . '/class-view-sanction-summary-section.php';

class SanctionEntrySegment {
    private int $containerId;
    private ?string $sanctionLevel;
    private ?string $sanctionParty;
    private ?string $sanctionedPartyDescriptionText;
    private ?string $sanctionReasonText;

    public function __construct(
        int $containerId, 
        ?string $sanctionLevel = null, 
        ?string $sanctionParty = null, 
        ?string $sanctionedPartyDescriptionText = null, 
        ?string $sanctionReasonText = null
    ) {
        $this->containerId = $containerId;
        $this->sanctionLevel = $sanctionLevel;
        $this->sanctionParty = $sanctionParty;
        $this->sanctionedPartyDescriptionText = $sanctionedPartyDescriptionText;
        $this->sanctionReasonText = $sanctionReasonText;
    }

    public function render(): void {
        $hiddenAttr = ($this->containerId === 1) ? '' : ' hidden';
        
        echo '<section id="section_sanction-entry-' . $this->containerId . '" class="section_sanction-entry"' . $hiddenAttr . '>';
        
        echo '<button 
            type="button" 
            data-entry-type="sanction_report" 
            id="button_sanction-entry-' . $this->containerId . '" 
            class="button_sanction-entry"
            aria-controls="section_sanction-info-' . $this->containerId . '"
            aria-expanded="false">';
            
        echo ($this->containerId === 1) ? 'I Had to Caution / Send-Off Someone' : 'I Had to Caution / Send-Off Someone Else';
        
        echo '</button>';
        
        if ($this->containerId >= 6) {
            echo '<section id="section_sanction-info-' . $this->containerId . '" 
                class="section_sanction-info-entry" 
                style="display:none;">';
            echo '<p 
                id="section_sanction-severe" 
                class="p_sanction-severe">
                Please Contact the Regional Referee Administrator and your Division Coordinator ASAP! 
                This match must be directly and thoroughly reported.
                </p>';
        } else {
            echo '<section id="section_sanction-info-' . $this->containerId . '" 
                class="section_sanction-info-entry" 
                style="display:none;">';
                
            (new SanctionNumberReminder($this->containerId))->render();
            (new SanctionLevelRadioSection($this->containerId, $this->sanctionLevel))->render();
            (new SanctionPartyRadioSection($this->containerId, $this->sanctionParty))->render();
            (new SanctionPartySection($this->containerId, $this->sanctionedPartyDescriptionText))->render();
            (new SanctionSummarySection($this->containerId, $this->sanctionReasonText))->render();
        }
        
        echo '</section>';
        echo '</section>';
    }
}
