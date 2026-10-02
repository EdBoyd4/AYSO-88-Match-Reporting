<?php

class SanctionPartyRadioSection {
    private int $containerId;
    private ?string $sanctionParty;

    public function __construct(int $containerId, ?string $sanctionParty = null) {
        $this->containerId = $containerId;
        $this->sanctionParty = $sanctionParty;
    }

    public function render(): void {
        echo '<section id="section_sanctioned-party-' . $this->containerId . '" 
        class="section_sanctioned-party">
        <p 
        id="p_sanctioned-party-detail-' . $this->containerId . '" 
        class="p_sanctioned-party-detail">
        I Cautioned / Sent-Off:
        </p>
        <section 
        id="sanctioned-party-detail-' . $this->containerId . '" 
        class="section_sanctioned-party-detail">';
        
        foreach (SANCTIONED_PARTY_OPTIONS as $item) {
            echo '
            <label><input 
            type="radio" 
            id="radio_button-sanction-party-' . $item['label'] . '-' . $this->containerId . '" 
            name="sanctionParty' . $this->containerId . '" 
            value="' . $item['label'] . '"  
            class="radio_button_sanctioned-party"';
            
            if ($item['label'] == $this->sanctionParty) {
                echo 'checked';
            }
            
            echo '>' . $item['text'] . '</label>';
        }
        
        echo '</section>
        </section>';
    }
}
