<?php
declare(strict_types=1);

/**
 * Read-only "what was already recorded for this match" block, shown on
 * report.php when it arrives already knowing which match (report.php?match=)
 * so the referee doesn't retype data already captured on the match report.
 * Mirrors that form's own section order and labels (Match Details, then
 * Additional Notes) -- see views/view-match-report-entry-AYSO-88/
 * compound/class-view-match-descriptor-fieldset.php and .../focused/
 * class-view-match-notes-fieldset.php for the originals. Deliberately NOT
 * those classes themselves: they're wired for the anonymous match-report.php
 * submission flow (its own JS, its own cascading picker, its own POST
 * target) -- this is a display-only read of already-persisted data
 * (MatchDataQueries::matchRow()), for an authenticated page with a
 * different job.
 *
 * Styling (.match-context and friends) lives in rapp-layout.php's shared
 * stylesheet, same as every other RAPP page's CSS -- there's no per-
 * component CSS file in this module to move it into.
 */
final class RappMatchContextView
{
    /**
     * @param array{match_date:string,match_time:string,division_name:?string,field_name:?string,home_team:string,away_team:string,referee_1:?string,referee_2:?string,referee_3:?string,match_issue:?string} $match
     */
    public function render(array $match): void
    {
        $officials = implode(', ', array_filter([$match['referee_1'] ?? null, $match['referee_2'] ?? null, $match['referee_3'] ?? null]));
        ?>
        <fieldset class="match-context">
            <legend>Match Details</legend>
            <dl>
                <dt>Date &amp; time</dt><dd><?= $this->esc($match['match_date']) ?> <?= $this->esc($match['match_time']) ?></dd>
                <dt>Division</dt><dd><?= $this->esc($match['division_name'] ?? '?') ?></dd>
                <dt>Field</dt><dd><?= $this->esc($match['field_name'] ?? '?') ?></dd>
                <dt>Teams</dt><dd><?= $this->esc($match['home_team']) ?> v <?= $this->esc($match['away_team']) ?></dd>
                <dt>Officials</dt><dd><?= $officials !== '' ? $this->esc($officials) : '<span class="muted">Not recorded</span>' ?></dd>
            </dl>
        </fieldset>
        <fieldset class="match-context">
            <legend>Additional Notes</legend>
            <?php if (!empty($match['match_issue'])): ?>
                <p><?= nl2br($this->esc($match['match_issue'])) ?></p>
            <?php else: ?>
                <p class="muted">No additional notes were recorded on the match report.</p>
            <?php endif; ?>
        </fieldset>
        <?php
    }

    private function esc(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
