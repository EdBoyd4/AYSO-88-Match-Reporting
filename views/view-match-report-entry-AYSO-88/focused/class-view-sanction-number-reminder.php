<?php

class SanctionNumberReminder {
    private int $containerId;

    public function __construct(int $containerId) {
        $this->containerId = $containerId;
    }

    public function render(): void {
        echo '<p 
        id="p_sanction-reminder-' . $this->containerId . '" 
        class="p_sanction-number-reminder">
        You are entering Sanction # ' . $this->containerId . ' of 5:
        </p>';
    }
}
