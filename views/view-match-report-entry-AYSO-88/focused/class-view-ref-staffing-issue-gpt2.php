<?php

class RefStaffingIssueGPT2 {
    private ?string $staffingIssueNumber;
    private ?string $staffingIssue;

    public function __construct(?string $staffingIssueNumber = null, ?string $staffingIssue = null) {
        $this->staffingIssueNumber = $staffingIssueNumber ?? ''; // Handle null case
        $this->staffingIssue = $staffingIssue;
    }

    public function render(): void {
        // Outer fieldset is its own BEM block (ref-staffing-descriptor),
        // matching its sibling fieldsets (match-notes-descriptor, etc). The
        // toggle button + detail section live in a nested .section-match-entry
        // wrapper, same as those siblings -- that shared class is what gives
        // the button its styling/hover/press effect, so it belongs on the
        // inner wrapper, not the fieldset itself.
        echo '<fieldset id="ref-staffing-descriptor" class="ref-staffing-descriptor">
                <legend class="ref-staffing-descriptor__legend">Referee Staffing Issue</legend>
                <section id="section_ref-staffing-issue-entry' . $this->staffingIssueNumber . '" class="section-match-entry">';
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
        echo '</section>
        </fieldset>';
    }
}
