<?php

class RefStaffingIssueGPT2 {
    private ?string $staffingIssueNumber;
    private ?string $staffingIssue;

    public function __construct(?string $staffingIssueNumber = null, ?string $staffingIssue = null) {
        $this->staffingIssueNumber = $staffingIssueNumber ?? ''; // Handle null case
        $this->staffingIssue = $staffingIssue;
    }

    public function render(): void {
        echo '<fieldset id="section_ref-staffing-issue' . $this->staffingIssueNumber . '" class="section-match-entry">
                <legend>Referee Staffing Issue</legend>';
        echo '<button 
                type="button"
                id="button_ref-staffing-issue' . $this->staffingIssueNumber . '"
                class="button_ref-staffing-issue"
                aria-controls="section_ref-staffing-issue-detail' . $this->staffingIssueNumber . '"
                aria-expanded="false">
                Report a Referee Staffing Issue
            </button>';
        echo '<section id="section_ref-staffing-issue-detail' . $this->staffingIssueNumber . '" class="section_match-issue" hidden>';
        echo '<label for="textarea_ref-staffing-issue' . $this->staffingIssueNumber . '" 
                id="label_ref-staffing-issue' . $this->staffingIssueNumber . '">Please Provide A Brief Description of the Issue(s):</label>
                <textarea
                    id="textarea_ref-staffing-issue' . $this->staffingIssueNumber . '" 
                    name="refStaffingIssueText' . $this->staffingIssueNumber . '" 
                    class="form-control" 
                    rows="1" 
                    cols="60">'
                    . ($this->staffingIssue === null ? '' : htmlspecialchars($this->staffingIssue)) . '
                </textarea>';
        echo '</section>';
        echo '</fieldset>';
    }
}
