<?php

class SanctionPartyCaptionSection {
    private int $containerId;

    public function __construct(int $containerId) {
        $this->containerId = $containerId;
    }

    public function render(): void {
        // Description Of The Player / Coach
        echo '<label 
        for="textarea_sanction-party-description-' . $this->containerId . '" 
        id="label_sanction-party-' . $this->containerId . '" 
        class="label_sanction-description">
        </label>
        <p 
        id="p_sanction-party-' . $this->containerId . '" 
        class="p_sanction-description">
        </p>';
    }
}
