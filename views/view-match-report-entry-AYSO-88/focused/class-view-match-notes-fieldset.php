<?php

class MatchNotesFieldset {
    private ?string $matchIssueNumber;
    private ?string $matchIssue;

    public function __construct(?string $matchIssueNumber = null, ?string $matchIssue = null) {
        $this->matchIssueNumber = $matchIssueNumber ?? '';
        $this->matchIssue = $matchIssue;
    }

    public function render(): void {
        echo '<fieldset class="match-notes-descriptor" id="match-notes-descriptor">
            <legend class="match-notes-descriptor__legend">
                Additional Notes
            </legend>
            <section id="section_match-issue-entry' . $this->matchIssueNumber . '" class="section-match-entry">';
        echo '<button 
                type="button"
                id="button_match-issue' . $this->matchIssueNumber . '"
                class="button_match-issue"
                aria-controls="section_match-issue-detail' . $this->matchIssueNumber . '"
                aria-expanded="false">
                Please Check Here If There was an Issue with Something Else (Spectator(s), Field, Etc.)
            </button>';
        echo '<section id="section_match-issue-detail' . $this->matchIssueNumber . '" class="section_match-issue" hidden>';
        echo '<label for="textarea_match-issue' . $this->matchIssueNumber . '" 
                id="label_match-issue' . $this->matchIssueNumber . '">Please Provide A Brief Description of the Issue(s):</label>
                <textarea id="textarea_match-issue' . $this->matchIssueNumber . '" 
                name="matchIssueText' . $this->matchIssueNumber . '" 
                class="form-control" 
                rows="1" 
                cols="60">'
                . ($this->matchIssue === null ? '' : htmlspecialchars($this->matchIssue)) . '</textarea>';
        echo '</section>';
        echo '</section>
    </fieldset>';
    }
}
