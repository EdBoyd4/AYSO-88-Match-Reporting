<?php

include_once GSS88_CONFIG_FILES . '/constants-sanction-detail-menus.php';

function sanctionNumberReminder($containerId){
    echo '<p 
        id="p_sanction-reminder-'.$containerId.'" 
        class="p_sanction-number-reminder">
        You are entering Sanction # '.$containerId.' of 5:
        </p>';
}

    // allows ref to select sanction level - Radio Button
function sanctionLevelRadioSection($containerId, $sanctionLevel = null){
    echo '<section id="section_sanction-level-'.$containerId.'" 
            class="section_sanction-level">
            <p 
            id="p_sanction-level-detail-'.$containerId.'" 
            class="p_sanction-level-detail">
            I Issued:
            </p>
            <section 
            id="section_sanction-level-detail-'.$containerId.'" 
            class="section_sanctioned-level-detail">';
    // name=value is sent to server
    foreach (SANCTION_LEVEL_OPTIONS as $item) {
        echo '
            <label>'. //  for="radio_button_sanction-'.$item['label'].'-'.$containerId.'"
            '<input 
            type="radio" 
            id="radio_button_sanction-'.$item['label'].'-'.$containerId.'" 
            name="sanctionLevel'.$containerId.'" 
            value="'.$item['label'].'" 
            class="radio_button_sanction-level"';
        if($item['label'] == $sanctionLevel){
                echo ' checked';
            }
        echo '>'.$item['text'].'</label>';
    }
    echo '</section>
    </section>'; 
}

// allows ref to select type of party sanctioned - Radio Button
function sanctionPartyRadioSection($containerId, $sanctionParty = null){
    echo '<section id="section_sanctioned-party-'.$containerId.'" 
        class="section_sanctioned-party">
        <p 
        id="p_sanctioned-party-detail-'.$containerId.'" 
        class="p_sanctioned-party-detail">
        I Cautioned / Sent-Off:
        </p>
        <section 
        id="sanctioned-party-detail-'.$containerId.'" 
        class="section_sanctioned-party-detail">';
    // name=value is sent to server
    foreach (SANCTIONED_PARTY_OPTIONS as $item) {
        echo '
            <label>' // for="radio_button_party-'.$item['label'].'-'.$containerId.'"
            .'
            <input 
            type="radio" 
            id="radio_button-sanction-party-'.$item['label'].'-'.$containerId.'" 
            name="sanctionParty'.$containerId.'" 
            value="' . $item['label'] . '"  
            class="radio_button_sanctioned-party"';
        if($item['label'] == $sanctionParty){
            echo 'checked';
        }
        echo '>'.$item['text'].'</label>';
    }
    echo '</section>
        </section>';  
}

function sanctionPartyCaptionSection($containerId){
    // Description Of The Player / Coach
    echo '<label 
        for="textarea_sanction-party-description-'.$containerId.'" 
        id="label_sanction-party-'.$containerId.'" 
        class="label_sanction-description">
        </label>
        <p 
        id="p_sanction-party-'.$containerId.'" 
        class="p_sanction-description">
        </p>';
}

function sanctionPartyDescriptionSection($containerId, $sanctionedPartyDescriptionText = null){
    echo '<textarea 
        name="sanction-party-description-'.$containerId.'" 
        id="textarea_sanction-party-description-'.$containerId.'" 
        class="form-control textarea_sanction-party-description" 
        rows = "1" 
        cols = "60">';
        if($sanctionedPartyDescriptionText != null){
            echo htmlspecialchars($sanctionedPartyDescriptionText, ENT_QUOTES, 'UTF-8');
        }
        echo '</textarea>';
}

function sanctionPartySection($containerId, $sanctionedPartyDescriptionText = null){
    echo '<section 
        id="section_sanction-party-description-'.$containerId.'" 
        class="section_sanction-detail" 
        style="display:none;">';
    sanctionPartyCaptionSection($containerId);
    sanctionPartyDescriptionSection($containerId, $sanctionedPartyDescriptionText);
    echo '</section>';
}

// allows ref to submit summary of incident
function sanctionSummary($containerId, $sanctionReasonText = null){
    echo '<section 
        id="section_sanction-summary-'.$containerId.'" 
        class="section_sanction-summary" 
        style="display:none;">
        <label 
        for="textarea_sanction-summary-'.$containerId.'"
        class="sanctionEntry_labels">
        Please Provide A Brief Description of What Happened:
        </label>
        <textarea 
        name="sanction'.$containerId.'SummaryText" 
        id="textarea_sanction-summary-'.$containerId.'" 
        class="form-control form-control textarea_sanction-summary" 
        rows = "1" 
        cols = "60">';
    if($sanctionReasonText != null){
        echo htmlspecialchars($sanctionReasonText, ENT_QUOTES, 'UTF-8');
    }
    echo '</textarea>
        </section>';
}

function sanctionReportBasic($containerId, $sanctionLevel = null, $sanctionParty = null, $sanctionedPartyDescriptionText = null, $sanctionReasonText = null){
    // creates div
        $hiddenAttr = ($containerId === 1) ? '' : ' hidden';
    echo '<section id="section_sanction-entry-'.$containerId.'" class="section_sanction-entry"'.$hiddenAttr.'>';
    echo '<button 
        type="button" 
        data-entry-type="sanction_report" 
        id="button_sanction-entry-'.$containerId.'" 
        class="button_sanction-entry"
        aria-controls="section_sanction-info-'.$containerId.'"
        aria-expanded="false">';
    echo ($containerId === 1) ? 'I Had to Caution / Send-Off Someone' : 'I Had to Caution / Send-Off Someone Else';
    echo '</button>';
    if ($containerId >= 6){
        echo '<section id="section_sanction-info-'.$containerId.'" 
            class="section_sanction-info-entry" 
            style="display:none;">';
        echo '<p 
            id="section_sanction-severe" 
            class="p_sanction-severe">
            Please Contact the Regional Referee Administrator and your Division Coordinator ASAP! 
            This match must be directly and thoroughly reported.
            </p>';
    }else{
        // initiates or cancels entry of sanction
        echo '<section id="section_sanction-info-'.$containerId.'" 
            class="section_sanction-info-entry" 
            style="display:none;">';
        sanctionNumberReminder($containerId);
        sanctionLevelRadioSection($containerId, $sanctionLevel);
        // allows ref to select type of party sanctioned - Radio Button
        sanctionPartyRadioSection($containerId, $sanctionParty);
        sanctionPartySection($containerId, $sanctionedPartyDescriptionText);
        sanctionSummary($containerId, $sanctionReasonText);
    }
    echo '</section>';
    echo '</section>';
}

function sanctionsReportsFieldSetView(){
    echo'<fieldset class="sanction-reports-descriptor" 
        id="sanction-reports-descriptor">
        <legend 
        class="sanction-reports-descriptor__legend">
        Sanctions Issued
        </legend>';
    sanctionReportBasic(1);
    sanctionReportBasic(2);
    sanctionReportBasic(3);
    sanctionReportBasic(4);
    sanctionReportBasic(5);
    sanctionReportBasic(6);
    echo '</fieldset>';
}
