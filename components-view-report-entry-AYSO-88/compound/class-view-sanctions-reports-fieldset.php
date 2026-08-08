<?php

require_once GSS88_CONFIG_FILES . '/constants-sanction-detail-menus.php';
require_once GSS88_VIEWS_REPORTS_FOCUSED . '/class-view-sanction-entry-segment.php';

class SanctionsReportsFieldSet {
    public function render(): void {
        echo '<fieldset class="sanction-reports-descriptor" 
            id="sanction-reports-descriptor">
            <legend 
            class="sanction-reports-descriptor__legend">
            Sanctions Issued
            </legend>';
            
        (new SanctionEntrySegment(1))->render();
        (new SanctionEntrySegment(2))->render();
        (new SanctionEntrySegment(3))->render();
        (new SanctionEntrySegment(4))->render();
        (new SanctionEntrySegment(5))->render();
        (new SanctionEntrySegment(6))->render();
        
        echo '</fieldset>';
    }
}
