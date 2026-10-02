<?php

/**
 * Optional "also report abuse" section on match-report.php, rendered right
 * before the Submit button. Filing a RAPP report is IN ADDITION to filing
 * the match report, never a substitute for it -- this section changes
 * nothing about the fields above it, and the match report still saves (and
 * is still required) regardless of whether this section is used.
 *
 * Toggled open the same way the "Additional Notes"/"Ref Staffing Issue"
 * sections already are on this form (aria-controls/aria-expanded button,
 * see controller-rapp-incident-section.js) -- not a checkbox, to match this
 * form's own existing idiom rather than introduce a second one.
 *
 * The email-code exchange happens inline via AJAX (controller-rapp-incident-
 * section.js -> match-report-rapp-otp.php), never a page reload -- gamecard
 * photos are file inputs, and a browser will not let a reload preserve a
 * chosen file, so any design that reloads this page mid-flow would cost the
 * referee their photo attachments.
 */
class RappIncidentFieldset {
    public function __construct(private string $csrfToken) {}

    public function render(): void {
        $csrf = htmlspecialchars($this->csrfToken, ENT_QUOTES, 'UTF-8');
        echo '
        <fieldset class="rapp-incident-descriptor" id="rapp-incident-descriptor">
            <legend class="rapp-incident-descriptor__legend">Referee Abuse (RAPP)</legend>
            <section id="section_rapp-incident-entry" class="section-match-entry">
                <button
                    type="button"
                    id="button_rapp-incident"
                    class="button_rapp-incident"
                    aria-controls="section_rapp-incident-detail"
                    aria-expanded="false"
                >Please Check Here If Someone Behaved Abusively Toward a Referee or Assistant Referee</button>

                <section id="section_rapp-incident-detail" class="section_rapp-incident" hidden>
                    <input type="hidden" name="rapp_csrf_token" id="rapp_csrf_token" value="' . $csrf . '">

                    <div id="rapp-recording-guidance" class="guidance" hidden>
                        <strong>Before you record:</strong>
                        <ul>
                            <li>Record <strong>only yourself</strong>, speaking <strong>privately</strong>.
                                Do not record the incident as it happens, other people, or a conversation.</li>
                            <li>Refer to people the way the misconduct reports do — team, player or
                                coach, jersey number, description — rather than by full name where you can.</li>
                        </ul>
                    </div>

                    <div id="rapp-otp-step">
                        <label for="rapp_email">Your email address</label>
                        <input type="email" id="rapp_email" name="rapp_email" autocomplete="email">
                        <button type="button" id="button_rapp-send-code" class="btn secondary">Email me a code</button>
                        <p id="rapp-otp-notice" class="msg info" hidden></p>
                        <p id="rapp-otp-error" class="msg error" hidden></p>

                        <div id="rapp-otp-code-row" hidden>
                            <label for="rapp_code">One-time code</label>
                            <input type="text" id="rapp_code" name="rapp_code" inputmode="numeric"
                                   autocomplete="one-time-code" pattern="[0-9]*" maxlength="10">
                            <button type="button" id="button_rapp-verify-code" class="btn secondary">Verify code</button>
                        </div>
                    </div>

                    <div id="rapp-report-fields" hidden>
                        <p class="msg ok">Email verified — you can now describe what happened.</p>
                        <label for="rapp_written_account">Written account
                            <span class="muted">(optional if you attach audio)</span></label>
                        <textarea id="rapp_written_account" name="rapp_written_account"
                                  placeholder="What happened, who was involved (by team / role / number), and when."></textarea>
                        <label for="rapp_audio">Voice recording
                            <span class="muted">(optional if you write an account)</span></label>
                        <input type="file" id="rapp_audio" name="rapp_audio" accept="audio/*" capture>
                    </div>
                </section>
            </section>
        </fieldset>';
    }
}
