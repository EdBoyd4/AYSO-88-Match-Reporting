<?php

class MatchReportFormHeader {
    public function render(): void {
        echo '<header class="form_ref_match-header">
                <img src="88_logo.png" alt="AYSO Region 88 Glendale logo" class="logo" />
                <h1 id="title">AYSO Region 88<br>Fall Core</h1>
                <h2>Referee Game Report</h2>
                <p class="tagline">Brought to You By Glendale Soccer Scores (gss.org)</p>
            </header>';
    }
}
