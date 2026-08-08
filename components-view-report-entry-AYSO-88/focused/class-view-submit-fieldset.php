<?php

class SubmitFieldset {
    public function render(): void {
        echo '
        <fieldset class="match-submit-descriptor" id="match-submit-descriptor">
            <legend class="match-submit-descriptor__legend">
                Report Submission
            </legend>
            <div class="submit-row">
                <input
                    type="submit"
                    value="Submit the Match Results"
                    name="submit"
                    id="submit"
                    class="btn"
                />
            </div>
        </fieldset>';
    }
}
